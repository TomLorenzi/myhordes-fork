<?php

namespace MyHordes\Prime\Service;

use App\Entity\ActionCounter;
use App\Entity\AffectItemSpawn;
use App\Entity\CauseOfDeath;
use App\Entity\ItemAction;
use App\Entity\ItemTargetDefinition;
use App\Entity\RequireLocation;
use App\Entity\Requirement;
use App\Enum\ItemPoisonType;
use App\Structures\TownConf;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ActionDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            'meta_requirements' => [
                'min_2_cp' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'cp' => [ 'min' => 2, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 2 CP.'],
                'min_3_cp' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'cp' => [ 'min' => 3, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 3 CP.'],
            ],

            'meta_results' => [
                'minus_2cp'    => [ 'cp' => 'minus_2' ],
                'minus_3cp'    => [ 'cp' => 'minus_3' ],
            ],

            'results' => [
                'cp' => [
                    'minus_2'       => [ 'max' => false, 'num' => -2 ],
                    'minus_3'       => [ 'max' => false, 'num' => -3 ],
                ],
            ],

            'actions' => [
                'repair_hero' => [ 'label' => 'Reparieren (3CP)', 'at00' => true, 'target' => ['broken' => true], 'meta' => [ 'min_3_cp', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'minus_3cp', 'repair_target', ['picto' => ['r_repair_#00'] ] ], 'message' => 'Du hast dein Handwerkstalent gebraucht, um damit {target} zu reparieren. Dabei hast du {minus_cp} CP eingesetzt.' ],
            ],

            'heroics' => [

            ],

            'specials' => [

            ],

            'camping' => [
            ],

            'home' => [

            ],

            'escort' => [

            ],

            'items' => [
                'keymol_#00' => [ 'repair_hero' ],
            ],

            'items_nw' => [
                'hurling_stick_#00' => 'nw_break',
            ],

            'message_keys' => [

            ],
        ]);
    }
}