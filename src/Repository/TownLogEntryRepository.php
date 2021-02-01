<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TownLogEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method TownLogEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method TownLogEntry[]    findAll()
 * @method TownLogEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TownLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TownLogEntry::class);
    }

    /**
     * @param Town $town The town we are fetching the logs from
     * @param int|null $day The day we are looking for
     * @param Citizen|boolean|null $citizen The concerned citizen
     * @param Zone|boolean|null $zone The concerned zone
     * @param int|int[]|null $type The specific type
     * @param int|null $max Maximum entries
     * @param bool|null $admin_only If we want to show admin_only logs
     * @return TownLogEntry[] Returns an array of TownLogEntry objects
     */
    public function findByFilter(Town $town, ?int $day = null, $citizen = null, $zone = null, $type = null, ?int $max = null, ?bool $adminOnly = false)
    {
        $q = $this->createQueryBuilder('t')
            ->andWhere('t.town = :town')->setParameter('town', $town);

        if ($day !== null) {
            if ($day <= 0) $day = $town->getDay();
            $q->andWhere('t.day = :day')->setParameter('day', $day);
        }

        if     (is_bool($citizen)) $q->andWhere($citizen ? 't.citizen IS NOT NULL' : 't.citizen IS NULL');
        elseif ($citizen !== null) $q->andWhere('t.citizen = :citizen OR t.secondaryCitizen = :citizen')->setParameter('citizen', $citizen)->andWhere("t.hidden = false");

        if     (is_bool($zone)) $q->andWhere($zone ? 't.zone IS NOT NULL' : 't.zone IS NULL');
        elseif ($zone !== null) $q->andWhere('t.zone = :zone')->setParameter('zone', $zone);

        if ($type !== null) {

            if (is_array($type)) $applicableEntryTemplates = $this->_em->getRepository(LogEntryTemplate::class)->findByTypes($type);
            else                 $applicableEntryTemplates = $this->_em->getRepository(LogEntryTemplate::class)->findByType($type);

            $q->andWhere( 't.logEntryTemplate IN (:type)' )->setParameter('type', $applicableEntryTemplates);
        }
        if ($adminOnly !== null) $q->andWhere('t.adminOnly = :adminOnly')->setParameter('adminOnly', $adminOnly);

        if ($max !== null) $q->setMaxResults( $max );


        return $q
            ->orderBy('t.timestamp', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return TownLogEntry[] Returns an array of TownLogEntry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TownLogEntry
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
