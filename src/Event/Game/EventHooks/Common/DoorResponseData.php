<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\SingleValue;

class DoorResponseData
{
    use SingleValue;

    public readonly string $action;

    /**
     * @return DoorResponseEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(string $action): void {
        $this->action = $action;
    }
}