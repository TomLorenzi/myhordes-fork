<?php

namespace App\Event\Game\Town\Addon\Dump;

use App\Entity\Item;
use App\Entity\ItemPrototype;

class DumpInsertionCheckData
{
    use DumpTrait;
    public ?ItemPrototype $consumable;
    public int $quantity = 0;
	public array $dumpableItems;

	/**
	 * @param ItemPrototype $item
	 * @return DumpInsertionCheckData
	 * @noinspection PhpDocSignatureInspection
	 */
	public function setup( ?ItemPrototype $item = null, int $quantity = 0 ): void {
		$this->consumable = $item;
		$this->quantity = $quantity;
	}

}