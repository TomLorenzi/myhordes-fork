<?php

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Forum|null find($id, $lockMode = null, $lockVersion = null)
 * @method Forum|null findOneBy(array $criteria, array $orderBy = null)
 * @method Forum[]    findAll()
 * @method Forum[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forum::class);
    }

    public function findForumsForUser(User $user, ?int $id = null)
    {
        $towns = [];
        foreach ($user->getCitizens() as $citizen) if ($citizen->getActive() && $citizen->getAlive()) $towns[] = $citizen->getTown();
        $q = $this->createQueryBuilder('f')
            ->andWhere('f.town IN (:towns) OR f.town IS NULL')->setParameter('towns', $towns)
            ->orderBy('f.town', 'DESC')
            ->addOrderBy('f.id', 'ASC');
        if ($id !== null)
            $q->andWhere('f.id = :id')->setParameter('id', $id);

        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Forum[] Returns an array of Forum objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Forum
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
