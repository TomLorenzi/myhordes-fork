<?php

namespace App\Repository;

use App\Entity\CommunityEventMeta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityEventMeta>
 *
 * @method CommunityEventMeta|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommunityEventMeta|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommunityEventMeta[]    findAll()
 * @method CommunityEventMeta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommunityEventMetaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityEventMeta::class);
    }

    public function save(CommunityEventMeta $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CommunityEventMeta $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CommunityEventMeta[] Returns an array of CommunityEventMeta objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CommunityEventMeta
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
