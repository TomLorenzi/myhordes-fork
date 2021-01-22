<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;

/**
 * @method CitizenVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenVote[]    findAll()
 * @method CitizenVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenVote::class);
    }

    public function countCitizenVotesFor(Citizen $votedCitizen, CitizenRole $role): int {
        try {
            return (int)$this->createQueryBuilder('c')->select('count(c.id)')
                ->andWhere('c.votedCitizen = :votedCitizen')->setParameter('votedCitizen', $votedCitizen)
                ->andWhere('c.role = :role')->setParameter('role', $role)
                ->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @param Citizen $votedCitizen
     * @return CitizenVote[] Returns an array of CitizenVote objects
     */
    public function findByVotedCitizen(Citizen $votedCitizen)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.votedCitizen = :votedCitizen')->setParameter('votedCitizen', $votedCitizen)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByCitizens(Citizen $autor, Citizen $votedCitizen): ?CitizenVote
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.autor = :autor')->setParameter('autor', $autor)
                ->andWhere('c.votedCitizen = :votedCitizen')->setParameter('votedCitizen', $votedCitizen)
                ->getQuery()
                ->getResult();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param Citizen $citizen The citizen who should have placed a vote
     * @param CitizenRole $role The role the citizen should have placed a vote for
     * @return CitizenVote[] Returns an array of CitizenVote objects
     */
    public function findOneByCitizenAndRole(Citizen $citizen, CitizenRole $role)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.autor = :citizen')->setParameter('citizen', $citizen)
            ->andWhere('c.role = :role')->setParameter('role', $role)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    // /**
    //  * @return CitizenVote[] Returns an array of CitizenVote objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CitizenVote
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
