<?php

namespace MyHordes\Prime\Event\Game\Town\Addon\Dump;

use App\Entity\ItemPrototype;
use App\Event\Game\Town\Addon\Dump\DumpTrait;

class DumpRetrieveCheckData
{
    use DumpTrait;
    public ?ItemPrototype $consumable;
    public int $quantity = 0;

	/**
	 * @param ItemPrototype $item
	 * @return DumpRetrieveCheckData
	 * @noinspection PhpDocSignatureInspection
	 */
	public function setup( ?ItemPrototype $item = null, int $quantity = 0 ): void {
		$this->consumable = $item;
		$this->quantity = $quantity;
	}

}