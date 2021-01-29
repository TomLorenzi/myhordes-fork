<?php

namespace App\Repository;

use App\Entity\RememberMeTokens;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
     * @return User[] Returns an array of User objects
     */
    public function findBySoulSearchQuery(string $name, int $limit = 10, array $skip = [])
    {
        $skip = array_filter(array_map( fn($u) => is_a($u, User::class) ? $u->getId() : $u, $skip));

        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.name LIKE :val OR u.displayName LIKE :val')->setParameter('val', "%{$name}%")
            ->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow')
            ->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost")
            ->andWhere('u.email != u.name');

        if (!empty($skip)) $qb->andWhere('u.id NOT IN (:skip)')->setParameter('skip', $skip);
        if ($limit > 0) $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
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

    /**
     * @return User[] Returns an array of User objects
     */
    public function findByBanned()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.shadowBan IS NOT NULL')
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
    public function loadUserByUsername(string $username)
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
                return $token ? $token->getUser() : null;
            default: return null;
        }
    }
}
