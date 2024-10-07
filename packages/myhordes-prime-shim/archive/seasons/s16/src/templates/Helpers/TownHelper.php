<?php

namespace MyHordes\Prime\Helpers;

use App\Entity\ItemPrototype;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckData;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpInsertionExecuteEvent;
use MyHordes\Prime\Event\Game\Town\Addon\Dump\DumpRetrieveCheckEvent;
use MyHordes\Prime\Event\Game\Town\Addon\Dump\DumpRetrieveExecuteEvent;

class TownHelper {
	public static function get_dump_def_for(ItemPrototype $proto, DumpInsertionExecuteEvent|DumpInsertionCheckEvent|DumpRetrieveCheckEvent|DumpRetrieveExecuteEvent $event): int {
		$check = $event;
		if (is_a($event, DumpInsertionExecuteEvent::class) || is_a($event, DumpRetrieveExecuteEvent::class)) $check = $event->check;

		$improved = $check->dump_upgrade_built;
		/** @var DumpInsertionCheckData $check */

		$baseDef = 0;

		$dumpableItemProperties = [
			'weapon' => [
				'bonus' => $check->weapon_dump_built ? 5 : 0,
				'add' => [
					'machine_gun_#00',
					'gun_#00',
					'chair_basic_#00',
					'machine_1_#00',
					'machine_2_#00',
					'machine_3_#00',
					'pc_#00'
				],
				'exclude' => []
			],
			'defence' => [
				'bonus' => $check->defense_dump_built ? 2 : 0,
				'add' => [],
				'exclude' => []
			],
			'food' => [
				'bonus' => $check->food_dump_built ? 3 : 0,
				'add' => [],
				'exclude' => []
			],
			'pet' => [
				'bonus' => $check->animal_dump_built ? 6 : 0,
				'add' => [
					'tekel_#00',
					'pet_dog_#00'
				],
				'exclude' => []
			],
			'wood' => [
				'bonus' => $check->wood_dump_built ? 1 : 0,
				'add' => [
					'wood_bad_#00',
					'wood2_#00'
				],
				'exclude' => []
			],
			'metal' => [
				'bonus' => $check->metal_dump_built ? 1 : 0,
				'add' => [
					'metal_bad_#00',
					'metal_#00'
				],
				'exclude' => []
			]
		];

		// Each dumpable item gives 1 def point
		foreach ($dumpableItemProperties as $property => $itemList) {
			if (($proto->hasProperty($property) && !in_array($proto->getName(), $itemList['exclude'])) || in_array($proto->getName(), $itemList['add'])) {
				$baseDef = 1 + $itemList['bonus'];
			}
		}

		if ($baseDef === 0) return $baseDef;

		// The dump upgrade adds 1 def point
		if ($check->dump_upgrade_built)
			$baseDef += 1;

		return $baseDef;
	}
}