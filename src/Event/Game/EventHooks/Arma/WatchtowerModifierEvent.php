<?php

namespace App\Event\Game\EventHooks\Arma;

use App\Event\Game\EventHooks\Common\WatchtowerModifierData;
use App\Event\Game\EventHooks\Common\WatchtowerModifierEvent as GenericWatchtowerModifierEvent;

/**
 * @property-read WatchtowerModifierData $data
 * @mixin WatchtowerModifierData
 */
class WatchtowerModifierEvent extends GenericWatchtowerModifierEvent {}