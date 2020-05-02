<?php

namespace App\Repository;

use App\Entity\LogEntryTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LogEntryTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogEntryTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogEntryTemplate[]    findAll()
 * @method LogEntryTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogEntryTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEntryTemplate::class);
    }

    /**
     * @return LogEntryTemplate[] Returns an array of LogEntryTemplate objects
     */
    public function findByName($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.name = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return LogEntryTemplate[] Returns an array of LogEntryTemplate objects
     */
    public function findByText($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.text = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return LogEntryTemplate[] Returns an array of LogEntryTemplate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LogEntryTemplate
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
