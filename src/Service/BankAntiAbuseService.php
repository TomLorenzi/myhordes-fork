<?php

namespace App\Service;

use App\Entity\ActionEventLog;
use App\Entity\Citizen;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;

class BankAntiAbuseService {

    private EntityManagerInterface $em;
    private ConfMaster $conf;

    public function __construct(ConfMaster $conf, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->conf = $conf;
    }

    public function increaseBankCount(Citizen $citizen) {
        $this->em->persist((new ActionEventLog())
            ->setType( $this->allowedToTake($citizen) ? ActionEventLog::ActionEventTypeBankTaken : ActionEventLog::ActionEventTypeBankLock )
            ->setTimestamp( new \DateTime() )
            ->setCitizen($citizen)
        );
    }

    public function allowedToTake(Citizen $citizen): bool {

        $town = $citizen->getTown();

        $nbObjectMax = $this->conf->getTownConfiguration($town)->get($town->getChaos() ? TownConf::CONF_BANK_ABUSE_LIMIT_CHAOS : TownConf::CONF_BANK_ABUSE_LIMIT, 5);
        $limit = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_BANK_ABUSE_LOCK, 15);

        $cutoff = (new \DateTime())->modify("-{$limit}min");

        $logs_lock = $this->em->getRepository(ActionEventLog::class)->createQueryBuilder('a')
            ->andWhere( 'a.type = :type' )->setParameter('type', ActionEventLog::ActionEventTypeBankLock)
            ->andWhere( 'a.citizen = :citizen')->setParameter('citizen', $citizen)
            ->andWhere( 'a.timestamp > :cutoff' )->setParameter('cutoff', $cutoff)
            ->orderBy( 'a.timestamp', 'ASC' )->setMaxResults(1) ->getQuery()->getResult();

        if (count($logs_lock) > 0) return false;

        $logs_taken = $this->em->getRepository(ActionEventLog::class)->createQueryBuilder('a')
            ->andWhere( 'a.type = :type' )->setParameter('type', ActionEventLog::ActionEventTypeBankTaken)
            ->andWhere( 'a.citizen = :citizen')->setParameter('citizen', $citizen)
            ->andWhere( 'a.timestamp > :cutoff' )->setParameter('cutoff', $cutoff)
            ->orderBy( 'a.timestamp', 'ASC' )->setMaxResults($nbObjectMax) ->getQuery()->getResult();


        return count($logs_taken) < $nbObjectMax;
    }
}