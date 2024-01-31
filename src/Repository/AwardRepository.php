<?php


namespace App\Repository;


use App\Entity\Award;
use App\Entity\AwardPrototype;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Award|null find($id, $lockMode = null, $lockVersion = null)
 * @method Award|null findOneBy(array $criteria, array $orderBy = null)
 * @method Award[]    findAll()
 * @method Award[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AwardRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Award::class);
    }

    public function getAwardsByUser(User $user) {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :val')
            ->setParameter('val', $user)
            ->getQuery()
            ->getResult();
    }

    public function hasAward(User $user, AwardPrototype $award): ?bool {
        try {
            return ($this->createQueryBuilder('a')
                    ->andWhere('a.user = :valUser')
                    ->setParameter('valUser', $user)
                    ->andWhere('a.prototype = :valProto')
                    ->setParameter('valProto', $award)
                    ->getQuery()
                    ->getOneOrNullResult() != null);
        } catch (NonUniqueResultException $e) {
            return true;
        }
    }
}