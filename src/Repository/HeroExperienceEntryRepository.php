<?php

namespace App\Repository;

use App\Entity\HeroExperienceEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeroExperienceEntry>
 *
 * @method HeroExperienceEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeroExperienceEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeroExperienceEntry[]    findAll()
 * @method HeroExperienceEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeroExperienceEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroExperienceEntry::class);
    }

    //    /**
    //     * @return HeroExperienceEntry[] Returns an array of HeroExperienceEntry objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?HeroExperienceEntry
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
