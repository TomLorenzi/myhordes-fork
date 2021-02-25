<?php


namespace App\Annotations;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation
 */
class GateKeeperProfile implements ConfigurationInterface
{

    public ?string $value = null;

    public bool $allow_during_attack  = false;
    public bool $record_user_activity = true;

    public bool $only_ghost           = false;
    public bool $only_incarnated      = false;
    public bool $only_alive           = false;
    public bool $only_with_profession = false;
    public bool $only_in_town         = false;
    public bool $only_beyond          = false;
    public bool $only_in_ruin         = false;

    public bool $hook                 = true;
    /**
     * @inheritDoc
     */
    public function getAliasName(): string {
        return 'GateKeeperProfile';
    }

    /**
     * @inheritDoc
     */
    public function allowArray(): bool {
        return false;
    }

    public function getValue(): string {
        return $this->value ?? '';
    }

    public function skipGateKeeper(): bool {
        return $this->getValue() === 'skip';
    }

    /**
     * @return bool
     */
    public function getAllowDuringAttack(): bool {
        return $this->allow_during_attack;
    }

    /**
     * @return bool
     */
    public function getRecordUserActivity(): bool {
        return $this->record_user_activity;
    }

    public function executeHook(): bool {
        return $this->hook;
    }

    public function onlyWhenGhost():      bool { return $this->only_ghost; }
    public function onlyWhenIncarnated(): bool { return $this->only_incarnated || $this->onlyWhenAlive() || $this->onlyWithProfession() || $this->onlyInTown() || $this->onlyInRuin() || $this->onlyBeyond(); }
    public function onlyWhenAlive():      bool { return $this->only_alive; }
    public function onlyWithProfession(): bool { return $this->only_with_profession; }
    public function onlyInTown(): bool { return $this->only_in_town; }
    public function onlyBeyond(): bool { return $this->only_beyond || $this->onlyInRuin(); }
    public function onlyInRuin(): bool { return $this->only_in_ruin; }

}