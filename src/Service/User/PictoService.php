<?php

namespace App\Service\User;

use App\Entity\Award;
use App\Entity\AwardPrototype;
use App\Entity\Picto;
use App\Entity\PictoComment;
use App\Entity\PictoPrototype;
use App\Entity\PictoRollup;
use App\Entity\Season;
use App\Entity\User;
use App\EventListener\ContainerTypeTrait;
use App\Interfaces\Entity\PictoRollupInterface;
use App\Service\CrowService;
use App\Structures\Entity\PictoRollupStructure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class PictoService implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly TagAwareCacheInterface $gameCachePool
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            CrowService::class,
        ];
    }

    /**
     * @param PictoRollupInterface[] $data
     * @return int
     */
    public function getPointsFromPictoRollupSet( array $data ): int {

        $count = 0;
        foreach ($data as $picto)
            $count += match ( $picto->getPrototype()->getName() ) {
                'r_ptame_#00' => match (true) {
                    $picto->getCount() <  100 => 0,
                    $picto->getCount() <  500 => 13,
                    $picto->getCount() < 1000 => 13 + 33,
                    $picto->getCount() < 2000 => 13 + 33 + 66,
                    $picto->getCount() < 3000 => 13 + 33 + 66 + 132,
                    default                   => 13 + 33 + 66 + 132 + 198,
                },

                'r_heroac_#00', 'r_explor_#00' => match(true) {
                    $picto->getCount() < 15 => 0,
                    $picto->getCount() < 30 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_cookr_#00', 'r_cmplst_#00', 'r_camp_#00', 'r_drgmkr_#00', 'r_jtamer_#00', 'r_jrangr_#00',
                'r_jguard_#00', 'r_jermit_#00', 'r_jtech_#00', 'r_jcolle_#00' => match(true) {
                    $picto->getCount() < 10 => 0,
                    $picto->getCount() < 25 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_animal_#00', 'r_plundr_#00' => match(true) {
                    $picto->getCount() < 30 => 0,
                    $picto->getCount() < 60 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_chstxl_#00', 'r_ruine_#00' => match(true) {
                    $picto->getCount() <  5 => 0,
                    $picto->getCount() < 10 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_buildr_#00' => match(true) {
                    $picto->getCount() < 100 => 0,
                    $picto->getCount() < 200 => 3.5,
                    default                  => 3.5 + 6.5,
                },

                'r_nodrug_#00' => match(true) {
                    $picto->getCount() < 20 => 0,
                    $picto->getCount() < 75 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_ebuild_#00' => match(true) {
                    $picto->getCount() < 1 => 0,
                    $picto->getCount() < 3 => 3.5,
                    default                => 3.5 + 6.5,
                },

                'r_digger_#00' => match(true) {
                    $picto->getCount() <  50 => 0,
                    $picto->getCount() < 300 => 3.5,
                    default                  => 3.5 + 6.5,
                },

                'r_deco_#00' => match(true) {
                    $picto->getCount() < 100 => 0,
                    $picto->getCount() < 250 => 3.5,
                    default                  => 3.5 + 6.5,
                },

                'r_explo2_#00' => match(true) {
                    $picto->getCount() <  5 => 0,
                    $picto->getCount() < 15 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_guide_#00' => match(true) {
                    $picto->getCount() <  300 => 0,
                    $picto->getCount() < 1000 => 3.5,
                    default                   => 3.5 + 6.5,
                },

                'r_theft_#00' => match(true) {
                    $picto->getCount() < 10 => 0,
                    $picto->getCount() < 30 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_maso_#00', 'r_guard_#00' => match(true) {
                    $picto->getCount() < 20 => 0,
                    $picto->getCount() < 40 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_surlst_#00' => match(true) {
                    $picto->getCount() <  10 => 0,
                    $picto->getCount() <  15 => 3.5,
                    $picto->getCount() <  30 => 3.5 + 6.5,
                    $picto->getCount() <  50 => 3.5 + 6.5 + 10,
                    $picto->getCount() < 100 => 3.5 + 6.5 + 10 + 13,
                    default                  => 3.5 + 6.5 + 10 + 13 + 16.5,
                },

                'r_suhard_#00' => match(true) {
                    $picto->getCount() <   5 => 0,
                    $picto->getCount() <  10 => 3.5,
                    $picto->getCount() <  20 => 3.5 + 6.5,
                    $picto->getCount() <  40 => 3.5 + 6.5 + 10,
                    default                  => 3.5 + 6.5 + 10 + 13,
                },

                'r_doutsd_#00' => match(true) {
                    $picto->getCount() < 20 => 0,
                    default                 => 3.5,
                },

                'r_door_#00' => match(true) {
                    $picto->getCount() < 1 => 0,
                    $picto->getCount() < 5 => 3.5,
                    default                => 3.5 + 6.5,
                },

                'r_wondrs_#00' => match(true) {
                    $picto->getCount() < 20 => 0,
                    $picto->getCount() < 50 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                'r_rp_#00' => match(true) {
                    $picto->getCount() <   5 => 0,
                    $picto->getCount() <  10 => 3.5,
                    $picto->getCount() <  20 => 3.5 + 6.5,
                    $picto->getCount() <  30 => 3.5 + 6.5 + 10,
                    $picto->getCount() <  40 => 3.5 + 6.5 + 10 + 13,
                    $picto->getCount() <  60 => 3.5 + 6.5 + 10 + 13 + 16.5,
                    default                  => 3.5 + 6.5 + 10 + 13 + 16.5 + 20,
                },

                'r_winbas_#00' => match(true) {
                    $picto->getCount() < 2 => 0,
                    $picto->getCount() < 5 => 13,
                    default                => 13 + 20,
                },

                'r_wintop_#00' => match(true) {
                    $picto->getCount() < 1 => 0,
                    default                => 20,
                },

                'r_killz_#00' => match(true) {
                    $picto->getCount() < 100 => 0,
                    $picto->getCount() < 200 => 3.5,
                    $picto->getCount() < 300 => 3.5 + 6.5,
                    $picto->getCount() < 800 => 3.5 + 6.5 + 10,
                    default                  => 3.5 + 6.5 + 10 + 13,
                },

                'r_cannib_#00' => match(true) {
                    $picto->getCount() < 10 => 0,
                    $picto->getCount() < 40 => 3.5,
                    default                 => 3.5 + 6.5,
                },

                default => 0,
            };

        return round($count);
    }

    /**
     * @param PictoRollupInterface[] $data
     * @return PictoRollupInterface[]
     */
    private function sort_data( array $data ): array {
        usort( $data, fn( PictoRollupInterface $a, PictoRollupInterface $b ) =>
        $b->getPrototype()->getRare() <=> $a->getPrototype()->getRare() ?:
            $b->getCount() <=> $a->getCount() ?:
                $b->getPrototype()->getId() <=> $a->getPrototype()->getId()
        );
        return $data;
    }

    /**
     * Accumulates multiple rows from the picto rollup table (i.e. multiple seasons) according to the given filters.
     * @param User $user The user to collect pictos for
     * @param bool $include_imported Include imported pictos in the accumulation
     * @param bool $include_old Include old (alpha) pictos in the accumulation
     * @return PictoRollupStructure[]
     */
    public function accumulateAllPictos(User $user, bool $include_imported = false, bool $include_old = false): array {
        $qb = $this->getService(EntityManagerInterface::class)->getRepository(PictoRollup::class)->createQueryBuilder('i')
            ->select('SUM(i.count) as c', 'IDENTITY(i.prototype) as p')
            ->groupBy('p')
            ->andWhere('i.user = :user')->setParameter('user', $user)
            ->andWhere('i.total = true');
        if (!$include_imported) $qb->andWhere('NOT (i.imported = false AND i.old = false AND i.season IS NULL)');
        if (!$include_old) $qb->andWhere('i.old = false');
        $data = $qb->getQuery()->getArrayResult();

        $prototypes = array_column( array_map(
            fn(PictoPrototype $p) => [ 'id' => $p->getId(), 'prototype' => $p ],
            $this->getService(EntityManagerInterface::class)->getRepository(PictoPrototype::class)->findBy(['id' => array_map( fn(array $a) => $a['p'], $data )])
        ), 'prototype', 'id');

        $data = array_map(
            fn($row) => new PictoRollupStructure( $prototypes[$row['p']], $user, $row['c'] ),
            $data
        );

        return $this->sort_data( $data );
    }

    /**
     * Fetches a single row from the picto rollup table (i.e. a single season or group)
     * Altering more than one of the filter params from their default value will produce unpredictable results.
     * @param User $user The user to collect pictos for
     * @param bool $imported Set true to fetch imported pictos
     * @param bool $old Set true to fetch old (alpha) pictos
     * @param Season|null $season Set to fetch pictos from a specific season
     * @return PictoRollupInterface[]
     */
    public function getPictoGroup( User $user, bool $imported = false, bool $old = false, Season $season = null ): array {
        return $this->sort_data(
            $this->getService(EntityManagerInterface::class)->getRepository(PictoRollup::class)
                ->findBy(['user' => $user, 'imported' => $imported, 'total' => !( $imported || $old ), 'old' => $old, 'season' => $season])
        );
    }

    public function computePictoUnlocks(User $user): void {

        $cache = [];

        $pictos = $this->accumulateAllPictos( $user, include_imported: true );
        foreach ($pictos as $picto)
            $cache[$picto->getPrototype()->getId()] = $picto->getCount();

        $skip_proto = [];
        $remove_awards = [];
        $award_awards = [];

        /** @var Award $award */
        foreach ($user->getAwards() as $award) {
            if ($award->getPrototype()) $skip_proto[] = $award->getPrototype();
            if ($award->getPrototype() && $award->getPrototype()->getAssociatedPicto() &&
                (!isset($cache[$award->getPrototype()->getAssociatedPicto()->getId()]) || $cache[$award->getPrototype()->getAssociatedPicto()->getId()] < $award->getPrototype()->getUnlockQuantity())
            )
                $remove_awards[] = $award;
        }

        $em = $this->getService(EntityManagerInterface::class);
        foreach ($em->getRepository(AwardPrototype::class)->findAll() as $prototype)
            if (!in_array($prototype,$skip_proto) &&
                (isset($cache[$prototype->getAssociatedPicto()->getId()]) && $cache[$prototype->getAssociatedPicto()->getId()] >= $prototype->getUnlockQuantity())
            ) {
                $user->addAward($award = (new Award())->setPrototype($prototype));
                $em->persist($award_awards[] = $award);
            }

        if (!empty($award_awards))
            $em->persist($this->getService(CrowService::class)->createPM_titleUnlock($user, $award_awards));


        foreach ($remove_awards as $r) {
            if ($user->getActiveIcon() === $r) $user->setActiveIcon(null);
            if ($user->getActiveTitle() === $r) $user->setActiveTitle(null);
            $user->removeAward($r);
            $em->remove($r);
        }

        try {
            if (!empty($award_awards) || !empty($remove_awards))
                $this->gameCachePool->invalidateTags(["user-{$user->getId()}-emote-unlocks"]);
        } catch (\Throwable $t) {}

    }

    /**
     * Returns all stored picto comments grouped by picto prototype
     * @param User $user
     * @return array
     */
    public function accumulateAllPictoComments(User $user): array {
        $data = $this->getService(EntityManagerInterface::class)
            ->getRepository(PictoComment::class)->createQueryBuilder('c')
            ->select('c.text as text', 'IDENTITY(p.prototype) as proto')
            ->innerJoin( Picto::class, 'p', Join::WITH, 'c.picto = p.id AND p.disabled = false AND p.persisted = 2 AND p.user = :user' )
            ->andWhere('c.owner = :user')->setParameter('user', $user)
            ->andWhere('c.display = true')
            ->getQuery()->getArrayResult();

        $result = [];
        foreach ($data as ['text' => $text, 'proto' => $proto_id]) {
            if (!array_key_exists($proto_id, $result)) $result[$proto_id] = [];
            $result[$proto_id][] = $text;
        }

        return $result;
    }

}