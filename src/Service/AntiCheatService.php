<?php

namespace App\Service;

use App\Entity\AccountRestriction;
use App\Entity\Activity;
use App\Entity\User;
use App\Structures\CheatTable;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use WhichBrowser\Parser;

class AntiCheatService {

    public function __construct(
        private readonly ConfMaster $conf,
        private readonly EntityManagerInterface $em,
        private readonly UserHandler $user_handler,
        private readonly TagAwareCacheInterface $gameCachePool,
    ) { }

    public function cleanseConnectionIdentifiers() {
        $old = $this->em->getRepository(Activity::class)->matching(
            (new Criteria())->where(Criteria::expr()->lte('dateTimeEnd', new DateTime('-7 days')))
        );
        foreach ($old as $e) $this->em->remove($e);
    }

    protected function blockTime(\DateTime $dateTime, bool $up = false, int $interval = 1800): \DateTime {
        $base = (clone $dateTime)->modify('today')->getTimestamp();
        $offset = (float)($dateTime->getTimestamp() - $base + ($up ? 1 : 0)) / (float)$interval;

        return (new \DateTime())->setTimestamp($base + round(($up ? ceil($offset) : floor($offset)) * $interval));
    }

    public function recordConnection(?User $user, Request $request) {
        if (!$user) return;

        $agent_raw = $request->headers->get('User-Agent') ?? '-no-agent-';
        $agent_string = $this->gameCachePool->get("ua_detect_" . md5($agent_raw), function (ItemInterface $item) use ($agent_raw) {
            $agent_parser = new Parser($agent_raw);
            $item->expiresAfter(6000);

            $device = $agent_parser->device->toString() ?: 'Unknown Device';
            return "{$device} | {$agent_parser->os->toString()} | {$agent_parser->browser->getName()}";
        });

        $prev_segment = $this->blockTime( new DateTime(), false );
        $next_segment = $this->blockTime( new DateTime(), true );

        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($user, $request, $prev_segment, $next_segment, $agent_string) {
            try {
                $existing = $em->getRepository(Activity::class)->createQueryBuilder('a')
                    ->andWhere('a.user = :user')->setParameter('user', $user)
                    ->andWhere('a.ip = :ip')->setParameter('ip', $request->getClientIp())
                    ->andWhere('a.agent = :agent')->setParameter('agent', $agent_string)
                    ->andWhere('a.domain = :host')->setParameter('host', $request->getHost())
                    ->andWhere('a.blockEnd >= :block')->setParameter('block', $prev_segment)
                    ->orderBy('a.blockEnd', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()->setLockMode(LockMode::PESSIMISTIC_WRITE)->getSingleResult();
            } catch (NoResultException $e) {
                $existing = (new Activity())
                    ->setUser( $user )
                    ->setIp($request->getClientIp())
                    ->setAgent($agent_string)
                    ->setDomain($request->getHost())
                    ->setBlockBegin($prev_segment)
                    ->setDateTimeBegin(new DateTime())
                    ->setRequests(0);
            }

            $existing->setBlockEnd($next_segment)->setDateTimeEnd(new DateTime())->setRequests( $existing->getRequests() + 1 );
            $em->persist( $existing );
        });

    }

