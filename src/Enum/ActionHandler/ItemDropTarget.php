<?php

namespace App\Enum\ActionHandler;

enum ItemDropTarget: int {
    case DropTargetDefault = 0;
    case DropTargetRucksack = 1;
    case DropTargetFloor = 2;
    case DropTargetPreferRucksack = 3;
    case DropTargetFloorOnly = 4;
    case DropTargetOrigin = 5;
}