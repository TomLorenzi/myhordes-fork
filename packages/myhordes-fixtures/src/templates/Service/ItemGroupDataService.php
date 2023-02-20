<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemGroupDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            'empty_dig' => array(
                'wood_bad_#00'              => 20,
                'metal_bad_#00'             => 12,
            ),
            'base_dig' => array(
                // Ressources
                'wood2_#00'                 => 170,
                'metal_#00'                 => 105,
                'wood_beam_#00'             => 12,
                'metal_beam_#00'            => 6,
                'pile_#00'                  => 50,
                'pharma_#00'                => 40,
                'meca_parts_#00'            => 10,
                'rustine_#00'               => 27,
                'jerrycan_#00'              => 15,
                'explo_#00'                 => 9,
                'tube_#00'                  => 12,
                'electro_#00'               => 5,
                'engine_part_#00'           => 2,
                'courroie_#00'              => 2,
                'deto_#00'                  => 9,
                'fence_#00'                 => 3,
                'rsc_pack_2_#00'            => 3,
                'rsc_pack_3_#00'            => 1,

                // Weapons
                'grenade_empty_#00'         => 70,
                'staff_#00'                 => 10,
                'watergun_empty_#00'        => 10,
                'cutter_#00'                => 5,
                'can_opener_#00'            => 5,
                'pilegun_empty_#00'         => 8,
                'knife_#00'                 => 2,
                'screw_#00'                 => 3,
                'small_knife_#00'           => 5,
                'gun_#00'                   => 1,
                'big_pgun_part_#00'         => 1,
                'chain_#00'                 => 5,
                'iphone_#00'                => 1,

                // Food, drugs, bev...
                'food_bag_#00'              => 50,
                'food_noodles_#00'          => 8,
                'spices_#00'                => 5,
                'can_#00'                   => 25,
                'meat_#00'                  => 5,
                'drug_hero_#00'             => 6,
                'drug_random_#00'           => 8,
                'disinfect_#00'             => 3,
                'drug_#00'                  => 10,
                'vodka_#00'                 => 7, //original : 10 (0,8446%)
                //'vodka_de_#00'            => 10,
                'pet_rat_#00'               => 7,
                'rhum_#00'                  => 3, //original : 3 (0,4223%)
                'hmeat_#00'                 => 2,
                'bandage_#00'               => 3,
                'xanax_#00'                 => 4,
                'pet_chick_#00'             => 5,
                'pet_pig_#00'               => 5,
                'pet_snake_#00'             => 8,
                'pet_cat_#00'               => 4,
                'water_cleaner_#00'         => 8,
                'poison_part_#00'           => 1,
                'chama_#00'                 => 1,
                'beta_drug_bad_#00'         => 1,
                'water_can_empty_#00'       => 1,
                'cadaver_#00'               => 1,

                // Boxes
                'chest_#00'                 => 8,
                'chest_tools_#00'           => 15,
                'chest_citizen_#00'         => 3,
                'chest_xl_#00'              => 1,
                'chest_food_#00'            => 4,
                'electro_box_#00'           => 9,
                'deco_box_#00'              => 13,
                'mecanism_#00'              => 10,
                'food_armag_#00'            => 1,
                'safe_#00'                  => 1,

                // Def
                'wood_plate_part_#00'       => 16,
                'plate_raw_#00'             => 10,
                'plate_#00'                 => 3,
                'door_#00'                  => 9,
                'lock_#00'                  => 4,
                'concrete_#00'              => 17,
                'trestle_#00'               => 8,
                'home_def_#00'              => 3,
                'car_door_part_#00'         => 1,

                // Misc
                'bag_#00'                   => 12,
                'cart_part_#00'             => 3,
                'repair_kit_part_raw_#00'   => 3,
                'repair_one_#00'            => 9,
                'chair_basic_#00'           => 4,
                'bed_#00'                   => 5,
                'lamp_#00'                  => 3,
                'music_part_#00'            => 2,
                'vibr_empty_#00'            => 2,
                'cyanure_#00'               => 2,
                'coffee_machine_part_#00'   => 1,
                'sport_elec_empty_#00'      => 4,
                'tagger_#00'                => 8,
                'digger_#00'                => 12,
                'game_box_#00'              => 2,
                'saw_tool_part_#00'         => 1,
                'chair_#00'                 => 3,
                'powder_#00'                => 12,
                'machine_1_#00'             => 8,
                'machine_2_#00'             => 8,
                'machine_3_#00'             => 8,
                'pc_#00'                    => 2,
                'home_box_#00'              => 3,
                'home_box_xl_#00'           => 1,
                'lights_#00'                => 4,
                'cigs_#00'                  => 2,
                'pilegun_upkit_#00'         => 1,
                'money_#00'                 => 1,
                'wood_log_#00'              => 2,
                'sheet_#00'                 => 1,
                'out_def_#00'               => 2,
                'smelly_meat_#00'           => 1,
                'maglite_off_#00'           => 1,
                'smoke_bomb_#00'            => 9,
                'bplan_drop_#00'            => 15,

                // RP
                'rp_book_#00'               => 1,
                'book_gen_letter_#00'       => 3,
                'book_gen_box_#00'          => 2,
                'postal_box_#00'            => 1,
                'rp_twin_#00'               => 1,
                'badge_#00'                 => 3,

                // Items added Sept 2013
                'wire_#00'                  => 8,
                'oilcan_#00'                => 12,
                'lens_#00'                  => 4,
                'diode_#00'                 => 5,
                'angryc_#00'                => 4,
                'chudol_#00'                => 4,
                'lilboo_#00'                => 5,
                'ryebag_#00'                => 6,
                'bquies_#00'                => 3,
                'cdelvi_#00'                => 1,
                'cdbrit_#00'                => 1,
                'cdphil_#00'                => 1,
                'catbox_#00'                => 2,
                'pet_snake2_#00'            => 4,
                'cinema_#00'                => 1,

                // Die Verdammten
                'fest_#00'                  => 4, //original : 10 (0,8446%)
                'bretz_#00'                 => 8,
                'tekel_#00'                 => 8,
            ),
            'christmas_dig' => [
                'christmas_suit_1_#00'      => 8,
                'christmas_suit_2_#00'      => 7,
                'christmas_suit_3_#00'      => 6,
                'sand_ball_#00'             => 10,
                'renne_#00'                 => 10,
                'food_xmas_#00'             => 5,
            ],
            'christmas_dig_post' => [
                'postal_box_#01'            => 3,
                'postal_box_xl_#00'         => 1,
            ],
            'easter_dig' => [
                'paques_#00'                => 207,
            ],
            'stpatrick_dig' => [
                'hurling_stick_#00'         => 25,
                'leprechaun_suit_#00'       => 7,
                'guiness_#00'               => 20,
            ],
            'stpatrick_dig_fair' => [
                'hurling_stick_#00'         => 2,
            ],
            'halloween_dig' => [
                'pumpkin_raw_#00'           => 5,
            ],
            'trash_good' => [
                'fence_#00'                 => 10,
                'wood2_#00'                 => 3,
                'metal_#00'                 => 1,
                'repair_one_#00'            => 1,
                'chain_#00'                 => 1,
                'home_box_#00'              => 1,
                'home_def_#00'              => 1,
                'chest_#00'                 => 1,
                'pharma_#00'                => 1,
            ],
            'trash_bad' => [
                'flesh_part_#00'            => 70,
                'fruit_sub_part_#00'        => 55,
                'pharma_part_#00'           => 40,
                'fruit_part_#00'            => 15,
                'water_cup_part_#00'        => 3,
            ],
        ]);
    }
}