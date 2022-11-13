<?php


namespace App\Structures;

use App\Entity\Zone;

/**
 * @property int $zombies
 * @property-read int $x
 * @property-read int $y
 * @property-read boolean $town
 * @property-read boolean $building
 */
class ZombieSpawnZone
{
    protected int $zombies;

    public function __construct(public readonly Zone $zone)  {
        $this->zombies = $zone->getZombies();
    }

    /**
     * @throws \Exception
     */
    public function __get(string $name): mixed {
        return match ($name) {
            'zombies' => $this->zombies,
            'x' => $this->zone->getX(),
            'y' => $this->zone->getY(),
            'town' => $this->zone->isTownZone(),
            'building' => (bool)$this->zone->getPrototype(),
            default => throw new \Exception("Getting invalid property '$name'")
        };
    }

    /**
     * @throws \Exception
     */
    public function __set(string $name, mixed $value): void {
        match ($name) {
            'zombies' => $this->zombies = max(0, (int)$value),
            default => throw new \Exception("Setting invalid property '$name'")
        };
    }

    public function addZombie(int $n = 1): void {
        $this->zombies += $n;
    }

    public function killZombie(int $n = 1): void {
        $this->zombies = max(0, $this->zombies - $n);
    }
}