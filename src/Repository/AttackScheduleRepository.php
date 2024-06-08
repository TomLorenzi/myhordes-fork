<?php

namespace App\Repository;

use App\Entity\AttackSchedule;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AttackSchedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method AttackSchedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method AttackSchedule[]    findAll()
 * @method AttackSchedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttackScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttackSchedule::class);
    }

    public function findNext(?DateTimeInterface $time): ?AttackSchedule
    {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.timestamp > :val')->setParameter('val', $time ?? new \DateTimeImmutable('now'))
                ->orderBy('a.timestamp', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findNextUncompleted(): ?AttackSchedule
    {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.completed = :val')->setParameter('val', false)
                ->orderBy('a.timestamp', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findNextUnstarted(): ?AttackSchedule
    {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.completed = :val')->setParameter('val', false)
                ->andWhere('a.startedAt IS NULL')
                ->orderBy('a.timestamp', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findPrevious(?DateTimeInterface $time): ?AttackSchedule
    {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.timestamp <= :val')->setParameter('val', $time ?? new \DateTimeImmutable('now'))
                ->orderBy('a.timestamp', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findByCompletion(bool $completed)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.completed = :val')->setParameter('val', $completed)
            ->orderBy('a.timestamp', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return AttackSchedule[] Returns an array of AttackSchedule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AttackSchedule
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
