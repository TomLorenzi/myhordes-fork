<?php

namespace App\Repository;

use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
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
     * @return Picto[]
     */
    public function findPendingByUser(User $user)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere('i.persisted < 2')
            ->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @return Picto[]
     */
    public function findNotPendingByUser(User $user)
    {
        return $this->createQueryBuilder('i')
            ->select('SUM(i.count) as c', 'pp.id', 'pp.rare', 'pp.icon', 'pp.label', 'pp.description', 'pp.name')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere('i.persisted = 2')
            ->orderBy('pp.rare', 'DESC')
            ->addOrderBy('c', 'DESC')
            ->addOrderBy('pp.id', 'DESC')
            ->leftJoin('i.prototype', 'pp')
            ->groupBy("i.prototype")
            ->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param Town|TownRankingProxy|null $town
     * @return Picto[]
     */
    public function findPictoByUserAndTown(User $user, $town)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :val')->setParameter('val', $user)
            ->andWhere(($town !== null && $town instanceof Town) ? 'i.town = :town' : 'i.townEntry = :town')->setParameter('town', $town)
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

    public function getAllByUserAndPicto(User $user, PictoPrototype $proto) {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :valUser')
            ->setParameter('valUser', $user)
            ->andWhere('a.prototype = :valProto')
            ->setParameter('valProto', $proto)
            ->andWhere('a.persisted = 2')
            ->getQuery()
            ->getResult();
    }
}
