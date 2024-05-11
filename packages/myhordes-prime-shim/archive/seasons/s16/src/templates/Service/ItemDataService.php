<?php

namespace MyHordes\Prime\Service;

use MyHordes\Fixtures\DTO\Items\ItemPrototypeDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $container = new ItemPrototypeDataContainer($data);

        // Modify heavy state
        $container
            ->modify('rlaunc_#00')->heavy(true)->commit()
            ->modify('distri_#00')->heavy(true)->commit();

        // Modify item names
        $container
            ->modify('fest_#00')->label('Abgestandenes Bier')->commit()
            ->modify('tekel_#00')->label('Räudiger Dackel')->commit()
            ->modify('cinema_#00')->label('Antiker Videoprojektor')->commit()
            ->modify('bretz_#00')->label('Sandige Bretzel')->commit()
            ->modify('vodka_de_#00')->label('Grüne Bierflasche (prime)')->commit()
            ->modify('guiness_#00')->label('Klebriges Pint')->commit()
            ->modify('badge_#00')->label('Rostiges Abzeichen')->commit()
            ->modify('hurling_stick_#00')->label('Primitiver Hurlingstock')->commit();

        // Modify watchpoints
		$container->modify('pc_#00')->watchpoint(15)->commit()
		    ->modify('watergun_1_#00')->watchpoint(2)->commit()
		    ->modify('watergun_2_#00')->watchpoint(4)->commit()
		    ->modify('watergun_3_#00')->watchpoint(6)->commit()
		    ->modify('watergun_opt_1_#00')->watchpoint(2)->commit()
		    ->modify('watergun_opt_2_#00')->watchpoint(4)->commit()
		    ->modify('watergun_opt_3_#00')->watchpoint(6)->commit()
		    ->modify('watergun_opt_4_#00')->watchpoint(9)->commit()
		    ->modify('watergun_opt_5_#00')->watchpoint(12)->commit()
		    ->modify('chair_basic_#00')->watchpoint(10)->commit()
		    ->modify('cinema_#00')->watchpoint(10)->commit()
		    ->modify('machine_2_#00')->watchpoint(15)->commit()
		    ->modify('machine_3_#00')->watchpoint(15)->commit()
		    ->modify('machine_1_#00')->watchpoint(15)->commit()
		    ->modify('concrete_wall_#00')->watchpoint(17)->commit()
		    ->modify('lawn_#00')->watchpoint(20)->commit()
		    ->modify('pet_snake2_#00')->watchpoint(15)->commit()
		    ->modify('pet_pig_#00')->watchpoint(25)->commit()
		    ->modify('pet_snake_#00')->watchpoint(25)->commit()
		    ->modify('small_knife_#00')->watchpoint(5)->commit()
		    ->modify('wrench_#00')->watchpoint(5)->commit()
		    ->modify('swiss_knife_#00')->watchpoint(5)->commit()
		    ->modify('staff_#00')->watchpoint(5)->commit()
		    ->modify('bone_#00')->watchpoint(5)->commit()
		    ->modify('can_opener_#00')->watchpoint(5)->commit()
		    ->modify('torch_off_#00')->watchpoint(5)->commit()
		    ->modify('screw_#00')->watchpoint(5)->commit()
		    ->modify('cutter_#00')->watchpoint(7)->commit()
		    ->modify('chain_#00')->watchpoint(7)->commit()
		    ->modify('knife_#00')->watchpoint(10)->commit()
		    ->modify('cutcut_#00')->watchpoint(15)->commit()
		    ->modify('iphone_#00')->watchpoint(5)->commit()
		    ->modify('grenade_#00')->watchpoint(8)->commit()
		    ->modify('pet_chick_#00')->watchpoint(8)->commit()
		    ->modify('pet_rat_#00')->watchpoint(12)->commit()
		    ->modify('pet_cat_#00')->watchpoint(12)->commit()
		    ->modify('boomfruit_#00')->watchpoint(12)->commit()
		    ->modify('bgrenade_#00')->watchpoint(12)->commit()
		    ->modify('angryc_#00')->watchpoint(18)->commit()
		    ->modify('pet_dog_#00')->watchpoint(25)->commit()
		    ->modify('tekel_#00')->watchpoint(18)->commit()
		    ->modify('torch_#00')->watchpoint(15)->commit()
		    ->modify('taser_#00')->watchpoint(5)->commit()
		    ->modify('big_pgun_#00')->watchpoint(11)->commit()
		    ->modify('pilegun_up_#00')->watchpoint(11)->commit()
		    ->modify('mixergun_#00')->watchpoint(18)->commit()
		    ->modify('lpoint1_#00')->watchpoint(5)->commit()
		    ->modify('lpoint2_#00')->watchpoint(10)->commit()
		    ->modify('lpoint3_#00')->watchpoint(15)->commit()
		    ->modify('lpoint4_#00')->watchpoint(20)->commit()
		    ->modify('jerrygun_#00')->watchpoint(20)->commit()
		    ->modify('cart_#00')->watchpoint(15)->commit()
		    ->modify('door_#00')->watchpoint(15)->commit()
		    ->modify('trestle_#00')->watchpoint(15)->commit()
		    ->modify('chair_#00')->watchpoint(15)->commit()
		    ->modify('table_#00')->watchpoint(15)->commit()
		    ->modify('bureau_#00')->watchpoint(20)->commit()
		    ->modify('car_door_#00')->watchpoint(25)->commit()
		    ->modify('water_can_1_#00')->watchpoint(8)->commit()
		    ->modify('water_can_2_#00')->watchpoint(16)->commit()
		    ->modify('water_can_3_#00')->watchpoint(24)->commit()
            ->modify('kalach_#00')->watchpoint(24)->commit()
		    ->modify('lamp_on_#00')->watchpoint(5)->commit()
		    ->modify('lamp_#00')->watchpoint(5)->commit()
		    ->modify('guitar_#00')->watchpoint(10)->commit()
		    ->modify('flare_#00')->watchpoint(15)->commit()
		    ->modify('hmeat_#00')->watchpoint(15)->commit()
		    ->modify('claymo_#00')->watchpoint(40)->commit()
		    ->modify('bone_meat_#00')->watchpoint(10)->commit()
		    ->modify('flash_#00')->watchpoint(5)->commit()
		    ->modify('renne_#00')->watchpoint(25)->commit()
		    ->modify('paques_#00')->watchpoint(18)->commit()
		    ->modify('music_#00')->watchpoint(-30)->commit()
		    ->modify('vibr_#00')->watchpoint(-10)->commit()
		    ->modify('sport_elec_#00')->watchpoint(-10)->commit()
		    ->modify('radio_on_#00')->watchpoint(-15)->commit()
		    ->modify('cards_#00')->watchpoint(-10)->commit()
		    ->modify('dice_#00')->watchpoint(-10)->commit()
		    ->modify('teddy_#00')->watchpoint(-15)->commit()
		    ->modify('gun_#00')->watchpoint(-20)->commit()
		    ->modify('machine_gun_#00')->watchpoint(-25)->commit()
		    ->modify('rlaunc_#00')->watchpoint(30)->commit()
		    ->modify('hurling_stick_#00')->watchpoint(15)->commit()
		    ->modify('badge_#00')->watchpoint(14)->commit()
		    ->modify('distri_#00')->watchpoint(20)->commit();

        $data = $container->toArray();
    }
}