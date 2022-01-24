<?php

namespace App\Repository;

use App\Entity\CitizenProfession;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CitizenProfession|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenProfession|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenProfession[]    findAll()
 * @method CitizenProfession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenProfessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenProfession::class);
    }

    public function findDefault(): ?CitizenProfession {
        return $this->findOneByName(CitizenProfession::DEFAULT);
    }

    /**
     * @return CitizenProfession[]
     */
    public function findSelectable(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name != :val')
            ->setParameter('val', CitizenProfession::DEFAULT)
            ->getQuery()
            ->getResult();
    }

    public function findOneByName(string $value): ?CitizenProfession
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return CitizenProfession[] Returns an array of CitizenProfession objects
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
    public function findOneBySomeField($value): ?CitizenProfession
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
