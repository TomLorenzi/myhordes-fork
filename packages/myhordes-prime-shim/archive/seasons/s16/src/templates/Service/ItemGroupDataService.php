<?php

namespace MyHordes\Prime\Service;

use App\Enum\DropMod;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemGroupDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_replace_recursive($data, [
            'base_dig' => array(
                // Inline christmas dig
                'christmas_suit_1_#00_xmas_alt_2' => ['item' => 'christmas_suit_1_#00', 'count' =>  8, 'mod' => DropMod::EventChristmasAlt2],
                'christmas_suit_2_#00_xmas_alt_2' => ['item' => 'christmas_suit_2_#00', 'count' =>  7, 'mod' => DropMod::EventChristmasAlt2],
                'christmas_suit_3_#00_xmas_alt_2' => ['item' => 'christmas_suit_3_#00', 'count' =>  6, 'mod' => DropMod::EventChristmasAlt2],
                'sand_ball_#00_xmas_alt_1'        => ['item' => 'sand_ball_#00'       , 'count' => 10, 'mod' => DropMod::EventChristmasAlt1],
                'renne_#00_xmas_alt_2'            => ['item' => 'renne_#00'           , 'count' => 10, 'mod' => DropMod::EventChristmasAlt2],
                'food_xmas_#00_xmas_alt_2'        => ['item' => 'food_xmas_#00'       , 'count' =>  5, 'mod' => DropMod::EventChristmasAlt2],
            ),
        ]);
    }
}