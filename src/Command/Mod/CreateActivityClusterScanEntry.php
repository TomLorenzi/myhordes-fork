<?php


namespace App\Command\Mod;


use App\Entity\Activity;
use App\Entity\ActivityCluster;
use App\Entity\ActivityClusterEntry;
use App\Entity\User;
use ArrayHelpers\Arr;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:mod:activity-clusters-scan',
    description: 'Identifies activity clusters and queues their further investigation'
)]
class CreateActivityClusterScanEntry extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Processes a single activity cluster entry.')

            ->addArgument('id',InputArgument::REQUIRED, 'User ID')
            ->addArgument('ip',InputArgument::REQUIRED, 'User IP')
            ->addArgument('cluster',InputArgument::REQUIRED, 'Cluster ID')
            ->addArgument('days',InputArgument::REQUIRED, 'Number of days in the past to consider')
        ;
        parent::configure();
    }

    /**
     * @param Activity[] $entries
     * @param DateTime $cutoff
     * @param int $stepLength
     * @return array
     */
    protected function partition(array $entries, DateTime $cutoff, int $stepLength = 1800): array {
        $partition = [];
        $c = $cutoff->getTimestamp();
        foreach ($entries as $entry) {
            $a = $entry->getBlockBegin()->getTimestamp();
            $b = $entry->getBlockEnd()->getTimestamp();

            while ($a < $b) {
                if ($a >= $c) Arr::set( $partition, "$a", Arr::get( $partition, "$a", 0 ) + 1 );
                $a += $stepLength;
            }
        }

        return $partition;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->entityManager->getRepository(User::class)->find($input->getArgument('id'));
        $cluster = $this->entityManager->getRepository(ActivityCluster::class)->find($input->getArgument('cluster'));
        $ip = $input->getArgument('ip');
        $days = $input->getArgument('days');

        if (!$user || !$cluster) {
            $output->writeln('<fg=red>User or cluster not found.</>', OutputInterface::VERBOSITY_VERBOSE);
            return 0;
        }

        $all_entries = $this->entityManager->getRepository(Activity::class)->findBy(['ip' => $ip]);
        $owner_entries = array_filter($all_entries, fn (Activity $clusterEntry) => $clusterEntry->getUser() === $user);
        $foreign_entries = array_filter($all_entries, fn (Activity $clusterEntry) => $clusterEntry->getUser() !== $user);

        $owner_ua = array_unique( array_map( fn(Activity $clusterEntry) => $clusterEntry->getAgent(), $owner_entries ) );
        $foreign_ua = array_unique( array_map( fn(Activity $clusterEntry) => $clusterEntry->getAgent(), $foreign_entries ) );

        if (empty($owner_entries) || empty($foreign_entries)) {
            $output->writeln('<fg=red>No matching activity found.</>', OutputInterface::VERBOSITY_VERBOSE);
            return 0;
        }

        $foreign_users = array_unique( array_map( fn (Activity $clusterEntry) => $clusterEntry->getUser()->getId(), $foreign_entries ) );

        $blacklist_ips = $this->entityManager->getRepository(Activity::class)->createQueryBuilder('a')
            ->select('DISTINCT a.ip')
            ->where('a.user IN (:others)')->setParameter('others', $foreign_users)
            ->andWhere('a.ip != :ip')->setParameter('ip', $ip)->getQuery()->getSingleColumnResult();

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->eq('user', $user));
        $criteria->andWhere($criteria->expr()->notIn('ip', [$ip, ...$blacklist_ips]));
        $out_of_cluster_entries = $this->entityManager->getRepository(Activity::class)->matching($criteria)->toArray();

        $output->writeln(sprintf( "Found <fg=green>%d</> owning activities in and <fg=green>%d</> outside the cluster, as well as <fg=red>%d</> foreign activities from <fg=red>%d</> other users.", count($owner_entries), count($out_of_cluster_entries), count($foreign_entries), count($foreign_users)) );

        // Partitioning
        $cutoff = (new DateTime())->modify("-{$days}days today");
        $own_partition = $this->partition( $owner_entries, $cutoff );
        $ooc_partition = $this->partition( $out_of_cluster_entries, $cutoff );
        $foreign_partition = $this->partition( $foreign_entries, $cutoff );

        $overlapping_blocks = count(array_intersect( array_keys( $own_partition ), array_keys( $foreign_partition ) ) );
        $ooc_overlapping_blocks = count(array_intersect( array_keys( $ooc_partition ), array_keys( $foreign_partition ) ) );

        $individual_overlap = 0;
        $overlapping_users = 0;
        foreach ($foreign_users as $foreign_user) {
            $p = $this->partition( array_filter( $foreign_entries, fn(Activity $a) => $a->getUser()->getId() === $foreign_user ), $cutoff );
            $c = count(array_intersect( array_keys( $own_partition ), array_keys( $p ) ) );
            if ($c > 0) $overlapping_users++;
            $individual_overlap += $c;
        }

        if ($overlapping_users === 0) $individual_overlap = 0;
        else $individual_overlap /= $overlapping_users;

        if (count( $own_partition ) > 0)
            $output->writeln(sprintf( "Result:\n" .
            "\tGeneral overlap: <fg=red>%d | %4.1f hours | %d%%</>\n" .
            "\tAvg. individual overlap: <fg=red>%4.1f | %4.1f hours | %d%% | %d/%d users (%d%%) in this cluster</>\n" .
            "\tUA similarity: <fg=red>%4.1f%%</>\n" .
            "\tAway from others: <fg=green>%d | %4.1f hours | %4.2f%%</>)"
            ,
            $overlapping_blocks, $overlapping_blocks/2, 100 * $overlapping_blocks / count( $own_partition ),
            $individual_overlap, $individual_overlap/2, 100 * $individual_overlap / count( $own_partition ), $overlapping_users, count($foreign_users), 100 * $overlapping_users/count($foreign_users),
            (count(array_intersect( $owner_ua, $foreign_ua )) + count(array_intersect( $foreign_ua, $owner_ua ))) / (count($owner_ua) + count($foreign_ua)) * 100,
            $ooc_overlapping_blocks, $ooc_overlapping_blocks/2, 100 * $ooc_overlapping_blocks / count( $own_partition ),
            ));
        else $output->writeln('<fg=green>No activity within the given time line.</>');

        $entry = $this->entityManager->getRepository(ActivityClusterEntry::class)->findOneBy([
            'user' => $user,
            'cluster' => $cluster,
            'cutoff' => $days
        ]) ?? (new ActivityClusterEntry())
            ->setUser($user)->setCluster($cluster)->setCutoff($days)->setFirstSeen(new DateTime());

        $entry
            ->setOwnBlocks( count( $own_partition ) )
            ->setForeignBlocks( count( $foreign_partition ) )
            ->setTotalOverlap( $overlapping_blocks )
            ->setAverageOverlap( $individual_overlap )
            ->setOverlappingUsers( $overlapping_users )
            ->setOverlappingUA( (count(array_intersect( $owner_ua, $foreign_ua )) + count(array_intersect( $foreign_ua, $owner_ua ))) / (count($owner_ua) + count($foreign_ua)) )
            ->setLastSeen( new DateTime() );

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return 0;
    }
}