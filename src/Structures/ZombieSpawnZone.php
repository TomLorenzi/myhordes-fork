<?php


namespace App\Structures;

use App\Entity\Zone;
use App\Service\RandomGenerator;

/**
 *
 * @property boolean $done
 * @property-read int $x
 * @property-read int $y
 * @property-read boolean $town
 * @property-read boolean $building
 * @property int $zombies
 * @property int $deads
 * @property int $zombieKills
 */
class ZombieSpawnZone
{
    public bool $done = false;

    protected ?int $zombies = null;
    protected ?int $deads = null;
    protected ?int $zombieKills = null;
    protected array $leads = [];

    public function __construct(public readonly Zone $zone)  {}

    /**
     * @throws \Exception
     */
    public function __get(string $name): mixed {
        return match ($name) {
            'x' => $this->zone->getX(),
            'y' => $this->zone->getY(),
            'town' => $this->zone->isTownZone(),
            'building' => (bool)$this->zone->getPrototype(),

            'zombies' => $this->zombies ?? $this->zone->getZombies(),
            'deads' => $this->deads ?? $this->zone->getPlayerDeaths(),
            'zombieKills' => $this->zombieKills ?? max(0, $this->zone->getInitialZombies() - $this->zone->getZombies()),
            default => throw new \Exception("Getting invalid property '$name'")
        };
    }

    /**
     * @throws \Exception
     */
    public function __set(string $name, mixed $value): void {
        match ($name) {
            'zombies' => $this->zombies = max(0, (int)$value),
            'deads' => $this->deads = max(0, (int)$value),
            'zombieKills' => $this->zombieKills = max(0, (int)$value),
            default => throw new \Exception("Setting invalid property '$name'")
        };
    }

    public function addZombie(int $n = 1): void {
        $this->zombies = max(0, ($this->zombies ?? $this->zone->getZombies()) + $n);
    }

    public function killZombie(int $n = 1): void {
        $this->addZombie( -$n );
    }

    public function addLead( ZombieSpawnBehaviour $lead, int $weight ): void {
        if ($weight > 0) $this->leads[] = [ $lead, $weight ];
    }

    public function getBehaviour( RandomGenerator $random ): ?ZombieSpawnBehaviour {
        if (empty($this->leads)) return null;

        $selected = $random->pickEntryFromRawRandomArray( $this->leads );
        if ($selected) $this->leads = array_filter( $this->leads, fn($e) => $e[0] !== $selected );
        return $selected;
    }

    public static function getZoneLevel( ZombieSpawnZone $v1, ZombieSpawnZone $v2 ): int {
        $ax = abs( $v1->x - $v2->x );
        $ay = abs( $v1->y - $v2->y );
        return round( sqrt( $ax * $ax + $ay * $ay ) );
    }
}