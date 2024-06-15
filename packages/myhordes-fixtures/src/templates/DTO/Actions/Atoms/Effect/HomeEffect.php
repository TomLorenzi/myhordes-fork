<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Service\Actions\Game\AtomProcessors\Effect\ProcessHomeEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @method self defense(?int $v)
 * @property ?int defense
 * @method self storage(?int $v)
 * @property ?int storage
 * @property string[] tags_temp
 * @property string[] tags_perm
 */
class HomeEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessHomeEffect::class;
    }

    public function setsTag(string $tag, bool $permanent): self {
        if ($permanent) $this->tags_perm = array_unique([...$this->tags_perm, $tag]);
        else $this->tags_temp = array_unique([...$this->tags_temp, $tag]);

        return $this;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'defense', 'storage' => 0,
            'tags_temp', 'tags_perm' => [],
            default => null
        };
    }

}