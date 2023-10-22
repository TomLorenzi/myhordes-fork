<?php

namespace App\Repository;

use App\Entity\CitizenRankingProxy;
use App\Entity\Season;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method CitizenRankingProxy|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenRankingProxy|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenRankingProxy[]    findAll()
 * @method CitizenRankingProxy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenRankingProxyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenRankingProxy::class);
    }

    public function findAllUnconfirmedDeath(User $user): ?array
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.confirmed = false')->andWhere('c.end is not NULL')
                ->andWhere('c.user = :user')->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        } catch (Exception $e) {
            return null;
        }
    }

    public function findNextUnconfirmedDeath(User $user): ?CitizenRankingProxy
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.confirmed = false')->andWhere('c.end is not NULL')
                ->andWhere('c.user = :user')->setParameter('user', $user)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (Exception $e) {
            return null;
        }
    }

    public function getNonAlphaTowns(User $user, ?\DateTime $from = null, $skip_redacted = false) {
        try {
            $qb = $this->createQueryBuilder('c')
                ->join('c.town', 't')
                ->andWhere('c.user = :user')->setParameter('user', $user)
                ->andWhere('t.season IS NOT NULL')
                ->andWhere('t.imported = false');

            if ( $from !== null )
                $qb->andWhere('c.end IS NOT NULL AND c.end >= :from')->setParameter('from', $from);

            if ($skip_redacted)
                $qb->andWhere('c.disabled = false')->andWhere('t.disabled = false');

            return $qb->getQuery()->getResult();
        } catch (Exception $e) {
            return [];
        }
    }

    public function countNonAlphaTowns(User $user, ?\DateTime $from = null, $skip_redacted = false): int {
        try {
            $qb = $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->join('c.town', 't')
                ->andWhere('c.user = :user')->setParameter('user', $user)
                ->andWhere('t.season IS NOT NULL')
                ->andWhere('t.imported = false');

            if ( $from !== null )
                $qb->andWhere('c.end IS NOT NULL AND c.end >= :from')->setParameter('from', $from);

            if ($skip_redacted)
                $qb->andWhere('c.disabled = false')->andWhere('t.disabled = false')->andWhere('c.disableFlag = 0');

            return $qb->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function findAllByUserAndSeason(User $user, ?Season $season, $limit10) {
        $query = $this->createQueryBuilder('c')
            ->join('c.town', 't')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('c.day', 'DESC')
            ->addOrderBy('c.id', 'DESC');

        if($season !== null)
            $query->andWhere('t.season = :season')
            ->setParameter('season', $season);
        else
            $query->andWhere('t.season IS NULL');

        if($limit10)
            $query->setMaxResults(10);

        return $query->getQuery()
            ->getResult()
        ;
    }

    public function findPastByUserAndSeason(User $user, ?Season $season, $limit10, $show_hidden = false) {
        $query = $this->createQueryBuilder('c')
            ->join('c.town', 't')
            ->andWhere('c.user = :user')
            ->andWhere('(c.end IS NOT NULL OR c.confirmed = true)')
            ->setParameter('user', $user)
            ->addOrderBy('c.day', 'DESC')
            ->addOrderBy('c.id', 'DESC');

        $disableTown = TownRankingProxy::DISABLE_ALL;
        $disableCitizen = CitizenRankingProxy::DISABLE_ALL;

        if (!$show_hidden)
            $query->andWhere("(BIT_AND(t.disableFlag, $disableTown) != $disableTown) AND (BIT_AND(c.disableFlag, $disableCitizen) != $disableCitizen)");

        if($season !== null)
            $query->andWhere('t.season = :season')
            ->setParameter('season', $season);
        else
            $query->andWhere('t.season IS NULL');

        if($limit10)
            $query->setMaxResults(10);

        return $query->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return CitizenRankingProxy[] Returns an array of CitizenRankingProxy objects
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
    public function findOneBySomeField($value): ?CitizenRankingProxy
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
