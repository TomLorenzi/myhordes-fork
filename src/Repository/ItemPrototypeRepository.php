<?php

namespace App\Repository;

use App\Entity\ItemCategory;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method ItemPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemPrototype[]    findAll()
 * @method ItemPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemPrototype::class);
    }

    public function findOneByName(string $value): ?ItemPrototype
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

    public function findAll() {
        return $this->findBy(array(), array("id" => "ASC"));
    }


    // /**
    //  * @return ItemPrototype[] Returns an array of ItemPrototype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ItemPrototype
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
