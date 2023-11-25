<?php

namespace App\Repository;

use App\Entity\Town;
use App\Entity\Zone;
use App\Entity\Citizen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Zone|null find($id, $lockMode = null, $lockVersion = null)
 * @method Zone|null findOneBy(array $criteria, array $orderBy = null)
 * @method Zone[]    findAll()
 * @method Zone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    /**
     * @param Town $town
     * @return Zone[]
     */
    public function findByTown(Town $town)
    {
        try {
            return $this->createQueryBuilder('z')
                ->andWhere('z.town = :t')->setParameter('t', $town)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function findOneByPosition(Town $town, int $x, int $y): ?Zone
    {
        try {
            return $this->createQueryBuilder('z')
                ->andWhere('z.town = :t')->setParameter('t', $town)
                ->andWhere('z.x = :px')->setParameter('px', $x)
                ->andWhere('z.y = :py')->setParameter('py', $y)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findPreviousCampersCount(Citizen $citizen): ?int {
        if(!$citizen->getTown()) { return 0; }
        if(!$citizen->getZone()) { return 0; }

        try {
            $query = $this->createQueryBuilder('z')
                ->select('count(c.id) as count')
                ->innerJoin("z.citizens", "c")
                ->andWhere('z.town = :t')->setParameter('t', $citizen->getTown())
                ->andWhere('z.x = :px')->setParameter('px', $citizen->getZone()->getX())
                ->andWhere('z.y = :py')->setParameter('py', $citizen->getZone()->getY())
                ->andWhere("c.campingTimestamp > 0");

            // Get hidden before citizen if he's already hidden
            if($citizen->getCampingTimestamp() > 0) {
                $query = $query
                    ->andWhere("c.campingTimestamp < :current")
                    ->setParameter("current", $citizen->getCampingTimestamp());
            }

            return $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return Zone[] Returns an array of Zone objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('z.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Zone
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
