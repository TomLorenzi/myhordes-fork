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

    private $propagation_blacklist = [];

    protected static function configuration(): ?string { return null; }

    public static function publicConfigurationClass(): string {
        return static::configuration() ?? stdClass::class;
    }

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

    public function __isset(string $name): bool
    {
        return match ($name) {
            'town', 'data' => true,
            default => isset($this->data_mixin->$name)
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

    /**
     * @param string $listenerClass
     * @param string|array $methodName
     * @return $this
     */
    public function skipPropagationTo(string $listenerClass, string|array $methodName = '*'): static {
        if (!array_key_exists( $listenerClass, $this->propagation_blacklist ))
            $this->propagation_blacklist[$listenerClass] = [];

        $this->propagation_blacklist[$listenerClass] =
            array_merge( $this->propagation_blacklist[$listenerClass], is_array($methodName) ? $methodName : [$methodName] );

        return $this;
    }

    public function shouldPropagateTo(string $listenerClass, string $methodName): bool {
        if ($this->isPropagationStopped()) return false;
        return !array_key_exists( $listenerClass, $this->propagation_blacklist ) || (
            !in_array( $methodName, $this->propagation_blacklist[$listenerClass] ) &&
            !in_array( '*', $this->propagation_blacklist[$listenerClass] )
        );
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