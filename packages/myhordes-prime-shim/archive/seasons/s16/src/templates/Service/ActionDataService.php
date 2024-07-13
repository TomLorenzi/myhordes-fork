<?php

namespace MyHordes\Prime\Service;

use App\Entity\ActionCounter;
use App\Entity\AffectItemSpawn;
use App\Entity\CauseOfDeath;
use App\Entity\ItemAction;
use App\Entity\ItemTargetDefinition;
use App\Entity\RequireLocation;
use App\Entity\Requirement;
use App\Enum\ActionHandler\PointType;
use App\Enum\ItemPoisonType;
use App\Structures\TownConf;
use MyHordes\Fixtures\DTO\Actions\Atoms\PointRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsDataContainer;
use MyHordes\Fixtures\DTO\ArrayDecoratorReadInterface;
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
        unset($data['actions']['bp_hotel_2']);
        unset($data['actions']['bp_hotel_3']);
        unset($data['actions']['bp_hotel_4']);
        unset($data['actions']['bp_bunker_2']);
        unset($data['actions']['bp_bunker_3']);
        unset($data['actions']['bp_bunker_4']);
        unset($data['actions']['bp_hospital_2']);
        unset($data['actions']['bp_hospital_3']);
        unset($data['actions']['bp_hospital_4']);

        $requirement_container = new RequirementsDataContainer();
        $requirement_container->add()->identifier('min_2_cp')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::CP)->min(2) )->text_key('pt_required')->commit();
        $requirement_container->add()->identifier('min_3_cp')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::CP)->min(3) )->text_key('pt_required')->commit();

        $data = array_replace_recursive($data, [
            'meta_requirements' => [],

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
                'bp_hotel_2'    => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['item_pumpkin_raw_#00', 'small_score_#00','small_strategy_#02', 'item_rp_book2_#00', 'small_canon_#01', 'small_wallimprove_#03'] ] ],                     'message_key' => 'read_blueprint' ],
                'bp_hotel_3'    => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['small_valve_#00', 'small_appletree_#00', 'small_scarecrow_#00', 'small_ikea_#00', 'small_moving_#00', 'small_labyrinth_#00', 'item_tamed_pet_#00', 'item_plate_#01', 'small_court_#00', 'small_coffin_#00'] ] ], 'message_key' => 'read_blueprint' ],
                'bp_hotel_4'    => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['small_waterdetect_#00', 'small_pmvbig_#00', 'small_wheel_#00', 'small_cinema_#00', 'small_strategy_#01'] ] ],                                              'message_key' => 'read_blueprint' ],

                'bp_bunker_2'   => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['small_rocketperf_#00', 'small_watercanon_#00', 'item_bgrenade_#00', 'item_courroie_#01', 'item_hmeat_#00', 'small_city_up_#00'] ] ],                                             'message_key' => 'read_blueprint' ],
                'bp_bunker_3'   => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['item_tube_#01', 'item_boomfruit_#00', 'item_pet_pig_#00', 'small_watchmen_#00', 'item_cutcut_#00', 'small_underground_#00', 'small_rocket_#00', 'item_keymol_#00', 'item_home_def_#00', 'small_coffin_#00'] ] ], 'message_key' => 'read_blueprint' ],
                'bp_bunker_4'   => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['small_water_#02', 'small_castle_#00', 'small_arma_#00', 'small_slave_#00', 'small_strategy_#01'] ] ],                                                                                         'message_key' => 'read_blueprint' ],

                'bp_hospital_2' => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['item_plate_#04', 'small_eden_#00', 'small_chicken_#00', 'small_cemetery_#00', 'small_spa4souls_#00', 'small_saw_#00'] ] ],                                                                            'message_key' => 'read_blueprint' ],
                'bp_hospital_3' => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['item_digger_#00', 'item_boomfruit_#01', 'small_watchmen_#01', 'small_tourello_#01', 'small_falsecity_#00', 'small_trashclean_#00', 'small_infirmary_#00', 'item_surv_book_#00', 'small_sprinkler_#00', 'small_coffin_#00'] ] ], 'message_key' => 'read_blueprint' ],
                'bp_hospital_4' => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside_bp' ], 'result' => [ 'consume_item', ['bp' => ['small_derrick_#01', 'small_crow_#00', 'small_pmvbig_#00', 'small_trash_#06', 'small_balloon_#00'] ] ],                                                                                                'message_key' => 'read_blueprint' ],
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

        $data['meta_requirements'] = array_merge_recursive(
            $data['meta_requirements'],
            $requirement_container->toArray()
        );

        array_walk_recursive( $data, fn(&$value) => is_a( $value, ArrayDecoratorReadInterface::class ) ? $value = $value->toArray() : $value );
    }
}