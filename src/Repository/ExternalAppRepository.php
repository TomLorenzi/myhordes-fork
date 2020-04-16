<?php

namespace App\Repository;

use App\Entity\ExternalApp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ExternalApp|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExternalApp|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalApp[]    findAll()
 * @method ExternalApp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalAppRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalApp::class);
    }
}
