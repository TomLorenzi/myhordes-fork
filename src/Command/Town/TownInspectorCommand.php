<?php


namespace App\Command\Town;


use App\Entity\ActionCounter;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Town;
use App\Entity\Zone;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\Maps\MapMaker;
use App\Service\Maps\MazeMaker;
use App\Service\NightlyHandler;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:town:inspect',
    description: 'Manipulates and lists information about a single town.'
)]
class TownInspectorCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private GameFactory $gameFactory;
    private TownHandler $townHandler;
    private ZoneHandler $zonehandler;
    private NightlyHandler $nighthandler;
    private MazeMaker $mazeMaker;
    private MapMaker $mapMaker;
    private Translator $trans;
    private CommandHelper $helper;
    private ConfMaster $conf;
    private GameProfilerService $gps;
    private EventProxyService $events;

    public function __construct(EntityManagerInterface $em, GameFactory $gf, ZoneHandler $zh, TownHandler $th,
                                NightlyHandler $nh, Translator $translator, MapMaker $map_maker, MazeMaker $maker,
                                CommandHelper $ch, ConfMaster $conf, GameProfilerService $gps, EventProxyService $events)
    {
        $this->entityManager = $em;
        $this->gameFactory = $gf;
        $this->zonehandler = $zh;
        $this->townHandler = $th;
        $this->nighthandler = $nh;
        $this->trans = $translator;
        $this->mapMaker = $map_maker;
        $this->mazeMaker = $maker;
        $this->helper = $ch;
        $this->conf = $conf;
        $this->gps = $gps;
        $this->events = $events;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you work on single towns.')
            ->addArgument('TownID', InputArgument::REQUIRED, 'The town ID')

            ->addOption('show-zones', null, InputOption::VALUE_NONE, 'Lists zone information.')
            ->addOption('show-conf',  null, InputOption::VALUE_NONE, 'Lists town config.')
            ->addOption('zone-limit-x', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Limits zone output to a given X coordinate. Use twice to specify range.', null)
            ->addOption('zone-limit-y', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Limits zone output to a given Y coordinate. Use twice to specify range.', null)


            ->addOption('reset-well-lock', null, InputOption::VALUE_NONE, 'Resets the well lock.')
            ->addOption('zombies', null, InputOption::VALUE_REQUIRED, 'Controls the zombie spawn; set to "reset" to clear all zombies, "daily" to perform a single daily spread or "global" to force a global respawn.')
            ->addOption('zombie-estimates', null, InputOption::VALUE_REQUIRED, 'Calculates the zombie estimations for the next days.')
            ->addOption('unlock-buildings', null, InputOption::VALUE_NONE, 'Unlocks all buildings.')
            ->addOption('build-buildings', null, InputOption::VALUE_NONE, 'Builds all unlocked buildings.')

            ->addOption('unveil-map', null, InputOption::VALUE_NONE, 'Uncovers the map')
            ->addOption('map-ds', null, InputOption::VALUE_REQUIRED, 'When used together with --unveil-map, sets the discovery state')
            ->addOption('map-zs', null, InputOption::VALUE_REQUIRED, 'When used together with --unveil-map, sets the zombie state')
            ->addOption('rebuild-explorables', null, InputOption::VALUE_NONE, 'Will regenerate all explorable ruin maps')

            ->addOption('set-chaos', null, InputOption::VALUE_REQUIRED, 'Enable/Disable chaos mode.')
            ->addOption('set-devastation', null, InputOption::VALUE_REQUIRED, 'Enable/Disable chaos mode and devastation')
            ->addOption('advance-day', null, InputOption::VALUE_NONE, 'Starts the nightly attack.')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'When used together with --advance-day, changes in the DB will not persist.')

            ->addOption('citizen', 'c', InputOption::VALUE_REQUIRED, 'When used together with --reset-well-lock, only the lock of the given citizen is released.', -1)

            ->addOption('nullify', null, InputOption::VALUE_NONE, 'Nullifies the town')

            ->addOption('no-info', '0', InputOption::VALUE_NONE, 'Disables the town summary.')
            ;
    }

    protected function info(Town $town, OutputInterface $output, bool $zones, ?array $x_range = null, ?array $y_range = null, bool $show_config = false) {
        $this->trans->setLocale($town->getLanguage() ?? 'de');
        $output->writeln('<comment>Common town data</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['ID', 'Open?', 'Name', 'Population', 'Type', 'Day', 'BankID'] );
        $table->addRow([
            $town->getId(),
            $town->isOpen(),
            $town->getName(),
            $town->getCitizenCount() . '/' . $town->getPopulation(),
            $town->getType()->getLabel(),
            $town->getDay(),
            $town->getBank()->getId(),
        ]);
        $table->render();
        $output->writeln("\n");

        if ($show_config) {
            $table = new Table($output);
            $table->setHeaders(['Property','Value']);

            foreach ($this->conf->getTownConfiguration( $town )->raw() as $name => $value) {
                if (is_bool($value)) $value = $value ? 'true' : 'false';
                elseif (is_array($value)) $value = empty($value) ? '[]' : implode("\n", array_map(function ($entry) {
                    return is_array($entry) ? implode(", ", $entry) : $entry;
                }, $value));
                $table->addRow([$name, "<info>{$value}</info>"]);
            }
            $table->render();
        }

        $output->writeln('<comment>Citizen list</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['UID', 'CID', 'Name', 'Job', 'Alive?','HomeID','InvIDs'] );
        foreach ($town->getCitizens() as $citizen) {
            $table->addRow([
                $citizen->getUser()->getId(),
                $citizen->getId(),
                $citizen->getActive() ? "<comment>{$citizen->getUser()->getUsername()}</comment>" : $citizen->getUser()->getUsername(),
                $citizen->getProfession()->getLabel(),
                (int)$citizen->getAlive(),
                $citizen->getHome()->getId(),
                "<comment>H</comment>:{$citizen->getHome()->getChest()->getId()} <comment>R</comment>:{$citizen->getInventory()->getId()}"
            ]);
        }
        $table->render();

        $output->writeln('<comment>Pre-disposed zombie attacks</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['Day', 'Est-Min', 'Zombies', 'Est-Max', 'Est-Q'] );
        foreach ($town->getZombieEstimations() as $estimation) {
            $estim = $this->townHandler->get_zombie_estimation($town, $estimation->getDay());
            $table->addRow([
                $estimation->getDay(),
                $estim[0]->getMin(),
                $estimation->getZombies(),
                $estim[0]->getMax(),
                round($estim[0]->getEstimation() * 100) . '%'
            ]);
        }
        $table->render();

        if ($zones) {
            $output->writeln('<comment>Zone list</comment>');
            $table = new Table( $output );
            $table->setHeaders( ['ID', 'X', 'Y', 'Zombies', 'Scout Offset', 'Citizens','InvIDs'] );
            foreach ($town->getZones() as $zone) {
                if (!empty($x_range) && ($zone->getX() < $x_range[0] || $zone->getX() > $x_range[1]) ) continue;
                if (!empty($y_range) && ($zone->getY() < $y_range[0] || $zone->getY() > $y_range[1]) ) continue;

                $table->addRow([
                    $zone->getId(),
                    $zone->getX(),
                    $zone->getY(),
                    $zone->getZombies(),
                    $zone->getScoutEstimationOffset(),
                    implode("\n", array_map(function(Citizen $c): string { return $c->getUser()->getUsername(); }, $zone->getCitizens()->getValues())),
                    $zone->getFloor()->getId()
                ]);
            }
            $table->render();
        }

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $no_info = $input->getOption('no-info');

        /** @var Town $town */
        $town = $this->helper->resolve_string($input->getArgument('TownID'), Town::class, 'Town', $this->getHelper('question'), $input, $output);
        if (!$town) {
            $output->writeln("<error>The given town ID is not valid.</error>");
            return -1;
        }

        $this->trans->setLocale($town->getLanguage() ?? 'de');

        $citizen = null;
        $citizen_id = (int)$input->getOption('citizen');
        if ($citizen_id > 0) {
            $citizen =  $this->entityManager->getRepository(Citizen::class)->find((int)$input->getOption('citizen'));
            if (!$citizen || $citizen->getTown()->getId() !== $town->getId()) {
                $output->writeln("<error>The given citizen ID is not valid.</error>");
                return -1;
            }
        }

        $changes = false;

        if ($input->getOption('reset-well-lock')) {

            /** @var ActionCounter[] $wells */
            $wells = array_map( function (Citizen $c): ActionCounter {
                return $c->getSpecificActionCounter(ActionCounter::ActionTypeWell);
            }, $citizen ? [$citizen] : $town->getCitizens()->getValues() );

            $num = 0;
            foreach ($wells as $well) {
                if ($well->getCount() == 0) continue;
                $well->setCount(0);
                $this->entityManager->persist($well);
                $num++;
            }
            $changes = $num > 0;
            $output->writeln("<comment>{$num}</comment> well counter/s have been reset.");
        }

        if ($input->getOption('unlock-buildings')) {
            do {
                $possible = $this->entityManager->getRepository(BuildingPrototype::class)->findProspectivePrototypes( $town );
                $changes |= ($found = !empty($possible));
                foreach ($possible as $proto) {
                    $this->townHandler->addBuilding($town, $proto);
                    $this->gps->recordBuildingDiscovered( $proto, $town, null, 'debug' );
                }
                $output->writeln("Added <comment>" . count($possible) . "</comment> buildings.");
            } while ($found);
            $this->entityManager->persist( $town );
        }

        if ($input->getOption('build-buildings')) {
            $built = 0;
            do {
                $buildings = $town->getBuildings();
                $changed = false;
                foreach ($buildings as $building) {
                    if(!$building->getComplete()) {
                        $this->events->buildingConstruction( $building, 'debug' );
                        $changed = true;
                        $changes = true;
                        $built++;
                        $this->entityManager->persist( $building );
                    }
                }
            } while ($changed);
            $output->writeln("Built <comment>$built</comment> buildings.");
        }

        if ($input->getOption('unveil-map')) {

            $ds = $input->getOption('map-ds') ?? Zone::DiscoveryStateCurrent;
            $zs = $input->getOption('map-zs') ?? Zone::ZombieStateExact;

            foreach ($town->getZones() as &$zone) if ($zone->getX() !== 0 || $zone->getY() !== 0) {
                $zone->setDiscoveryStatus( $ds );
                $zone->setZombieStatus( $zs );
            }
            $changes = true;
            $this->entityManager->persist( $town );
        }

        if ($input->getOption('rebuild-explorables')) {

            $conf = $this->conf->getTownConfiguration( $town );
            foreach ($town->getZones() as &$zone) if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
                $changes = true;
                $this->mazeMaker->setTargetZone($zone);
                $zone->setExplorableFloors($conf->get(TownConf::CONF_EXPLORABLES_FLOORS, 1));
                $this->mazeMaker->createField();  
                $this->mazeMaker->generateCompleteMaze();

                foreach ($zone->getExplorerStats() as $stat) {
                    $stat->getCitizen()->removeExplorerStat($stat);
                    $this->entityManager->remove( $stat );
                }
            }

            $this->entityManager->persist( $town );
        }

        if ($chaos = $input->getOption('set-chaos')) {
            $town->setChaos(intval($chaos) !== 0);
            if($chaos === 0)
                $town->setDevastated(0);
            $this->entityManager->persist( $town );
            $changes = true;
        }

        if ($devast = $input->getOption('set-devastation')) {
            $town->setChaos(intval($devast) !== 0);
            $town->setDevastated(intval($devast) !== 0);
            $town->setDoor(true);
            $this->entityManager->persist( $town );
            $changes = true;
        }

        if ($input->getOption('advance-day')) {
            if ($this->nighthandler->advance_day($town, $this->conf->getCurrentEvents( $town )) && !$input->getOption('dry')) {
                foreach ($this->nighthandler->get_cleanup_container() as $c) $this->entityManager->remove($c);
                $town->setAttackFails(0);
                $this->entityManager->persist( $town );
                $changes = true;
            }

        }

        if ($spawn = $input->getOption('zombies'))
            switch ($spawn) {
                case 'reset':
                    foreach ($town->getZones() as $zone) $zone->setInitialZombies(0)->setZombies(0);
                    $changes = true;
                    $output->writeln("<comment>Zombies</comment> have been removed.");
                    break;
                case 'daily':
                    $this->mapMaker->dailyZombieSpawn( $town, 1, MapMaker::RespawnModeAuto );
                    $changes = true;
                    $output->writeln("<comment>Daily Zombie spawn</comment> has been executed.");
                    break;
                case 'global':
                    $this->mapMaker->dailyZombieSpawn( $town, 0, MapMaker::RespawnModeForce );
                    $changes = true;
                    $output->writeln("<comment>Global Zombie respawn</comment> has been executed.");
                    break;
                default:
                    $output->writeln("<error>Invalid value for --zombies option.</error>");
                    break;
            }

        if ($n = $input->getOption('zombie-estimates')) {
            $this->townHandler->calculate_zombie_attacks( $town, $n );
            $this->entityManager->persist( $town );
            $changes = $n > 0;
        }

        if ($input->getOption('nullify')) {
            $output->write("<comment>Nullifying town</comment>... ");
            $this->gameFactory->nullifyTown($town);
            $output->writeln("<info>OK!</info>");
            $no_info = $changes = true;
        }

        if ($changes) {
            $output->write("<comment>Updating database</comment>... ");
            $this->entityManager->flush();
            $output->writeln("<info>OK!</info>");
        }

        $rg_x = $input->getOption('show-zones') ? $input->getOption('zone-limit-x') : null;
        $rg_y = $input->getOption('show-zones') ? $input->getOption('zone-limit-y') : null;

        if ($rg_x !== null && count($rg_x) === 1) $rg_x[1] = $rg_x[0];
        if ($rg_y !== null && count($rg_y) === 1) $rg_y[1] = $rg_y[0];

        return $no_info ? 0 : $this->info($town, $output, $input->getOption('show-zones'), $rg_x, $rg_y, $input->getOption('show-conf'));
    }
}