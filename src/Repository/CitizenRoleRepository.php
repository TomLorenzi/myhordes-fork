<?php

namespace App\Repository;

use App\Entity\CitizenRole;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CitizenRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenRole[]    findAll()
 * @method CitizenRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenRole::class);
    }

    public function findDefault(): ?CitizenRole {
        return $this->findOneByName(CitizenRole::DEFAULT);
    }

    public function findOneByName(string $value): ?CitizenRole
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return CitizenRole[] Returns an array of CitizenRole objects
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
    public function findOneBySomeField($value): ?CitizenRole
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
