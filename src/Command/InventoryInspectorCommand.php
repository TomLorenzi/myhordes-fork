<?php


namespace App\Command;


use App\Entity\Inventory;
use App\Entity\ItemPrototype;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class InventoryInspectorCommand extends Command
{
    protected static $defaultName = 'app:inventory';

    private $entityManager;
    private $itemFactory;
    private $inventoryHandler;

    public function __construct(EntityManagerInterface $em, ItemFactory $if, InventoryHandler $ih)
    {
        $this->entityManager = $em;
        $this->itemFactory = $if;
        $this->inventoryHandler = $ih;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Manipulates and lists information about a single inventory.')
            ->setHelp('This command allows you work on single inventories.')
            ->addArgument('InventoryID', InputArgument::REQUIRED, 'The inventory ID')

            ->addOption('spawn', 's',InputOption::VALUE_REQUIRED, 'Spawns a new item (given by PID or PNAME) in the selected inventory.')
            ->addOption('number','sn',InputOption::VALUE_REQUIRED, 'Sets the number of spawned items when used together with --spawn or -s.', 1)
            ->addOption('set-broken',null,InputOption::VALUE_NONE,     'When used together with --spawn or -s, the created item/s will be broken.')
            ->addOption('set-poison',null,InputOption::VALUE_NONE,     'When used together with --spawn or -s, the created item/s will be poisoned.')
        ;
    }

    protected function info(Inventory $inventory, OutputInterface $output) {
        $output->write('This is a ');
        if ($inventory->getTown())
            $output->writeln("<comment>bank</comment> " .
                "in town <info>'{$inventory->getTown()->getName()}' ({$inventory->getTown()->getId()})</info>.\n");
        if ($inventory->getHome())
            $output->writeln("<comment>chest</comment> " .
                "for citizen <info>'{$inventory->getHome()->getCitizen()->getUser()->getUsername()}' ({$inventory->getHome()->getCitizen()->getId()})</info> " .
                "in town <info>'{$inventory->getHome()->getCitizen()->getTown()->getName()}' ({$inventory->getHome()->getCitizen()->getTown()->getId()})</info>.\n");
        if ($inventory->getCitizen())
            $output->writeln("<comment>backpack</comment> " .
                "for citizen <info>'{$inventory->getCitizen()->getUser()->getUsername()}' ({$inventory->getCitizen()->getId()})</info> " .
                "in town <info>'{$inventory->getCitizen()->getTown()->getName()}' ({$inventory->getCitizen()->getTown()->getId()})</info>.\n");
        if ($inventory->getZone())
            $output->writeln("<comment>floor</comment> " .
                "for zone <info>'{$inventory->getZone()->getX()}/{$inventory->getZone()->getY()}' ({$inventory->getZone()->getId()})</info> " .
                "in town <info>'{$inventory->getZone()->getTown()->getName()}' ({$inventory->getZone()->getTown()->getId()})</info>.\n");
        if ($inventory->getRuinZone())
            $output->writeln("<comment>ruin floor</comment> " .
                "at <info>'{$inventory->getRuinZone()->getX()}/{$inventory->getRuinZone()->getY()}' ({$inventory->getRuinZone()->getId()})</info> " .
                "for zone <info>'{$inventory->getZone()->getX()}/{$inventory->getZone()->getY()}' ({$inventory->getZone()->getId()})</info> " .
                "in town <info>'{$inventory->getZone()->getTown()->getName()}' ({$inventory->getZone()->getTown()->getId()})</info>.\n");

        $output->writeln('<comment>Inventory content</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['IID', 'PID', 'Prototype', 'Name', 'Broken?', 'Poison?'] );

        foreach ($inventory->getItems() as $item)
            $table->addRow([
                $item->getId(),
                $item->getPrototype()->getId(),
                $item->getPrototype()->getName(),
                $item->getPrototype()->getLabel(),
                (int)$item->getBroken(),
                (int)$item->getPoison()
            ]);
        $table->render();

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Inventory $inventory */
        $inventory = $this->entityManager->getRepository(Inventory::class)->find( (int)$input->getArgument('InventoryID') );

        $updated = false;

        if (!$inventory) {
            $output->writeln("<error>The selected inventory could not be found.</error>");
            return 1;
        }

        if ($spawn = $input->getOption('spawn')) {

            $spawn = is_numeric($spawn)
                ? $this->entityManager->getRepository(ItemPrototype::class)->find( (int)$spawn )
                : $this->entityManager->getRepository(ItemPrototype::class)->findOneByName( $spawn );

            if (!$spawn) {
                $output->writeln("<error>The selected item prototype could not be found.</error>");
                return 1;
            }

            $output->writeln( "Spawning <info>{$input->getOption('number')}</info> instances of '<info>{$spawn->getLabel()}</info>'.\n" );

            for ($i = 0; $i < $input->getOption('number'); $i++) {
                $this->inventoryHandler->forceMoveItem($inventory, $this->itemFactory->createItem($spawn->getName(), (bool)$input->getOption('set-broken'), (bool)$input->getOption('set-poison')));
                $this->entityManager->flush();
            }

            $updated = true;
        }

        if ($updated) {
            $this->entityManager->persist($inventory);
            $this->entityManager->flush();
        }

        return $this->info($inventory, $output);
    }
}