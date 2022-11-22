<?php


namespace App\Structures;

use App\Entity\Zone;
use App\Enum\HordeSpawnBehaviourType;
use App\Service\RandomGenerator;


class ZombieSpawnBehaviour
{
    public function __construct(
        public readonly HordeSpawnBehaviourType $type,
        public readonly ZombieSpawnZone $zone,
        public readonly ?bool $out = null,
        public readonly ?int $tx = null,
        public readonly ?int $ty = null,
        public readonly ?int $max = null,
        public readonly ?int $power = null
    )  {}

    /**
     * @param ZombieSpawnBehaviour[] $list
     * @param ZombieSpawnZone $zone
     * @param int $d
     * @return void
     */
    public static function Deads(array &$list, ZombieSpawnZone $zone, int $d): void {
        $list[] = new ZombieSpawnBehaviour(
            HordeSpawnBehaviourType::Move, $zone,
            out: false, tx: $zone->x, ty: $zone->y, max: min( $d + mt_rand(0,2) / 3, 3 ), power: $d * 10
        );

        if ( mt_rand( 0, max( 0, 9 - $d) ) === 0 )
            $list[] = new ZombieSpawnBehaviour( HordeSpawnBehaviourType::Grow, $zone,
                power: mt_rand(0, $d - floor( $d/2 ) - 1) + floor( $d/2 )
            );

    }

    public static function ZombieKills(array &$list, ZombieSpawnZone $zone, int $d): void {
        $list[] = new ZombieSpawnBehaviour(
                 HordeSpawnBehaviourType::Move, $zone,
            out: true, tx: $zone->x, ty: $zone->y, max: mt_rand(1,3), power: $d
        );
        $list[] = new ZombieSpawnBehaviour(
                 HordeSpawnBehaviourType::Move, $zone,
            out: false, tx: $zone->x, ty: $zone->y, max: mt_rand(1,3), power: 100 - $d
        );
    }

    public static function OwnWay(array &$list, ZombieSpawnZone $zone, int $mapSize, RandomGenerator $random): void {

        $list[] = $random->pickEntryFromRawRandomArray([
            [ new ZombieSpawnBehaviour( HordeSpawnBehaviourType::Move, $zone,
                out: false,
                tx: max(0, min($zone->x + $random->pick([-1,1]) * mt_rand(1,5), $mapSize - 1)),
                ty: max(0, min($zone->y + $random->pick([-1,1]) * mt_rand(1,5), $mapSize - 1)),
                max: mt_rand(0,1), power: 20 + $zone->zombies * 2
            ),
                50 - ($zone->building ? 25 : 0) + $zone->zombies * 5 ],
            [ new ZombieSpawnBehaviour( HordeSpawnBehaviourType::Grow, $zone, power: max(1, mt_rand(0, (int)(min(10, floor($zone->zombies / 2 + 1)) + 1) - 1)) ),
                max(0, 50 - $zone->zombies) ],
            [ new ZombieSpawnBehaviour( HordeSpawnBehaviourType::Eat, $zone, power: mt_rand( 1, 2 )),
                max(0, -5 + $zone->zombies * 2) ],
        ]);

    }
}