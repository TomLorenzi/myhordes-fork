<?php

namespace App\Event\Game\EventHooks\April;

use App\Event\Game\EventHooks\Common\DoorResponseData;
use App\Event\Game\EventHooks\Common\DoorResponseEvent as GenericDoorResponseEvent;

/**
 * @property-read DoorResponseData $data
 * @mixin DoorResponseData
 */
class DoorResponseEvent extends GenericDoorResponseEvent {}