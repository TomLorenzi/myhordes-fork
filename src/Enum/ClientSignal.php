<?php

namespace App\Enum;

enum ClientSignal: string
{
    case InventoryUpdated = 'inventory-changed';
    case InventoryHeadlessUpdate = 'inventory-changed-headless';
    case StatusUpdated = 'status-changed';
}
