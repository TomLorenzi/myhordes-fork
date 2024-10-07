<?php

namespace MyHordes\Prime\Service;

use App\Entity\LogEntryTemplate;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class PictoDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
		$data = array_replace_recursive($data, [
			[
				'label' => 'Blaugoldige Thermalbäder',
				'description' => 'Hey, du hättest an deine Seife denken sollen!',
				'icon' => 'r_thermal',
				'rare' => true,
				'priority' => 3,
			],
			[
				'label' => 'Leichenverbrenner',
				'description' => 'Durchgeführte feurige Begräbnisrituale an den Leichen von Bürgern',
				'icon' => 'r_cburn',
				'rare' => true,
				'priority' => 3,
			],
		]);
    }
}
