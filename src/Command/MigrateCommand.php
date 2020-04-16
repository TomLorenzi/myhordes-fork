<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\HeroicActionPrototype;
use App\Entity\Item;
use App\Entity\Town;
use App\Entity\User;
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

        return 1;
    }
}