<?php

namespace App\Repository;

use App\Entity\Changelog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Changelog|null find($id, $lockMode = null, $lockVersion = null)
 * @method Changelog|null findOneBy(array $criteria, array $orderBy = null)
 * @method Changelog[]    findAll()
 * @method Changelog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChangelogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Changelog::class);
    }

    public function findAll(){
        try {
            return $this->createQueryBuilder('c')
                ->orderBy('c.version', 'DESC')
                ->orderBy('c.lang')
                ->getQuery()
                ->getResult();
        } catch (Exception $e) {
            return null;
        }
    }

    public function findByLang($lang){
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.lang = :lang')
                ->setParameter('lang', $lang)
                ->orderBy('c.version', 'DESC')
                ->getQuery()
                ->getResult();
        } catch (Exception $e) {
            return null;
        }
    }

    // /**
    //  * @return Changelog[] Returns an array of Changelog objects
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
    public function findOneBySomeField($value): ?Changelog
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
