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
 * @property-read bool $common
 */
abstract class GameInteractionEvent extends GameEvent
{
    /**
     * @var array
     */
    private array $error_codes = [];

    /**
     * @var array $messages
     */
    private array $messages = [];

    private bool $process_common_effects = true;

    private readonly mixed $data_mixin;

    public function __construct(
        private readonly Citizen $citizen,
        TownConf $townConfig,
        MyHordesConf $gameConfig,
    ) {
        parent::__construct($this->citizen->getTown(), $townConfig, $gameConfig);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'citizen' => $this->citizen,
            'common' => $this->process_common_effects,
            default => parent::__get($name)
        };
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'citizen', 'common' => true,
            default => parent::__isset($name)
        };
    }

    public function pushErrorCode(int $code, int $priority = 0, bool $cancelCommonEffects = true): static {
        if (!isset( $this->error_codes[$priority] )) $this->error_codes[$priority] = [];
        $this->error_codes[$priority][] = $code;
        if ($cancelCommonEffects) $this->process_common_effects = false;
        return $this;
    }

    public function pushMessage(string $message, string $type = 'notice', int $priority = 0): static {
        if (!isset( $this->messages[$priority] )) $this->messages[$priority] = [];
        $this->messages[$priority][] = [$type,$message];
        return $this;
    }

    public function pushError(int $code, ?string $message = null, int $priority = 0, bool $cancelCommonEffects = true): static {
        $this->pushErrorCode($code, $priority, $cancelCommonEffects);
        if ($message !== null) $this->pushMessage($message, 'error', $priority);
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
}