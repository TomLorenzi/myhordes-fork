<?php

namespace App\Event\Game\EventHooks\Common;

class WatchtowerModifierData
{
    public int $min, $max, $dayOffset;
    public float $quality;

    public ?string $message = null;

    /**
     * @return WatchtowerModifierEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(int $min, int $max, int $dayOffset, float $quality): void {
        $this->min = $min;
        $this->max = $max;
        $this->dayOffset = $dayOffset;
        $this->quality = $quality;
    }
}