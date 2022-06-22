<?php

namespace App\Repository;

use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\Season;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeatureUnlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeatureUnlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeatureUnlock[]    findAll()
 * @method FeatureUnlock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeatureUnlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureUnlock::class);
    }

    public function findActiveForUser(User $user, ?Season $season, $feature)
    {
        if (is_string($feature)) $feature = $this->getEntityManager()->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => $feature]);
        if ($feature === null) return [];
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')->setParameter('user',$user)
            ->andWhere('f.prototype = :feature')->setParameter('feature', $feature)
            ->andWhere('((f.expirationMode = :femn) OR (f.expirationMode = :fems AND f.season ' . ($season === null ? 'IS NULL' : '= :season') . ') OR (f.expirationMode = :femt AND f.timestamp >= :now) OR (f.expirationMode = :femc AND f.townCount > 0) )')
            ->setParameter('femn', FeatureUnlock::FeatureExpirationNone)
            ->setParameter('fems', FeatureUnlock::FeatureExpirationSeason);
        if ($season !== null) $qb->setParameter('season', $season);

        return $qb->setParameter('femt', FeatureUnlock::FeatureExpirationTimestamp)->setParameter('now', new DateTime())
            ->setParameter('femc', FeatureUnlock::FeatureExpirationTownCount)
            ->orderBy('f.expirationMode', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findOneActiveForUser(User $user, ?Season $season, $feature): ?FeatureUnlock {
        $tmp = $this->findActiveForUser($user,$season,$feature);
        return !empty($tmp) ? $tmp[count($tmp)-1] : null;
    }

    // /**
    //  * @return FeatureUnlock[] Returns an array of FeatureUnlock objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FeatureUnlock
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
