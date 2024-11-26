<?php

namespace MyHordes\Prime\Service;

use App\Enum\DropMod;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class RuinDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
		// Data source: https://docs.google.com/spreadsheets/d/1V9m5IxynXZyBL0O4Hy7PrDBIIxp6UPWwZjhQ2dIGgS8/edit#gid=0
        $data = array_replace_recursive($data, [
			"bunker" => [
				"capacity" => 5
			],
			
			"police" => [
				"capacity" => 4
			],
			
			"tent" => [
				"capacity" => 1
			],
			
			"bar2" => [
				"capacity" => 3
			],
			
			"tank" => [
				"capacity" => 1
			],
			
			"army" => [
				"capacity" => 5
			],
			
			"trench" => [
				"capacity" => 2
			],
			
			"cave3" => [
				"capacity" => 2
			],
			
			"home" => [
				"capacity" => 2
			],
			
			"albi" => [
				"capacity" => 3
			],
			
			"cave" => [
				"capacity" => 2
			],
			
			"pump" => [
				"capacity" => 1
			],
			
			"bike" => [
				"capacity" => 2
			],
			
			"freight" => [
				"capacity" => 3
			],
			
			"hospital" => [
				"capacity" => 4
			],
			
			"aerodrome" => [
				"capacity" => 5
			],
			
			"cafe" => [
				"capacity" => 3
			],
			
			"autobahn" => [
				"capacity" => 2
			],
			
			"cars" => [
				"capacity" => 2
			],
			
			"obi" => [
				"capacity" => 2
			],
			
			"container" => [
				"capacity" => 1
			],
			
			"doner" => [
				"capacity" => 2
			],
			
			"duke" => [
				"capacity" => 3
			],
			
			"woods" => [
				"capacity" => 2
			],
			
			"mine" => [
				"capacity" => 2
			],
			
			"quarry" => [
				"capacity" => 2
			],
			
			"ufo" => [
				"capacity" => 1
			],
			
			"ekea" => [
				"capacity" => 3
			],
			
			"mczombie" => [
				"capacity" => 2
			],
			
			"plane" => [
				"capacity" => 2
			],
			
			"shed" => [
				"capacity" => 1
			],
			
			"cave2" => [
				"capacity" => 1
			],
			
			"fair" => [
				"capacity" => 2
			],
			
			"house" => [
				"capacity" => 1
			],
			
			"water" => [
				"capacity" => 2
			],
			
			"lab" => [
				"capacity" => 2
			],
			
			"ambulance" => [
				"capacity" => 1
			],
			
			"warehouse" => [
				"capacity" => 5
			],
			
			"carpark" => [
				"capacity" => 3
			],
			
			"motel" => [
				"capacity" => 5
			],
			
			"post" => [
				"capacity" => 2,
                "drops" => [
                    'postal_box_#01_xmas_alt_1' => ['item' => 'postal_box_#01', 'count' => 3, 'mod' => DropMod::EventChristmasAlt1],
                    'postal_box_xl_#00_xmas_alt_2' => ['item' => 'postal_box_xl_#00', 'count' => 3, 'mod' => DropMod::EventChristmasAlt2],
                ]
			],
			
			"dll" => [
				"capacity" => 2
			],
			
			"emma" => [
				"capacity" => 1
			],
			
			"mayor" => [
				"capacity" => 1
			],
			
			"lkw" => [
				"capacity" => 1
			],
			
			"school" => [
				"capacity" => 3
			],
			
			"office" => [
				"capacity" => 4
			],
			
			"villa" => [
				"capacity" => 3
			],
			
			"construction" => [
				"capacity" => 3
			],
			
			"silo" => [
				"capacity" => 3
			],
			
			"street" => [
				"capacity" => 1
			],
			
			"park" => [
				"capacity" => 3
			],
			
			"guns" => [
				"capacity" => 3
			],
			
			"warehouse2" => [
				"capacity" => 4
			],
			
			"pharma" => [
				"capacity" => 2
			],
			
			"bar" => [
				"capacity" => 2
			],
			
			"supermarket" => [
				"capacity" => 4
			],
			
			"tomb" => [
				"capacity" => 1
			],
			
			"well" => [
				"capacity" => 1
			],
			
			"deserted_bunker" => [
				"capacity" => 4
			],
			
			"deserted_hotel" => [
				"capacity" => 4
			],
			
			"deserted_hospital" => [
				"capacity" => 4
			],
			
			"cemetary" => [
				"capacity" => 0
			],			
		]);
    }
}