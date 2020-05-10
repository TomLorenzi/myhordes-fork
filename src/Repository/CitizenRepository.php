<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\Town;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Citizen|null find($id, $lockMode = null, $lockVersion = null)
 * @method Citizen|null findOneBy(array $criteria, array $orderBy = null)
 * @method Citizen[]    findAll()
 * @method Citizen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Citizen::class);
    }

    public function findActiveByUser(User $user): ?Citizen
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.user = :user')
                ->andWhere('c.active = :active')
                ->setParameter('user', $user)
                ->setParameter('active', true)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findOneByRoleAndTown(CitizenRole $role, Town $town): ?Citizen
    {
        try {
            return $this->createQueryBuilder('c')
                ->innerJoin('c.roles', 'r')
                ->andWhere('c.town = :town')
                ->andWhere('r = :role')
                ->andWhere('c.alive = 1')
                ->setParameter('town', $town)
                ->setParameter('role', $role)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findCitizenWithRole(Town $town)
    {
        try {
            return $this->createQueryBuilder('c')
                ->innerJoin('c.roles', 'r')
                ->andWhere('c.town = :town')
                ->andWhere('c.alive = 1')
                ->setParameter('town', $town)
                ->getQuery()
                ->getResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return Citizen[] Returns an array of Citizen objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Citizen
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
