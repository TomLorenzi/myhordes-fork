<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Enum\ActionHandler\PointType;
use App\Enum\ActionHandler\RelativeMaxPoint;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\SortDefinitionWord;
use App\Service\Actions\Game\AtomProcessors\Effect\ProcessStatusEffect;
use App\Structures\SortDefinition;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * @property-read ?PointType pointType
 * @property-read ?int pointValue
 * @property-read ?RelativeMaxPoint pointRelativeToMax
 * @property-read ?int pointExceedMax
 * @property-read ?int pointCapAt
 * @property-read ?bool resetThirstCounter
 * @property-read ?int actionCounterType
 * @property-read ?int actionCounterValue
 * @property-read ?string statusFrom
 * @property-read ?string statusTo
 * @property-read ?string role
 * @property-read ?bool roleIsAdded
 * @property-read ?int ghoulHunger
 * @property-read ?bool ghoulHungerForced
 * @property-read ?int statusProbability
 * @property-read ?bool statusProbabilityModifiable
 * @method self kill(?int $v)
 * @property ?int kill
 * @method self enableIf(bool|CitizenProperties $v)
 * @property ?bool enableIf
 * @property-read  ?bool appliesToTarget
 */
class StatusEffect extends EffectAtom {

    protected static function defaultSortDefinition(): SortDefinition {
        return new SortDefinition(SortDefinitionWord::Start);
    }

    public function getClass(): string
    {
        return ProcessStatusEffect::class;
    }

    public function applyEffectToTarget(?bool $v = true): self {
        $this->appliesToTarget = true;
        return $this;
    }

    public function point(PointType $type, int|CitizenProperties $value, bool|RelativeMaxPoint $relativeToMax = RelativeMaxPoint::RelativeToMax, int|CitizenProperties|null $exceedMax = 0, ?int $capAt = null): self
    {
        $this->pointType = $type;
        $this->pointValue = $value;
        $this->pointRelativeToMax = match ($relativeToMax) {
            true => RelativeMaxPoint::RelativeToMax,
            false => RelativeMaxPoint::Absolute,
            default => $relativeToMax
        };
        $this->pointExceedMax = $exceedMax;
        $this->pointCapAt = $capAt;
        return $this;
    }

    public function resetsThirstCounter(bool $b = true): self {
        $this->resetThirstCounter = $b;
        return $this;
    }

    public function count(int $counter, int $value = 1): self {
        $this->actionCounterType = $counter;
        $this->actionCounterValue = $value;
        return $this;
    }

    public function role(string $role, bool $add = true): self {
        $this->role = $role;
        $this->roleIsAdded = $add;
        return $this;
    }

    public function addsStatus(string $status): self {
        $this->statusTo = $status;
        $this->statusFrom = null;
        return $this;
    }

    public function removesStatus(string $status): self {
        $this->statusFrom = $status;
        $this->statusTo = null;
        return $this;
    }

    public function morphsStatus(string $from, string $to): self {
        $this->statusFrom = $from;
        $this->statusTo = $to;
        return $this;
    }

    public function modifiesStatus(): bool {
        return $this->statusTo !== null || $this->statusFrom !== null;
    }

    public function ghoulHunger(int $value, bool $forced = false): self {
        $this->ghoulHunger = $value;
        $this->ghoulHungerForced = $forced;
        return $this;
    }

    public function probability(int $value, bool $allowModifiers = true): self {
        $this->statusProbability = $value;
        $this->statusProbabilityModifiable = $allowModifiers;
        return $this;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'pointValue', 'pointExceedMax', 'actionCounterValue' => 0,
            'resetThirstCounter' => false,
            'enableIf' => true,
            default => null
        };
    }

    public function getApCost(): int {
        return ($this->pointType === PointType::AP && !$this->pointRelativeToMax->isRelative()) ? $this->pointValue : 0;
    }

    public function getMpCost(): int {
        return ($this->pointType === PointType::MP && !$this->pointRelativeToMax->isRelative()) ? $this->pointValue : 0;
    }

    public function getCpCost(): int {
        return ($this->pointType === PointType::CP && !$this->pointRelativeToMax->isRelative()) ? $this->pointValue : 0;
    }

    public function getSpCost(): int {
        return ($this->pointType === PointType::SP && !$this->pointRelativeToMax->isRelative()) ? $this->pointValue : 0;
    }

    protected static function beforeSerialization(array $data): array {
        $data['pointType'] = ($data['pointType'] ?? null) !== null ? $data['pointType']->value : null;
        $data['pointRelativeToMax'] = ($data['pointRelativeToMax'] ?? null) !== null ? $data['pointRelativeToMax']->value : null;
        return parent::beforeSerialization( $data );
    }

    protected static function afterSerialization(array $data): array {
        $data['pointType'] = ($data['pointType'] ?? null) !== null ? PointType::from( $data['pointType'] ) : null;
        $data['pointRelativeToMax'] = ($data['pointRelativeToMax'] ?? null) !== null ? RelativeMaxPoint::from( $data['pointRelativeToMax'] ) : null;
        return parent::afterSerialization( $data );
    }

}