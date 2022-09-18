<?php

namespace App\Repository;

use App\Entity\ZoneActivityMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZoneActivityMarker>
 *
 * @method ZoneActivityMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method ZoneActivityMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method ZoneActivityMarker[]    findAll()
 * @method ZoneActivityMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZoneActivityMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZoneActivityMarker::class);
    }

    public function add(ZoneActivityMarker $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ZoneActivityMarker $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ZoneActivityMarker[] Returns an array of ZoneActivityMarker objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('z')
//            ->andWhere('z.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('z.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ZoneActivityMarker
//    {
//        return $this->createQueryBuilder('z')
//            ->andWhere('z.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
