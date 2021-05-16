<?php

namespace App\Repository;

use App\Entity\OfficialGroupMessageLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OfficialGroupMessageLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method OfficialGroupMessageLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method OfficialGroupMessageLink[]    findAll()
 * @method OfficialGroupMessageLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfficialGroupMessageLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfficialGroupMessageLink::class);
    }

    // /**
    //  * @return OfficialGroupMessageLink[] Returns an array of OfficialGroupMessageLink objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OfficialGroupMessageLink
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
