<?php

namespace App\Enum\Game;

enum TransferItemOption: int
{
    case None = 0;
    case EnforcePlacement = 3;
    case AllowMultiHeavy  = 5;
    case AllowExtraBag  = 10;
}
