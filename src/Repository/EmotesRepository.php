<?php


namespace App\Repository;

use App\Entity\Emotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Emotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Emotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Emotes[]    findAll()
 * @method Emotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmotesRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Emotes::class);
    }

    public function findByTag(string $value): ?Emotes {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.tag = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function getDefaultEmotes() {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isActive = true')
            ->andWhere('a.requiresUnlock = false')
            ->getQuery()
            ->getResult();
    }

}
