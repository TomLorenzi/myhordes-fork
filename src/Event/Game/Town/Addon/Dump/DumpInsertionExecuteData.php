<?php

namespace App\Event\Game\Town\Addon\Dump;

use App\Entity\ItemPrototype;
use App\Event\Traits\FlashMessageTrait;

class DumpInsertionExecuteData
{
    use FlashMessageTrait;

    public readonly DumpInsertionCheckData $check;

	public readonly int $quantity;
	public int $addedDefense = 0;
    /**
     * @param DumpInsertionCheckData $check
     * @return DumpInsertionExecuteEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(DumpInsertionCheckData|DumpInsertionCheckEvent $check): void {
        $this->check = is_a( $check, DumpInsertionCheckData::class ) ? $check : $check->data;
		$this->quantity = $this->check->quantity;
    }
}