<?php

namespace App\Repository;

use App\Entity\SoulResetMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SoulResetMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method SoulResetMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method SoulResetMarker[]    findAll()
 * @method SoulResetMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SoulResetMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoulResetMarker::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(SoulResetMarker $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SoulResetMarker $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return SoulResetMarker[] Returns an array of SoulResetMarker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SoulResetMarker
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
