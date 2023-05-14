<?php

namespace App\Repository;

use App\Entity\TownAspectRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TownAspectRevision>
 *
 * @method TownAspectRevision|null find($id, $lockMode = null, $lockVersion = null)
 * @method TownAspectRevision|null findOneBy(array $criteria, array $orderBy = null)
 * @method TownAspectRevision[]    findAll()
 * @method TownAspectRevision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TownAspectRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TownAspectRevision::class);
    }

    public function save(TownAspectRevision $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TownAspectRevision $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return TownAspectRevision[] Returns an array of TownAspectRevision objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TownAspectRevision
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
