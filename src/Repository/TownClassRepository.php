<?php

namespace App\Repository;

use App\Entity\CitizenProfession;
use App\Entity\TownClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method TownClass|null find($id, $lockMode = null, $lockVersion = null)
 * @method TownClass|null findOneBy(array $criteria, array $orderBy = null)
 * @method TownClass[]    findAll()
 * @method TownClass[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TownClassRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TownClass::class);
    }

    public function findOneByName(string $value): ?TownClass
    {
        try {
            return $this->createQueryBuilder('t')
                ->andWhere('t.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return TownClass[] Returns an array of TownClass objects
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
    public function findOneBySomeField($value): ?TownClass
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
