<?php

namespace App\Event\Game;

use App\Entity\Citizen;
use App\Entity\Town;
use App\Service\ConfMaster;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Exception;
use stdClass;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @property-read Town $town
 * @property-read TownConf $townConfig
 * @property-read MyHordesConf $gameConfig
 * @property-read mixed $data
 */
abstract class GameEvent extends Event
{
    private bool $state_modified = false;

    private bool $persist = true;

    private readonly mixed $data_mixin;

    protected static function configuration(): ?string { return null; }

    public function __construct(
        protected readonly Town $town,
        public readonly TownConf $townConfig,
        public readonly MyHordesConf $gameConfig,
    ) {
        $this->data_mixin = new (static::configuration() ?? stdClass::class);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'town' => $this->town,
            'data' => $this->data_mixin,
            default => $this->data_mixin->$name
        };
    }

    public function __set(string $name, $value): void
    {
        $this->data_mixin->$name = $value;
    }

    public function __call(string $name, array $arguments)
    {
        $r = call_user_func_array( [$this->data_mixin, $name], $arguments );
        return $name === 'setup' ? $this : $r;
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