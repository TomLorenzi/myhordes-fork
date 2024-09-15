<?php

namespace App\Enum\Game;

enum LogHiddenType: int
{
    case Visible = 0;
    case Hidden = 1;
    case Deleted = 2;

    public function visible(): bool {
        return $this === self::Visible;
    }

    public function hidden(): bool {
        return !$this->visible();
    }

}


