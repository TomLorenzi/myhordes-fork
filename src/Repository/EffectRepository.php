<?php

namespace App\Repository;

use App\Entity\Effect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Effect>
 *
 * @method Effect|null find($id, $lockMode = null, $lockVersion = null)
 * @method Effect|null findOneBy(array $criteria, array $orderBy = null)
 * @method Effect[]    findAll()
 * @method Effect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EffectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Effect::class);
    }

    //    /**
    //     * @return Effect[] Returns an array of Effect objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Effect
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
