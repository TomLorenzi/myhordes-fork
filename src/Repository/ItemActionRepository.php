<?php

namespace App\Repository;

use App\Entity\ItemAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method ItemAction|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemAction|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemAction[]    findAll()
 * @method ItemAction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemAction::class);
    }

    public function findOneByName(string $value): ?ItemAction
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
    //  * @return ItemAction[] Returns an array of ItemAction objects
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
    public function findOneBySomeField($value): ?ItemAction
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
