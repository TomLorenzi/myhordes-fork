<?php

namespace App\Repository;

use App\Entity\MarketingCampaignConversion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketingCampaignConversion>
 *
 * @method MarketingCampaignConversion|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarketingCampaignConversion|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarketingCampaignConversion[]    findAll()
 * @method MarketingCampaignConversion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarketingCampaignConversionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketingCampaignConversion::class);
    }

    public function save(MarketingCampaignConversion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MarketingCampaignConversion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MarketingCampaignConversion[] Returns an array of MarketingCampaignConversion objects
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

//    public function findOneBySomeField($value): ?MarketingCampaignConversion
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
