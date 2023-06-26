<?php

namespace App\Repository;

use App\Entity\HeaderStat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HeaderStat|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeaderStat|null findFirst()
 * @method HeaderStat|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeaderStat[]    findAll()
 * @method HeaderStat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeaderStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeaderStat::class);
    }

}
