<?php

namespace App\Repository;

use App\Entity\AntiSpamDomains;
use App\Enum\DomainBlacklistType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AntiSpamDomains|null find($id, $lockMode = null, $lockVersion = null)
 * @method AntiSpamDomains|null findOneBy(array $criteria, array $orderBy = null)
 * @method AntiSpamDomains[]    findAll()
 * @method AntiSpamDomains[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AntiSpamDomainsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AntiSpamDomains::class);
    }

    public function findActive(DomainBlacklistType $type, string $value): ?AntiSpamDomains
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')->setParameter('type', $type)
            ->andWhere('a.domain = :value')->setParameter('value', $type->convert($value));

        $qb->andWhere( $qb->expr()->orX(
            'a.until IS NULL',
            'a.until > :now',
        ) )->setParameter('now', new \DateTime());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param DomainBlacklistType $type
     * @return AntiSpamDomains[]
     */
    public function findAllActive(DomainBlacklistType $type): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')->setParameter('type', $type);

        $qb->andWhere( $qb->expr()->orX(
            'a.until IS NULL',
            'a.until > :now',
        ) )->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return AntiSpamDomains[] Returns an array of AntiSpamDomains objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AntiSpamDomains
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
