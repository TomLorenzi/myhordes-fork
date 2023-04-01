<?php

namespace App\Repository;

use App\Entity\UserSwapPivot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSwapPivot>
 *
 * @method UserSwapPivot|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSwapPivot|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSwapPivot[]    findAll()
 * @method UserSwapPivot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSwapPivotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSwapPivot::class);
    }

    public function save(UserSwapPivot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserSwapPivot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return UserSwapPivot[] Returns an array of UserSwapPivot objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserSwapPivot
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
