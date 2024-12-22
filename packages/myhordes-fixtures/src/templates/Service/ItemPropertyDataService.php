<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemPropertyDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            'saw_tool_#00'               => [ 'impoundable', 'can_opener', 'box_opener' ],
            'saw_tool_part_#00'          => [ 'impoundable' ],
            'can_opener_#00'             => [ 'weapon', 'can_opener', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'screw_#00'                  => [ 'weapon', 'can_opener', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'swiss_knife_#00'            => [ 'weapon', 'can_opener', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'wrench_#00'                 => [ 'weapon', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'cutter_#00'                 => [ 'weapon', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'small_knife_#00'            => [ 'weapon', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'bone_#00'                   => [ 'weapon', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'cutcut_#00'                 => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'hurling_stick_#00'          => [ 'weapon', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'chair_basic_#00'            => [ 'box_opener', 'nw_ikea', 'nw_armory', 'parcel_opener_h' ],
            'chair_#00'                  => [ 'nw_ikea' ],
            'staff_#00'                  => [ 'weapon', 'box_opener', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'chain_#00'                  => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'cigs_#00'                   => [ 'impoundable' ],
            'pc_#00'                     => [ 'box_opener', 'nw_ikea', 'nw_armory' ],
            'door_#00'                   => [ 'impoundable', 'defence' ],
            'car_door_#00'               => [ 'impoundable', 'defence' ],
            'car_door_part_#00'          => [ 'impoundable' ],
            'pet_dog_#00'                => [ 'defence', 'pet', 'esc_fixed', 'lock' ],
            'plate_#00'                  => [ 'impoundable', 'defence', 'deco' ],
            'plate_raw_#00'              => [ 'impoundable' ],
            'torch_#00'                  => [ 'impoundable', 'defence', 'weapon', 'nw_ikea', 'nw_armory', 'prevent_night' ],
            'tekel_#00'                  => [ 'defence', 'lock', 'pet' ],
            'trestle_#00'                => [ 'impoundable', 'defence' ],
            'table_#00'                  => [ 'impoundable', 'defence', 'nw_ikea' ],
            'bed_#00'                    => [ 'impoundable', 'defence', 'nw_ikea' ],
            'wood_plate_#00'             => [ 'impoundable', 'defence', 'deco' ],
            'concrete_#00'               => [ 'impoundable' ],
            'concrete_wall_#00'          => [ 'impoundable', 'defence' ],
            'wood_bad_#00'               => [ 'impoundable', 'ressource' ],
            'metal_bad_#00'              => [ 'impoundable', 'ressource' ],
            'wood2_#00'                  => [ 'impoundable', 'ressource', 'hero_find' ],
            'metal_#00'                  => [ 'impoundable', 'ressource', 'hero_find' ],
            'wood_beam_#00'              => [ 'impoundable', 'ressource' ],
            'metal_beam_#00'             => [ 'impoundable', 'ressource' ],
            'courroie_#00'               => [ 'impoundable', 'ressource' ],
            'deto_#00'                   => [ 'impoundable', 'ressource' ],
            'tube_#00'                   => [ 'impoundable', 'ressource' ],
            'rustine_#00'                => [ 'impoundable', 'ressource' ],
            'electro_#00'                => [ 'impoundable', 'ressource' ],
            'meca_parts_#00'             => [ 'impoundable', 'ressource' ],
            'explo_#00'                  => [ 'impoundable', 'ressource' ],
            'pumpkin_on_#00'             => [ 'impoundable', 'defence' ],
            'pumpkin_off_#00'            => [ 'impoundable' ],
            'pumpkin_raw_#00'            => [ 'impoundable' ],
            'mecanism_#00'               => [ 'ressource', 'hero_find_lucky' ],
            'grenade_#00'                => [ 'weapon', 'hero_find', 'nw_armory', 'hero_find_lucky', 'nw_shooting' ],
            'bgrenade_#00'               => [ 'weapon', 'nw_armory', 'nw_shooting' ],
            'boomfruit_#00'              => [ 'weapon', 'nw_armory' ],
            'pilegun_#00'                => [ 'weapon', 'nw_armory' ],
            'pilegun_up_#00'             => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
            'pilegun_up_empty_#00'       => [ 'impoundable' ],
            'pilegun_upkit_#00'          => [ 'impoundable' ],
            'big_pgun_#00'               => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
            'big_pgun_empty_#00'         => [ 'impoundable' ],
            'big_pgun_part_#00'          => [ 'impoundable' ],
            'mixergun_#00'               => [ 'weapon', 'nw_armory' ],
            'chainsaw_#00'               => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory', 'parcel_opener_h' ],
            'chainsaw_empty_#00'         => [ 'impoundable' ],
            'chainsaw_part_#00'          => [ 'impoundable' ],
            'taser_#00'                  => [ 'weapon', 'nw_armory' ],
            'lpoint4_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
            'lpoint3_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
            'lpoint2_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
            'lpoint1_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
            'watergun_opt_5_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
            'watergun_opt_4_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
            'watergun_opt_3_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
            'watergun_opt_2_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
            'watergun_opt_1_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
            'watergun_opt_part_#00'      => [ 'impoundable' ],
            'watergun_opt_empty_#00'     => [ 'impoundable' ],
            'kalach_#00'                 => [ 'impoundable', 'nw_shooting', 'nw_armory' ],
            'watergun_3_#00'             => [ 'weapon', 'nw_shooting', 'nw_armory' ],
            'watergun_2_#00'             => [ 'weapon', 'nw_shooting', 'nw_armory' ],
            'watergun_1_#00'             => [ 'weapon', 'nw_shooting', 'nw_armory' ],
            'jerrygun_#00'               => [ 'impoundable', 'weapon', 'esc_fixed' ],
            'jerrygun_part_#00'          => [ 'impoundable' ],
            'jerrygun_off_#00'           => [ 'impoundable' ],
            'jerrycan_#00'               => [ 'impoundable', 'hero_find_lucky' ],
            'knife_#00'                  => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory', 'parcel_opener', 'parcel_opener_h' ],
            'lawn_#00'                   => [ 'weapon', 'nw_armory' ],
            'torch_off_#00'              => [ 'weapon', 'nw_armory' ],
            'iphone_#00'                 => [ 'weapon', 'nw_armory' ],
            'machine_1_#00'              => [ 'esc_fixed', 'nw_ikea', 'nw_armory', 'impoundable' ],
            'machine_2_#00'              => [ 'esc_fixed', 'nw_ikea', 'nw_armory', 'impoundable' ],
            'machine_3_#00'              => [ 'esc_fixed', 'nw_ikea', 'nw_armory', 'impoundable' ],
            'disinfect_#00'              => [ 'impoundable', 'drug' ],
            'drug_#00'                   => [ 'can_poison', 'impoundable', 'drug' ],
            'drug_hero_#00'              => [ 'impoundable', 'drug', 'esc_fixed' ],
            'drug_random_#00'            => [ 'drug' ],
            'beta_drug_bad_#00'          => [ 'drug' ],
            'beta_drug_#00'              => [ 'impoundable', 'drug' ],
            'xanax_#00'                  => [ 'drug', 'hero_find_lucky' ],
            'drug_water_#00'             => [ 'drug' ],
            'bandage_#00'                => [ 'impoundable', 'drug' ],
            'pharma_#00'                 => [ 'impoundable', 'drug' ],
            'pharma_part_#00'            => [ 'drug' ],
            'lsd_#00'                    => [ 'impoundable', 'drug' ],
            'april_drug_#00'             => [ 'drug', 'can_cook' ],
            'fungus_#00'                 => [ 'food', 'inedible', 'can_cook' ],
            'radio_on_#00'               => [ 'impoundable', 'nw_ikea' ],
            'water_#00'                  => [ 'impoundable', 'can_poison', 'hero_find', 'esc_fixed', 'hero_find_lucky',  'found_poisoned', 'is_water' ],
            'can_open_#00'               => [ 'can_poison', 'food', 'can_cook', 'single_use' ],
            'vegetable_#00'              => [ 'can_poison', 'food', 'can_cook', 'single_use' ],
            'fruit_#00'                  => [ 'can_poison', 'food', 'can_cook', 'single_use' ],
            'water_can_3_#00'            => [ 'impoundable','esc_fixed', 'is_water' ],
            'water_can_2_#00'            => [ 'impoundable','esc_fixed', 'is_water' ],
            'water_can_1_#00'            => [ 'impoundable','esc_fixed', 'is_water' ],
            'water_can_empty_#00'        => [ 'impoundable' ],
            'water_cup_#00'              => [ 'is_water', 'esc_fixed' ],
            'potion_#00'                 => [ 'impoundable', 'is_water' ],
            'lock_#00'                   => [ 'lock' ],
            'dfhifi_#01'                 => [ 'lock' ],
            'pile_#00'                   => [ 'impoundable', 'hero_find', 'hero_find_lucky' ],
            'food_bag_#00'               => [ 'hero_find' ],
            'rsc_pack_3_#00'             => [ 'impoundable', 'hero_find_lucky' ],
            'rsc_pack_2_#00'             => [ 'impoundable', 'hero_find' ],
            'rsc_pack_1_#00'             => [ 'impoundable' ],
            'bretz_#00'                  => [ 'food', 'can_cook', 'single_use' ],
            'undef_#00'                  => [ 'food', 'can_cook', 'single_use' ],
            'dish_#00'                   => [ 'food', 'single_use' ],
            'chama_#00'                  => [ 'food', 'can_cook', 'single_use' ],
            'food_bar1_#00'              => [ 'food', 'can_cook', 'single_use' ],
            'food_bar2_#00'              => [ 'food', 'can_cook', 'single_use' ],
            'food_bar3_#00'              => [ 'food', 'can_cook', 'single_use' ],
            'food_biscuit_#00'           => [ 'food', 'can_cook', 'single_use' ],
            'food_chick_#00'             => [ 'food', 'can_cook', 'single_use' ],
            'food_pims_#00'              => [ 'food', 'can_cook', 'single_use' ],
            'food_tarte_#00'             => [ 'food', 'can_cook', 'single_use' ],
            'food_sandw_#00'             => [ 'food', 'can_cook', 'single_use' ],
            'food_noodles_#00'           => [ 'food', 'can_cook', 'single_use' ],
            'hmeat_#00'                  => [ 'food', 'can_cook', 'single_use' ],
            'bone_meat_#00'              => [ 'food', 'can_cook', 'single_use' ],
            'cadaver_#00'                => [ 'food', 'can_cook', 'single_use' ],
            'food_noodles_hot_#00'       => [ 'food', 'esc_fixed', 'single_use' ],
            'meat_#00'                   => [ 'food', 'esc_fixed', 'hero_find_lucky', 'single_use' ],
            'vegetable_tasty_#00'        => [ 'food', 'esc_fixed', 'single_use' ],
            'dish_tasty_#00'             => [ 'impoundable', 'food', 'esc_fixed', 'single_use' ],
            'food_candies_#00'           => [ 'food', 'esc_fixed', 'single_use' ],
            'chama_tasty_#00'            => [ 'food', 'esc_fixed', 'single_use' ],
            'woodsteak_#00'              => [ 'food', 'esc_fixed', 'single_use' ],
            'egg_#00'                    => [ 'food', 'esc_fixed', 'single_use' ],
            'apple_#00'                  => [ 'food', 'esc_fixed', 'single_use' ],
            'angryc_#00'                 => [ 'pet', 'weapon' ],
            'pet_cat_#00'                => [ 'pet', 'nw_trebuchet' ],
            'pet_chick_#00'              => [ 'pet', 'nw_trebuchet' ],
            'pet_pig_#00'                => [ 'pet', 'nw_trebuchet' ],
            'pet_rat_#00'                => [ 'pet', 'nw_trebuchet' ],
            'pet_snake_#00'              => [ 'pet', 'nw_trebuchet' ],
            'pet_snake2_#00'             => [ 'pet' ],
            'renne_#00'                  => [ 'impoundable', 'nw_trebuchet' ],
            'book_gen_letter_#00'        => [ 'esc_fixed' ],
            'book_gen_box_#00'           => [ 'esc_fixed' ],
            'postal_box_#00'             => [ 'esc_fixed' ],
            'postal_box_#01'             => [ 'esc_fixed' ],
            'pocket_belt_#00'            => [ 'esc_fixed', 'no_post' ],
            'bag_#00'                    => [ 'esc_fixed', 'no_post' ],
            'bagxl_#00'                  => [ 'esc_fixed', 'no_post' ],
            'cart_#00'                   => [ 'esc_fixed', 'no_post' ],
            'radius_mk2_#00'             => [ 'impoundable', 'esc_fixed' ],
            'radius_mk2_part_#00'        => [ 'impoundable' ],
            'rp_book_#00'                => [ 'esc_fixed' ],
            'rp_book_#01'                => [ 'esc_fixed' ],
            'rp_book2_#00'               => [ 'esc_fixed' ],
            'rp_scroll_#00'              => [ 'esc_fixed' ],
            'rp_scroll_#01'              => [ 'esc_fixed' ],
            'rp_sheets_#00'              => [ 'esc_fixed' ],
            'rp_letter_#00'              => [ 'esc_fixed' ],
            'rp_manual_#00'              => [ 'esc_fixed' ],
            'lilboo_#00'                 => [ 'esc_fixed', 'prevent_terror' ],
            'lights_#00'                 => [ 'impoundable' ],
            'rp_twin_#00'                => [ 'esc_fixed' ],
            'home_box_#00'               => [ 'impoundable', 'nw_ikea' ],
            'home_box_xl_#00'            => [ 'impoundable' ],
            'lamp_#00'                   => [ 'nw_ikea' ],
            'lamp_on_#00'                => [ 'nw_ikea', 'prevent_night' ],
            'music_#00'                  => [ 'nw_ikea' ],
            'distri_#00'                 => [ 'nw_ikea' ],
            'guitar_#00'                 => [ 'impoundable', 'nw_ikea' ],
            'bureau_#00'                 => [ 'nw_ikea' ],
            'rlaunc_#00'                 => [ 'impoundable', 'nw_armory', 'weapon' ],
            'repair_one_#00'             => [ 'impoundable', 'hero_find_lucky' ],
            'electro_box_#00'            => [ 'hero_find_lucky' ],
            'christmas_candy_#00'        => [ 'food', 'can_cook', 'single_use' ],
            'omg_this_will_kill_you_#00' => [ 'can_cook' ],
            'chudol_#00'                 => [ 'prevent_terror' ],
            'maglite_1_#00'              => [ 'prevent_night' ],
            'maglite_2_#00'              => [ 'prevent_night' ],
            'wood_xmas_#00'              => [ 'food', 'single_use' ],
            'fence_#00'                  => [ 'impoundable' ],
            'engine_#00'                 => [ 'impoundable' ],
            'engine_part_#00'            => [ 'impoundable' ],
            'repair_kit_#00'             => [ 'impoundable' ],
            'repair_kit_part_#00'        => [ 'impoundable' ],
            'repair_kit_part_raw_#00'    => [ 'impoundable' ],
            'home_def_#00'               => [ 'impoundable' ],
            'firework_box_#00'           => [ 'impoundable' ],
            'firework_powder_#00'        => [ 'impoundable' ],
            'firework_tube_#00'          => [ 'impoundable' ],
            'badge_#00'                  => [ 'impoundable' ],
            'wood_log_#00'               => [ 'impoundable' ],
            'chkspk_#00'                 => [ 'impoundable' ],
            'claymo_#00'                 => [ 'impoundable' ],
            'paques_#00'                 => [ 'impoundable' ],
            'trapma_#00'                 => [ 'impoundable', 'nosteal' ],
            'christmas_suit_full_#00'    => [ 'impoundable' ],
            'christmas_suit_1_#00'       => [ 'impoundable' ],
            'christmas_suit_2_#00'       => [ 'impoundable' ],
            'christmas_suit_3_#00'       => [ 'impoundable' ],
            'leprechaun_suit_#00'        => [ 'impoundable' ],
            'bplan_box_#00'              => [ 'impoundable' ],
            'bplan_box_e_#00'            => [ 'impoundable' ],
            'bplan_drop_#00'             => [ 'impoundable' ],
            'bplan_c_#00'                => [ 'impoundable' ],
            'bplan_u_#00'                => [ 'impoundable' ],
            'bplan_r_#00'                => [ 'impoundable' ],
            'bplan_e_#00'                => [ 'impoundable' ],
            'hbplan_u_#00'               => [ 'impoundable' ],
            'hbplan_r_#00'               => [ 'impoundable' ],
            'hbplan_e_#00'               => [ 'impoundable' ],
            'bbplan_u_#00'               => [ 'impoundable' ],
            'bbplan_r_#00'               => [ 'impoundable' ],
            'bbplan_e_#00'               => [ 'impoundable' ],
            'mbplan_u_#00'               => [ 'impoundable' ],
            'mbplan_r_#00'               => [ 'impoundable' ],
            'mbplan_e_#00'               => [ 'impoundable' ],
            'noodle_prints_#00'          => [ 'food', 'can_cook', 'single_use' ],
            'noodle_prints_#01'          => [ 'food', 'can_cook', 'single_use' ],
            'noodle_prints_#02'          => [ 'food', 'can_cook', 'single_use' ],
        ]);
    }
}
