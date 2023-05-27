<?php

namespace App\Repository;

use App\Entity\MarketingCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketingCampaign>
 *
 * @method MarketingCampaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarketingCampaign|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarketingCampaign[]    findAll()
 * @method MarketingCampaign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarketingCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketingCampaign::class);
    }

    public function save(MarketingCampaign $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MarketingCampaign $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MarketingCampaign[] Returns an array of MarketingCampaign objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MarketingCampaign
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
