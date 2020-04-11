<?php

namespace App\Repository;

use App\Entity\RequireZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RequireZone|null find($id, $lockMode = null, $lockVersion = null)
 * @method RequireZone|null findOneBy(array $criteria, array $orderBy = null)
 * @method RequireZone[]    findAll()
 * @method RequireZone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequireZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequireZone::class);
    }

    public function findOneByName(string $value): ?RequireZone
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
