<?php


namespace App\Command\Info;


use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\ZonePrototype;
use App\Interfaces\NamedEntity;
use App\Service\CommandHelper;
use App\Service\RandomGenerator;
use App\Structures\IdentifierSemantic;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

class BalancingCommand extends Command
{
    protected static $defaultName = 'app:info:balancing';

    private CommandHelper $helper;
    private EntityManagerInterface $em;
    private RandomGenerator $rand;
    private TranslatorInterface $translator;


    public function __construct(CommandHelper $h, EntityManagerInterface $em, RandomGenerator $rand, TranslatorInterface $translator)
    {
        $this->helper = $h;
        $this->em = $em;
        $this->rand = $rand;
        $this->translator = $translator;
        $this->translator->setLocale("en");
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Dumps balancing information')
            ->addArgument('what', InputArgument::REQUIRED, 'What would you like to know? [item-spawnrate]')
            ->addArgument('for',  InputArgument::OPTIONAL, 'What object would you like to know about?')

            ->addOption('named-drop', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Adds a named drop overwrite to the resolver.')
        ;
    }

    protected function executeItemDroprate(ItemPrototype $itemPrototype, array $named, SymfonyStyle $io): int {
        $fun_filter = fn($e) => $e[1] > 0.0;

        $fun_beautify = fn($e) => [$e[0], round($e[1] * 100, $e[1] < 0.01 ? 4 : ( $e[1] < 0.1 ? 2 : 1) ) . '%'];

        $fun_by_name = function ($name) use ($itemPrototype) {
            return [$name,$this->rand->resolveChance( $this->em->getRepository(ItemGroup::class)->findOneByName($name),$itemPrototype )];
        };

        $fun_by_ruin = function (ZonePrototype $z) use ($named, $itemPrototype) {
            return [$z->getLabel(),$this->rand->resolveChance( $z->getDropByNames($named), $itemPrototype )];
        };

        $io->title("Item Drop Rates for <info>{$itemPrototype->getLabel()}</info>");

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

        $data = array_map( $fun_beautify, array_filter( array_map($fun_by_name, ['christmas_dig','christmas_dig_post','easter_dig','stpatrick_dig','stpatrick_dig_fair','halloween_dig']), $fun_filter));
        if (!empty($data)) {
            $io->section('Events');
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

    protected function executeGroupDroprate(ItemGroup $itemGroup, array $named, SymfonyStyle $io): int {
        $io->title("Item Drop Rates for <info>{$itemGroup->getName()}</info>");

        $fun_by_proto = function ($itemPrototype) use ($itemGroup) {
            return [$this->translator->trans($itemPrototype->getLabel(), [], 'items'), $this->rand->resolveChance( $itemGroup ,$itemPrototype )];
        };

        $data = [];
        foreach ($itemGroup->getEntries() as $entry) {
            $chances = $fun_by_proto($entry->getPrototype());
            $chances[1] = round($chances[1] * 100, $chances[1] < 0.01 ? 4 : ( $chances[1] < 0.1 ? 2 : 1) ) . '%';
            $data[] = $chances;
        }

        if (!empty($data)) {
            $io->section('Items');
            $io->table(['Item', 'Chance'], $data);
        }

        return 0;
    }

    protected function getPrincipal( string $class, string $label, InputInterface $input, OutputInterface $output ): object {
        if (!$input->hasArgument('for')) throw new \Exception('Subject required.');
        $resolved = $this->helper->resolve_string( $input->getArgument('for') ?? '', $class, $label, $this->getHelper('question'), $input, $output);
        if (!$resolved) throw new \Exception('Subject invalid.');
        return $resolved;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return match ($input->getArgument('what')) {
            'item-spawnrate' => $this->executeItemDroprate($this->getPrincipal(ItemPrototype::class, 'Item Prototype', $input, $output), $input->getOption('named-drop') ?? [], new SymfonyStyle($input, $output)),
            'group-spawnrate' => $this->executeGroupDroprate($this->getPrincipal(ItemGroup::class, 'Item Group', $input, $output), $input->getOption('named-drop') ?? [], new SymfonyStyle($input, $output)),
            'ruin-spawnrate' => $this->executeGroupDroprate($this->getPrincipal(ZonePrototype::class, 'Zone Prototype', $input, $output)->getDrops(), $input->getOption('named-drop') ?? [], new SymfonyStyle($input, $output)),
            default => throw new \Exception('Unknown topic.'),
        };
    }
}
