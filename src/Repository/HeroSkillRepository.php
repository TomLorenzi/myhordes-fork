<?php

namespace App\Repository;

use App\Entity\HeroSkill;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HeroSkill|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeroSkill|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeroSkill[]    findAll()
 * @method HeroSkill[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeroSkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroSkill::class);
    }

    public function getLatestForUser(User $user){
        return $this->createQueryBuilder('h')
            ->andWhere('h.user = :user')
            ->orderBy('h.dateUnlock', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
