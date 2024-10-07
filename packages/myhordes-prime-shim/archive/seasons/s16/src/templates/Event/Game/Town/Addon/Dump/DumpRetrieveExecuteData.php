<?php

namespace MyHordes\Prime\Event\Game\Town\Addon\Dump;

use App\Entity\ItemPrototype;
use App\Event\Traits\FlashMessageTrait;

class DumpRetrieveExecuteData
{
    use FlashMessageTrait;

    public readonly DumpRetrieveCheckData $check;

	public readonly int $quantity;
	public int $removedDefense = 0;
    /**
     * @param DumpRetrieveCheckData $check
     * @return DumpRetrieveExecuteEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(DumpRetrieveCheckData|DumpRetrieveCheckEvent $check): void {
        $this->check = is_a( $check, DumpRetrieveCheckData::class ) ? $check : $check->data;
		$this->quantity = $this->check->quantity;
    }
}