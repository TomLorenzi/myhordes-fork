<?php


namespace App\Repository;

use App\Entity\AwardPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AwardPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method AwardPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method AwardPrototype[]    findAll()
 * @method AwardPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AwardPrototypeRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, AwardPrototype::class);
    }

    public function getAwardsByPicto(string $value) {
        return $this->createQueryBuilder('a')
            ->andWhere('a.associatedPicto = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
    }

    public function getIndividualAward(string $picto, int $amount): ?AwardPrototype {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.associatedPicto = :picto')
                ->setParameter('picto', $picto)
                ->andWhere('a.unlockQuantity = :amount')
                ->setParameter('amount', $amount)
                ->getQuery()
                ->getOneOrNullResult();
        } catch(NonUniqueResultException $e) {
            return null;
        }
    }

    public function getAwardByTitle(string $value): ?AwardPrototype {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.title = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch(NonUniqueResultException $e) {
            return null;
        }
    }

    public function getAwardByIcon(string $value): ?AwardPrototype {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.icon = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch(NonUniqueResultException $e) {
            return null;
        }
    }
}