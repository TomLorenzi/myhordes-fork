<?php


namespace App\Command;


use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Interfaces\NamedEntity;
use App\Service\CommandHelper;
use App\Structures\IdentifierSemantic;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveCommand extends Command
{
    protected static $defaultName = 'app:resolve';

    private $helper;

    public function __construct(CommandHelper $h)
    {
        $this->helper = $h;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Resolves a given identifier.')
            ->setHelp('This command resolves a given identifier.')
            ->addArgument('Identifier', InputArgument::REQUIRED, 'The identifier')

            ->addOption('as',   null, InputOption::VALUE_REQUIRED, 'Expected class')
            ->addOption('hint', null, InputOption::VALUE_REQUIRED, 'Expected class')

        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('Identifier');

        $as = $input->getOption('as') ?? null;
        if ($as !== null && strpos($as, 'App\Entity\\') === false) $as = 'App\Entity\\' . $as;

        $result = $as ? $this->helper->resolve_as($id, $as, $input->getOption('hint') ?? null) : $this->helper->resolve($id);

        $print = function (array $matches, string $label) use ($output,$result) {
            if (empty($matches)) return;

            $output->writeln("<info>$label</info>");
            foreach ($matches as $match) {
                $e = $result->getMatchedObject($match);
                $match_info = "<comment>matched by '{$result->getMatchedProperty($match)}'</comment>";
                $id_info = "[$match]";

                switch (get_class($e)) {
                    case User::class:
                        /** @var User $e */
                        $out = ["User #{$e->getId()} <comment>{$e->getUsername()}</comment> ({$e->getEmail()})",$id_info,$match_info];
                        break;
                    case Citizen::class:
                        /** @var Citizen $e */
                        $out = ["Citizen #{$e->getId()} <comment>{$e->getUser()->getUsername()}</comment> ({$e->getProfession()->getLabel()} in {$e->getTown()->getName()})",$id_info,$match_info];
                        break;
                    case ItemPrototype::class:
                        /** @var ItemPrototype $e */
                        $out = ["Item Type #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})",$id_info,$match_info];
                        break;
                    case PictoPrototype::class:
                        /** @var PictoPrototype $e */
                        $out = ["Picto Type #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})",$id_info,$match_info];
                        break;
                    case BuildingPrototype::class:
                        /** @var BuildingPrototype $e */
                        $out = ["Building Type #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})",$id_info,$match_info];
                        break;
                    case Town::class:
                        /** @var Town $e */
                        $out = ["Town #{$e->getId()} <comment>{$e->getName()}</comment> ({$e->getLanguage()}, Day {$e->getDay()})",$id_info,$match_info];
                        break;
                    default:
                        $cls_ex = explode('\\', get_class($e));
                        $niceName =  preg_replace('/(\w)([ABCDEFGHIJKLMNOPQRSTUVWXYZ\d])/', '$1 $2', array_pop($cls_ex));
                        if (is_a($e, NamedEntity::class)) {
                            $out = ["$niceName #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})",$id_info,$match_info]; break;
                        } else
                            $out = [$id_info,$match_info]; break;
                }

                $output->writeln("\t" . implode(', ', $out));
            }
            $output->writeln("");
        };

        $print($result->getMatches( IdentifierSemantic::LikelyMatch, true ), 'Likely Exact Matches');
        $print($result->getMatches( IdentifierSemantic::PerfectMatch, true ), 'Exact Matches');
        $print($result->getMatches( IdentifierSemantic::StrongMatch, true ), 'Strong Matches');
        $print($result->getMatches( IdentifierSemantic::WeakMatch, true ), 'Weak Matches');
        $print($result->getMatches( IdentifierSemantic::GuessMatch, true ), 'Guessed Matches');

        return 0;
    }
}
