<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Enum\ActionHandler\ItemDropTarget;
use App\Enum\ActionHandler\PointType;
use App\Enum\ItemPoisonType;
use App\Service\Actions\Game\AtomProcessors\Effect\ProcessItemEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @property-read ?string consumeItem
 * @property-read ?int consumeItemCount
 * @property-read ?array spawn
 * @property-read bool consumeSource
 * @property-read bool morphSource
 * @property-read ?string morphSourceType
 * @property-read ?bool breakSource
 * @property-read ?bool equipSource
 * @property-read ?ItemPoisonType poisonSource
 * @property-read bool spawnTarget
 * @property-read bool consumeTarget
 * @property-read bool morphTarget
 * @property-read ?string morphTargetType
 * @property-read ?bool breakTarget
 * @property-read ?bool equipTarget
 * @property-read ?ItemPoisonType poisonTarget
 * @method self spawnAt (ItemDropTarget $v)
 * @property ItemDropTarget spawnAt
 * @method self spawnCount (?int $v)
 * @property ?int spawnCount
 */
class ItemEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessItemEffect::class;
    }

    public function consume(string $item, int $count = 1): self
    {
        $this->consumeItem = $item;
        $this->consumeItemCount = $count;
        return $this;
    }

    public function resetSpawn(): self {
        $this->spawn = [];
        return $this;
    }

    public function addSpawn(string $item, int $chance = 1, int $count = 1): self {
        $this->spawn = [
            ...$this->spawn,
            [[$item,$count],$chance]
        ];
        return $this;
    }

    public function addSpawnList(array $items, int $chance = 1, int $count = 1): self {
        $this->spawn = [
            ...$this->spawn,
            ...array_map(fn(string $item) => [[$item,$count],$chance], $items)
        ];
        return $this;
    }

    public function consumeSource(): self {
        $this->consumeSource = true;
        $this->morphSource = false;
        $this->morphSourceType = null;
        $this->poisonSource = null;
        $this->breakSource = null;
        $this->equipSource = null;
        return $this;
    }

    public function morphSource(?string $prototype = null, ?bool $break = null, ItemPoisonType|bool|null $poison = null, ?bool $equip = null): self {
        $this->consumeSource = false;
        $this->morphSource = true;
        $this->morphSourceType = $prototype;
        $this->poisonSource = match(true) {
            $poison === true  => ItemPoisonType::Deadly,
            $poison === false => ItemPoisonType::None,
            default => $poison
        };
        $this->breakSource = $break;
        $this->equipSource = $equip;
        return $this;
    }

    public function spawnTarget(): self {
        $this->spawnTarget = true;
        $this->consumeTarget = false;
        $this->morphTarget = false;
        $this->morphTargetType = null;
        $this->poisonTarget = null;
        $this->breakTarget = null;
        $this->equipTarget = null;
        return $this;
    }

    public function consumeTarget(): self {
        $this->spawnTarget = false;
        $this->consumeTarget = true;
        $this->morphTarget = false;
        $this->morphTargetType = null;
        $this->poisonTarget = null;
        $this->breakTarget = null;
        $this->equipTarget = null;
        return $this;
    }

    public function morphTarget(?string $prototype = null, ?bool $break = null, ItemPoisonType|bool|null $poison = null, ?bool $equip = null): self {
        $this->spawnTarget = false;
        $this->consumeTarget = false;
        $this->morphTarget = true;
        $this->morphTargetType = $prototype;
        $this->poisonTarget = match(true) {
            $poison === true  => ItemPoisonType::Deadly,
            $poison === false => ItemPoisonType::None,
            default => $poison
        };
        $this->breakTarget = $break;
        $this->equipTarget = $equip;
        return $this;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'consumeItemCount' => 0,
            'spawnCount' => 1,
            'spawn' => [],
            'spawnAt' => ItemDropTarget::DropTargetDefault,
            'spawnTarget', 'consumeTarget', 'morphTarget', 'consumeSource', 'morphSource' => false,
            default => null
        };
    }

    protected static function beforeSerialization(array $data): array {
        $data['spawnAt'] = ($data['spawnAt'] ?? ItemDropTarget::DropTargetDefault)->value;
        $data['poisonSource'] = ($data['poisonSource'] ?? null) !== null ? $data['poisonSource']->value : null;
        return parent::beforeSerialization( $data );
    }

    protected static function afterSerialization(array $data): array {
        $data['spawnAt'] = ItemDropTarget::from( ($data['spawnAt'] ?? ItemDropTarget::DropTargetDefault->value) );
        $data['poisonSource'] = ($data['poisonSource'] ?? null) !== null ? ItemPoisonType::from( $data['poisonSource'] ) : null;
        return parent::afterSerialization( $data );
    }

}