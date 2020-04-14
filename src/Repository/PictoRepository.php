<?php

namespace App\Repository;

use App\Entity\Picto;
use App\Entity\Town;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Picto|null find($id, $lockMode = null, $lockVersion = null)
 * @method Picto|null findByUser(user $criteria, array $orderBy = null)
 * @method Picto[]    findAll()
 * @method Picto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PictoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Picto::class);
    }

    public function findByUser(User $value)
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.user = :val')
                ->setParameter('val', $user)
                ->getQuery()
                ->getResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findPendingByUser(User $user)
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.user = :val')
                ->andWhere('i.persisted < 2')
                ->setParameter('val', $user)
                ->getQuery()
                ->getResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findNotPendingByUser(User $user)
    {
        try {
            return $this->createQueryBuilder('i')
                ->select('SUM(i.count) as c', 'pp.id', 'pp.rare', 'pp.icon', 'pp.label', 'pp.description')
                ->andWhere('i.user = :val')
                ->andWhere('i.persisted = 2')
                ->orderBy('pp.rare', 'DESC')
                ->addOrderBy('c', 'DESC')
                ->setParameter('val', $user)
                ->leftJoin('i.prototype', 'pp')
                ->groupBy("i.prototype")
                ->getQuery()
                ->getResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findPictoByUserAndTown(User $user, Town $town)
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.user = :val')
                ->andWhere('i.town =  :town')
                ->setParameter('val', $user)
                ->setParameter('town', $town)
                ->getQuery()
                ->getResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return Picto[] Returns an array of Picto objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Picto
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
