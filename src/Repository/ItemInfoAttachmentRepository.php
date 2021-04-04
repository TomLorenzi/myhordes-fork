<?php

namespace App\Repository;

use App\Entity\ItemInfoAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ItemInfoAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemInfoAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemInfoAttachment[]    findAll()
 * @method ItemInfoAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemInfoAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemInfoAttachment::class);
    }

    // /**
    //  * @return ItemInfoAttachment[] Returns an array of ItemInfoAttachment objects
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
    public function findOneBySomeField($value): ?ItemInfoAttachment
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
