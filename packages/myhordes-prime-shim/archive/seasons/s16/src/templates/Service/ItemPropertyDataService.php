<?php

namespace MyHordes\Prime\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemPropertyDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        // Properties to remove from the public package
        $remove = [
            'wrench_#00'                 => ['nw_armory'],
            'bone_#00'                   => ['nw_armory'],
            'chair_basic_#00'            => ['nw_armory'],
            'pc_#00'                     => ['nw_armory'],
            'torch_#00'                  => ['nw_ikea', 'nw_armory'],
            'grenade_#00'                => ['nw_armory'],
            'bgrenade_#00'               => ['nw_armory'],
            'boomfruit_#00'              => ['nw_armory'],
            'pilegun_#00'                => ['nw_armory'],
            'pilegun_up_#00'             => ['nw_armory'],
            'big_pgun_#00'               => ['nw_armory'],
            'mixergun_#00'               => ['nw_armory'],
            'taser_#00'                  => ['nw_armory'],
            'lpoint4_#00'                => ['nw_armory'],
            'lpoint3_#00'                => ['nw_armory'],
            'lpoint2_#00'                => ['nw_armory'],
            'lpoint1_#00'                => ['nw_armory'],
            'watergun_opt_5_#00'         => ['nw_armory'],
            'watergun_opt_4_#00'         => ['nw_armory'],
            'watergun_opt_3_#00'         => ['nw_armory'],
            'watergun_opt_2_#00'         => ['nw_armory'],
            'watergun_opt_1_#00'         => ['nw_armory'],
            'kalach_#00'                 => ['nw_armory'],
            'watergun_3_#00'             => ['nw_armory'],
            'watergun_2_#00'             => ['nw_armory'],
            'watergun_1_#00'             => ['nw_armory'],
            'torch_off_#00'              => ['nw_armory'],
            'iphone_#00'                 => ['nw_armory'],
            'machine_1_#00'              => ['nw_armory'],
            'machine_2_#00'              => ['nw_armory'],
            'machine_3_#00'              => ['nw_armory'],
            'home_box_#00'               => ['nw_ikea'],
        ];

        // Properties to add in addition to the public package
        $add = [
            'door_#00'                   => ['nw_ikea'],
            'pet_dog_#00'                => ['nw_trebuchet'],
            'tekel_#00'                  => ['nw_trebuchet'],
            'trestle_#00'                => ['nw_ikea'],
            'jerrygun_#00'               => ['nw_shooting'],
            'water_can_3_#00'            => ['nw_shooting'],
            'water_can_2_#00'            => ['nw_shooting'],
            'water_can_1_#00'            => ['nw_shooting'],
            'hmeat_#00'                  => ['nw_trebuchet'],
            'bone_meat_#00'              => ['nw_trebuchet'],
            'angryc_#00'                 => ['nw_trebuchet'],
            'pet_snake2_#00'             => ['nw_trebuchet'],
            'cart_#00'                   => ['nw_ikea'],
            'badge_#00'                  => ['nw_ikea'],
            'flash_#00'                  => ['nw_armory'],
            'gun_#00'                    => ['nw_armory'],
            'machine_gun_#00'            => ['nw_armory'],
            'cinema_#00'                 => ['nw_ikea'],
            'deco_box_#00'               => ['nw_ikea'],
            'potion_#00'                 => ['esc_fixed'],
            'rlaunc_#00'                 => ['esc_fixed'],
            'kalach_#00'                 => ['esc_fixed'],
            'kalach_#01'                 => ['esc_fixed'],
            'claymo_#00'                 => ['esc_fixed'],
			'taser_#00'					 => ['nw_impact_cumul'],
			'lamp_on_#00'				 => ['nw_impact_cumul'],
			'coffee_#00'				 => ['nw_impact_cumul'],
        ];

        foreach ($remove as $element => $properties)
            if (array_key_exists( $element, $data ))
                $data[ $element ] = array_filter( $data[ $element ], fn(string $s) => !in_array( $s, $properties ) );

        foreach ($add as $element => $properties)
            $data[ $element ] = array_unique( array_merge( $data[ $element ] ?? [], $properties ) );
    }
}
