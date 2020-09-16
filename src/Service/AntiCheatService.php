<?php

namespace App\Service;

use App\Entity\ConnectionIdentifier;
use App\Entity\User;
use App\Structures\CheatTable;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class AntiCheatService {

    private $em;
    private $conf;
    private $user_handler;

    public function __construct(ConfMaster $conf, EntityManagerInterface $em, UserHandler $uh)
    {
        $this->em = $em;
        $this->conf = $conf;
        $this->user_handler = $uh;
    }

    public function cleanseConnectionIdentifiers() {
        $old = $this->em->getRepository(ConnectionIdentifier::class)->matching(
            (new Criteria())->where(Criteria::expr()->lte('lastUsed', new DateTime('-7 days')))
        );
        foreach ($old as $e) $this->em->remove($e);
    }

    public function recordConnection(?User $user, Request $request) {

        if (!$user) return;

        $id = md5($request->getClientIp());
        $existing = $this->em->getRepository(ConnectionIdentifier::class)->findOneBy(['user' => $user, 'identifier' => $id]);
        if (!$existing) $existing = (new ConnectionIdentifier())->setUser($user)->setIdentifier($id);

        $existing->setLastUsed(new DateTime('now'));
        $this->em->persist($existing);

    }

    /**
     * @return CheatTable[]
     */
    public function createMultiAccountReport(): array {

        /** @var QueryBuilder $qb */
        $repo = $this->em->getRepository(ConnectionIdentifier::class);;

        $id_data = array_column(
            $repo->createQueryBuilder('c')
                ->select('c.identifier', 'count(c.id) as n')
                ->groupBy('c.identifier')
                ->having('n > 1')
                ->orderBy('n', 'DESC')
                ->getQuery()->getScalarResult(),
            'n', 'identifier');

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
                    ->select('c.lastUsed', 'identity(c.user) as user_id')
                    ->where('c.identifier = :i')->setParameter('i', $identifier)
                    ->orderBy('user_id', 'ASC')
                    ->getQuery()->getScalarResult(),
            'lastUsed', 'user_id');

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
            if     ($dif <  3600) return 50 + (1 - $dif/ 3600) * 50; // Score 50 - 100 if dif is below 1  hour
            elseif ($dif < 21600) return 10 + (1 - $dif/21600) * 40; // Score 10 -  50 if dif is below 6  hours
            elseif ($dif < 86400) return  0 + (1 - $dif/86400) * 10; // Score  0 -  10 if dif is below 24 hours
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
                foreach ($time_list as $time_dif)
                    $struct->addLikeliness( $fun_score_time($time_dif) * ($fun_get_user($multi)->getShadowBan() ? 2 : 1) );
            }
            $ret[$principal] = $struct;
        }

        $c = [];
        foreach ($ret as $id => $struct) {
            $c[$id] = 0;
            foreach ($struct->getUsers() as $user)
                $c[$id] += $ret[$user->getId()]->getLikeliness() * min(0.5,$struct->getLikeliness() / 100);
        }
        foreach ($ret as $id => $struct) $struct->addLikeliness( $c[$id] );

        uasort( $ret, function (CheatTable $a, CheatTable $b) { return $b->getLikeliness() <=> $a->getLikeliness(); } );

        return array_filter($ret, function(CheatTable $c) { return $c->getLikeliness() >= 10; });
    }
}