<?php

namespace App\Enum;

enum ItemPoisonType: int {
    case None               = 0;
    case Deadly             = 1;
    case Infectious         = 2;

    public function poisoned(): bool {
        return $this !== self::None;
    }
}