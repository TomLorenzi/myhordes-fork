<?php


namespace App\Command;


use App\Entity\AffectStatus;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenStatus;
use App\Entity\HeroicActionPrototype;
use App\Entity\Item;
use App\Entity\Picto;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Service\CitizenHandler;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'app:migrate';

    private $kernel;

    private $game_factory;
    private $entity_manager;
    private $citizen_handler;
    private $randomizer;
    private $inventory_handler;

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em, RandomGenerator $rg, CitizenHandler $ch, InventoryHandler $ih)
    {
        $this->kernel = $kernel;

        $this->game_factory = $gf;
        $this->entity_manager = $em;
        $this->randomizer = $rg;
        $this->citizen_handler = $ch;
        $this->inventory_handler = $ih;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Performs migrations to update content after a version update.')
            ->setHelp('Migrations.')

            ->addOption('update-db', 'u', InputOption::VALUE_NONE, 'Creates and performs a doctrine migration, updates fixtures.')
            ->addOption('update-trans', 't', InputOption::VALUE_REQUIRED, 'Updates all translation files for a single language')

            ->addOption('assign-heroic-actions-all', null, InputOption::VALUE_NONE, 'Resets the heroic actions for all citizens in all towns.')
            ->addOption('init-item-stacks', null, InputOption::VALUE_NONE, 'Sets item count for items without a counter to 1')
            ->addOption('delete-legacy-logs', null, InputOption::VALUE_NONE, 'Deletes legacy log entries')
            ->addOption('set-default-zonetag', null, InputOption::VALUE_NONE, 'Set the default tag to all zones')
            ->addOption('assign-building-hp', null, InputOption::VALUE_NONE, 'Give HP to all buildings (so they can be attacked by zeds)')
            ->addOption('assign-building-defense', null, InputOption::VALUE_NONE, 'Give defense to all buildings (so they can be attacked by zeds)')
            ->addOption('update-ranking-entries', null, InputOption::VALUE_NONE, 'Update ranking values')
            ->addOption('update-shaman-immune', null, InputOption::VALUE_NONE, 'Changes status tg_immune to tg_shaman_immune')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('update-db')) {

            $made_migration = true;
            $db_is_current  = true;

            $command = $this->getApplication()->find('doctrine:migrations:diff');
            try {
                $args = new ArrayInput([
                    '--allow-empty-diff' => true,
                ]);
                $args->setInteractive(false);
                $command->run($args, $output);
            } catch (Exception $e) {
                $output->writeln("Unable to create a migration: <error>{$e->getMessage()}</error>");
                $made_migration = false;
            }

            if ($made_migration) {
                $command = $this->getApplication()->find('doctrine:migrations:migrate');
                try {
                    $args = new ArrayInput([
                        '--all-or-nothing' => true,
                        '--allow-no-migration' => true,
                    ]);
                    $args->setInteractive(false);
                    $command->run($args, $output);
                } catch (Exception $e) {
                    $output->writeln("Unable to migrate: <error>{$e->getMessage()}</error>");
                    $db_is_current = false;
                }
            }

            if ($db_is_current) {
                $command = $this->getApplication()->find('doctrine:fixtures:load');
                try {
                    $command->run(new ArrayInput([
                        '--append' => true,
                    ]), $output);
                } catch (Exception $e) {
                    $output->writeln("Unable to insert fixtures: <error>{$e->getMessage()}</error>");
                    return 1;
                }
            }

            return 0;
        }

        if ($lang = $input->getOption('update-trans')) {

            $command = $this->getApplication()->find('translation:update');

            $output->writeln("Now working on translations for <info>{$lang}</info>...");
            $input = new ArrayInput([
                'locale' => $lang,
                '--force' => true,
                '--sort' => 'asc',
                '--output-format' => 'xlf2',
                '--prefix' => '',
            ]);
            $input->setInteractive(false);
            try {
                $command->run($input, new NullOutput());
            } catch (Exception $e) {
                $output->writeln("Error: <error>{$e->getMessage()}</error>");
                return 1;
            }

            $output->writeln('Done!');
            return 0;
        }

        if ($input->getOption('assign-heroic-actions-all')) {
            $heroic_actions = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findAll();
            foreach ($this->entity_manager->getRepository(Citizen::class)->findAll() as $citizen) {
                foreach ($heroic_actions as $heroic_action)
                    /** @var $heroic_action HeroicActionPrototype */
                    $citizen->addHeroicAction( $heroic_action );
                $this->entity_manager->persist( $citizen );
            }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('init-item-stacks')) {
            foreach ($this->entity_manager->getRepository(Item::class)->findAll() as $item) {
                /** @var $item Item */
                if ($item->getCount() == 0) {
                    $item->setCount( 1 );
                    $this->entity_manager->persist( $item );
                }
            }
            $this->entity_manager->flush();

            foreach ($this->entity_manager->getRepository(Town::class)->findAll() as $town) {
                /** @var $town Town*/
                foreach ($town->getBank()->getItems() as $item)
                    if ($item->getCount() <= 1) {
                        $target = $this->inventory_handler->findStackPrototype( $town->getBank(), $item );
                        if ($target) {
                            $target->setCount( $target->getCount() + 1);
                            $this->inventory_handler->forceRemoveItem( $item );
                            $this->entity_manager->persist($target);
                        }

                    }
            }

            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('delete-legacy-logs')) {
            /** @var TownLogEntry[] $log_entries */
            $log_entries = $this->entity_manager->getRepository(TownLogEntry::class)->findAll();
            foreach ($log_entries as $entry)
                if ($entry->getLogEntryTemplate() === null)
                $this->entity_manager->remove( $entry );
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('set-default-zonetag')) {
            /** @var Zone[] $zones */
            $zones = $this->entity_manager->getRepository(Zone::class)->findAll();
            $defaultTag = $this->entity_manager->getRepository(ZoneTag::class)->findOneByRef(0);
            foreach ($zones as $entry)
                if ($entry->getTag() === null){
                    $entry->setTag($defaultTag);
                    $this->entity_manager->persist($entry);
                }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('assign-building-hp')) {
            /** @var Building[] $buildings */
            $building = $this->entity_manager->getRepository(Building::class)->findAll();
            foreach ($building as $entry)
                if ($entry->getComplete()){
                    $entry->setHp($entry->getPrototype()->getHp());
                    $this->entity_manager->persist($entry);
                }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('assign-building-defense')) {
            /** @var Building[] $buildings */
            $building = $this->entity_manager->getRepository(Building::class)->findAll();
            foreach ($building as $entry)
                if ($entry->getComplete()){
                    $entry->setDefense($entry->getPrototype()->getDefense());
                    $this->entity_manager->persist($entry);
                }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('update-ranking-entries')) {
            /** @var Town[] $towns */
            $towns = $this->entity_manager->getRepository(Town::class)->findAll();
            foreach ($towns as $town)
                $this->entity_manager->persist( TownRankingProxy::fromTown( $town, true ));
            $this->entity_manager->flush();
            $output->writeln('Towns updated!');

            /** @var Citizen[] $citizens */
            $citizens = $this->entity_manager->getRepository(Citizen::class)->findAll();
            foreach ($citizens as $citizen)
                $this->entity_manager->persist( CitizenRankingProxy::fromCitizen( $citizen, true ));
            $this->entity_manager->flush();
            $output->writeln('Citizens updated!');

            /** @var Picto[] $pictos */
            $pictos = $this->entity_manager->getRepository(Picto::class)->findAll();
            foreach ($pictos as $picto)
                if ($picto->getTownEntry() === null && $picto->getTown() && $picto->getTown()->getRankingEntry())
                    $this->entity_manager->persist( $picto->setTownEntry( $picto->getTown()->getRankingEntry() ) );
            $this->entity_manager->flush();
            $output->writeln('Pictos updated!');

            return 0;
        }

        if ($input->getOption('update-shaman-immune')) {
            /** @var Town[] $towns */
            $old_immune_status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("tg_immune");
            $new_immune_status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("tg_shaman_immune");

            if($old_immune_status === null){
                $output->writeln("Old tg_immune status has been already migrated !");
                return 0;
            }

            $citizens = $this->entity_manager->getRepository(Citizen::class)->findCitizensWithStatus($old_immune_status);

            $output->writeln(count($citizens) . " citizens to update");

            foreach ($citizens as $citizen) {
                $citizen->removeStatus($old_immune_status);
                $citizen->addStatus($new_immune_status);
                $this->entity_manager->persist($citizen);
            }

            $output->writeln('Citizens status updated!');

            $affectStatuses = $this->entity_manager->getRepository(AffectStatus::class)->findByResult($old_immune_status);

            $output->writeln(count($affectStatuses) . " AffectStatuses' results to update");

            foreach ($affectStatuses as $affectStatus) {
                $affectStatus->setResult($new_immune_status);
                $this->entity_manager->persist($affectStatus);
            }

            $output->writeln('AffectStatuses\' results updated!');

            $affectStatuses = $this->entity_manager->getRepository(AffectStatus::class)->findByInitial($old_immune_status);

            $output->writeln(count($affectStatuses) . " AffectStatuses' initial to update");

            foreach ($affectStatuses as $affectStatus) {
                $affectStatus->setInitial($new_immune_status);
                $this->entity_manager->persist($affectStatus);
            }

            $output->writeln('AffectStatuses\' initial updated!');

            $this->entity_manager->remove($old_immune_status);

            $output->writeln('Old tg_immune status removed!');

            $this->entity_manager->flush();

            return 0;
        }


        return 1;
    }
}