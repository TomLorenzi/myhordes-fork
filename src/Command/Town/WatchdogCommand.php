<?php


namespace App\Command\Town;

use App\Entity\Town;
use App\Service\ConfMaster;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\TownHandler;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownSetup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

#[AsCommand(
    name: 'app:town:watchdog',
    description: 'Ensures enough towns are open according to configuration'
)]
class WatchdogCommand extends Command implements SelfSchedulingCommand
{

    public function __construct(
        protected EntityManagerInterface $em,
        protected ConfMaster $confMaster,
        protected GameFactory $gameFactory,
        protected GameProfilerService $gps,
        protected TownHandler $townHandler,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Let's check if there is enough opened town
        $openTowns = $this->em->getRepository(Town::class)->findOpenTown();

        $conf = $this->confMaster->getGlobalConf();

        $count = [];
        $langs = $conf->get( MyHordesConf::CONF_TOWNS_AUTO_LANG, [] );
        foreach ($langs as $lang) $count[$lang] = [];

        foreach ($openTowns as $openTown) {
            if (!isset($count[$openTown->getLanguage()])) continue;
            if (!isset($count[$openTown->getLanguage()][$openTown->getType()->getName()])) $count[$openTown->getLanguage()][$openTown->getType()->getName()] = 0;
            $count[$openTown->getLanguage()][$openTown->getType()->getName()]++;
        }

        $minOpenTown = [
            'small'  => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_SMALL, 1 ),
            'remote' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_REMOTE, 1 ),
            'panda'  => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_PANDA, 1 ),
            'custom' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_CUSTOM, 0 ),
        ];

        foreach ($langs as $lang)
            foreach ($minOpenTown as $type => $min) {
                $current = $count[$lang][$type] ?? 0;
                while ($current < $min) {

                    $current_events = $this->confMaster->getCurrentEvents();
                    $name_changers = array_values(
                        array_map( fn(EventConf $e) => $e->get( EventConf::EVENT_MUTATE_NAME ), array_filter($current_events,fn(EventConf $e) => $e->active() && $e->get( EventConf::EVENT_MUTATE_NAME )))
                    );

                    $this->em->persist($newTown = $this->gameFactory->createTown(
                        new TownSetup( $type, language: $lang, nameMutator: $name_changers[0] ?? null )
                    ));
                    $this->em->flush();

                    $this->gps->recordTownCreated( $newTown, null, 'cron' );
                    $this->em->flush();


                    if (!empty(array_filter($current_events,fn(EventConf $e) => $e->active()))) {
                        if (!$this->townHandler->updateCurrentEvents($newTown, $current_events))
                            $this->em->clear();
                        else {
                            $this->em->persist($newTown);
                            $this->em->flush();
                        }
                    }

                    $current++;
                }
            }

        return 0;
    }

    public function schedule(CommandTask $task): void
    {
        $task
            ->everyMinute()
            ->withoutOverlapping(600)
            ->description('Automated town creator')
        ;
    }
}