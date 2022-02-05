<?php

namespace App\Repository;

use App\Entity\RequireZombiePresence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RequireZombiePresence|null find($id, $lockMode = null, $lockVersion = null)
 * @method RequireZombiePresence|null findOneBy(array $criteria, array $orderBy = null)
 * @method RequireZombiePresence[]    findAll()
 * @method RequireZombiePresence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequireZombiePresenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequireZombiePresence::class);
    }

    public function findOneByName(string $value): ?RequireZombiePresence
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return RequireZombiePresence[] Returns an array of RequireZombiePresence objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RequireZombiePresence
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
