<?php


namespace App\Command\Info;

use App\Command\LanguageCommand;
use App\Entity\BuildingPrototype;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\Result;
use App\Entity\ZonePrototype;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ActionCommand extends LanguageCommand
{
    protected static $defaultName = 'app:info:actions';

    private EntityManagerInterface $em;
    private RandomGenerator $rand;

    public function __construct(EntityManagerInterface $em, RandomGenerator $rand)
    {
        $this->em = $em;
        $this->rand = $rand;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Dumps actions information')
            ->addArgument('what', InputArgument::REQUIRED, 'What is the source of the action (item, workshop, home)')
            ->addArgument('for',  InputArgument::OPTIONAL, 'What object would you like to know about?')
        ;
        parent::configure();
    }

    protected function executeItemActions(ItemPrototype $item, SymfonyStyle $io): int {
        $io->title("Item Actions for <info>" . $this->translate($item->getLabel(), 'items') . "</info>");

        foreach ($item->getActions() as $action) {
            $io->section('Actions');
            $io->writeln("Action label: " . $this->translate($action->getLabel(), "items"));
            foreach ($action->getResults() as $result) {
                $this->displayActions($result, $io);
            }
        }

        return 0;
    }

    protected function displayActions(Result $result, SymfonyStyle $io) {
        $io->section("Action name: <info>{$result->getName()}</info>");

        if ($result->getAp()) {
            if ($result->getAp()->getMax())
                $io->writeln("Set APs to <info>max value</info>");
            else
                $io->writeln("Change APs by <info>{$result->getAp()->getAp()}</info>, setting the bonus to <info>{$result->getAp()->getBonus()}</info>");
        }

        if ($result->getBlueprint()) {
            $protos = [];
            if ($result->getBlueprint()->getType() > -1) {
                $io->writeln("Unlock building of type <info>{$result->getBlueprint()->getType()}</info>");
                $list = $this->em->getRepository(BuildingPrototype::class)->findBy(['blueprint' =>$result->getBlueprint()->getType()]);
                foreach ($list as $proto) {
                    $protos[] = "Unlock building <info>" . $this->translate($proto->getLabel(), 'buildings') . "</info> (<info>{$proto->getName()}</info>)";
                }
            }

            if (count($result->getBlueprint()->getList()) > 0) {
                foreach ($result->getBlueprint()->getList() as $proto) {
                    $protos[] = "Unlock building <info>" . $this->translate($proto->getLabel(), 'buildings') . "</info> (<info>{$proto->getName()}</info>)";
                }
            }

            if (!empty($protos)) {
                $io->writeln("Unlock list:");
                $io->listing($protos);
            }
        }

        if ($result->getConsume())
            $io->writeln("Consume <info>{$result->getConsume()->getCount()}</info>x <info>" . $this->translate($result->getConsume()->getPrototype()->getLabel(), "items") . "</info>");

        if ($result->getCp()) {
            if ($result->getCp()->getMax())
                $io->writeln("Set CPs to <info>max value</info>");
            else
                $io->writeln("Change CPs by <info>{$result->getCp()->getCp()}</info>, setting the bonus to <info>{$result->getCp()->getBonus()}</info>");
        }

        if ($result->getCustom())
            $io->writeln("Execute custom action N. <info>{$result->getCustom()}</info>");

        if ($result->getDeath())
            $io->writeln("Kills the citizen with CauseOfDeath <info>" . $this->translate($result->getDeath()->getCause()->getLabel(), "game") . "</info>");

        if ($result->getGlobalPicto())
            $io->writeln("Give picto <info>" . $this->translate($result->getGlobalPicto()->getPrototype()->getLabel(), "game"). "</info> (<info>{$result->getGlobalPicto()->getPrototype()->getName()}</info>) to the entire town");

        if ($result->getHome()) {
            if($result->getHome()->getAdditionalDefense())
                $io->writeln("Adds <info>{$result->getHome()->getAdditionalDefense()}</info> defense to the citizen's home");

            if($result->getHome()->getAdditionalStorage())
                $io->writeln("Adds <info>{$result->getHome()->getAdditionalStorage()}</info> storage to the citizen's home");
        }

        if ($result->getItem()) {
            if ($result->getItem()->getConsume())
                $io->writeln("Consume the item (disappear from the inventory)");

            if ($result->getItem()->getBreak())
                $io->writeln("Breaks the items");

            if ($result->getItem()->getMorph())
                $io->writeln("Change the item into <info>" . $this->translate($result->getItem()->getMorph()->getLabel(), "items") . "</info>  (<info>{$result->getItem()->getMorph()->getName()}</info>)");
        }

        if ($result->getPicto())
            $io->writeln("Give picto <info>" . $this->translate($result->getPicto()->getPrototype()->getLabel(), "game"). "</info> (<info>{$result->getPicto()->getPrototype()->getName()}</info>) to the citizen");

        if ($result->getPm())
            if ($result->getPm()->getMax())
                $io->writeln("Set PMs to <info>max value</info>");
            else
                $io->writeln("Change PMs by <info>{$result->getPm()->getPm()}</info>, setting the bonus to <info>{$result->getPm()->getBonus()}</info>");

        if ($result->getRolePlayText())
            $io->writeln("Unlock a Role Play text");

        if ($result->getSpawn()) {
            if ($result->getSpawn()->getPrototype())
                $io->writeln("Spawn item <info>" . $this->translate($result->getSpawn()->getPrototype()->getLabel(), "items") . "</info>  (<info>{$result->getSpawn()->getPrototype()->getName()}</info>) x<info>{$result->getSpawn()->getCount()}</info>");
            else {
                $protos = [];
                if ($result->getSpawn()->getItemGroup()) {
                    $list = $result->getSpawn()->getItemGroup();
                    foreach ($list->getEntries() as $entry) {
                        $protos[] = "<info>" . $this->translate($entry->getPrototype()->getLabel(), 'buildings') . "</info> (" . round($this->rand->resolveChance( $list, $entry->getPrototype() ) * 100, 2) . "%)";
                    }
                    $io->listing($protos);
                }
            }
        }

        if ($result->getStatus()) {
            if ($result->getStatus()->getCitizenHunger())
                $io->writeln("Change citizen hunger by <info>{$result->getStatus()->getCitizenHunger()}</info>");

            if ($result->getStatus()->getResetThirstCounter())
                $io->writeln("Reset thirst counter");

            if ($result->getStatus()->getCounter() !== null)
                $io->writeln("Increment action counter <info>{$result->getStatus()->getCounter()}</info>");

            if ($result->getStatus()->getRole() !== null && $result->getStatus()->getRoleAdd() !== null) {
                if ($result->getStatus()->getRoleAdd()) {
                    $io->writeln("Add new role <info>" . $this->translate($result->getStatus()->getRole()->getLabel(), "game") . "</info>");
                } else {
                    $io->writeln("Remove role <info>" . $this->translate($result->getStatus()->getRole()->getLabel(), "game") . "</info>");
                }
            }

            if ($result->getStatus()->getInitial() && $result->getStatus()->getResult()) {
                $io->writeln("Replace status <info>" . $this->translate($result->getStatus()->getInitial()->getLabel(), "game") . "</info> by <info>" . $this->translate($result->getStatus()->getResult()->getLabel(), "game") . "</info>");
            }
            elseif ($result->getStatus()->getInitial()) {
                $io->writeln("Remove status <info>" . $this->translate($result->getStatus()->getInitial()->getLabel(), "game") . "</info>");
            }
            elseif ($result->getStatus()->getResult()) {
                $io->writeln("Add status <info>" . $this->translate($result->getStatus()->getResult()->getLabel(), "game") . "</info>");
            }
        }


        if ($result->getTown())
            $io->writeln("Adds <info>{$result->getTown()->getAdditionalDefense()}</info> defense to the town");

        if ($result->getWell())
            $io->writeln("Change well level by <info>{$result->getWell()->getFillMin()}</info>-<info>{$result->getWell()->getFillMax()}</info>");

        if ($result->getZombies()) {
            $io->writeln("Remove <info>{$result->getZombies()->getMin()}</info>-<info>{$result->getZombies()->getMax()}</info> zombies from the current zone");
        }

        if ($result->getZone()) {
            if ($result->getZone()->getChatSilence())
                $io->writeln("Hide chat messages for <info>{$result->getZone()->getChatSilence()} sec</info>");

            if ($result->getZone()->getEscape())
                $io->writeln("Allow citizen to escape for <info>{$result->getZone()->getEscape()} sec</info>");

            if ($result->getZone()->getImproveLevel())
                $io->writeln("Improve camping level by <info>{$result->getZone()->getImproveLevel()}</info>");

            if ($result->getZone()->getUncoverRuin())
                $io->writeln("Reduce necessary AP to uncover ruin");

            if ($result->getZone()->getUncoverZones())
                $io->writeln("Uncover surrounding zones");
        }

        if ($result->getResultGroup()) {
            $io->writeln($result->getResultGroup()->getName());
            foreach ($result->getResultGroup()->getEntries() as $entry) {
                foreach ($entry->getResults() as $subResult) {
                    $this->displayActions($subResult, $io);
                }
            }
        }
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
            'item' => $this->executeItemActions($this->getPrincipal(ItemPrototype::class, 'Item Prototype', $input, $output), new SymfonyStyle($input, $output)),
            default => throw new \Exception('Unknown topic.'),
        };
    }
}
