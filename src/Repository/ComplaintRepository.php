<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\Complaint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;

/**
 * @method Complaint|null find($id, $lockMode = null, $lockVersion = null)
 * @method Complaint|null findOneBy(array $criteria, array $orderBy = null)
 * @method Complaint[]    findAll()
 * @method Complaint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComplaintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Complaint::class);
    }

    public function countComplaintsFor( Citizen $culprit, int $severity = Complaint::SeverityBanish ): int {
        try {
            return (int)$this->createQueryBuilder('c')->select('sum(c.count)')
                ->andWhere('c.culprit = :culprit')->setParameter('culprit', $culprit)
                ->andWhere('c.severity >= :sev')->setParameter('sev', $severity)
                ->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @param Citizen $culprit
     * @return Complaint[] Returns an array of Complaint objects
     */
    public function findByCulprit(Citizen $culprit)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.culprit = :culprit')->setParameter('culprit', $culprit)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByCitizens(Citizen $autor, Citizen $culprit): ?Complaint
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.autor = :autor')->setParameter('autor', $autor)
                ->andWhere('c.culprit = :culprit')->setParameter('culprit', $culprit)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (Exception $e) {
            return null;
        }
    }

    // /**
    //  * @return Complaint[] Returns an array of Complaint objects
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
    public function findOneBySomeField($value): ?Complaint
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
