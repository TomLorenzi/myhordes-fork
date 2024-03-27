<?php

namespace App\Repository;

use App\Entity\PictoRollup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PictoRollup>
 *
 * @method PictoRollup|null find($id, $lockMode = null, $lockVersion = null)
 * @method PictoRollup|null findOneBy(array $criteria, array $orderBy = null)
 * @method PictoRollup[]    findAll()
 * @method PictoRollup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PictoRollupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PictoRollup::class);
    }

//    /**
//     * @return PictoRollup[] Returns an array of PictoRollup objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PictoRollup
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
