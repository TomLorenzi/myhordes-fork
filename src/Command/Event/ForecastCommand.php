<?php


namespace App\Command\Event;

use App\Entity\AutomaticEventForecast;
use App\Entity\CommunityEvent;
use App\Entity\CommunityEventTownPreset;
use App\Entity\ForumUsagePermissions;
use App\Entity\UserGroup;
use App\Service\Actions\Ghost\CreateTownFromConfigAction;
use App\Service\ConfMaster;
use App\Service\PermissionHandler;
use App\Structures\TownSetup;
use ArrayHelpers\Arr;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsCommand(
    name: 'app:event:forecast',
    description: 'Performs maintenance tasks on automatic events.'
)]
#[AsScheduledTask('12 0 * * *', description: 'Performs maintenance tasks on automatic events.')]
class ForecastCommand extends Command
{

    public function __construct(
        protected EntityManagerInterface $em,
        protected ConfMaster $conf
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('create-towns', null, InputOption::VALUE_NONE, 'Creates towns for community events that start in 48 hours or less.')
            ->addOption('urgent-create-towns', null, InputOption::VALUE_NONE, 'Creates towns for community events that are marked as "urgently".')
            ->addOption('auto-end', null, InputOption::VALUE_NONE, 'Disables events that have ended (+50 days old or no living towns left)')
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = (new DateTimeImmutable())->modify('today-64days');
        $end = (new DateTimeImmutable())->modify('today+1year');

        $all = $this->conf->getAllEvents();
        $whitelist = [];

        foreach ($all as $id => $conf) {

            /** @var null|DateTime $ev_end */
            $ev_end = null;

            $current = DateTime::createFromImmutable($start);

            while ($current < $end) {
                $e = $this->conf->getEventSchedule($conf['trigger'] ?? [], $current, $ev_begin, $ev_end, true);
                if ($ev_begin === null || $ev_end === null || ($ev_begin < $start && $ev_end < $start)) break;

                $whitelist[] = $instance_id = "$id:{$ev_begin->format('Y')}";

                $forecast = $this->em->getRepository( AutomaticEventForecast::class )->findOneBy(['identifier' => $instance_id])
                    ?? (new AutomaticEventForecast())->setIdentifier($instance_id)->setEvent($id);

                $created = $forecast->getId() === null;
                $outdated = $forecast->getStart()?->getTimestamp() !== $ev_begin->getTimestamp() ||
                    $forecast->getEnd()?->getTimestamp() !== $ev_end->getTimestamp() ||
                    $forecast->getEvent() !== $id
                ;

                if ($created || $outdated) {
                    $this->em->persist( $forecast
                                            ->setEvent( $id )
                                            ->setStart( DateTimeImmutable::createFromMutable( $ev_begin ) )
                                            ->setEnd( DateTimeImmutable::createFromMutable( $ev_end ) )
                    );
                    $this->em->flush();
                }

                if ($created) $output->writeln("<fg=green>Created</> event forecast <fg=blue>{$forecast->getId()}</> for event <info>$instance_id</info>");
                elseif ($outdated) $output->writeln("<fg=yellow>Updated</> event forecast <fg=blue>{$forecast->getId()}</> for event <info>$instance_id</info>");

                if ($ev_end) $current = (clone $ev_end)->modify("+1day");
            }
        }

        foreach ($this->em->getRepository(AutomaticEventForecast::class)->matching((new Criteria())
            ->andWhere(Criteria::expr()->notIn('identifier', $whitelist))
            ->andWhere(Criteria::expr()->gt('end', new DateTimeImmutable()))
        ) as $unknownEvent) {
            /** @var AutomaticEventForecast $unknownEvent */
            $output->writeln("<fg=red>Removed</> event forecast <fg=blue>{$unknownEvent->getId()}</> for unknown event <info>{$unknownEvent->getIdentifier()}</info> (claims to be <info>{$unknownEvent->getEvent()}</info>)");
            $this->em->remove($unknownEvent);
            $this->em->flush();
        }

        return 0;
    }
}