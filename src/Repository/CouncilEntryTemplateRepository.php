<?php

namespace App\Repository;

use App\Entity\CouncilEntryTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CouncilEntryTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method CouncilEntryTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method CouncilEntryTemplate[]    findAll()
 * @method CouncilEntryTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CouncilEntryTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CouncilEntryTemplate::class);
    }

    // /**
    //  * @return CouncilEntryTemplate[] Returns an array of CouncilEntryTemplate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CouncilEntryTemplate
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
