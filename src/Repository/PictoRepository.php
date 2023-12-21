<?php

namespace App\Repository;

use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

/**
 * @method Picto|null find($id, $lockMode = null, $lockVersion = null)
 * @method Picto[]    findAll()
 * @method Picto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PictoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Picto::class);
    }

    /**
     * @param User $user
     * @return Picto[]
     */
    public function findByUser(User $user)
    {
        return $this->createQueryBuilder('i')
            ->select('SUM(i.count) as c', 'pp.id', 'pp.rare', 'pp.icon', 'pp.label', 'pp.description', 'pp.name')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->orderBy('pp.rare', 'DESC')
            ->addOrderBy('pp.priority', 'DESC')
            ->addOrderBy('c', 'DESC')
            ->addOrderBy('pp.id', 'DESC')
            ->leftJoin('i.prototype', 'pp')
            ->groupBy("i.prototype")
            ->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param Town|TownRankingProxy|null $town
     * @param PictoPrototype $prototype
     * @return Picto|null
     */
    public function findByUserAndTownAndPrototype(User $user, $town, PictoPrototype $prototype)
    {
        try {
            $qb = $this->createQueryBuilder('i')
                ->andWhere('i.user = :user')->setParameter('user', $user)
                ->andWhere('i.prototype =  :prototype')->setParameter('prototype', $prototype);
            if($town !== null){
                if ($town instanceof Town)
                    $qb->andWhere("i.town = :town");
                else
                    $qb->andWhere("i.townEntry = :town");
                $qb->setParameter('town', $town);
            } else {
                $qb->andWhere("i.town IS NULL");
            }
            return $qb->getQuery()
                        ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param User $user
     * @param Town|TownRankingProxy $town
     * @return Picto[]
     */
    public function findPendingByUserAndTown(User $user, $town)
    {
        $query = $this->createQueryBuilder('i')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere('i.persisted < 2');
        if(is_a($town, Town::class))
            $query->andWhere('i.town = :town')->setParameter('town', $town);
        else if (is_a($town, TownRankingProxy::class))
            $query->andWhere('i.townEntry = :town')->setParameter('town', $town);
        return $query->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param Town|TownRankingProxy $town
     * @return Picto[]
     */
    public function findNotPendingByUserAndTown(User $user, $town)
    {
        $query = $this->createQueryBuilder('i')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere('i.persisted = 2');
        if(is_a($town, Town::class))
            $query->andWhere('i.town = :town')->setParameter('town', $town);
        else if (is_a($town, TownRankingProxy::class))
            $query->andWhere('i.townEntry = :town')->setParameter('town', $town);
        return $query->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @return Picto[]
     */
    public function findNotPendingByUser(User $user, ?bool $imported = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('SUM(i.count) as c', 'pp.id', 'pp.rare', 'pp.icon', 'pp.label', 'pp.description', 'pp.name')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere('i.persisted = 2')
            ->andWhere('i.disabled = false')
            ->andWhere('i.old = false')
            ->orderBy('pp.rare', 'DESC')
            ->addOrderBy('c', 'DESC')
            ->addOrderBy('pp.id', 'DESC')
            ->leftJoin('i.prototype', 'pp')
            ->groupBy("i.prototype");

        if ($imported !== null)
            $qb->andWhere('i.imported = :imported')->setParameter('imported', $imported);

        return $qb
            ->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @return Picto[]
     */
    public function findOldByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('SUM(i.count) as c', 'pp.id', 'pp.rare', 'pp.icon', 'pp.label', 'pp.description', 'pp.name')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere('i.persisted = 2')
            ->andWhere('i.disabled = false')
            ->andWhere('i.old = true')
            ->andWhere('i.imported = false')
            ->orderBy('pp.rare', 'DESC')
            ->addOrderBy('pp.priority', 'DESC')
            ->addOrderBy('c', 'DESC')
            ->addOrderBy('pp.id', 'DESC')
            ->leftJoin('i.prototype', 'pp')
            ->groupBy("i.prototype");

        return $qb
            ->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param Town|TownRankingProxy|null $town
     * @return Picto[]
     */
    public function findPictoByUserAndTown(User $user, Town|TownRankingProxy|null $town): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere(($town instanceof Town) ? 'i.town = :town' : 'i.townEntry = :town')->setParameter('town', $town)
            ->addOrderBy('i.count', 'DESC')
            ->addOrderBy('i.prototype', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * @param PictoPrototype $prototype
     * @return int
     */
    public function countPicto(PictoPrototype $prototype): int {
        try {
            return (int)$this->createQueryBuilder('p')->select('sum(p.count)')
                ->andWhere('p.prototype = :prototype')->setParameter('prototype', $prototype)
                ->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }
}
