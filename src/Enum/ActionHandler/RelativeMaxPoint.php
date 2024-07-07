<?php

namespace App\Enum\ActionHandler;

enum RelativeMaxPoint: int {
    case Absolute = 0;
    case RelativeToMax = 1;
    case RelativeToExtensionMax = 2;

    public function isRelative(): bool {
        return $this !== self::Absolute;
    }
}