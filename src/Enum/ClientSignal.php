<?php

namespace App\Enum;

enum ClientSignal: string
{
    case InventoryUpdated = 'inventory-changed';
    case StatusUpdated = 'status-changed';
}
