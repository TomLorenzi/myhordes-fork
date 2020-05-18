<?php

namespace App\Repository;

use App\Entity\Zone;
use App\Entity\RuinZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RuinZone|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuinZone|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuinZone[]    findAll()
 * @method RuinZone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuinZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuinZone::class);
    }

    /**
     * @param Zone $zone
     * @return RuinZone[]
     */
    public function findByZone(Zone $zone)
    {
        try {
            return $this->createQueryBuilder('rz')
                ->andWhere('rz.zone = :z')->setParameter('z', $zone)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function findOneByPosition(Zone $zone, int $x, int $y): ?RuinZone
    {
        try {
            return $this->createQueryBuilder('rz')
                ->andWhere('rz.zone = :z')->setParameter('z', $zone)
                ->andWhere('rz.x = :px')->setParameter('px', $x)
                ->andWhere('rz.y = :py')->setParameter('py', $y)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return RuinZone[] Returns an array of RuinZone objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('rz')
            ->andWhere('rz.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('rz.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Zone
    {
        return $this->createQueryBuilder('rz')
            ->andWhere('rz.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
