<?php

namespace App\Enum;

enum ItemPoisonType: int {
    case None               = 0;
    case Deadly             = 1;
    case Infectious         = 2;
    case Strange            = 3;

    public function poisoned(): bool {
        return $this !== self::None;
    }

    public function mix( ItemPoisonType $t ): ItemPoisonType {
        return match ($this) {
            self::None => $t,
            self::Deadly => $this,
            self::Infectious => $t === ItemPoisonType::Deadly ? $t : $this,
            self::Strange => $t !== ItemPoisonType::None ? $t : $this,
        };
    }
}