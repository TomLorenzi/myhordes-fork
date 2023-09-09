<?php

namespace App\Enum;

use DateTimeImmutable;

enum ScavengingActionType {
    case Dig;
    case DigExploration;
    case Scavenge;
}