<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\EscapeTimer;
use App\Entity\Zone;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method EscapeTimer|null find($id, $lockMode = null, $lockVersion = null)
 * @method EscapeTimer|null findOneBy(array $criteria, array $orderBy = null)
 * @method EscapeTimer[]    findAll()
 * @method EscapeTimer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EscapeTimerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EscapeTimer::class);
    }

    /**
     * @param Citizen $c
     * @return EscapeTimer[]
     */
    public function findAllByCitizen( Citizen $c )
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.citizen = :ctz')->setParameter('ctz', $c)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @param Zone $z
     * @param bool $only_global
     * @return EscapeTimer[]
     */
    public function findAllByZone( Zone $z, $only_global = false )
    {
        $q = $this->createQueryBuilder('e')
            ->andWhere('e.zone = :zne')->setParameter('zne', $z);
        if ($only_global)
            $q->andWhere('e.citizen IS NULL');
       return $q
            ->getQuery()
            ->getResult();
    }

    public function findActiveByCitizen(Citizen $c, $only_personal = false, $allow_desperate = false): ?EscapeTimer
    {
        if (!$c->getZone()) return null;

        /** @var EscapeTimer|null $selected_timer */
        $selected_timer = null;
        $q = $this->createQueryBuilder('e')
            ->andWhere('e.zone = :zne')->setParameter('zne', $c->getZone())
            ->andWhere('e.time > :now')->setParameter('now', new DateTime());
        if ($only_personal)
            $q->andWhere('e.citizen = :ctz')->setParameter('ctz', $c);
        if (!$allow_desperate)
            $q->andWhere('e.desperate = :desp')->setParameter('desp', false);
        else
            $q->andWhere('e.citizen = :ctz OR e.citizen IS NULL')->setParameter('ctz', $c);
        $timers = $q
            ->getQuery()
            ->getResult();

        foreach ($timers as $timer) {
            /** @var EscapeTimer $timer */
            if ($selected_timer === null || $timer->getTime() > $selected_timer->getTime())
                $selected_timer = $timer;
        }

        return $selected_timer;
    }

    // /**
    //  * @return EscapeTimer[] Returns an array of EscapeTimer objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EscapeTimer
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
