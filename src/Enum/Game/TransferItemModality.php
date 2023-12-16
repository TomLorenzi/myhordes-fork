<?php

namespace App\Enum\Game;

enum TransferItemModality: int
{
    case None             = 0;
    case Tamer            = 1;
    case Impound          = 2;
    case BankTheft        = 4;
}
