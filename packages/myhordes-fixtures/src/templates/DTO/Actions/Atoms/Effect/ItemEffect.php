<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Enum\ActionHandler\ItemDropTarget;
use App\Service\Actions\Game\AtomProcessors\Effect\ProcessItemEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @property-read ?string consumeItem
 * @property-read ?int consumeItemCount
 * @property-read ?array spawn
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

    protected function default(string $name): mixed {
        return match($name) {
            'consumeItemCount' => 0,
            'spawnCount' => 1,
            'spawn' => [],
            'spawnAt' => ItemDropTarget::DropTargetDefault,
            default => null
        };
    }

    protected static function beforeSerialization(array $data): array {
        $data['spawnAt'] = ($data['spawnAt'] ?? ItemDropTarget::DropTargetDefault)->value;
        return parent::beforeSerialization( $data );
    }

    protected static function afterSerialization(array $data): array {
        $data['spawnAt'] = ItemDropTarget::from( ($data['spawnAt'] ?? ItemDropTarget::DropTargetDefault->value) );
        return parent::afterSerialization( $data );
    }

}