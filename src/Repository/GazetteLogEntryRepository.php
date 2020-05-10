<?php

namespace App\Repository;

use App\Entity\LogEntryTemplate;
use App\Entity\Gazette;
use App\Entity\GazetteLogEntry;
use App\Entity\TownLogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method GazetteLogEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method GazetteLogEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method GazetteLogEntry[]    findAll()
 * @method GazetteLogEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GazetteLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GazetteLogEntry::class);
    }

    /**
     * @param Gazette $gazette
     * @param int|int[],null $type
     * @param int|null $max
     * @return TownLogEntry[] Returns an array of TownLogEntry objects
     */
    public function findByFilter(Gazette $gazette, $type = null, ?int $max = null)
    {
        if (!$gazette->getId()) return [];
        $q = $this->createQueryBuilder('g')
            ->andWhere('g.gazette = :gazette')->setParameter('gazette', $gazette);

        if ($type !== null) {
            if (is_array($type)) $applicableEntryTemplates = $this->_em->getRepository(LogEntryTemplate::class)->findByTypes($type);
            else                 $applicableEntryTemplates = $this->_em->getRepository(LogEntryTemplate::class)->findByType($type);

            $q->andWhere( 'g.logEntryTemplate IN (:type)' )->setParameter('type', $applicableEntryTemplates);
        }

        if ($max !== null) $q->setMaxResults( $max );

        return $q
            ->orderBy('g.id', 'ASC')
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
