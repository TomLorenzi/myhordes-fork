<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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
     * @return int
     */
    public function countRecentRecipients( User $user ): int {
        $owned_groups = $this->createQueryBuilder('u')->select('u')->leftJoin('u.association', 'g')
            ->andWhere('u.user = :user')->setParameter('user', $user)
            ->andWhere('u.associationLevel = :founder')->setParameter('founder', UserGroupAssociation::GroupAssociationLevelFounder)
            ->andWhere('g.ref3 > :cutoff')->setParameter('cutoff', (new DateTime('-24hours'))->getTimestamp())
            ->getQuery()->getResult();


        return $this->count( ['association' => array_map(fn(UserGroupAssociation $a) => $a->getAssociation(), $owned_groups)] );
    }

    /**
     * @param User $user
     * @param null $association
     * @param array $skip
     * @param int $limit
     * @param bool $archive
     * @return int|mixed|string
     */
    public function findByUserAssociation( User $user, $association = null, array $skip = [], int $limit = 0, bool $archive = false, ?string $filter = null ) {
        $qb = $this->createQueryBuilder('u')->select('u')->leftJoin('u.association', 'g')
            ->andWhere('u.user = :user')->setParameter('user', $user)
            ->andWhere('u.bref = :arch')->setParameter('arch', $archive)
            ->orderBy('u.priority', 'DESC')
            ->addOrderBy('g.ref2', 'DESC')->addOrderBy('u.id', 'DESC');

        if ($filter !== null)
            $qb->andWhere('g.name LIKE :filter')->setParameter('filter', "%{$filter}%");

        if (is_array($association))
            $qb->andWhere('u.associationType IN (:assoc)')->setParameter('assoc', $association);
        elseif ($association !== null) $qb->andWhere('u.associationType = :assoc')->setParameter('assoc', $association);

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);
        if ($limit > 0) $qb->setMaxResults( $limit );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param DateTime|null $newer_then
     * @return UserGroupAssociation[]
     */
    public function getUnreadPMsByUser( User $user, ?DateTime $newer_then = null, ?bool $archive = false) {
        $qb = $this->createQueryBuilder('u')->select('u')->leftJoin('u.association', 'g')
            ->andWhere('u.user = :user')->setParameter('user', $user)
            ->andWhere('u.ref1 < g.ref1 OR u.ref1 IS NULL')
            ->andWhere('u.bref = :arch')->setParameter('arch', $archive)
            ->orderBy('g.ref2', 'DESC')
            ->andWhere('u.associationType IN (:assoc)')->setParameter('assoc', [UserGroupAssociation::GroupAssociationTypePrivateMessageMember,UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember]);

        if ($newer_then !== null) $qb->andWhere('g.ref2 > :time')->setParameter('time', $newer_then->getTimestamp());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param bool $include_pm
     * @param bool $include_groups
     * @return int
     */
    public function countUnreadPMsByUser( User $user, bool $include_pm = true, bool $include_groups = true ): int {
        $filter = [];
        if ($include_pm) $filter[] = UserGroupAssociation::GroupAssociationTypePrivateMessageMember;
        if ($include_groups) $filter[] = UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember;

        if (empty($filter)) return 0;

        $qb = $this->createQueryBuilder('u')->select('COUNT(u.id)')->leftJoin('u.association', 'g')
            ->andWhere('u.user = :user')->setParameter('user', $user)
            ->andWhere('u.ref1 < g.ref1 OR u.ref1 IS NULL')
            ->andWhere('u.bref = false')
            ->andWhere('u.associationType IN (:assoc)')->setParameter('assoc', $filter);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (Exception $e) { return 0; }
    }

    /**
     * @param User $user
     * @return int|mixed|string
     */
    public function countUnreadInactivePMsByUser( User $user ): int {
        $qb = $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.user = :user')->setParameter('user', $user)
            ->andWhere('u.ref1 < u.ref3 OR u.ref1 IS NULL')
            ->andWhere('u.bref = false')
            ->andWhere('u.associationType = :assoc')->setParameter('assoc', UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (Exception $e) { return 0; }
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
