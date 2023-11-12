<?php


namespace App\Command\Info;

use App\Command\LanguageCommand;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\ZonePrototype;
use App\Enum\DropMod;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:info:balancing',
    description: 'Dumps balancing information'
)]
class BalancingCommand extends LanguageCommand
{
    private EntityManagerInterface $em;
    private RandomGenerator $rand;

    public function __construct(EntityManagerInterface $em, RandomGenerator $rand)
    {
        $this->em = $em;
        $this->rand = $rand;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
			->addArgument('what', InputArgument::REQUIRED, 'What would you like to know? [item-spawnrate, group-spawnrate, ruin-spawnrate]')
            ->addArgument('for',  InputArgument::OPTIONAL, 'What object would you like to know about?')

            ->addOption('named-drop', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Adds a named drop overwrite to the resolver.')

            ->addOption('withMod', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Adds additional drop mod.')
            ->addOption('withoutMod', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Removes drop mod.')
            ->addOption('withoutDefaultMods', null, InputOption::VALUE_NONE, 'Removed default drop mods.')
            ->addOption('withAllMods', null, InputOption::VALUE_NONE, 'Use all drop mods')
        ;
        parent::configure();
    }

    protected function executeItemDroprate(ItemPrototype $itemPrototype, array $modList, array $named, SymfonyStyle $io): int {
        $fun_filter = fn($e) => $e[1] > 0.0;

        $fun_beautify = fn($e) => [$e[0], round($e[1] * 100, $e[1] < 0.01 ? 4 : ( $e[1] < 0.1 ? 2 : 1) ) . '%'];

        $fun_by_name = function ($name) use ($modList, $itemPrototype) {
            return [$name,$this->rand->resolveChance( $this->em->getRepository(ItemGroup::class)->findOneByName($name),$itemPrototype,$modList )];
        };

        $fun_by_ruin = function (ZonePrototype $z) use ($modList, $named, $itemPrototype) {
            return [ $this->translate($z->getLabel(), 'game' ), $this->rand->resolveChance( $z->getDropByNames($named), $itemPrototype, $modList )];
        };

        $io->title("Item Drop Rates for <info>" . $this->translate($itemPrototype->getLabel(), "items") . "</info>");

        $data = array_map( $fun_beautify, array_filter( array_map($fun_by_name, ['empty_dig','base_dig']), $fun_filter));
        if (!empty($data)) {
            $io->section('Zones');
            $io->table(['Type', 'Chance'], $data);
        }

        $data = array_map( $fun_beautify, array_filter( array_map($fun_by_name, ['trash_bad','trash_good']), $fun_filter));
        if (!empty($data)) {
            $io->section('Mechanics');
            $io->table(['Type', 'Chance'], $data);
        }

        $data = array_map( $fun_beautify, array_filter( array_map($fun_by_ruin, $this->em->getRepository(ZonePrototype::class)->findBy(['explorable' => false])), $fun_filter));
        if (!empty($data)) {
            $io->section('Ruins');
            $io->table(['Type', 'Chance'], $data);
        }

        $data = array_map( $fun_beautify, array_filter( array_map($fun_by_ruin, $this->em->getRepository(ZonePrototype::class)->findBy(['explorable' => true])), $fun_filter));
        if (!empty($data)) {
            $io->section('Explorable Ruins');
            $io->table(['Type', 'Chance'], $data);
        }

        return 0;
    }

    protected function executeGroupDroprate(ItemGroup $itemGroup, array $modList, array $named, SymfonyStyle $io): int {
        $io->title("Item Drop Rates for <info>{$itemGroup->getName()}</info>");

        $fun_by_proto = function ($itemPrototype) use ($modList, $itemGroup) {
            return [$this->translate($itemPrototype->getLabel(), 'items'), $this->rand->resolveChance( $itemGroup ,$itemPrototype, $modList )];
        };

        $data = [];
        foreach ($itemGroup->getEntries() as $entry)
            $data[] = $fun_by_proto($entry->getPrototype());

        usort($data, fn($a, $b) => $b[1] <=> $a[1] ?: strcmp( $b[0], $a[0] ));
        $data = array_map( fn( $a ) => [ $a[0], round($a[1] * 100, $a[1] < 0.01 ? 4 : ( $a[1] < 0.1 ? 2 : 1) ) . '%' ], array_filter($data, fn($a) => $a[1] > 0 ) );

        if (!empty($data)) {
            $io->section('Items');
            $io->table(['Item', 'Chance'], $data);
        }

        return 0;
    }



    protected function getPrincipal( string $class, string $label, InputInterface $input, OutputInterface $output ): ItemPrototype|ItemGroup|ZonePrototype {
        if (!$input->hasArgument('for')) throw new \Exception('Subject required.');
        $resolved = $this->helper->resolve_string( $input->getArgument('for') ?? '', $class, $label, $this->getHelper('question'), $input, $output);
        if (!$resolved) throw new \Exception('Subject invalid.');
		if (method_exists( $resolved, 'getLabel' )) $output->writeln("Your query has been resolved to <info>{$this->translate($resolved->getLabel(), 'game')}</info>");
		elseif (method_exists( $resolved, 'getName' )) $output->writeln("Your query has been resolved to <info>{$resolved->getName()}</info>");
		else $output->writeln("Your query has been resolved to <info>{$resolved->getId()}</info>");
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $resolved;
    }

    protected function toDropMod(int|string $mod): ?DropMod {
        $result = null;
        if (is_int( $mod )) $result = DropMod::tryFrom( $mod );
        if ($result) return $result;

        try {
            $result = DropMod::tryFrom( (new ReflectionEnum(DropMod::class))->getCase( $mod )->getBackingValue() );
        } catch (\Throwable $t) {}

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('withAllMods') )
            $modGroups = $input->getOption('withoutDefaultMods') ? array_filter( DropMod::cases(), fn(DropMod $d) => !$d->isDefaultMod() ) : DropMod::cases();
        else $modGroups = $input->getOption('withoutDefaultMods') ? [] : DropMod::defaultMods();

        foreach ( $input->getOption('withMod') as $withMod )
            if ($m = $this->toDropMod($withMod))
                $modGroups[] = $m;
        foreach ( $input->getOption('withoutMod') as $withoutMod )
            if ($m = $this->toDropMod($withoutMod))
                $modGroups = array_filter( $modGroups, fn(DropMod $d) => $d !== $m );

        return match ($input->getArgument('what')) {
            'item-spawnrate' => $this->executeItemDroprate($this->getPrincipal(ItemPrototype::class, 'Item Prototype', $input, $output), $modGroups, $input->getOption('named-drop') ?? [], new SymfonyStyle($input, $output)),
            'group-spawnrate' => $this->executeGroupDroprate($this->getPrincipal(ItemGroup::class, 'Item Group', $input, $output), $modGroups, $input->getOption('named-drop') ?? [], new SymfonyStyle($input, $output)),
            'ruin-spawnrate' => $this->executeGroupDroprate($this->getPrincipal(ZonePrototype::class, 'Zone Prototype', $input, $output)->getDrops(), $modGroups, $input->getOption('named-drop') ?? [], new SymfonyStyle($input, $output)),
            default => throw new \Exception('Unknown topic.'),
        };
    }
}
