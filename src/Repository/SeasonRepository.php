<?php

namespace App\Repository;

use App\Entity\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Season|null find($id, $lockMode = null, $lockVersion = null)
 * @method Season|null findOneBy(array $criteria, array $orderBy = null)
 * @method Season[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    public function findAll(): array
    {
        return $this->findBy([],['number' => 'DESC','subNumber' => 'DESC']);
    }

    public function findPastAndPresent(): array
    {
        $currentSeason = $this->findOneBy(['current' => true]);
        if (!$currentSeason) return $this->findAll();

        $qb = $this->createQueryBuilder( 's' );
        $or = $qb->expr()->orX();

        $and = $qb->expr()->andX();
        $and->add( $qb->expr()->eq('s.number', $currentSeason->getNumber()) );
        $and->add( $qb->expr()->lte('s.subNumber', $currentSeason->getSubNumber()) );

        $or->add($qb->expr()->lt('s.number', $currentSeason->getNumber()));
        $or->add($and);

        return $qb
            ->where( $or )
            ->addOrderBy('s.number', 'DESC')
            ->addOrderBy('s.subNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatest(){
        return $this->createQueryBuilder('s')
            ->addOrderBy('s.number', 'DESC')
            ->addOrderBy('s.subNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNext(?Season $season, $include_beta = false): ?Season
    {
        $qb = $this->createQueryBuilder('s')
            ->addOrderBy('s.number', 'ASC')
            ->addOrderBy('s.subNumber', 'ASC')
            ->setMaxResults(1);

        $and = $qb->expr()->andX();
        if (!$include_beta) $and->add( $qb->expr()->gte('s.number', 1) );
        if ($season) {
            $sub_and = $qb->expr()->andX();
            $sub_and->add( $qb->expr()->eq('s.number', $season->getNumber()) );
            $sub_and->add( $qb->expr()->gt('s.subNumber', $season->getSubNumber()) );

            $or = $qb->expr()->orX();
            $or->add( $qb->expr()->gt('s.number', $season->getNumber()) );
            $or->add( $sub_and );

            $and->add( $or );
        }

        return $qb
            ->where( $and )
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return Season[] Returns an array of Season objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Season
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
