<?php

namespace App\Repository;

use App\Entity\Town;
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
     * @param Town $town
     * @return RuinZone[]
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

    public function findOneByPosition(Town $town, int $x, int $y): ?RuinZone
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
