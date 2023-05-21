<?php

namespace App\Repository;

use App\Entity\CommunityEventTownPreset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityEventTownPreset>
 *
 * @method CommunityEventTownPreset|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommunityEventTownPreset|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommunityEventTownPreset[]    findAll()
 * @method CommunityEventTownPreset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommunityEventTownPresetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityEventTownPreset::class);
    }

    public function save(CommunityEventTownPreset $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CommunityEventTownPreset $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CommunityEventTownPreset[] Returns an array of CommunityEventTownPreset objects
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

//    public function findOneBySomeField($value): ?CommunityEventTownPreset
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
