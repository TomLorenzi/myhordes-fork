<?php

namespace App\Enum\ActionHandler;

enum PointType: int {
    case AP = 1;
    case CP = 2;
    case MP = 3;
    case SP = 4;

    public function letterCode(): string {
        return match ($this) {
            self::AP => 'ap',
            self::CP => 'cp',
            self::MP => 'pm',
            self::SP => 'sp',
        };
    }
}