    /**
     * @return CheatTable[]
     */
    public function createMultiAccountReport(): array {

        /** @var QueryBuilder $qb */
        $repo = $this->em->getRepository(Activity::class);;

        $id_data = array_column(
            $repo->createQueryBuilder('c')
                ->select('c.ip', 'count(c.ip) as n')
                ->groupBy('c.ip')
                ->having('n > 1')
                ->orderBy('n', 'DESC')
                ->getQuery()->getScalarResult(),
            'n', 'ip');

        $user_matrix = [];

        $fun_add = function(int $id_a, int $id_b, int $dif) use (&$user_matrix) {
            if ($id_a === $id_b) return;
            if (!isset($user_matrix[$id_a])) $user_matrix[$id_a] = [];
            if (!isset($user_matrix[$id_a][$id_b])) $user_matrix[$id_a][$id_b] = [];
            $user_matrix[$id_a][$id_b][] = abs($dif);
        };

        foreach ($id_data as $identifier => $count) {

            $user_times = array_column(
                $repo->createQueryBuilder('c')
                    ->select('c.dateTimeEnd', 'identity(c.user) as user_id')
                    ->where('c.ip = :i')->setParameter('i', $identifier)
                    ->orderBy('user_id', 'ASC')
                    ->getQuery()->getScalarResult(),
            'dateTimeEnd', 'user_id');

            foreach ($user_times as $user_id_a => $time_a)
                foreach ($user_times as $user_id_b => $time_b)
                    $fun_add( $user_id_a, $user_id_b, strtotime($time_a) - strtotime($time_b) );

        }

        $ret = [];

        $user_cache = [];
        $fun_get_user = function (int $id) use (&$user_cache): User {
            return $user_cache[$id] ?? ($user_cache[$id] = $this->em->getRepository(User::class)->find($id));
        };

        $fun_score_time = function(int $dif): int {
            if     ($dif <  3600) return (int)(50 + (1 - $dif/ 3600) * 50); // Score 50 - 100 if dif is below 1  hour
            elseif ($dif < 21600) return (int)(10 + (1 - $dif/21600) * 40); // Score 10 -  50 if dif is below 6  hours
            elseif ($dif < 86400) return (int)( 0 + (1 - $dif/86400) * 10); // Score  0 -  10 if dif is below 24 hours
            else return 0;
        };

        foreach ($user_matrix as $principal => $user_list) {
            if ($this->user_handler->hasRole($fun_get_user($principal), "ROLE_CROW")) continue;
            $struct = new CheatTable($fun_get_user($principal));
            foreach ($user_list as $multi => $time_list) {
                if ($this->user_handler->hasRole($fun_get_user($multi), "ROLE_CROW")) continue;
                foreach ($fun_get_user($principal)->getConnectionWhitelists() as $wl)
                    if ($wl->getUsers()->contains( $fun_get_user($multi) )) continue 2;
                $struct->addUser( $fun_get_user($multi) );

                $factor = $this->user_handler->isRestricted( $fun_get_user($multi), AccountRestriction::RestrictionGameplay ) ? 2 : 1;

                $lev = min(
                    levenshtein( $fun_get_user($principal)->getUsername(), $fun_get_user($multi)->getUsername() ),
                    levenshtein( $fun_get_user($principal)->getUsername(), $fun_get_user($multi)->getDisplayName() ?? '' ),
                    levenshtein( $fun_get_user($principal)->getUsername(), explode( '@', $fun_get_user($multi)->getEmail() ?? '' )[0]),
                );

                if ($fun_get_user($principal)->getDisplayName())
                    $lev = min(
                        $lev,
                        levenshtein( $fun_get_user($principal)->getDisplayName(), $fun_get_user($multi)->getUsername() ),
                        levenshtein( $fun_get_user($principal)->getDisplayName(), $fun_get_user($multi)->getDisplayName() ?? '' ),
                        levenshtein( $fun_get_user($principal)->getDisplayName(), explode( '@', $fun_get_user($multi)->getEmail() ?? '' )[0]),
                    );

                if ($fun_get_user($principal)->getEmail()) {
                    $email = explode( '@', $fun_get_user($principal)->getEmail())[0];
                    $lev = min(
                        $lev,
                        levenshtein( $email, $fun_get_user($multi)->getUsername() ),
                        levenshtein( $email, $fun_get_user($multi)->getDisplayName() ?? '' ),
                        levenshtein( $email, explode( '@', $fun_get_user($multi)->getEmail() ?? '' )[0]),
                    );
                }

                if ($lev <= 0) $struct->addLikeliness(intval(1500 * $factor));
                elseif ($lev <= 1) $struct->addLikeliness(intval(750 * $factor));
                elseif ($lev <= 2) $struct->addLikeliness(intval(500 * $factor));
                elseif ($lev <= 3) $struct->addLikeliness(intval(250 * $factor));

                foreach ($time_list as $time_dif)
                    $struct->addLikeliness( intval($fun_score_time($time_dif) * $factor ));
            }
            $ret[$principal] = $struct;
        }

        $c = [];
        foreach ($ret as $id => $struct) {
            $c[$id] = 0;
            foreach ($struct->getUsers() as $user)
                $c[$id] += $ret[$user->getId()]->getLikeliness() * min(0.5,$struct->getLikeliness() / 100);
        }
        foreach ($ret as $id => $struct) $struct->addLikeliness( intval($c[$id]) );

        uasort( $ret, function (CheatTable $a, CheatTable $b) { return $b->getLikeliness() <=> $a->getLikeliness(); } );

        return array_filter($ret, function(CheatTable $c) { return $c->getLikeliness() >= 10; });
    }
}