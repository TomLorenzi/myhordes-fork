<?php

namespace App\Repository;

use App\Entity\HeroSkillUnlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeroSkillUnlock>
 *
 * @method HeroSkillUnlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeroSkillUnlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeroSkillUnlock[]    findAll()
 * @method HeroSkillUnlock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeroSkillUnlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroSkillUnlock::class);
    }

    //    /**
    //     * @return HeroSkillUnlock[] Returns an array of HeroSkillUnlock objects
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

    //    public function findOneBySomeField($value): ?HeroSkillUnlock
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
