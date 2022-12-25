<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemGroupDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            'empty_dig' => array(
                'wood_bad_#00'              => 43250,
                'metal_bad_#00'             => 22000,
            ),
            'base_dig' => array(
                // Ressources
                'wood2_#00'                 => 17300,
                'metal_#00'                 => 9875,
                'wood_beam_#00'             => 1176,
                'metal_beam_#00'            => 592,
                'pile_#00'                  => 4766,
                'pharma_#00'                => 3935,
                'meca_parts_#00'            => 964,
                'rustine_#00'               => 2578,
                'jerrycan_#00'              => 1456,
                'explo_#00'                 => 771,
                'tube_#00'                  => 1184,
                'electro_#00'               => 427,
                'engine_part_#00'           => 193,
                'courroie_#00'              => 179,
                'deto_#00'                  => 935,
                'fence_#00'                 => 293,
                'rsc_pack_2_#00'            => 290,
                'rsc_pack_3_#00'            => 89,

                // Weapons
                'grenade_empty_#00'         => 6915,
                'staff_#00'                 => 1138,
                'watergun_empty_#00'        => 953,
                'cutter_#00'                => 476,
                'can_opener_#00'            => 469,
                'pilegun_empty_#00'         => 784,
                'knife_#00'                 => 194,
                'screw_#00'                 => 240,
                'small_knife_#00'           => 501,
                'gun_#00'                   => 90,
                'big_pgun_part_#00'         => 95,
                'chain_#00'                 => 493,
                'iphone_#00'                => 112,

                // Food, drugs, bev...
                'food_bag_#00'              => 4845,
                'food_noodles_#00'          => 763,
                'spices_#00'                => 508,
                'can_#00'                   => 2445,
                'meat_#00'                  => 474,
                'drug_hero_#00'             => 634,
                'drug_random_#00'           => 818,
                'disinfect_#00'             => 288,
                'drug_#00'                  => 965,
                'vodka_#00'                 => 200,
                //'vodka_de_#00'            => 6,
                'pet_rat_#00'               => 710,
                'rhum_#00'                  => 499,
                'hmeat_#00'                 => 195,
                'bandage_#00'               => 284,
                'xanax_#00'                 => 418,
                'pet_chick_#00'             => 491,
                'pet_pig_#00'               => 474,
                'pet_snake_#00'             => 877,
                'pet_cat_#00'               => 396,
                'water_cleaner_#00'         => 803,
                'poison_part_#00'           => 90,
                'chama_#00'                 => 94,
                'beta_drug_bad_#00'         => 90,
                'water_can_empty_#00'       => 93,
                'cadaver_#00'               => 102,

                // Boxes
                'chest_#00'                 => 734,
                'chest_tools_#00'           => 1390,
                'chest_citizen_#00'         => 282,
                'chest_xl_#00'              => 90,
                'chest_food_#00'            => 364,
                'electro_box_#00'           => 854,
                'deco_box_#00'              => 1309,
                'mecanism_#00'              => 1063,
                'food_armag_#00'            => 87,
                'safe_#00'                  => 88,

                // Def
                'wood_plate_part_#00'       => 1529,
                'plate_raw_#00'             => 963,
                'plate_#00'                 => 283,
                'door_#00'                  => 841,
                'lock_#00'                  => 369,
                'concrete_#00'              => 1689,
                'trestle_#00'               => 790,
                'home_def_#00'              => 294,
                'car_door_part_#00'         => 95,

                // Misc
                'bag_#00'                   => 808,
                'cart_part_#00'             => 274,
                'repair_kit_part_raw_#00'   => 293,
                'repair_one_#00'            => 907,
                'chair_basic_#00'           => 423,
                'bed_#00'                   => 501,
                'lamp_#00'                  => 288,
                'music_part_#00'            => 210,
                'vibr_empty_#00'            => 189,
                'cyanure_#00'               => 206,
                'coffee_machine_part_#00'   => 103,
                'sport_elec_empty_#00'      => 376,
                'tagger_#00'                => 794,
                'digger_#00'                => 1231,
                'game_box_#00'              => 192,
                'saw_tool_part_#00'         => 90,
                'chair_#00'                 => 273,
                'powder_#00'                => 1159,
                'machine_1_#00'             => 777,
                'machine_2_#00'             => 729,
                'machine_3_#00'             => 794,
                'pc_#00'                    => 215,
                'home_box_#00'              => 302,
                'home_box_xl_#00'           => 109,
                'lights_#00'                => 381,
                'cigs_#00'                  => 181,
                'pilegun_upkit_#00'         => 90,
                'money_#00'                 => 110,
                'wood_log_#00'              => 190,
                'sheet_#00'                 => 115,
                'out_def_#00'               => 180,
                'smelly_meat_#00'           => 102,
                'maglite_off_#00'           => 100,
                'smoke_bomb_#00'            => 814,
                'bplan_drop_#00'            => 800,

                // RP
                'rp_book_#00'               => 90,
                'book_gen_letter_#00'       => 286,
                'book_gen_box_#00'          => 209,
                'postal_box_#00'            => 98,
                'rp_twin_#00'               => 87,
                'badge_#00'                 => 238,

                // Items added Sept 2013
                'wire_#00'                  => 691,
                'oilcan_#00'                => 1100,
                'lens_#00'                  => 366,
                'diode_#00'                 => 416,
                'angryc_#00'                => 342,
                'chudol_#00'                => 347,
                'lilboo_#00'                => 458,
                'ryebag_#00'                => 541,
                'bquies_#00'                => 257,
                'cdelvi_#00'                => 82,
                'cdbrit_#00'                => 92,
                'cdphil_#00'                => 67,
                'catbox_#00'                => 170,
                'pet_snake2_#00'            => 877,
                'cinema_#00'                => 53,

                // Die Verdammten
                'fest_#00'                  => 964,
                'bretz_#00'                 => 775,
                'tekel_#00'                 => 400,
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
                'fence_#00'                 => 76,
                'wood2_#00'                 => 22,
                'metal_#00'                 => 5,
                'repair_one_#00'            => 7,
                'chain_#00'                 => 5,
                'home_box_#00'              => 7,
                'home_def_#00'              => 10,
                'chest_#00'                 => 8,
            ],
            'trash_bad' => [
                'flesh_part_#00'            => 502,
                'fruit_sub_part_#00'        => 337,
                'pharma_part_#00'           => 278,
                'fruit_part_#00'            => 87,
                'water_cup_part_#00'        => 28,
            ],
        ]);
    }
}