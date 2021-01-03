<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserGroupAssociation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserGroupAssociation|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGroupAssociation|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGroupAssociation[]    findAll()
 * @method UserGroupAssociation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGroupAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGroupAssociation::class);
    }

    /**
     * @param User $user
     * @param null $association
     * @param array $skip
     * @param int $limit
     * @return int|mixed|string
     */
    public function findByUserAssociation( User $user, $association = null, array $skip = [], int $limit = 0 ) {
        $qb = $this->createQueryBuilder('u')->select('u')->leftJoin('u.association', 'g')
            ->andWhere('u.user = :user')->setParameter('user', $user)
            ->orderBy('g.ref2', 'DESC')->orderBy('u.id', 'DESC');

        if (is_array($association))
            $qb->andWhere('u.associationType IN (:assoc)')->setParameter('assoc', $association);
        elseif ($association !== null) $qb->andWhere('u.associationType = :assoc')->setParameter('assoc', $association);

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);
        if ($limit > 0) $qb->setMaxResults( $limit );

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return UserGroupAssociation[] Returns an array of UserGroupAssociation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserGroupAssociation
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
