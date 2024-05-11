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
        unset($data['items_nw']['pet_pig_#00']);
        unset($data['items_nw']['pet_snake_#00']);
        unset($data['items_nw']['iphone_#00']);
        unset($data['items_nw']['pet_chick_#00']);
        unset($data['items_nw']['pet_rat_#00']);
        unset($data['items_nw']['pet_cat_#00']);
        unset($data['items_nw']['lamp_on_#00']);
        unset($data['items_nw']['bone_meat_#00']);
        unset($data['items_nw']['music_#00']);
        unset($data['items_nw']['radio_on_#00']);
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
                'nw_empty_proj'      => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'lens_#00',               'consume' => false]] ] ],
                'nw_empty_lpoint'    => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'lpoint_#00',             'consume' => false]] ] ],
                'nw_empty_jerrygun'  => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'jerrygun_off_#00',       'consume' => false]] ] ],
                'nw_empty_lamp'      => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'lamp_#00',               'consume' => false]] ] ],
                'nw_empty_bone'      => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'bone_#00',               'consume' => false]] ] ],
                'nw_empty_music'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'music_part_#00',         'consume' => false]] ] ],
                'nw_empty_sport'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'sport_elec_empty_#00',   'consume' => false]] ] ],
                'nw_empty_radio'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'radio_off_#00',          'consume' => false]] ] ],
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
                'cinema_#00'        => 'nw_empty_proj',
                'pet_snake2_#00'    => 'nw_destroy',
                'pet_pig_#00'       => 'nw_destroy',
                'pet_snake_#00'     => 'nw_destroy',
                'concrete_wall_#00' => 'nw_break',
                'iphone_#00'        => 'nw_destroy',
                'pet_chick_#00'     => 'nw_destroy',
                'pet_rat_#00'       => 'nw_destroy',
                'pet_cat_#00'       => 'nw_destroy',
                'angryc_#00'        => 'nw_destroy',
                'pet_dog_#00'       => 'nw_destroy',
                'tekel_#00'         => 'nw_destroy',
                'lpoint1_#00'       => 'nw_empty_lpoint',
                'lpoint2_#00'       => 'nw_empty_lpoint',
                'lpoint3_#00'       => 'nw_empty_lpoint',
                'lpoint4_#00'       => 'nw_empty_lpoint',
                'jerrygun_#00'      => 'nw_empty_jerrygun',
                'lamp_on_#00'       => 'nw_empty_lamp',
                'bone_meat_#00'     => 'nw_empty_bone',
                'coffee_#00'        => 'nw_destroy',
                'flash_#00'         => 'nw_destroy',
                'music_#00'         => 'nw_empty_music',
                'sport_elec_#00'    => 'nw_empty_sport',
                'radio_on_#00'      => 'nw_empty_radio',
                'cards_#00'         => 'nw_destroy',
                'dice_#00'          => 'nw_destroy',
                'teddy_#00'         => 'nw_destroy',
                'gun_#00'           => 'nw_destroy',
                'machine_gun_#00'   => 'nw_destroy',
            ],

            'message_keys' => [

            ],
        ]);
    }
}