<?php

namespace App\Repository;

use App\Entity\TwinoidImportPreview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TwinoidImportPreview|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwinoidImportPreview|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwinoidImportPreview[]    findAll()
 * @method TwinoidImportPreview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwinoidImportPreviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwinoidImportPreview::class);
    }

    // /**
    //  * @return TwinoidImportPreview[] Returns an array of TwinoidImportPreview objects
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
    public function findOneBySomeField($value): ?TwinoidImportPreview
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
