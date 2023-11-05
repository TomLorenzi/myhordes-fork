<?php

namespace App\Event\Game\EventHooks\Purge;

use App\Event\Game\EventHooks\Common\DashboardModifierData;
use App\Event\Game\EventHooks\Common\DashboardModifierEvent as GenericDashboardModifierEvent;

/**
 * @property-read DashboardModifierData $data
 * @mixin DashboardModifierData
 */
class DashboardModifierEvent extends GenericDashboardModifierEvent {}