<?php

namespace App\Repository;

use App\Entity\RegistrationLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @method RegistrationLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegistrationLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegistrationLog[]    findAll()
 * @method RegistrationLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistrationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistrationLog::class);
    }

    public function countRecentRegistrations(string $ip): int
    {
        try {
            return $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->andWhere('r.identifier = :md')->setParameter('md', md5($ip))
                ->andWhere('r.date > :cut')->setParameter('cut', new DateTime('-24hour'))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return -1;
        }
    }

    // /**
    //  * @return RegistrationLog[] Returns an array of RegistrationLog objects
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
    public function findOneBySomeField($value): ?RegistrationLog
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
