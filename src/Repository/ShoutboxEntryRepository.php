<?php

namespace App\Repository;

use App\Entity\Shoutbox;
use App\Entity\ShoutboxEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @method ShoutboxEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShoutboxEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShoutboxEntry[]    findAll()
 * @method ShoutboxEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShoutboxEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoutboxEntry::class);
    }

    /**
     * @param Shoutbox $s
     * @param DateTime|null $cutoff
     * @param int|null $limit
     * @return ShoutboxEntry[] Returns an array of ShoutboxEntry objects
     */
    public function findFromShoutbox(Shoutbox $s, ?DateTime $cutoff = null, ?int $limit = null )
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.shoutbox = :val')->setParameter('val', $s)
            ->orderBy('s.timestamp', 'DESC')
            ->orderBy('s.id', 'DESC');
        if ($cutoff !== null) $qb->andWhere('s.timestamp > :cut')->setParameter('cut', $cutoff);
        if ($limit !== null) $qb->setMaxResults($limit);
        return $qb->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?ShoutboxEntry
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
