<?php

namespace App\Enum;

enum HeroXPType: int {
    case Legacy = 0;
    case Manual = 1;
    case Global = 100;
    case Seasonal = 200;

}