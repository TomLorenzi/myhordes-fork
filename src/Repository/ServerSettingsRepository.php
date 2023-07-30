<?php

namespace App\Repository;

use App\Entity\ServerSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServerSettings>
 *
 * @method ServerSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServerSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServerSettings[]    findAll()
 * @method ServerSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServerSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerSettings::class);
    }

//    /**
//     * @return ServerSettings[] Returns an array of ServerSettings objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ServerSettings
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
