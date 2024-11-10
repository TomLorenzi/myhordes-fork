<?php

namespace App\Repository;

use App\Entity\MayorMark;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MayorMark>
 *
 * @method MayorMark|null find($id, $lockMode = null, $lockVersion = null)
 * @method MayorMark|null findOneBy(array $criteria, array $orderBy = null)
 * @method MayorMark[]    findAll()
 * @method MayorMark[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MayorMarkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MayorMark::class);
    }

//    /**
//     * @return MayorMark[] Returns an array of MayorMark objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MayorMark
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
