<?php

namespace MyHordes\Fixtures\Data;

use App\Enum\ActionHandler\ItemDropTarget;
use App\Enum\ItemPoisonType;
use ArrayHelpers\Arr;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\HomeEffect;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\ItemEffect;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\MessageEffect;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\PictoEffect;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\RolePlayTextEffect;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\TownEffect;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\ZoneEffect;
use MyHordes\Fixtures\DTO\Actions\EffectsDataContainer;

class ActionEffectProvider
{
    public static function create(array $data): EffectsDataContainer {
        $effects_container = new EffectsDataContainer();

        //<editor-fold desc="MessageEffects">
        $effects_container->add()->identifier('do_nothing_attack')->add((new MessageEffect())->text('Mit aller Kraft schlägst du mehrmals auf einen Zombie ein, aber <strong>es scheint ihm nichts anzuhaben</strong>!'))->commit();
        $effects_container->add()->identifier('do_nothing_attack2')->add((new MessageEffect())->text('Sie greifen einen Zombie mit Ihrem {item} an, aber <strong>er reagiert nicht einmal</strong> und macht weiter!'))->commit();
        $effects_container->add()->identifier('msg_effect_para')->add((new MessageEffect())->text('Die Medizin gibt dir Kraft: Du bist jetzt immun gegen Infektionen und kannst nicht in einen Ghul verwandelt werden. Diese Wirkung lässt nach dem Angriff nach.'))->commit();
        $effects_container->add()->identifier('msg_battery_use')->add((new MessageEffect())->text(Arr::get($data,'message_keys.battery_use'))->order(100))->commit();
        $effects_container->add()->identifier('msg_battery_drop')->add((new MessageEffect())->text(Arr::get($data,'message_keys.battery_dropped'))->order(100))->commit();
        $effects_container->add()->identifier('msg_battery_destroy')->add((new MessageEffect())->text(Arr::get($data,'message_keys.battery_destroyed'))->order(100))->commit();
        $effects_container->add()->identifier('msg_throw_jerrycan')->add((new MessageEffect())->text('<nt-morphed>Gute Nachrichten: Es ist noch Wasser im Kanister!</nt-morphed><t-morphed><strong>Der Kanister ist LEER</strong>!</t-morphed>'))->commit();
        $effects_container->add()->identifier('msg_heroic_arma_fail')->add((new MessageEffect())->text(Arr::get($data,'message_keys.heroic_arma_fail')))->commit();
        $effects_container->add()->identifier('msg_heroic_arma_success')->add((new MessageEffect())->text(Arr::get($data,'message_keys.heroic_arma_success')))->commit();
        $effects_container->add()->identifier('msg_drug_normal_ap')->add((new MessageEffect())->text(Arr::get($data,'message_keys.drug_normal_ap')))->commit();
        $effects_container->add()->identifier('msg_drug_terror')->add((new MessageEffect())->text(Arr::get($data,'message_keys.drug_terror')))->commit();
        $effects_container->add()->identifier('msg_drug_addict_ap')->add((new MessageEffect())->text(Arr::get($data,'message_keys.drug_addict_ap')))->commit();
        $effects_container->add()->identifier('msg_drug_no_effect')->add((new MessageEffect())->text(Arr::get($data,'message_keys.drug_no_effect')))->commit();
        $effects_container->add()->identifier('msg_drug_candy_addict')->add((new MessageEffect())->text('Du schluckst das Bonbon mit einem Lächeln auf den Lippen herunter... das jedoch schnell wieder verschwindet! Die Füllung besteht aus einem <strong>starken psychoaktiven Gift!</strong><t-stat-up-addict>{hr}Du bist jetzt ein Süchtiger!</t-stat-up-addict>'))->commit();
        $effects_container->add()->identifier('msg_drug_candy_terror')->add((new MessageEffect())->text('Du schluckst das Bonbon mit einem Lächeln auf den Lippen herunter... das jedoch schnell wieder verschwindet! Die Füllung besteht aus einem <strong>starken psychoaktiven Gift!</strong><t-stat-up-terror>{hr}Du bist vor Angst erstarrt!</t-stat-up-terror>'))->commit();
        $effects_container->add()->identifier('msg_drug_candy_infect')->add((new MessageEffect())->text('Du schluckst das Bonbon mit einem Lächeln auf den Lippen herunter... das jedoch schnell wieder verschwindet! Die Füllung besteht aus einem <strong>starken psychoaktiven Gift!</strong><t-stat-up-infection>{hr}Du bist jetzt infiziert!</t-stat-up-infection>'))->commit();
        $effects_container->add()->identifier('msg_break_item')->add((new MessageEffect())->text('Deine Waffe ist durch den harten Aufschlag <strong>kaputt</strong> gegangen...')->order(100))->commit();
        //</editor-fold>

        //<editor-fold desc="PictoEffects">
        $effects_container->add()->identifier('picto_drug_exp')->add((new PictoEffect())->picto('r_cobaye_#00'))->commit();
        $effects_container->add()->identifier('picto_animal')->add((new PictoEffect())->picto('r_animal_#00'))->commit();
        $effects_container->add()->identifier('picto_masochism')->add((new PictoEffect())->picto('r_maso_#00'))->commit();
        $effects_container->add()->identifier('picto_ban_emanc')->add((new PictoEffect())->picto('r_solban_#00'))->commit();
        $effects_container->add()->identifier('picto_home_upgrade')->add((new PictoEffect())->picto('r_hbuild_#00'))->commit();
        $effects_container->add()->identifier('picto_repair')->add((new PictoEffect())->picto('r_repair_#00'))->commit();
        $effects_container->add()->identifier('picto_cannibal')->add((new PictoEffect())->picto('r_cannib_#00'))->commit();
        $effects_container->add()->identifier('picto_soul_purify')
            ->add((new PictoEffect())->picto('r_collec_#00'))
            ->add((new PictoEffect())->picto('r_mystic_#00')->forEntireTown(true))
            ->commit();
        //</editor-fold>

        //<editor-fold desc="TownEffects">
        $effects_container->add()->identifier('town_well_2')->add((new TownEffect())->well(2))->commit();
        $effects_container->add()->identifier('town_well_1_3')->add((new TownEffect())->well(1,3))->commit();
        $effects_container->add()->identifier('town_well_4_9')->add((new TownEffect())->well(4,9))->commit();
        $effects_container->add()->identifier('town_sdef_5')->add((new TownEffect())->soulDefense(5))->commit();
        //</editor-fold>

        //<editor-fold desc="ZoneEffects">
        $effects_container->add()->identifier('zonemarker')->add((new ZoneEffect())->uncover())->commit();
        $effects_container->add()->identifier('nessquick')->add((new ZoneEffect())->clean(2,3))->commit();
        $effects_container->add()->identifier('zone_escape_30')->add((new ZoneEffect())->escape(30))->commit();
        $effects_container->add()->identifier('zone_escape_40')->add((new ZoneEffect())->escape(40))->commit();
        $effects_container->add()->identifier('zone_escape_60')->add((new ZoneEffect())->escape(60))->commit();
        $effects_container->add()->identifier('zone_escape_120')->add((new ZoneEffect())->escape(120))->commit();
        $effects_container->add()->identifier('zone_escape_300')->add((new ZoneEffect())->escape(300))->commit();
        $effects_container->add()->identifier('zone_escape_600_armag')->add((new ZoneEffect())->escape(600)->escapeTag('armag'))->commit();
        $effects_container->add()->identifier('zone_chat_60')->add((new ZoneEffect())->chatSilence(60))->commit();
        $effects_container->add()->identifier('zone_improve_5')->add((new ZoneEffect())->improveLevel(5.0))->commit();
        $effects_container->add()->identifier('zone_improve_9')->add((new ZoneEffect())->improveLevel(9.0))->commit();
        $effects_container->add()->identifier('zone_kill_2')->add((new ZoneEffect())->kills(2))->commit();
        $effects_container->add()->identifier('zone_kill_2_4')->add((new ZoneEffect())->kills(2,4))->commit();
        $effects_container->add()->identifier('zone_kill_5_9')->add((new ZoneEffect())->kills(5,9))->commit();
        $effects_container->add()->identifier('zone_kill_6_10')->add((new ZoneEffect())->kills(6,10))->commit();
        //</editor-fold>

        //<editor-fold desc="HomeEffects">
        $effects_container->add()->identifier('home_def_1')->add((new HomeEffect())->defense(1))->commit();
        $effects_container->add()->identifier('home_store_1')->add((new HomeEffect())->storage(1))->commit();
        $effects_container->add()->identifier('home_store_2')->add((new HomeEffect())->storage(2))->commit();
        //</editor-fold>

        //<editor-fold desc="ItemEffects">
        $effects_container->add()->identifier('consume_item')->add((new ItemEffect())->consumeSource())->commit();
        $effects_container->add()->identifier('break_item')->add((new ItemEffect())->morphSource(break: true))->commit();
        $effects_container->add()->identifier('cleanse_item')->add((new ItemEffect())->morphSource(poison: false))->commit();

        $effects_container->add()->identifier('spawn_target')->add((new ItemEffect())->spawnTarget())->commit();
        $effects_container->add()->identifier('consume_target')->add((new ItemEffect())->consumeTarget())->commit();
        $effects_container->add()->identifier('repair_target')->add((new ItemEffect())->morphTarget(break: false))->commit();
        $effects_container->add()->identifier('poison_target')->add((new ItemEffect())->morphTarget(poison: true))->commit();
        $effects_container->add()->identifier('poison_infect_target')->add((new ItemEffect())->morphTarget(poison: ItemPoisonType::Infectious))->commit();

        $effects_container->add()->identifier('consume_water')->add((new ItemEffect())->consume('water_#00'))->commit();
        $effects_container->add()->identifier('consume_matches')->add((new ItemEffect())->consume('lights_#00'))->commit();
        $effects_container->add()->identifier('consume_battery')->add((new ItemEffect())->consume('pile_#00'))->commit();
        $effects_container->add()->identifier('consume_micropur')->add((new ItemEffect())->consume('water_cleaner_#00'))->commit();
        $effects_container->add()->identifier('consume_drug')->add((new ItemEffect())->consume('drug_#00'))->commit();
        $effects_container->add()->identifier('consume_jerrycan')->add((new ItemEffect())->consume('jerrycan_#00'))->commit();
        $effects_container->add()->identifier('consume_2_pharma')->add((new ItemEffect())->consume('pharma_#00', 2))->commit();

        $effects_container->add()->identifier('empty_jerrygun')->add((new ItemEffect())->morphSource('jerrygun_off_#00'))->commit();
        $effects_container->add()->identifier('produce_watercan3')->add((new ItemEffect())->morphSource('water_can_3_#00'))->commit();
        $effects_container->add()->identifier('produce_watercan2')->add((new ItemEffect())->morphSource('water_can_2_#00'))->commit();
        $effects_container->add()->identifier('produce_watercan1')->add((new ItemEffect())->morphSource('water_can_1_#00'))->commit();
        $effects_container->add()->identifier('produce_watercan0')->add((new ItemEffect())->morphSource('water_can_empty_#00', poison: false))->commit();
        $effects_container->add()->identifier('hero_tamer_3')->add((new ItemEffect())->morphSource('tamed_pet_drug_#00'))->commit();
        $effects_container->add()->identifier('hero_hunter')->add((new ItemEffect())->morphSource('vest_on_#00'))->commit();
        $effects_container->add()->identifier('morph_open_can')->add((new ItemEffect())->morphSource('can_open_#00'))->commit();
        $effects_container->add()->identifier('morph_elec_empty')->add((new ItemEffect())->morphSource('sport_elec_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_rsc_pack_2')->add((new ItemEffect())->morphSource('rsc_pack_2_#00'))->commit();
        $effects_container->add()->identifier('morph_rsc_pack_1')->add((new ItemEffect())->morphSource('rsc_pack_1_#00'))->commit();
        $effects_container->add()->identifier('morph_christmas_2')->add((new ItemEffect())->morphSource('chest_christmas_2_#00'))->commit();
        $effects_container->add()->identifier('morph_christmas_1')->add((new ItemEffect())->morphSource('chest_christmas_1_#00'))->commit();

        $effects_container->add()->identifier('morph_pilegun')->add((new ItemEffect())->morphSource('pilegun_#00'))->commit();
        $effects_container->add()->identifier('morph_pilegun_up')->add((new ItemEffect())->morphSource('pilegun_up_#00'))->commit();
        $effects_container->add()->identifier('morph_big_pgun')->add((new ItemEffect())->morphSource('big_pgun_#00'))->commit();
        $effects_container->add()->identifier('morph_mixergun')->add((new ItemEffect())->morphSource('mixergun_#00'))->commit();
        $effects_container->add()->identifier('morph_chainsaw')->add((new ItemEffect())->morphSource('chainsaw_#00'))->commit();
        $effects_container->add()->identifier('morph_taser')->add((new ItemEffect())->morphSource('taser_#00'))->commit();
        $effects_container->add()->identifier('morph_lpoint4')->add((new ItemEffect())->morphSource('lpoint4_#00'))->commit();

        $effects_container->add()->identifier('morph_lamp_on')->add((new ItemEffect())->morphSource('lamp_on_#00'))->commit();
        $effects_container->add()->identifier('morph_vibr')->add((new ItemEffect())->morphSource('vibr_#00'))->commit();
        $effects_container->add()->identifier('morph_radius_mk2')->add((new ItemEffect())->morphSource('radius_mk2_#00'))->commit();
        $effects_container->add()->identifier('morph_maglite_2')->add((new ItemEffect())->morphSource('maglite_2_#00'))->commit();
        $effects_container->add()->identifier('morph_radio_on')->add((new ItemEffect())->morphSource('radio_on_#00'))->commit();
        $effects_container->add()->identifier('morph_sport_elec')->add((new ItemEffect())->morphSource('sport_elec_#00'))->commit();
        $effects_container->add()->identifier('morph_jerrygun')->add((new ItemEffect())->morphSource('jerrygun_#00'))->commit();

        $effects_container->add()->identifier('morph_watergun_opt_5')->add((new ItemEffect())->morphSource('watergun_opt_5_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_3')->add((new ItemEffect())->morphSource('watergun_3_#00'))->commit();
        $effects_container->add()->identifier('morph_kalach')->add((new ItemEffect())->morphSource('kalach_#00'))->commit();
        $effects_container->add()->identifier('morph_grenade')->add((new ItemEffect())->morphSource('grenade_#00'))->commit();
        $effects_container->add()->identifier('morph_bgrenade')->add((new ItemEffect())->morphSource('bgrenade_#00'))->commit();

        $effects_container->add()->identifier('morph_pilegun_empty')->add((new ItemEffect())->morphSource('pilegun_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_pilegun_up_empty')->add((new ItemEffect())->morphSource('pilegun_up_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_big_pgun_empty')->add((new ItemEffect())->morphSource('big_pgun_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_mixergun_empty')->add((new ItemEffect())->morphSource('mixergun_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_chainsaw_empty')->add((new ItemEffect())->morphSource('chainsaw_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_taser_empty')->add((new ItemEffect())->morphSource('taser_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_lpoint3')->add((new ItemEffect())->morphSource('lpoint3_#00'))->commit();
        $effects_container->add()->identifier('morph_lpoint2')->add((new ItemEffect())->morphSource('lpoint2_#00'))->commit();
        $effects_container->add()->identifier('morph_lpoint1')->add((new ItemEffect())->morphSource('lpoint1_#00'))->commit();
        $effects_container->add()->identifier('morph_lpoint')->add((new ItemEffect())->morphSource('lpoint_#00'))->commit();

        $effects_container->add()->identifier('morph_watergun_opt_4')->add((new ItemEffect())->morphSource('watergun_opt_4_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_opt_3')->add((new ItemEffect())->morphSource('watergun_opt_3_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_opt_2')->add((new ItemEffect())->morphSource('watergun_opt_2_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_opt_1')->add((new ItemEffect())->morphSource('watergun_opt_1_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_opt_empty')->add((new ItemEffect())->morphSource('watergun_opt_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_2')->add((new ItemEffect())->morphSource('watergun_2_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_1')->add((new ItemEffect())->morphSource('watergun_1_#00'))->commit();
        $effects_container->add()->identifier('morph_watergun_empty')->add((new ItemEffect())->morphSource('watergun_empty_#00'))->commit();
        $effects_container->add()->identifier('morph_kalach_empty')->add((new ItemEffect())->morphSource('kalach_#01'))->commit();
        $effects_container->add()->identifier('morph_torch_off')->add((new ItemEffect())->morphSource('torch_off_#00'))->commit();
        $effects_container->add()->identifier('morph_staff2')->add((new ItemEffect())->morphSource('staff2_#00'))->commit();

        $effects_container->add()->identifier('spawn_doggy')->add((new ItemEffect())
                                                                      ->addSpawn('food_bar2_#00', 222)
                                                                      ->addSpawn('food_chick_#00', 194)
                                                                      ->addSpawn('food_biscuit_#00', 188)
                                                                      ->addSpawn('food_pims_#00', 186)
                                                                      ->addSpawn('food_bar3_#00', 181)
                                                                      ->addSpawn('food_tarte_#00', 174)
                                                                      ->addSpawn('food_bar1_#00', 168)
                                                                      ->addSpawn('food_sandw_#00', 162)
        )->commit();

        $effects_container->add()->identifier('spawn_lunch')->add((new ItemEffect())
                                                                      ->addSpawnList(['food_candies_#00', 'food_noodles_hot_#00', 'vegetable_tasty_#00', 'meat_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_c_chest')->add((new ItemEffect())
                                                                        ->addSpawnList(['pile_#00', 'radio_off_#00', 'pharma_#00', 'lights_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_h_chest')->add((new ItemEffect())
                                                                        ->addSpawnList(['watergun_empty_#00', 'pilegun_empty_#00', 'flash_#00', 'repair_one_#00', 'smoke_bomb_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_postbox')->add((new ItemEffect())
                                                                        ->addSpawnList(['money_#00', 'rp_book_#00', 'rp_book_#01', 'rp_sheets_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_postbox_xl')->add((new ItemEffect())
                                                                           ->addSpawnList(['machine_gun_#00', 'rsc_pack_2_#00', 'rhum_#00', 'vibr_empty_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_letterbox')->add((new ItemEffect())
                                                                          ->addSpawnList(['rp_book2_#00', 'rp_manual_#00', 'rp_scroll_#00', 'rp_scroll_#01', 'rp_sheets_#00', 'rp_letter_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_justbox')->add((new ItemEffect())
                                                                        ->addSpawnList(['money_#00', 'rp_book_#00', 'rp_book_#01', 'rp_sheets_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_gamebox')->add((new ItemEffect())
                                                                        ->addSpawnList(['dice_#00', 'cards_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_abox')->add((new ItemEffect())
                                                                     ->addSpawn('bplan_r_#00')
        )->commit();

        $effects_container->add()->identifier('spawn_cbox')->add((new ItemEffect())
                                                                     ->addSpawn('bplan_c_#00', 50)
                                                                     ->addSpawn('bplan_u_#00', 35)
                                                                     ->addSpawn('bplan_r_#00', 10)
                                                                     ->addSpawn('bplan_e_#00', 5)
        )->commit();

        $effects_container->add()->identifier('spawn_xmas_dv')->add((new ItemEffect())
                                                                        ->addSpawnList(['omg_this_will_kill_you_#00', 'pocket_belt_#00', 'christmas_candy_#00'], 8)
                                                                        ->addSpawnList(['rp_manual_#00', 'rp_sheets_#00', 'rp_letter_#00', 'rp_scroll_#00', 'rp_book_#00', 'rp_book_#01', 'rp_book2_#00'])
        )->commit();
        $effects_container->add()->identifier('spawn_xmas_3')->add((new ItemEffect())
                                                                       ->addSpawnList(['omg_this_will_kill_you_#00'])
        )->commit();
        $effects_container->add()->identifier('spawn_xmas_2')->add((new ItemEffect())
                                                                       ->addSpawnList(['christmas_candy_#00'])
        )->commit();
        $effects_container->add()->identifier('spawn_xmas_1')->add((new ItemEffect())
                                                                       ->addSpawnList(['xmas_gift_#00'])
        )->commit();
        $effects_container->add()->identifier('spawn_matbox')->add((new ItemEffect())
                                                                       ->addSpawnList(['wood2_#00', 'metal_#00'])
        )->commit();

        $effects_container->add()->identifier('spawn_metalbox')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetRucksack)
                                                                         ->addSpawn('bandage_#00', 28)
                                                                         ->addSpawn('vodka_#00', 20)
                                                                         ->addSpawnList(['drug_hero_#00', 'drug_#00'], 16)
                                                                         ->addSpawnList(['explo_#00', 'rhum_#00'], 8)
                                                                         ->addSpawn('lights_#00', 4)
        )->commit();
        $effects_container->add()->identifier('spawn_metalbox2')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetRucksack)
                                                                          ->addSpawnList(['mixergun_part_#00','watergun_opt_part_#00'], 19)
                                                                          ->addSpawnList(['pocket_belt_#00', 'chainsaw_part_#00', 'lawn_part_#00'], 12)
                                                                          ->addSpawnList(['pilegun_upkit_#00', 'cutcut_#00'], 10)
                                                                          ->addSpawn('big_pgun_part_#00', 7)
        )->commit();
        $effects_container->add()->identifier('spawn_catbox')->add((new ItemEffect())
                                                                       ->addSpawnList(['poison_part_#00', 'pet_cat_#00', 'angryc_#00'])
        )->commit();
        $effects_container->add()->identifier('spawn_toolbox')->add((new ItemEffect())
                                                                        ->addSpawn('pharma_#00', 25)
                                                                        ->addSpawn('explo_#00', 19)
                                                                        ->addSpawn('meca_parts_#00', 17)
                                                                        ->addSpawn('rustine_#00', 13)
                                                                        ->addSpawn('tube_#00', 13)
                                                                        ->addSpawn('pile_#00', 12)
        )->commit();
        $effects_container->add()->identifier('spawn_foodbox')->add((new ItemEffect())
                                                                        ->addSpawn('hmeat_#00', 13)
                                                                        ->addSpawn('can_#00', 11)
                                                                        ->addSpawnList(['food_bag_#00', 'vegetable_#00'], 8)
                                                                        ->addSpawn('meat_#00', 7)
        )->commit();
        $effects_container->add()->identifier('spawn_phone')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloor)
                                                                      ->addSpawnList(['deto_#00', 'metal_bad_#00', 'pile_broken_#00', 'electro_#00'])
        )->commit();
        $effects_container->add()->identifier('spawn_phone_nw')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetRucksack)
                                                                         ->addSpawnList(['deto_#00', 'metal_bad_#00', 'pile_broken_#00', 'electro_#00'])
        )->commit();
        $effects_container->add()->identifier('spawn_proj')->add((new ItemEffect())
                                                                     ->addSpawn('lens_#00')
        )->commit();
        $effects_container->add()->identifier('spawn_empty_battery')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloor)
                                                                              ->addSpawn('pile_broken_#00')
        )->commit();
        $effects_container->add()->identifier('spawn_battery')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloor)
                                                                        ->addSpawn('pile_#00')
        )->commit();
        $effects_container->add()->identifier('spawn_safe')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetRucksack)
                                                                     ->addSpawn('pocket_belt_#00', 15)
                                                                     ->addSpawnList(['watergun_opt_part_#00', 'lawn_part_#00', 'chainsaw_part_#00', 'mixergun_part_#00', 'cutcut_#00', 'pilegun_upkit_#00', 'meca_parts_#00'], 10)
                                                                     ->addSpawnList(['big_pgun_part_#00', 'book_gen_letter_#00'], 5)
        )->commit();
        $effects_container->add()->identifier('spawn_asafe')->add((new ItemEffect())
                                                                      ->addSpawn('bplan_e_#00')
        )->commit();

        $effects_container->add()->identifier('spawn_meat_4xs')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloorOnly)
                                                                         ->addSpawn('meat_#00', count: 4)
        )->commit();
        $effects_container->add()->identifier('spawn_meat_4x')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloorOnly)
                                                                        ->addSpawn('undef_#00', count: 4)
        )->commit();
        $effects_container->add()->identifier('spawn_meat_2xs')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloorOnly)
                                                                         ->addSpawn('meat_#00', count: 2)
        )->commit();
        $effects_container->add()->identifier('spawn_meat_2x')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloorOnly)
                                                                        ->addSpawn('undef_#00', count: 2)
        )->commit();
        $effects_container->add()->identifier('spawn_meat_bmb')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloorOnly)
                                                                         ->addSpawn('flesh_#00', count: 2)
        )->commit();

        $effects_container->add()->identifier('spawn_potion')->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloorOnly)
                                                                       ->addSpawn('potion_#00')
        )->commit();

        $effects_container->add()->identifier('spawn_2_watercup')->add((new ItemEffect())
                                                                           ->addSpawn('water_cup_#00', count: 2)
        )->commit();
        $effects_container->add()->identifier('spawn_2_water')->add((new ItemEffect())
                                                                        ->addSpawn('water_#00', count: 2)
        )->commit();
        $effects_container->add()->identifier('spawn_3_water')->add((new ItemEffect())
                                                                        ->addSpawn('water_#00', count: 3)
        )->commit();
        //</editor-fold>

        //<editor-fold desc="Various">
        $effects_container->add()->identifier('find_rp')->add(new RolePlayTextEffect())->commit();
        //</editor-fold>

        // Composite
        $effects_container->add()->identifier('break_item')
            ->add((new PictoEffect())->picto('r_broken_#00'))
            ->add((new MessageEffect())->text('Deine Waffe ist durch den harten Aufschlag <strong>kaputt</strong> gegangen...')->order(100))
            ->commit();

        $effects_container->add()->identifier('drunk')
            ->add((new PictoEffect())->picto('r_alcool_#00'))->commit();

        $effects_container->add()->identifier('drug_any')
            ->add((new PictoEffect())->picto('r_drug_#00'))->commit();

        $effects_container->add()->identifier('drug_addict_no_msg')
            ->add((new PictoEffect())->picto('r_drug_#00'))->commit();

        $effects_container->clone('drug_addict_no_msg')->identifier('drug_addict')
            ->add((new MessageEffect())->text( '<t-stat-up-addict>Schlechte Neuigkeiten! Du bist jetzt abhängig! Von nun an musst du jeden Tag eine Droge nehmen... oder STERBEN!</t-stat-up-addict>')->order(100))
            ->commit();


        $effects_container->add()->identifier('eat_ap6')
            ->add((new MessageEffect())->escort(false)->text( 'Es schmeckt wirklich komisch... aber es erfüllt seinen Zweck: Dein Hunger ist gestillt. Glaub aber nicht, dass du dadurch zusätzliche APs erhältst...'))->commit();

        $effects_container->add()->identifier('eat_ap7')
            ->add((new MessageEffect())->escort(false)->text( 'Einmal ist zwar keinmal, dennoch genießt du dein(e) <span class="tool">{item}</span>. Das ist mal ne echte Abwechslung zu dem sonstigen Fraß... Du spürst deine Kräfte wieder zurückkehren.{hr}Du hast <strong>1 zusätzlichen AP erhalten!</strong>'))->commit();

        $effects_container->add()->identifier('infect')
            ->add((new MessageEffect())->text( 'Schlechte Nachrichten, das hättest du nicht in den Mund nehmen sollen... Du bist infiziert!'))->commit();

        $effects_container->add()->identifier('kill_1_zombie_s')
            ->add((new ZoneEffect())->kills(1))
            ->commit();

        $effects_container->clone('kill_1_zombie_s')->identifier('kill_1_zombie')
            ->add((new MessageEffect())->text( Arr::get($data,'message_keys.weapon_use')))
            ->commit();

        $effects_container->add()->identifier('kill_1_2_zombie')
            ->add((new ZoneEffect())->kills(1,2))
            ->add((new MessageEffect())->text( Arr::get($data,'message_keys.weapon_use')))
            ->commit();

        $effects_container->add()->identifier('kill_2_zombie')
            ->add((new ZoneEffect())->kills(2))
            ->add((new MessageEffect())->text( Arr::get($data,'message_keys.weapon_use')))
            ->commit();

        $effects_container->add()->identifier('kill_3_zombie')
            ->add((new ZoneEffect())->kills(3))
            ->add((new MessageEffect())->text( Arr::get($data,'message_keys.weapon_use')))
            ->commit();

        $effects_container->add()->identifier('kill_all_zombie')
            ->add((new ZoneEffect())->kills(999999))
            ->commit();

        $effects_container->add()->identifier('home_lab_success')
            ->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloor)
                      ->addSpawn('drug_hero_#00')
            )
            ->add((new PictoEffect())->picto('r_drgmkr_#00'))
            ->add((new MessageEffect())->text( Arr::get($data,'message_keys.use_lab_success')))
            ->commit();

        $effects_container->add()->identifier('home_lab_failure')
            ->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloor)
                      ->addSpawnList(['drug_#00', 'xanax_#00', 'drug_random_#00', 'drug_water_#00', 'water_cleaner_#00'])
            )
            ->add((new MessageEffect())->text( Arr::get($data,'message_keys.use_lab_fail')))->commit();

        $effects_container->add()->identifier('home_kitchen_success')
            ->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloor)
                      ->addSpawn('dish_tasty_#00')
            )
            ->add((new PictoEffect())->picto('r_cookr_#00'))
            ->commit();

        $effects_container->add()->identifier('home_kitchen_failure')
            ->add((new ItemEffect())->spawnAt(ItemDropTarget::DropTargetFloor)
                      ->addSpawn('dish_#00')
            )
            ->commit();

        return $effects_container;
    }
}