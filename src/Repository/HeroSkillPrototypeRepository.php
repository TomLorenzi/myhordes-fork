<?php

namespace App\Repository;

use App\Entity\HeroSkillPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HeroSkillPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeroSkillPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeroSkillPrototype[]    findAll()
 * @method HeroSkillPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeroSkillPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroSkillPrototype::class);
    }

    public function getNextUnlockable(int $currentDays) {
        return $this->createQueryBuilder('h')
            ->andWhere('h.daysNeeded > :days')
            ->orderBy('h.daysNeeded', 'ASC')
            ->orderBy('h.id', 'ASC')
            ->setParameter('days', $currentDays)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getUnlocked(int $currentDays) {
        return $this->createQueryBuilder('h')
            ->andWhere('h.daysNeeded <= :days')
            ->orderBy('h.daysNeeded', 'ASC')
            ->orderBy('h.id', 'ASC')
            ->setParameter('days', $currentDays)
            ->getQuery()
            ->getResult();
    }

    public function getLatestUnlocked(int $currentDays) {
        return $this->createQueryBuilder('h')
            ->andWhere('h.daysNeeded <= :days')
            ->orderBy('h.daysNeeded', 'DESC')
            ->orderBy('h.id', 'DESC')
            ->setParameter('days', $currentDays)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
