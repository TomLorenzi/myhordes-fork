<?php

namespace App\Repository;

use App\Entity\GazetteEntryTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GazetteEntryTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method GazetteEntryTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method GazetteEntryTemplate[]    findAll()
 * @method GazetteEntryTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GazetteEntryTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GazetteEntryTemplate::class);
    }

    /**
     * @return GazetteEntryTemplate[] Returns an array of GazetteEntryTemplate objects
     */

    public function findByType($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.type = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return GazetteEntryTemplate[] Returns an array of GazetteEntryTemplate objects
     */

    public function findByTypes(array $value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.type IN (:val)')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return GazetteEntryTemplate[] Returns an array of GazetteEntryTemplate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GazetteEntryTemplate
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
