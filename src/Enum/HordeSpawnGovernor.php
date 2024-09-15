<?php

namespace App\Enum;

enum HordeSpawnGovernor: int {
    case Hordes             = -2;
    case MyHordes           = -1;
    case HordesOnline       =  0;
    case HordesModDone      =  1;
    case HordesCrowdControl =  2;

    public function myHordes(): bool {
        return $this->value < 0;
    }

    public function hordes(): bool {
        return !$this->myHordes();
    }
}