<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\ScoutVisit;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method ScoutVisit|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScoutVisit|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScoutVisit[]    findAll()
 * @method ScoutVisit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScoutVisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScoutVisit::class);
    }

    public function findByCitizenAndZone(Citizen $c, Zone $z): ?ScoutVisit
    {
        if (!$c->getZone()) return null;
        try {
            return $this->createQueryBuilder('d')
                ->andWhere('d.scout = :ctz')->setParameter('ctz', $c)
                ->andWhere('d.zone = :zne')->setParameter('zne', $z)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return ScoutVisit[] Returns an array of ScoutVisit objects
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
    public function findOneBySomeField($value): ?ScoutVisit
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
