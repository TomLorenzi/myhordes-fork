<?php

namespace App\Enum\Game;

enum TransferItemOption
{
    case EnforcePlacement;
    case AllowMultiHeavy;
    case AllowExtraBag;
    case Silent;
}
