<?php

namespace App\Repository;

use App\Entity\CitizenRankingProxy;
use App\Entity\RememberMeTokens;
use App\Entity\Season;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Enum\UserAccountType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // /**
    //  * @return User[] Returns an array of User objects
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

    /**
     * @param int $level
     * @return User[] Returns an array of User objects
     */
    public function findByLeastElevationLevel(int $level)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.rightsElevation >= :val')->setParameter('val', $level)
            ->getQuery()->getResult();
    }

    public function findOneByName(string $value): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->andWhere('u.name = :val')->setParameter('val', $value)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) { return null; }
    }

    /**
     * @param string $value
     * @param bool|UserAccountType[] $filter_special_users
     * @param bool $take_first
     * @return User|null
     */
    public function findOneByNameOrDisplayName(string $value, bool|array $filter_special_users = true, bool $take_first = false): ?User
    {
        try {
            $qb = $this->createQueryBuilder('u')
                ->andWhere('u.name = :val OR u.displayName = :val')->setParameter('val', $value);

            if ($filter_special_users === true) $filter_special_users = UserAccountType::filterable();
            elseif ($filter_special_users === false) $filter_special_users = [];

            foreach ($filter_special_users as $filter)
                if ($filter->canBeFiltered())
                    $filter->applyFilter( $qb );

            return $qb->orderBy('u.id','ASC')
                ->setMaxResults( $take_first ? 1 : null )
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) { return null; }
    }

    /**
     * @param string $value
     * @param bool $filter_special_users
     * @return int
     */
    public function countByNameOrDisplayName(string $value, bool|array $filter_special_users = true): int
    {
        try {
            $qb = $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')->andWhere('u.name = :val OR u.displayName = :val')->setParameter('val', $value);

            if ($filter_special_users === true) $filter_special_users = UserAccountType::filterable();
            elseif ($filter_special_users === false) $filter_special_users = [];

            foreach ($filter_special_users as $filter)
                if ($filter->canBeFiltered())
                    $filter->applyFilter( $qb );

            return $qb->getQuery()->getSingleScalarResult();

        } catch (\Throwable $t) { return 0; }
    }

    /**
     * @param string $value
     * @return User[] Returns an array of User objects
     */
    public function findByNameContains(string $value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.name LIKE :val OR u.displayName LIKE :val')->setParameter('val', '%' . $value . '%')
            ->getQuery()->getResult();
    }

    /**
     * @param string $name
     * @param int $limit
     * @param array $skip
     * @param bool|UserAccountType[] $filter_special_users
     * @return User[] Returns an array of User objects
     */
    public function findBySoulSearchQuery(string $name, int $limit = 10, array $skip = [], bool|array $filter_special_users = true): array
    {
        $skip = array_filter(array_map( fn($u) => is_a($u, User::class) ? $u->getId() : $u, $skip));

        if ($filter_special_users === true) $filter_special_users = UserAccountType::filterable();
        elseif ($filter_special_users === false) $filter_special_users = [];

        $name = str_replace(['\\','%', '_'],['\\\\', '\\%','\\_'],$name);
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.name LIKE :val OR u.displayName LIKE :val')->setParameter('val', "%{$name}%");

        foreach ($filter_special_users as $filter)
            if ($filter->canBeFiltered())
                $filter->applyFilter( $qb );

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);
        if ($limit > 0) $qb->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        usort($results, function( User $a, User $b ) use ($name) {
           $a_fits = str_starts_with( mb_strtolower($a->getName()), mb_strtolower( $name ) );
           $b_fits = str_starts_with( mb_strtolower($b->getName()), mb_strtolower( $name ) );

           if ($a_fits && !$b_fits) return -1;
           elseif ($b_fits && !$a_fits) return 1;

            return levenshtein( mb_strtolower($a->getName()), mb_strtolower( $name ) ) - levenshtein( mb_strtolower($b->getName()), mb_strtolower( $name ) );
        });

        return $results;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param bool $myhordes_only
     * @param array $skip
     * @return Collection|User[] Returns an array of User objects
     */
    public function getGlobalSoulRankingPage(int $offset = 0, int $limit = 10, bool $myhordes_only = false, array $skip = [])
    {
        $skip = array_filter(array_map( fn($u) => is_a($u, User::class) ? $u->getId() : $u, $skip));

        $all_points = $myhordes_only ? '(u.soulPoints)' : '(u.soulPoints + COALESCE(u.importedSoulPoints, 0))';

        $qb = $this->createQueryBuilder('u')
            ->select("$all_points as allPoints", 'u')
            ->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow')
            ->andWhere('u.email NOT LIKE :anim')->setParameter('anim', 'anim')
            ->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost")
            ->andWhere('u.email != u.name')
            ->andWhere("$all_points > 0")
            ->orderBy('allPoints', 'DESC')->addOrderBy('u.id', 'DESC');;

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);
        if ($limit > 0) $qb->setMaxResults($limit);
        if ($offset > 0) $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    public function getGlobalSoulRankingUserStats( User $user, bool $myhordes_only, ?int &$sp, ?int &$place ) {
        $sp = $myhordes_only ? ($user->getSoulPoints() ?? 0) : $user->getAllSoulPoints();

        if ($sp <= 0) {
            $place = -1;
            return;
        }

        $all_points = $myhordes_only ? '(u.soulPoints)' : '(u.soulPoints + COALESCE(u.importedSoulPoints, 0))';

        try {
            $place = $this->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow')
                    ->andWhere('u.email NOT LIKE :anim')->setParameter('anim', 'anim')
                    ->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost")
                    ->andWhere('u.email != u.name')
                    ->andWhere("$all_points > :own OR ($all_points = :own AND u.id > :uid)")->setParameter('own', max($sp,0))->setParameter('uid', $user->getId())
                    ->getQuery()->getSingleScalarResult() + 1;
        } catch (Exception $e) {
            $place = -1;
        }
    }

    /**
     * @param bool $myhordes_only
     * @param array $skip
     * @return int
     */
    public function countGlobalSoulRankings(bool $myhordes_only = false, array $skip = []): int
    {
        $skip = array_filter(array_map( fn($u) => is_a($u, User::class) ? $u->getId() : $u, $skip));

        $all_points = $myhordes_only ? '(u.soulPoints)' : '(u.soulPoints + COALESCE(u.importedSoulPoints, 0))';

        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow')
            ->andWhere('u.email NOT LIKE :anim')->setParameter('anim', 'anim')
            ->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost")
            ->andWhere('u.email != u.name')
            ->andWhere("$all_points > 0");

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param Season|null $season
     * @param array $skip
     * @return Collection|User[] Returns an array of User objects
     */
    public function getSeasonSoulRankingPage(int $offset = 0, int $limit = 10, ?Season $season = null, array $skip = [])
    {
        $skip = array_filter(array_map( fn($u) => is_a($u, User::class) ? $u->getId() : $u, $skip));

        $qb = $this->createQueryBuilder('u')
            ->select("SUM(r.points) as allPoints", 'u')
            ->innerJoin(CitizenRankingProxy::class, 'r', Join::WITH, 'u.id = r.user')
            ->innerJoin(TownRankingProxy::class, 't', Join::WITH, 'r.town = t.id')
            ->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow')
            ->andWhere('u.email NOT LIKE :anim')->setParameter('anim', 'anim')
            ->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost")
            ->andWhere('u.email != u.name')
            ->andWhere('r.points > 0')->andWhere( 'r.disabled = :false' )->andWhere( 'r.confirmed = :true' )
            ->andWhere( 't.disabled = :false' )->andWhere( 't.event = :false' )
            ->andWhere( 't.imported = :false' )
            ->groupBy('u.id')
            ->andHaving("SUM(r.points) > 0")
            ->setParameter('false', false)->setParameter('true', true)
            ->orderBy('allPoints', 'DESC')->addOrderBy('u.id', 'DESC');

        if ($season === null) $qb->andWhere('t.season IS NULL');
        else $qb->andWhere('t.season = :season')->setParameter('season', $season);

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);
        if ($limit > 0) $qb->setMaxResults($limit);
        if ($offset > 0) $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    public function getSeasonSoulRankingUserStats( User $user, ?Season $season, ?int &$sp, ?int &$place ) {
        try {
            $qb = $this->createQueryBuilder('u')
                ->select("SUM(r.points) as allPoints")
                ->innerJoin(CitizenRankingProxy::class, 'r', Join::WITH, 'u.id = r.user')
                ->innerJoin(TownRankingProxy::class, 't', Join::WITH, 'r.town = t.id')
                ->andWhere('u.id = :uid')->setParameter('uid', $user->getId())
                ->andWhere('r.points > 0')->andWhere( 'r.disabled = :false' )->andWhere( 'r.confirmed = :true' )
                ->andWhere( 't.disabled = :false' )->andWhere( 't.event = :false' )
                ->andWhere( 't.imported = :false' )
                ->setParameter('false', false)->setParameter('true', true);

            if ($season === null) $qb->andWhere('t.season IS NULL');
            else $qb->andWhere('t.season = :season')->setParameter('season', $season);

            $sp = $qb->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            $sp = 0;
        }

        if ($sp <= 0) {
            $place = -1;
            return;
        }

        $qb = $this->createQueryBuilder('u')
            ->select('u.id')
            ->innerJoin(CitizenRankingProxy::class, 'r', Join::WITH, 'u.id = r.user')
            ->innerJoin(TownRankingProxy::class, 't', Join::WITH, 'r.town = t.id')
            ->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow')
            ->andWhere('u.email NOT LIKE :anim')->setParameter('anim', 'anim')
            ->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost")
            ->andWhere('u.email != u.name')
            ->andWhere('r.points > 0')->andWhere( 'r.disabled = :false' )->andWhere( 'r.confirmed = :true' )
            ->andWhere( 't.disabled = :false' )->andWhere( 't.event = :false' )
            ->andWhere( 't.imported = :false' )
            ->groupBy('u.id')
            ->andHaving("SUM(r.points) > :own OR (SUM(r.points) = :own AND u.id > :uid)")->setParameter('own', max($sp,0))->setParameter('uid', $user->getId())
            ->setParameter('false', false)->setParameter('true', true);

        if ($season === null) $qb->andWhere('t.season IS NULL');
        else $qb->andWhere('t.season = :season')->setParameter('season', $season);

        try {
            $place = count($qb->getQuery()->getScalarResult()) + 1;
        } catch (Exception $e) {
            $place = -1;
        }

    }

    /**
     * @param Season|null $season
     * @param array $skip
     * @return int
     */
    public function countSeasonSoulRankings(?Season $season = null, array $skip = []): int
    {
        $skip = array_filter(array_map( fn($u) => is_a($u, User::class) ? $u->getId() : $u, $skip));

        $qb = $this->createQueryBuilder('u')
            ->select('u.id')
            ->innerJoin(CitizenRankingProxy::class, 'r', Join::WITH, 'u.id = r.user')
            ->innerJoin(TownRankingProxy::class, 't', Join::WITH, 'r.town = t.id')
            ->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow')
            ->andWhere('u.email NOT LIKE :anim')->setParameter('anim', 'anim')
            ->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost")
            ->andWhere('u.email != u.name')->andWhere('r.points > 0')
            ->andWhere( 'r.disabled = :false' )->andWhere( 'r.confirmed = :true' )
            ->andWhere( 't.disabled = :false' )->andWhere( 't.event = :false' )
            ->andWhere( 't.imported = :false' )
            ->groupBy('u.id')
            ->andHaving("SUM(r.points) > 0")
            ->setParameter('false', false)->setParameter('true', true);

        if ($season === null) $qb->andWhere('t.season IS NULL');
        else $qb->andWhere('t.season = :season')->setParameter('season', $season);

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);

        //try {
            return count($qb->getQuery()->getResult());
        //} catch (Exception $e) {
        //    return 0;
        //}
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findByDisplayNameContains(string $value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.displayName LIKE :val')->setParameter('val', '%' . $value . '%')
            ->getQuery()->getResult();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findByMailContains(string $value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email LIKE :val')->setParameter('val', '%' . $value . '%')
            ->getQuery()->getResult();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findByNameOrMailContains(string $value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.name LIKE :val OR u.displayName LIKE :val')
            ->orWhere('u.email LIKE :val')->setParameter('val', '%' . $value . '%')
            ->getQuery()->getResult();
    }

    public function findOneByMail(string $value): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->andWhere('u.email = :val')->setParameter('val', $value)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) { return null; }
    }

    public function findOneByEternalID(string $value): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->andWhere('u.eternalID = :val')->setParameter('val', $value)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) { return null; }
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findAboutToBeDeleted()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.deleteAfter IS NOT NULL')
            ->getQuery()->getResult();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findNeedToBeDeleted()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.deleteAfter IS NOT NULL')
            ->andWhere('u.deleteAfter < :now')->setParameter('now', new \DateTime('now'))
            ->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername(string $username): ?User
    {
        $components = explode('::', $username, 2);
        list( $domain, $name ) = count($components) === 2 ? $components : ['myh',$components[0]];

        switch ($domain) {
            case 'myh':
                $user = $this->findOneByName($name);
                if (!$user && strpos($name, '@') !== false )
                    $user = $this->findOneByMail($name);
                return $user;
            case 'etwin':
                return $this->findOneByEternalID( $name );
            case 'tkn':
                $token = $this->getEntityManager()->getRepository(RememberMeTokens::class)->findOneBy(['token' => $name]);
                return $token?->getUser()?->isDisabled() ? null : $token?->getUser();
            default: return null;
        }
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->loadUserByUsername($identifier);
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findInverseFriends(User $user, bool $single_side_only = true): array
    {
        $inverse_friends = $this->createQueryBuilder('u')
            ->andWhere(':user MEMBER OF u.friends')->setParameter('user', $user)
            ->getQuery()->getResult();

        return $single_side_only ? array_filter($inverse_friends, fn($i) => !$user->getFriends()->contains($i)) : $inverse_friends;
    }
}
