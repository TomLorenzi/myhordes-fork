<?php


namespace App\Command\Event;

use App\Entity\CommunityEvent;
use App\Entity\CommunityEventTownPreset;
use App\Entity\ForumUsagePermissions;
use App\Entity\UserGroup;
use App\Service\Actions\Ghost\CreateTownFromConfigAction;
use App\Service\PermissionHandler;
use App\Structures\TownSetup;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsCommand(
    name: 'app:event:watchdog',
    description: 'Performs maintenance tasks on community events.'
)]
#[AsScheduledTask('12 0 * * *', description: 'Create upcoming community event towns', arguments: '--create-towns')]
#[AsScheduledTask('15,45 * * * *', description: 'Create community event towns that are marked as "urgent".', arguments: '--urgent-create-towns')]
#[AsScheduledTask('18 0 * * *', description: 'Conclude finished community events.', arguments: '--auto-end')]
class WatchdogCommand extends Command
{

    public function __construct(
        protected EntityManagerInterface $em,
        protected CreateTownFromConfigAction $createTownFromConfigAction,
        protected PermissionHandler $permissionHandler
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

    protected function create_towns(OutputInterface $output, bool $urgent): void {

        /** @var CommunityEvent[] $pending_events */
        $pending_events = $this->em->getRepository(CommunityEvent::class)->matching(
            $urgent
                ? (Criteria::create())
                    ->andWhere(Criteria::expr()->eq('urgent', true))
                : (Criteria::create())
                    ->where(Criteria::expr()->neq( 'starts', null ))
                    ->andWhere(Criteria::expr()->lte( 'starts', (new DateTime())->modify('+48hour') ))
                    ->andWhere(Criteria::expr()->eq('ended', false))
        );

        $meta_ids = [];

        foreach ($pending_events as $event)
            foreach ($event->getTownPresets() as $townPreset)
                if ($townPreset->getTownId() === null && $townPreset->getTown() === null)
                    $meta_ids[] = $townPreset->getId();

        $meta_ids = array_reverse($meta_ids);
        $this->em->clear();

        while (!empty($meta_ids)) {
            $id = array_pop($meta_ids);
            if (!$preset = $this->em->getRepository(CommunityEventTownPreset::class)->find($id)) continue;

            $output->write("Instancing town for preset <fg=yellow>{$preset->getId()}</> of event <fg=yellow>{$preset->getEvent()->getId()}</>...");
            $result = ($this->createTownFromConfigAction)($preset->getHeader(), $preset->getRules(), creator: $preset->getEvent()->getOwner(), force_disable_incarnate: true);
            if ($result->hasError()) $output->writeln("<fg=red>Error {$result->error()}</> {$result->exception()?->getMessage()}");
            elseif (!$result->town()) $output->writeln("<fg=red>Could not obtain town instance!</>");
            else {
                // Set the town schedule
                $result->town()->setScheduledFor( $preset->getEvent()->getStarts() );

                // Assign town instance
                $preset->setTown( $result->town() )->setTownId( $result->town()->getId() );
                $this->em->persist($preset);

                // Create an Animaction user group with Oracle/Mod permissions on the town forum and associate the user
                // to it. Also, give the global Animaction group read access to the forum
                $this->em->persist( $ga = (new UserGroup())->setName("[town:{$result->town()->getId()}:animaction]")->setType(UserGroup::GroupTownAnimaction)->setRef1($result->town()->getId()) );
                $this->em->persist( (new ForumUsagePermissions())->setForum($result->town()->getForum())->setPrincipalGroup($ga)->setPermissionsGranted(
                    ForumUsagePermissions::PermissionReadWrite |
                    ForumUsagePermissions::PermissionModerate | ForumUsagePermissions::PermissionFormattingModerator |
                    ForumUsagePermissions::PermissionHelp | ForumUsagePermissions::PermissionFormattingOracle
                )->setPermissionsDenied(ForumUsagePermissions::PermissionNone) );
                $g_anim = $this->em->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultAnimactorGroup]);
                $this->em->persist( (new ForumUsagePermissions())->setForum($result->town()->getForum())->setPrincipalGroup($g_anim)->setPermissionsGranted(
                    ForumUsagePermissions::PermissionRead
                )->setPermissionsDenied(ForumUsagePermissions::PermissionNone) );
                $this->permissionHandler->associate( $preset->getEvent()->getOwner(), $ga );

                $this->em->flush();
                $this->em->clear();
                $output->writeln('<fg=green>OK!</> ');
            }

        }
    }

    protected function auto_end_events(OutputInterface $output): void {
        /** @var CommunityEvent[] $pending_events */
        $pending_events = $this->em->getRepository(CommunityEvent::class)->matching(
            (Criteria::create())
                ->where(Criteria::expr()->neq( 'starts', null ))
                ->andWhere(Criteria::expr()->lte( 'starts', (new DateTime())->modify('+48hour') ))
                ->andWhere(Criteria::expr()->eq('ended', false))
        );

        foreach ($pending_events as $event) {

            $all_towns_ended = null;
            foreach ($event->getTownPresets() as $preset) if ($preset->getTownId()) {
                if ($all_towns_ended === null) $all_towns_ended = true;
                if ($preset->getTown() !== null) $all_towns_ended = false;
            }

            $time_diff = (new DateTime())->getTimestamp() - $event->getStarts()->getTimestamp();

            // 50 days
            if ($all_towns_ended || $time_diff >= 4320000) {
                $output->writeln("Automatically concluding event <fg=yellow>{$event->getId()}</>.");
                $event->setEnded(true);
                $this->em->persist($event);
            }
        }

        $this->em->flush();
        $this->em->clear();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('create-towns')) $this->create_towns($output, false);
        if ($input->getOption('urgent-create-towns')) $this->create_towns($output, true);
        if ($input->getOption('auto-end')) $this->create_towns($output);

        return 0;
    }
}