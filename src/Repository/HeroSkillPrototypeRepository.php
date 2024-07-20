<?php

namespace App\Repository;

use App\Entity\HeroSkillPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HeroSkillPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeroSkillPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeroSkillPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeroSkillPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroSkillPrototype::class);
    }

    public function findAll(): array {
        return $this->createQueryBuilder('h')
            ->orderBy('h.daysNeeded', 'ASC')
            ->addOrderBy('h.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextUnlockable(int $currentDays) {
        return $this->createQueryBuilder('h')
            ->andWhere('h.daysNeeded > :days')
            ->andWhere('h.enabled = 1')
            ->andWhere('h.legacy = 1')
            ->orderBy('h.daysNeeded', 'ASC')
            ->addOrderBy('h.id', 'ASC')
            ->setParameter('days', $currentDays)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getUnlocked(int $currentDays) {
        return $this->createQueryBuilder('h')
            ->andWhere('h.daysNeeded <= :days')
            ->andWhere('h.enabled = 1')
            ->andWhere('h.legacy = 1')
            ->orderBy('h.daysNeeded', 'ASC')
            ->addOrderBy('h.id', 'ASC')
            ->setParameter('days', $currentDays)
            ->getQuery()
            ->getResult();
    }

    public function getLatestUnlocked(int $currentDays) {
        return $this->createQueryBuilder('h')
            ->andWhere('h.daysNeeded <= :days')
            ->andWhere('h.enabled = 1')
            ->andWhere('h.legacy = 1')
            ->orderBy('h.daysNeeded', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setParameter('days', $currentDays)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByName(string $value): ?HeroSkillPrototype
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
