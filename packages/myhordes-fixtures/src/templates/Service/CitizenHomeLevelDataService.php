<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class CitizenHomeLevelDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            0 => [ 'label' => 'Feldbett',              'icon' => 'home_lv0', 'def' =>  0, 'ap' => 0, 'resources' => [], 'building' => null, 'upgrades' => false, 'theft' => false ],
            1 => [ 'label' => 'Zelt',                  'icon' => 'home_lv1', 'def' =>  1, 'ap' => 2, 'resources' => [], 'building' => null, 'upgrades' => true,  'theft' => false ],
            2 => [ 'label' => 'Baracke',               'icon' => 'home_lv2', 'def' =>  3, 'ap' => 6, 'resources' => [], 'building' => null, 'upgrades' => true,  'theft' => false ],
            3 => [ 'label' => 'Hütte',                 'icon' => 'home_lv3', 'def' =>  6, 'ap' => 4, 'resources' => ['wood2_#00' => 1], 'building' => null, 'upgrades' => true,  'theft' => false ],
            4 => [ 'label' => 'Haus',                  'icon' => 'home_lv4', 'def' => 10, 'ap' => 6, 'resources' => ['metal_#00' => 1], 'building' => null, 'upgrades' => true,  'theft' => false ],
            5 => [ 'label' => 'Umzäuntes Haus',        'icon' => 'home_lv5', 'def' => 15, 'ap' => 6, 'resources' => ['wood2_#00' => 3, 'metal_#00' => 2], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
            6 => [ 'label' => 'Befestigte Unterkunft', 'icon' => 'home_lv6', 'def' => 25, 'ap' => 7, 'resources' => ['concrete_wall_#00' => 1, 'wood2_#00' => 3, 'metal_#00' => 4], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
            7 => [ 'label' => 'Bunker',                'icon' => 'home_lv7', 'def' => 35, 'ap' => 7, 'resources' => ['meca_parts_#00' => 3, 'concrete_wall_#00' => 2, 'plate_raw_#00' => 1, 'metal_#00' => 6], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
            8 => [ 'label' => 'Schloss',               'icon' => 'home_lv8', 'def' => 55, 'ap' => 7, 'resources' => ['meca_parts_#00' => 5, 'concrete_wall_#00' => 2, 'plate_raw_#00' => 3, 'wood2_#00' => 5, 'metal_#00' => 10], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
        ]);
    }
}