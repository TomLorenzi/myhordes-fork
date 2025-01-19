<?php


namespace App\Command\Info;

use App\Command\LanguageCommand;
use App\Entity\ItemGroup;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\ZonePrototype;
use App\Enum\Configuration\MyHordesSetting;
use App\Service\ConfMaster;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:info:item',
    description: 'Dumps balancing information'
)]
class ItemCommand extends LanguageCommand
{
    private EntityManagerInterface $em;
    private ConfMaster $conf;

    public function __construct(EntityManagerInterface $em, ConfMaster $conf)
    {
        $this->em = $em;
        $this->conf = $conf;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('item',  InputArgument::OPTIONAL, 'What item would you like to know about?')
            ->addOption('not', null, InputOption::VALUE_NONE, 'If we want items NOT matching the argument (only for item properties)')
        ;
        parent::configure();
    }


    protected function printItemInfo( ItemPrototype $item, OutputInterface $output ): int {
        $output->writeln("<comment>{$item->getLabel()}</comment> [{$item->getName()}]");
        $output->writeln('');

        $all_langs = $this->conf->getGlobalConf()->get(MyHordesSetting::Languages);

        $real_langs = array_map( fn(array $a) => $a['code'], array_filter( $all_langs, fn(array $a) =>  $a['generate'] ) );
        $fake_langs = array_map( fn(array $a) => $a['code'], array_filter( $all_langs, fn(array $a) => !$a['generate'] ) );

        foreach ( array_merge( $real_langs, $fake_langs ) as $lang )
            $output->writeln("<info>{$lang}</info>: <comment>{$this->translator->trans( $item->getLabel(), [], 'items', $lang )}</comment>\n<comment>{$this->translator->trans( $item->getDescription(), [], 'items', $lang )}</comment>\n");

        if (!$item->getProperties()->isEmpty()) {
            $output->writeln('Properties:');
            foreach ($item->getProperties() as $property)
                $output->writeln( "\t{$property->getName()}" );
            $output->writeln('');
        }

        if (!$item->getActions()->isEmpty()) {
            $output->writeln('Actions:');
            foreach ($item->getActions() as $action)
                $output->writeln( "\t<comment>{$this->translate($action->getLabel(), 'items')}</comment> [{$action->getName()}]" );
            $output->writeln('');
        }

        if ($item->getNightWatchAction()) {
            $output->writeln('Night Watch Action:');
            $output->writeln( "\t{$item->getNightWatchAction()->getName()}" );
            $output->writeln('');
        }

        $output->writeln("Sort: <info>{$item->getSort()}</info>");
        $output->writeln("Deco: <info>{$item->getDeco()}</info>");
        $output->writeln("Watchpoints: <info>{$item->getWatchpoint()}</info>");
        $output->writeln("Heavy: <info>" . ( $item->getHeavy() ? 'yes' : 'no') . "</info>");
        $output->writeln("Stackable: <info>" . ( $item->getIndividual() ? 'no' : 'yes') . "</info>");
        $output->writeln("Hidden in foreign chests: <info>" . ( $item->getHideInForeignChest() ? 'yes' : 'no') . "</info>");

        return 0;
    }

    protected function printItemPropertyInfo( ItemProperty $item, OutputInterface $output, bool $not = false ): int {
        $output->writeln(($not ? "<fg=red;options=bold>NOT</> " : "") . "<comment>{$item->getName()}</comment> [{$item->getId()}]");
        $output->writeln('');

        if (!$item->getItemPrototypes()->isEmpty()) {
            $prototypes = $not ? array_udiff($this->em->getRepository(ItemPrototype::class)->findAll(), $item->getItemPrototypes()->toArray(), function($a, $b) {
                return $a->getId() <=> $b->getId();
            }) : $item->getItemPrototypes();
            $output->writeln('Items:');

            foreach ($prototypes as $prototype)
                $output->writeln( "\t<comment>{$this->translate($prototype->getLabel(), 'items')}</comment> [{$prototype->getName()}]" );
            $output->writeln('');
        }

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->hasArgument('item')) throw new \Exception('Subject required.');
        /** @var ItemPrototype|ItemProperty $item */
        $item = $this->helper->resolve_string( $input->getArgument('item') ?? '', [ItemPrototype::class, ItemProperty::class], 'Item', $this->getHelper('question'), $input, $output);
        if (!$item) throw new \Exception('Subject invalid.');

        return match( $item::class ) {
            ItemPrototype::class => $this->printItemInfo( $item, $output ),
            ItemProperty::class => $this->printItemPropertyInfo( $item, $output, $input->getOption("not") ),
            default => -1
        };
    }
}
