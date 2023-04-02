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
 * @property-read Citizen $citizen
 * @property-read Town $town
 * @property-read TownConf $townConfig
 * @property-read MyHordesConf $gameConfig
 * @property-read mixed $data
 */
abstract class GameInteractionEvent extends Event
{
    /**
     * @var array
     */
    private array $error_codes = [];

    /**
     * @var array $messages
     */
    private array $messages = [];

    private bool $state_modified = false;

    private readonly mixed $data_mixin;

    protected static function configuration(): ?string { return null; }

    public function __construct(
        private readonly Citizen $citizen,
        public readonly TownConf $townConfig,
        public readonly MyHordesConf $gameConfig,
    ) {
        $this->data_mixin = new (static::configuration() ?? stdClass::class);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'citizen' => $this->citizen,
            'town' => $this->citizen->getTown(),
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

    public function pushErrorCode(int $code, int $priority = 0): static {
        if (!isset( $this->error_codes[$priority] )) $this->error_codes[$priority] = [];
        $this->error_codes[$priority][] = $code;
        return $this;
    }

    public function pushMessage(string $message, int $priority = 0): static {
        if (!isset( $this->messages[$priority] )) $this->messages[$priority] = [];
        $this->messages[$priority][] = $message;
        return $this;
    }

    public function pushError(int $code, ?string $message = null, int $priority = 0): static {
        $this->pushErrorCode($code, $priority);
        if ($message !== null) $this->pushMessage($message, $priority);
        return $this;
    }

    public function hasError(): bool {
        return !empty( $this->error_codes );
    }

    public function getErrorCodes(): array {
        ksort( $this->error_codes );
        return array_reduce( $this->error_codes, fn(array $carry, array $list) => array_merge( $carry, $list ), [] );
    }

    public function getErrorCode(): ?int {
        if (!$this->hasError()) return null;
        return $this->getErrorCodes()[0];
    }

    public function getMessages(): array {
        ksort( $this->messages );
        return array_reduce( $this->messages, fn(array $carry, array $list) => array_merge( $carry, $list ), [] );
    }

    public function markModified(): static {
        $this->state_modified = true;
        return $this;
    }

    public function wasModified(): bool {
        return $this->state_modified;
    }
}