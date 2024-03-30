<?php

namespace App\Repository;

use App\Entity\PictoComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PictoComment>
 *
 * @method PictoComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method PictoComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method PictoComment[]    findAll()
 * @method PictoComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PictoCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PictoComment::class);
    }

//    /**
//     * @return PictoComment[] Returns an array of PictoComment objects
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

//    public function findOneBySomeField($value): ?PictoComment
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
