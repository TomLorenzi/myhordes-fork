<?php


namespace App\Command\Mod;


use App\Entity\Activity;
use App\Entity\ActivityCluster;
use App\Entity\User;
use ArrayHelpers\Arr;
use Carbon\Carbon;
use Composer\Console\Input\InputOption;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as InputOptionAlias;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsCommand(
    name: 'app:mod:identify-activity-clusters',
    description: 'Identifies activity clusters and queues their further investigation'
)]
#[AsScheduledTask('40 */6 * * *', description: 'Identifies activity clusters and queues their further investigation')]
class IdentifyActivityClusters extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOptionAlias::VALUE_NONE, 'Does not actually create clusters or queue any jobs.' )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->entityManager->getRepository(Activity::class);
        $cluster_ids = array_column($repository->createQueryBuilder('a')
            ->select('a.ip', 'COUNT(DISTINCT a.user) AS c')
            ->groupBy('a.ip')
            ->orderBy('c', 'DESC')
            ->having('c > 2')->getQuery()->getScalarResult()
        , 'c', 'ip');

        $output->writeln(
            sprintf('Found <fg=green>%d</> suspicious clusters, the most suspicious one containing <fg=red>%d</> users.', count($cluster_ids), $cluster_ids[array_key_first($cluster_ids)]),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $cluster_users = [];

        foreach ($cluster_ids as $ip => $count) {
            $boxed_spans = $repository->createQueryBuilder('a')
                ->select('IDENTITY(a.user) AS user', 'MIN( a.blockBegin ) AS min', 'MAX( a.blockEnd ) AS max')
                ->where('a.ip = :ip')->setParameter('ip', $ip)
                ->groupBy('a.user')
                ->orderBy('min', 'ASC')->addOrderBy('max', 'ASC')->getQuery()->getScalarResult();

            $nulldate = "2020-01-01 08:00:00";
            $findate = "9999-01-01 08:00:00";

            $all_users = array_map( fn(array $a) => $a['user'], $boxed_spans );
            $whitelisted_users = [];
            foreach ($boxed_spans as ['user' => $user_id]) {
                if (in_array($user_id, $whitelisted_users)) continue;

                $user = $this->entityManager->getRepository(User::class)->find($user_id);
                foreach ($user?->getConnectionWhitelists() ?? [] as $list)
                    foreach ($list->getUsers() as $other_user)
                        if (in_array( $other_user->getId(), $all_users )) {
                            $whitelisted_users[] = $user_id;
                            $whitelisted_users[] = $other_user->getId();
                        }
            }
            $whitelisted_users = array_unique($whitelisted_users);

            if (count($whitelisted_users) > 0)
                $output->writeln( sprintf("Cluster <fg=green>%s</> contains <fg=green>%d</> whitelisted users (out of <fg=red>%d</>)", $ip, count($whitelisted_users), count($all_users)), OutputInterface::VERBOSITY_VERBOSE );

            $relevant_users = [];
            foreach ( $boxed_spans as $n => ['user' => $user_id, 'min' => $min, 'max' => $max] ) {

                $box_before_end = Carbon::create(Arr::get($boxed_spans, ($n-1) . '.min', $nulldate));
                $box_after_begin  = Carbon::create(Arr::get($boxed_spans, ($n+1) . '.min', $findate));

                $box_begin = Carbon::create( $min );
                $box_end = Carbon::create( $max );

                if (
                    $box_begin->diffInHours( $box_before_end ) < -4 &&
                    $box_end->diffInHours( $box_after_begin ) > 4
                ) {
                    $output->writeln( "Discarding user <fg=green>$user_id</> from cluster <fg=green>$ip</> as an outlier.", OutputInterface::VERBOSITY_VERBOSE );
                    continue;
                }

                $relevant_users[] = $user_id;
            }

            if ((count($relevant_users) - count($whitelisted_users)) < 3) {
                $output->writeln( "Discarding cluster <fg=green>$ip</> for a lack of relevant users.", OutputInterface::VERBOSITY_VERBOSE );
                continue;
            }

            $cluster_users[$ip] = $relevant_users;
        }

        $dry = $input->getOption('dry-run');
        foreach ( $cluster_users as $ip => $users ) {
            $identifier = md5($ip);
            $ipv6 = str_contains($ip, ':');

            $cluster = $this->entityManager->getRepository(ActivityCluster::class)->findOneBy([
                'identifier' => $identifier,
                'ipv6' => $ipv6,
            ]) ?? (new ActivityCluster())->setIdentifier($identifier)->setIpv6($ipv6)->setFirstSeen(new DateTime());
            $cluster->setLastSeen(new DateTime());

            if ($cluster->getId() === null) $output->writeln(sprintf("<fg=green>Creating</> an activity cluster for identifier <fg=yellow>%s</> and queuing scans for <fg=red>%d</> users.", $identifier, count($users)), OutputInterface::VERBOSITY_VERBOSE );
            else $output->writeln(sprintf("<fg=green>Updating</> activity cluster <fg=yellow>%s</> for identifier <fg=yellow>%s</> and queuing scans for <fg=red>%d</> users.", $cluster->getId(), $identifier, count($users)), OutputInterface::VERBOSITY_VERBOSE );

            $this->entityManager->persist($cluster);

            if (!$dry) {
                $this->entityManager->flush();

                foreach ($users as $user_id) {
                    $this->bus->dispatch(new RunCommandMessage("app:mod:activity-clusters-scan $user_id $ip {$cluster->getId()} 1"));
                    $this->bus->dispatch(new RunCommandMessage("app:mod:activity-clusters-scan $user_id $ip {$cluster->getId()} 3"));
                    $this->bus->dispatch(new RunCommandMessage("app:mod:activity-clusters-scan $user_id $ip {$cluster->getId()} 7"));
                }
            }

        }

        return 0;
    }
}