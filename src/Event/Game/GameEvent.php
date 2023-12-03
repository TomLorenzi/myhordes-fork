<?php

namespace App\Event\Game;

use App\Entity\Town;
use App\Event\Event;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;

/**
 * @property-read Town $town
 * @property-read TownConf $townConfig
 * @property-read MyHordesConf $gameConfig
 */
abstract class GameEvent extends Event
{
    private bool $state_modified = false;

    private bool $persist = true;

    public function __construct(
        protected readonly Town $town,
        public readonly TownConf $townConfig,
        public readonly MyHordesConf $gameConfig,
    ) {
        parent::__construct();
    }

    public function __get(string $name)
    {
        return match ($name) {
            'town' => $this->town,
            default => parent::__get($name)
        };
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'town' => true,
            default => parent::__isset($name)
        };
    }

    public function markModified(): static {
        $this->state_modified = true;
        return $this;
    }

    public function cancelPersist(): static {
        $this->persist = false;
        return $this;
    }

    public function wasModified(): bool {
        return $this->state_modified;
    }

    public function shouldPersist(): bool {
        return $this->persist;
    }
}