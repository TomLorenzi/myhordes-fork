<?php

namespace App\Repository;

use App\Entity\CitizenEscortSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method CitizenEscortSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenEscortSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenEscortSettings[]    findAll()
 * @method CitizenEscortSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenEscortSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenEscortSettings::class);
    }
}
