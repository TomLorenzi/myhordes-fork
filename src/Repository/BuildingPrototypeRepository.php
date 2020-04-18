<?php

namespace App\Repository;

use App\Entity\BuildingPrototype;
use App\Entity\Town;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method BuildingPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method BuildingPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method BuildingPrototype[]    findAll()
 * @method BuildingPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BuildingPrototypeRepository extends ServiceEntityRepository
{
    private $name_cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BuildingPrototype::class);
    }

    /**
     * @return BuildingPrototype[]
     */
    public function findRootBuildingPrototypes()
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.parent = :val')
            ->setParameter('val', null)
            ->orderBy('b.label', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findOneByName(string $value, bool $cache = true): ?BuildingPrototype
    {
        $local_cache = [];
        if ($cache) $local_cache = &$this->name_cache;
        try {
            return $local_cache[$value] ?? ($local_cache[$value] = $this->createQueryBuilder('i')
                ->andWhere('i.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult() ) ;
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param Town $town
     * @param int|null $bp_class
     * @return BuildingPrototype[]
     */
    public function findProspectivePrototypes( Town $town, ?int $bp_class = null ) {
        $include = [];
        foreach ($town->getBuildings() as $building)
            $include[$building->getPrototype()->getId()] = true;
        $include = array_keys($include);

        $qb = $this->createQueryBuilder('b');
        if ($bp_class !== null)
            $qb->andWhere('b.blueprint = :bp')->setParameter('bp', $bp_class);

        if (!empty($include))
            $qb->andWhere('b.id NOT IN (:existing)')->andWhere('b.parent IN (:existing) OR b.parent IS NULL')->setParameter('existing', $include);
        else $qb->andWhere('b.parent IS NULL');

        $qb->orderBy("b.parent", "ASC")->orderBy("b.orderBy", "ASC");

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return BuildingPrototype[] Returns an array of BuildingPrototype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BuildingPrototype
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
