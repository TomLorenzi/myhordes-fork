<?php

namespace App\Repository;

use App\Entity\RequireBuilding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RequireBuilding|null find($id, $lockMode = null, $lockVersion = null)
 * @method RequireBuilding|null findOneBy(array $criteria, array $orderBy = null)
 * @method RequireBuilding[]    findAll()
 * @method RequireBuilding[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequireBuildingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequireBuilding::class);
    }

    public function findOneByName(string $value): ?RequireBuilding
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return RequireBuilding[] Returns an array of RequireBuilding objects
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
    public function findOneBySomeField($value): ?RequireBuilding
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
