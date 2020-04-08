<?php


namespace App\Command;


use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\WellCounter;
use App\Entity\Zone;
use App\Service\GameFactory;
use App\Service\NightlyHandler;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class TownInspectorCommand extends Command
{
    protected static $defaultName = 'app:town';

    private $entityManager;
    private $gameFactory;
    private $townHandler;
    private $zonehandler;
    private $nighthandler;
    private $trans;

    public function __construct(EntityManagerInterface $em, GameFactory $gf, ZoneHandler $zh, TownHandler $th, NightlyHandler $nh, Translator $translator)
    {
        $this->entityManager = $em;
        $this->gameFactory = $gf;
        $this->zonehandler = $zh;
        $this->townHandler = $th;
        $this->nighthandler = $nh;
        $this->trans = $translator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Manipulates and lists information about a single town.')
            ->setHelp('This command allows you work on single towns.')
            ->addArgument('TownID', InputArgument::REQUIRED, 'The town ID')

            ->addOption('show-zones', null, InputOption::VALUE_NONE, 'Lists zone information.')
            ->addOption('reset-well-lock', null, InputOption::VALUE_NONE, 'Resets the well lock.')
            ->addOption('zombies', null, InputOption::VALUE_REQUIRED, 'Controls the zombie spawn; set to "reset" to clear all zombies, "daily" to perform a single daily spread or "global" to force a global respawn.')
            ->addOption('zombie-estimates', null, InputOption::VALUE_REQUIRED, 'Calculates the zombie estimations for the next days.')
            ->addOption('unlock-buildings', null, InputOption::VALUE_NONE, 'Unlocks all buildings.')

            ->addOption('unveil-map', null, InputOption::VALUE_NONE, 'Uncovers the map')
            ->addOption('map-ds', null, InputOption::VALUE_REQUIRED, 'When used together with --unveil-map, sets the discovery state')
            ->addOption('map-zs', null, InputOption::VALUE_REQUIRED, 'When used together with --unveil-map, sets the zombie state')

            ->addOption('advance-day', null, InputOption::VALUE_NONE, 'Starts the nightly attack.')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'When used together with --advance-day, changes in the DB will not persist.')

            ->addOption('citizen', 'c', InputOption::VALUE_REQUIRED, 'When used together with --reset-well-lock, only the lock of the given citizen is released.', -1)

            ->addOption('no-info', '0', InputOption::VALUE_NONE, 'Disables the town summary.')
            ;
    }

    protected function info(Town $town, OutputInterface $output, bool $zones) {
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
            $table->addRow([
                $estimation->getDay(),
                round( $estimation->getZombies() - $estimation->getZombies() * $estimation->getOffsetMin()/100),
                $estimation->getZombies(),
                round( $estimation->getZombies() + $estimation->getZombies() * $estimation->getOffsetMax()/100),
                round((1 - (($estimation->getOffsetMin() + $estimation->getOffsetMax()) - 10) / 24) * 100) . '%'
            ]);
        }
        $table->render();

        if ($zones) {
            $output->writeln('<comment>Zone list</comment>');
            $table = new Table( $output );
            $table->setHeaders( ['ID', 'X', 'Y', 'Zombies', 'Citizens','InvIDs'] );
            foreach ($town->getZones() as $zone) {
                $table->addRow([
                    $zone->getId(),
                    $zone->getX(),
                    $zone->getY(),
                    $zone->getZombies(),
                    implode("\n", array_map(function(Citizen $c): string { return $c->getUser()->getUsername(); }, $zone->getCitizens()->getValues())),
                    $zone->getFloor()->getId()
                ]);
            }
            $table->render();
        }

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Town $town */
        $town = $this->entityManager->getRepository(Town::class)->find( (int)$input->getArgument('TownID') );
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

            /** @var WellCounter[] $wells */
            $wells = array_map( function (Citizen $c): WellCounter {
                return $c->getWellCounter();
            }, $citizen ? [$citizen] : $town->getCitizens()->getValues() );

            $num = 0;
            foreach ($wells as $well) {
                if ($well->getTaken() == 0) continue;
                $well->setTaken(0);
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
                foreach ($possible as $proto) $this->townHandler->addBuilding( $town, $proto );
                $output->writeln("Added <comment>" . count($possible) . "</comment> buildings.");
            } while ($found);
            $this->entityManager->persist( $town );
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

        if ($input->getOption('advance-day')) {
            if ($this->nighthandler->advance_day($town) && !$input->getOption('dry')) {
                foreach ($this->nighthandler->get_cleanup_container() as $c) $this->entityManager->remove($c);
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
                    $this->zonehandler->dailyZombieSpawn( $town, 1, ZoneHandler::RespawnModeAuto );
                    $changes = true;
                    $output->writeln("<comment>Daily Zombie spawn</comment> has been executed.");
                    break;
                case 'global':
                    $this->zonehandler->dailyZombieSpawn( $town, 0, ZoneHandler::RespawnModeForce );
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

        if ($changes) {
            $output->write("<comment>Updating database</comment>... ");
            $this->entityManager->flush();
            $output->writeln("<info>OK!</info>");
        }

        return ($input->getOption('no-info')) ? 0 : $this->info($town, $output, $input->getOption('show-zones'));
    }
}