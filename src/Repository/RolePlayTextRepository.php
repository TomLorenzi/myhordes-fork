<?php

namespace App\Repository;

use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RolePlayText|null find($id, $lockMode = null, $lockVersion = null)
 * @method RolePlayText|null findOneBy(array $criteria, array $orderBy = null)
 * @method RolePlayText[]    findAll()
 * @method RolePlayText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RolePlayTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RolePlayText::class);
    }

    public function findOneByName(string $value): ?RolePlayText
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param string|null $lang
     * @param bool $include_non_unlockable
     * @return RolePlayText[] Returns an array of RolePlayText objects
     */
    public function findAllByLang(?string $lang, bool $include_non_unlockable = false)
    {
        return $this->findAllByLangExcept($lang, [], $include_non_unlockable);
    }

    /**
     * @param string|null $lang
     * @param RolePlayText[]|FoundRolePlayText[]|string $except
     * @param bool $include_non_unlockable
     * @return RolePlayText[] Returns an array of RolePlayText objects
     */
    public function findAllByLangExcept(?string $lang, array $except, bool $include_non_unlockable = false)
    {
        $id_list = $name_list = [];
        foreach ($except as $entry) {
            if (is_a($entry, RolePlayText::class)) $id_list[] = $entry->getId() ?? 0;
            if (is_a($entry, FoundRolePlayText::class)) $id_list[] = $entry->getText()->getId() ?? 0;
            if (is_string( $entry )) $name_list[] = $entry;
        }

        $qb = $this->createQueryBuilder('r')->orderBy('r.id', 'ASC');

        if (!empty($id_list)) $qb->andWhere('r.id NOT IN (:black1)')->setParameter('black1', $id_list);
        if (!empty($name_list)) $qb->andWhere('r.name NOT IN (:black2)')->setParameter('black2', $name_list);

        if ($lang !== null) $qb->andWhere('r.language = :val')->setParameter('val', $lang);
        if (!$include_non_unlockable) $qb->andWhere('r.unlockable = :unlockable')->setParameter('unlockable', true);

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return RolePlayText[] Returns an array of RolePlayText objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RolePlayText
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
