<?php

namespace App\Repository;

use App\Entity\GlobalPoll;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GlobalPoll|null find($id, $lockMode = null, $lockVersion = null)
 * @method GlobalPoll|null findOneBy(array $criteria, array $orderBy = null)
 * @method GlobalPoll[]    findAll()
 * @method GlobalPoll[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GlobalPollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GlobalPoll::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(GlobalPoll $entity, bool $flush = true): void
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
    public function remove(GlobalPoll $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return GlobalPoll[] Returns an array of GlobalPoll objects
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

    /**
     * @return GlobalPoll[] Returns an array of GlobalPoll objects
     */
    public function findByState(bool $include_past, bool $include_active, bool $include_future)
    {
        $qb = $this->createQueryBuilder('g')->orderBy( 'g.startDate', 'DESC' );

        if (!$include_past)
            $qb->andWhere('g.endDate >= :now');
        if (!$include_future)
            $qb->andWhere('g.startDate <= :now');
        if (!$include_active)
            $qb->andWhere('g.startDate > :now OR g.endDate < :now');

        if (!$include_past || !$include_active || !$include_future)
            $qb->setParameter('now', new DateTime());

        return $qb->getQuery()
            ->getResult()
        ;
    }
}
