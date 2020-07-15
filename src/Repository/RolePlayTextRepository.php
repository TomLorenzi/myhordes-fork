<?php

namespace App\Repository;

use App\Entity\RolePlayText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RolePlayText|null find($id, $lockMode = null, $lockVersion = null)
 * @method RolePlayText|null findOneBy(array $criteria, array $orderBy = null)
 * @method RolePlayText[]    findAll()
 * @method RolePlayText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RolePlayTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RolePlayText::class);
    }

    public function findOneByName(string $value): ?RolePlayText
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

    /**
     * @param string $lang
     * @return RolePlayText[] Returns an array of RolePlayText objects
     */
    public function findAllByLang(string $lang)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.language = :val')
            ->setParameter('val', $lang)
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return RolePlayText[] Returns an array of RolePlayText objects
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
    public function findOneBySomeField($value): ?RolePlayText
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
