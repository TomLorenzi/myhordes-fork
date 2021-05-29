<?php

namespace App\Repository;

use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\Season;
use App\Entity\User;
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
        if (is_string($feature)) $feature = $this->getEntityManager()->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name', $feature]);
        if ($feature === null) return [];
        return $this->createQueryBuilder('f')
            ->orWhere('(f.expirationMode = :femn)')->setParameter('femn', FeatureUnlock::FeatureExpirationNone)
            ->orWhere('(f.expirationMode = :fems AND f.season = :season)')->setParameter('fems', FeatureUnlock::FeatureExpirationSeason)->setParameter('season', $season)
            ->orWhere('(f.expirationMode = :femt AND f.timestamp >= :now)')->setParameter('femt', FeatureUnlock::FeatureExpirationTimestamp)->setParameter('now', new \DateTime())
            ->orWhere('(f.expirationMode = :femc AND f.townCount > 0)')->setParameter('femc', FeatureUnlock::FeatureExpirationTownCount)
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
