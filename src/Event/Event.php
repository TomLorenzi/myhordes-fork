<?php

namespace App\Event;

use stdClass;
use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

/**
 * @property-read mixed $data
 */
abstract class Event extends BaseEvent
{
    private readonly mixed $data_mixin;

    private array $propagation_blacklist = [];

    protected static function configuration(): ?string { return null; }

    public static function publicConfigurationClass(): string {
        return static::configuration() ?? stdClass::class;
    }

    public function __construct() {
        $this->data_mixin = new (static::configuration() ?? stdClass::class);
    }

    public function __get(string $name)
    {
        return $name === 'data' ? $this->data_mixin : $this->data_mixin->$name;
    }

    public function __isset(string $name): bool
    {
        return $name === 'data' || isset($this->data_mixin->$name);
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
}
