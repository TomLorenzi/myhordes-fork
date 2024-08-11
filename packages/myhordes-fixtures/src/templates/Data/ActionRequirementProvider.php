<?php

namespace MyHordes\Fixtures\Data;

use App\Entity\ActionCounter;
use App\Entity\Requirement;
use App\Enum\ActionHandler\PointType;
use App\Service\Actions\Game\AtomProcessors\Require\Custom\GuardTowerUseIsNotMaxed;
use App\Service\Actions\Game\AtomProcessors\Require\Custom\RoleVote;
use App\Structures\TownConf;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\BuildingRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ConfigRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\CounterRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\CustomClassRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\EscortRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\FeatureRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\HomeRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\InventorySpaceRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ItemRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\LocationRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\PointRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ProfessionRoleRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\StatusRequirement;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\TimeRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsDataContainer;

class ActionRequirementProvider
{
    public static function create(array $data): RequirementsDataContainer {
        $requirement_container = new RequirementsDataContainer();
        $requirement_container->add()->identifier('can_use_friendship')->type(Requirement::HideOnFail)->add( (new FeatureRequirement())->feature('f_share') )->commit();
        $requirement_container->add()->identifier('hunter_no_followers')->type( Requirement::MessageOnFail )->text('Du kannst die <strong>Tarnkleidung</strong> nicht benutzen, wenn du {escortCount} Personen im Schlepptau hast...')->add( (new EscortRequirement())->maxFollowers(0) )->commit();
        $requirement_container->add()->identifier('room_for_item')->type( Requirement::MessageOnFail )->add( (new InventorySpaceRequirement()) )->commit();
        $requirement_container->add()->identifier('room_for_item_in_chest')->type( Requirement::MessageOnFail )->add( (new InventorySpaceRequirement())->ignoreInventory(true)->container(false) )->commit();
        $requirement_container->add()->identifier('guard_tower_not_max')->type( Requirement::MessageOnFail )->add( (new CustomClassRequirement())->requirement(GuardTowerUseIsNotMaxed::class) )->commit();

        $requirement_container->add()->identifier('vote_shaman_needed')->type( Requirement::HideOnFail )->add( (new CustomClassRequirement())->requirement(RoleVote::class)->args(['needed' => 'shaman']) )->commit();
        $requirement_container->add()->identifier('vote_shaman_not_given')->type( Requirement::CrossOnFail )->add( (new CustomClassRequirement())->requirement(RoleVote::class)->args(['hasNotVoted' => 'shaman']) )->commit();
        $requirement_container->add()->identifier('vote_guide_needed')->type( Requirement::HideOnFail )->add( (new CustomClassRequirement())->requirement(RoleVote::class)->args(['needed' => 'guide']) )->commit();
        $requirement_container->add()->identifier('vote_guide_not_given')->type( Requirement::CrossOnFail )->add( (new CustomClassRequirement())->requirement(RoleVote::class)->args(['hasNotVoted' => 'guide']) )->commit();

        //<editor-fold desc="ProfessionRoleRequirements">
        $requirement_container->add()->identifier('profession_heroic')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->hero(true) )->commit();
        $requirement_container->add()->identifier('profession_basic')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('basic', true) )->commit();
        $requirement_container->add()->identifier('profession_collec')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('collec', true) )->commit();
        $requirement_container->add()->identifier('profession_guardian')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('guardian', true) )->commit();
        $requirement_container->add()->identifier('profession_hunter')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('hunter', true) )->commit();
        $requirement_container->add()->identifier('profession_tamer')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('tamer', true) )->commit();
        $requirement_container->add()->identifier('profession_tech')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('tech', true) )->commit();
        $requirement_container->add()->identifier('profession_shaman')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('shaman', true) )->commit();
        $requirement_container->add()->identifier('profession_survivalist')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('survivalist', true) )->commit();

        $requirement_container->add()->identifier('not_profession_tech')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->job('tech', false) )->commit();

        $requirement_container->add()->identifier('role_shaman')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->role('shaman', true) )->commit();
        $requirement_container->add()->identifier('role_ghoul')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->role('ghoul', true) )->commit();
        $requirement_container->clone('role_ghoul')->identifier('role_ghoul_serum')->type( Requirement::MessageOnFail )->text('Du kannst dieses Serum nicht auf dich selbst anwenden, oder du wirst der beste Freund eines Ghuls...')->commit();
        $requirement_container->add()->identifier('not_role_ghoul')->type( Requirement::HideOnFail )->add( (new ProfessionRoleRequirement())->role('ghoul', false) )->commit();
        //</editor-fold>

        //<editor-fold desc="StatusRequirements">
        $requirement_container->add()->identifier('never_cross')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_never', true) )->commit();

        $requirement_container->add()->identifier('drink_cross')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('hasdrunk', false) )->commit();
        $requirement_container->clone('drink_cross')->identifier('drink_mesg')->type( Requirement::MessageOnFail )->text('Du hast <strong>heute bereits getrunken</strong>: weitere Rationen werden nur deinen Durst löschen, deine AP aber nicht erneuern.')->commit();
        $requirement_container->clone('drink_cross')->identifier('drink_hide')->type( Requirement::HideOnFail )->commit();
        $requirement_container->add()->identifier('drink_rhide')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('hasdrunk', true) )->commit();

        $requirement_container->add()->identifier('drink_tl0a')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('thirst1', false) )->commit();
        $requirement_container->add()->identifier('drink_tl0b')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('thirst2', false) )->commit();
        $requirement_container->add()->identifier('drink_tl1')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('thirst1', true) )->commit();
        $requirement_container->add()->identifier('drink_tl2')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('thirst2', true) )->commit();

        $requirement_container->add()->identifier('not_yet_dice')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_dice', false) )->text_key('once_a_day')->commit();
        $requirement_container->add()->identifier('not_yet_card')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_cards', false) )->text_key('once_a_day')->commit();
        $requirement_container->add()->identifier('not_yet_teddy')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_teddy', false) )->text_key('once_a_day')->commit();

        $requirement_container->add()->identifier('not_yet_guitar')->type( Requirement::MessageOnFail )->add( (new StatusRequirement())->status('tg_guitar', false) )->text('Vorsicht, zu viel Musik ist schädlich, und einer deiner Mitbürger hat dieses Instrument heute bereits benutzt. Deine Ohren würden das nicht überleben.')->commit();
        $requirement_container->add()->identifier('not_yet_beta')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_betadrug', false) )->commit();
        $requirement_container->add()->identifier('not_yet_sbook')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_sbook', false) )->commit();
        $requirement_container->add()->identifier('not_yet_hero')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_hero', false) )->commit();
        $requirement_container->add()->identifier('not_yet_home_cleaned')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_home_clean' , false) )->commit();
        $requirement_container->add()->identifier('not_yet_home_showered')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_home_shower', false) )->commit();
        $requirement_container->add()->identifier('not_yet_home_heal_1')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_home_heal_1', false) )->commit();
        $requirement_container->add()->identifier('not_yet_home_heal_2')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_home_heal_2', false) )->commit();
        $requirement_container->add()->identifier('not_yet_home_defbuff')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_home_defbuff', false) )->commit();
        $requirement_container->add()->identifier('not_yet_rested')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_rested', false) )->commit();
        $requirement_container->add()->identifier('not_yet_immune')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_shaman_immune', false) )->commit();
        $requirement_container->add()->identifier('immune')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_shaman_immune', true) )->commit();

        $requirement_container->add()->identifier('eat_ap')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('haseaten', false) )->text_key('once_a_day')->commit();

        $requirement_container->add()->identifier('drug_1')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('drugged', false) )->commit();
        $requirement_container->add()->identifier('drug_2')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('drugged', true) )->commit();

        $requirement_container->add()->identifier('not_tired')->type( Requirement::MessageOnFail )->add( (new StatusRequirement())->status('tired', false) )->text('Solange du <strong>erschöpft bist</strong>, kannst du diese Aktion nicht ausführen (da du keine Aktionspunkte mehr hast)... Trink oder iss etwas, oder nimm eine Droge, ansonsten musst du bis <strong>morgen</strong> warten.')->commit();

        $requirement_container->add()->identifier('is_wounded')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_meta_wound', true) )->commit();
        $requirement_container->add()->identifier('is_not_wounded')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('tg_meta_wound', false) )->commit();
        $requirement_container->add()->identifier('is_not_wounded_hands')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('wound2', false) )->commit();
        $requirement_container->clone('is_not_wounded_hands')->identifier('is_not_wounded_hands_repair')->type( Requirement::MessageOnFail )->text('Mit deiner verletzten Hand bist du eigentlich nicht in der Lage, etwas zu halten. Trotzdem versuchst du dich hartnäckig in filigraner Reparaturarbeit, stößt dir dabei aber die Hand, was dir viele unnötige Schmerzen bereitet. Vielleicht solltest du stattdessen nach einem Weg suchen, dich selbst zu heilen.')->commit();
        $requirement_container->add()->identifier('is_not_bandaged')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('healed', false) )->commit();

        $requirement_container->add()->identifier('is_wounded_h')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_meta_wound', true) )->commit();
        $requirement_container->add()->identifier('is_not_wounded_h')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_meta_wound', false) )->commit();
        $requirement_container->add()->identifier('is_infected_h')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('infection', true) )->commit();
        $requirement_container->add()->identifier('is_not_infected_h')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('infection', false) )->commit();

        $requirement_container->add()->identifier('not_drunk')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('drunk', false) )->commit();
        $requirement_container->add()->identifier('not_hungover')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('hungover', false) )->commit();

        $requirement_container->add()->identifier('must_be_terrorized')->type( Requirement::CrossOnFail )->add( (new StatusRequirement())->status('terror', true) )->text('Das brauchst du gerade nicht ...')->commit();
        $requirement_container->clone('must_be_terrorized')->identifier('must_be_terrorized_hd')->type( Requirement::HideOnFail )->commit();
        $requirement_container->add()->identifier('must_not_be_terrorized')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('terror', false) )->text('Das brauchst du gerade nicht ...')->commit();

        $requirement_container->add()->identifier('must_not_be_banished')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->shunned(false) )->commit();
        $requirement_container->add()->identifier('must_not_be_banished_w')->type( Requirement::MessageOnFail )->add( (new StatusRequirement())->shunned(false) )->text_key('water_purification_impossible')->commit();
        $requirement_container->add()->identifier('must_be_banished')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->shunned(true) )->commit();

        $requirement_container->add()->identifier('must_not_be_hidden')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_hide', false) )->commit();
        $requirement_container->add()->identifier('must_not_be_tombed')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_tomb', false) )->commit();
        $requirement_container->add()->identifier('must_be_hidden')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_hide', true) )->commit();
        $requirement_container->add()->identifier('must_be_tombed')->type( Requirement::HideOnFail )->add( (new StatusRequirement())->status('tg_tomb', true) )->commit();
        //</editor-fold>

        //<editor-fold desc="PointRequirements">
        $requirement_container->add()->identifier('no_bonus_ap')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::AP)->max(0)->fromLimit() )->text_key('already_full_ap')->commit();
        $requirement_container->add()->identifier('no_full_ap')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::AP)->max(-1)->fromLimit() )->text_key('already_full_ap')->commit();
        $requirement_container->clone('no_full_ap')->identifier('not_thirsty')->type( Requirement::MessageOnFail )->text_key('already_full_ap_drink')->commit();
        $requirement_container->clone('no_full_ap')->identifier('no_full_ap_msg')->type( Requirement::MessageOnFail )->text('Das brauchst du gerade nicht ...')->commit();
        $requirement_container->clone('no_full_ap')->identifier('no_full_ap_msg_food')->type( Requirement::MessageOnFail )->text('Du brauchst im Moment <strong>nichts zu essen</strong>, da du nicht müde bist und noch alle deine Aktionspunkte hast.')->commit();

        $requirement_container->add()->identifier('min_6_ap')->type( Requirement::MessageOnFail )->add( (new PointRequirement())->require(PointType::AP)->min(6) )->text_key('pt_required')->commit();
        $requirement_container->add()->identifier('min_5_ap')->type( Requirement::MessageOnFail )->add( (new PointRequirement())->require(PointType::AP)->min(5) )->text_key('pt_required')->commit();
        $requirement_container->add()->identifier('min_1_ap')->type( Requirement::MessageOnFail )->add( (new PointRequirement())->require(PointType::AP)->min(1) )->text_key('pt_required')->commit();

        $requirement_container->add()->identifier('no_cp')->type( Requirement::HideOnFail )->add( (new PointRequirement())->require(PointType::CP)->max(0) )->commit();
        $requirement_container->add()->identifier('min_1_cp')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::CP)->min(1) )->text_key('pt_required')->commit();
        $requirement_container->add()->identifier('min_1_cp_hd')->type( Requirement::HideOnFail )->add( (new PointRequirement())->require(PointType::CP)->min(1) )->commit();

        $requirement_container->add()->identifier('min_1_pm')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::MP)->min(1) )->text_key('pt_required')->commit();
        $requirement_container->add()->identifier('min_2_pm')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::MP)->min(2) )->text_key('pt_required')->commit();
        $requirement_container->add()->identifier('min_3_pm')->type( Requirement::CrossOnFail )->add( (new PointRequirement())->require(PointType::MP)->min(3) )->text_key('pt_required')->commit();
        //</editor-fold>

        //<editor-fold desc="TimeRequirements">
        $requirement_container->add()->identifier('not_before_day_2')->type( Requirement::CrossOnFail )->add( (new TimeRequirement())->minDay(2) )->text('Dies kannst du erst ab <strong>Tag {day_min}</strong> tun.')->commit();
        $requirement_container->add()->identifier('must_be_day')->type( Requirement::HideOnFail )->add( (new TimeRequirement())->atDay() )->commit();
        $requirement_container->add()->identifier('must_be_night')->type( Requirement::HideOnFail )->add( (new TimeRequirement())->atNight() )->commit();
        //</editor-fold>

        //<editor-fold desc="CounterRequirements">
        $requirement_container->add()->identifier('lab_counter_below_1')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeLab)->max( 0 ) )->commit();
        $requirement_container->add()->identifier('lab_counter_below_4')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeLab)->max( 3 ) )->commit();
        $requirement_container->add()->identifier('lab_counter_below_6')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeLab)->max( 5 ) )->commit();
        $requirement_container->add()->identifier('lab_counter_below_9')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeLab)->max( 8 ) )->commit();
        $requirement_container->add()->identifier('kitchen_counter_below_1')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeKitchen)->max( 0 ) )->commit();
        $requirement_container->add()->identifier('kitchen_counter_below_2')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeKitchen)->max( 1 ) )->commit();
        $requirement_container->add()->identifier('kitchen_counter_below_3')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeKitchen)->max( 2 ) )->commit();
        $requirement_container->add()->identifier('kitchen_counter_below_4')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeKitchen)->max( 3 ) )->commit();
        $requirement_container->add()->identifier('kitchen_counter_below_5')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeKitchen)->max( 4 ) )->commit();
        $requirement_container->add()->identifier('kitchen_counter_below_6')->type( Requirement::CrossOnFail )->add( (new CounterRequirement())->counter(ActionCounter::ActionTypeHomeKitchen)->max( 5 ) )->commit();
        //</editor-fold>

        //<editor-fold desc="BuildingRequirements">
        $requirement_container->add()->identifier('must_have_purifier')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('item_jerrycan_#00', true) )->commit();
        $requirement_container->add()->identifier('must_not_have_purifier')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('item_jerrycan_#00', false) )->commit();
        $requirement_container->add()->identifier('must_have_filter')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('item_jerrycan_#01', true) )->commit();
        $requirement_container->add()->identifier('must_not_have_filter')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('item_jerrycan_#01', false) )->commit();

        $requirement_container->add()->identifier('must_have_shower')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_shower_#00', true) )->commit();
        $requirement_container->add()->identifier('must_have_slaughter')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('item_meat_#00', true) )->commit();
        $requirement_container->add()->identifier('must_have_hospital')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_infirmary_#00', true) )->commit();
        $requirement_container->add()->identifier('must_have_guardtower')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_watchmen_#00', true) )->commit();
        $requirement_container->add()->identifier('must_have_crowsnest')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_watchmen_#01', true) )->commit();
        $requirement_container->add()->identifier('must_have_valve')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_valve_#00', true) )->commit();
        $requirement_container->add()->identifier('must_have_cinema')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_cinema_#00', true) )->commit();
        $requirement_container->add()->identifier('must_have_hammam')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_spa4souls_#00', true) )->commit();

        $requirement_container->add()->identifier('must_have_lab')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('item_acid_#00', true) )->commit();
        $requirement_container->add()->identifier('must_not_have_lab')->type( Requirement::MessageOnFail )->add( (new BuildingRequirement())->building('item_acid_#00', false) )->text('Vielleicht solltest du stattdessen dein Labor benutzen...')->commit();
        $requirement_container->add()->identifier('must_have_canteen')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_cafet_#01', true) )->commit();
        $requirement_container->add()->identifier('must_not_have_canteen')->type( Requirement::HideOnFail )->add( (new BuildingRequirement())->building('small_cafet_#01', false) )->commit();

        $requirement_container->add()->identifier('must_not_have_valve')->type( Requirement::MessageOnFail )->add( (new BuildingRequirement())->building('small_valve_#00', false) )->text('Vielleicht solltest du das mithilfe des Wasserhahns füllen...')->commit();
        //</editor-fold>

        //<editor-fold desc="ConfigRequirements">
        $requirement_container->add()->identifier('feature_camping')->type( Requirement::HideOnFail )->add( (new ConfigRequirement())->config(TownConf::CONF_FEATURE_CAMPING, true) )->commit();
        $requirement_container->add()->identifier('during_christmas')->type( Requirement::CrossOnFail )->add( (new ConfigRequirement())->event('christmas') )->text_key('not_in_event')->commit();
        $requirement_container->add()->identifier('must_be_aprils_fools')->type( Requirement::CrossOnFail )->add( (new ConfigRequirement())->event('afools') )->text_key('not_in_event')->commit();
        //</editor-fold>

        //<editor-fold desc="ItemRequirements">
        $requirement_container->add()->identifier('have_can_opener')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->property('can_opener')->store('item_tool') )->text('Du hast nichts, mit dem du dieses Ding aufbekommen könntest..')->commit();
        $requirement_container->add()->identifier('have_box_opener')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->property('box_opener')->store('item_tool') )->text('Du hast nichts, mit dem du dieses Ding aufbekommen könntest..')->commit();
        $requirement_container->add()->identifier('have_parcel_opener')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->property('parcel_opener')->store('item_tool') )->text('Du hast nichts, mit dem du dieses Ding aufbekommen könntest..')->commit();
        $requirement_container->add()->identifier('have_parcel_opener_home')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->property('parcel_opener_h')->store('item_tool') )->text('Du hast nichts, mit dem du dieses Ding aufbekommen könntest..')->commit();

        $requirement_container->add()->identifier('have_can_opener_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('can_opener')->store('item_tool') )->commit();
        $requirement_container->add()->identifier('have_box_opener_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('box_opener')->store('item_tool') )->commit();
        $requirement_container->add()->identifier('have_parcel_opener_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('parcel_opener')->store('item_tool') )->commit();
        $requirement_container->add()->identifier('have_parcel_opener_home_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('parcel_opener_h')->store('item_tool') )->commit();

        $requirement_container->add()->identifier('not_have_can_opener_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('can_opener')->count(0) )->commit();
        $requirement_container->add()->identifier('not_have_box_opener_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('box_opener')->count(0) )->commit();
        $requirement_container->add()->identifier('not_have_parcel_opener_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('parcel_opener')->count(0) )->commit();
        $requirement_container->add()->identifier('not_have_parcel_opener_home_hd')->type( Requirement::HideOnFail )->add( (new ItemRequirement())->property('parcel_opener_h')->count(0) )->commit();

        $requirement_container->add()->identifier('have_water_shaman')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('water_#00') )->text('Du musst etwas Wasser zum Umwandeln haben, um den Trank vorzubereiten.')->commit();
        $requirement_container->add()->identifier('have_water')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('water_#00') )->text_key('item_needed_generic')->commit();
        $requirement_container->add()->identifier('have_canister')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('jerrycan_#00') )->text_key('item_needed_generic')->commit();
        $requirement_container->add()->identifier('have_battery')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('pile_#00') )->text_key('item_needed_generic')->commit();
        $requirement_container->add()->identifier('have_matches')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('lights_#00') )->text_key('item_needed_generic')->commit();
        $requirement_container->add()->identifier('have_2_pharma')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('pharma_#00')->count(2) )->text_key('item_needed_generic')->commit();
        $requirement_container->add()->identifier('must_have_micropur')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('water_cleaner_#00') )->text_key('item_needed_generic')->commit();
        $requirement_container->add()->identifier('must_have_micropur_in')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('water_cleaner_#00') )->text_key('water_purification_impossible')->commit();
        $requirement_container->add()->identifier('must_have_drug')->type( Requirement::MessageOnFail )->add( (new ItemRequirement())->item('drug_#00') )->text_key('item_needed_generic')->commit();
        //</editor-fold>

        //<editor-fold desc="LocationRequirements">
        $requirement_container->add()->identifier('must_be_outside')->type( Requirement::HideOnFail )->add( (new LocationRequirement())->beyond(true) )->commit();
        $requirement_container->add()->identifier('must_be_outside_or_exploring')->type( Requirement::HideOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null) )->commit();
        $requirement_container->add()->identifier('must_be_exploring')->type( Requirement::HideOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(true) )->commit();
        $requirement_container->add()->identifier('must_be_inside')->type( Requirement::HideOnFail )->add( (new LocationRequirement())->town(true) )->commit();
        $requirement_container->add()->identifier('must_be_inside_bp')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->town(true) )->text('Wenn du den Plan studieren willst, musst du in die relative Ruhe der Stadt zurückkehren.')->commit();
        $requirement_container->add()->identifier('must_be_at_buried_ruin')->type( Requirement::CrossOnFail )->add( (new LocationRequirement())->beyond(true)->atBuriedRuin(true) )->text('Wenn du den Plan studieren willst, musst du in die relative Ruhe der Stadt zurückkehren.')->commit();
        $requirement_container->add()->identifier('must_be_outside_not_at_doors')->type( Requirement::HideOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->minAp(1) )->commit();
        $requirement_container->add()->identifier('must_be_outside_3km')->type( Requirement::CrossOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->minKm(3) )->text('Du musst mindestens 3 Kilometer von der Stadt entfernt sein, um das zu tun.')->commit();
        $requirement_container->add()->identifier('must_be_outside_within_hr')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->maxKm(11) )->text('Du bist <strong>zu weit von der Stadt entfernt</strong>, um diese Fähigkeit benutzen zu können! Genauer gesagt bist du {km_from_town} km entfernt. Die maximale Entfernung darf höchstens 11 km betragen.')->commit();

        $requirement_container->add()->identifier('must_have_zombies')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->minZombies(1) )->text('Zum Glück sind hier keine Zombies...')->commit();
        $requirement_container->add()->identifier('must_be_blocked')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->isControlled(false) )->text('Das solltest du nur in einer ausweglosen Situation tun...')->commit();
        $requirement_container->add()->identifier('must_not_be_blocked')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->isControlled(true) )->text('Das kannst du nicht tun während du umzingelt bist...')->commit();
        $requirement_container->add()->identifier('must_have_control')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->isControlledOrTempControlled(true) )->text('Das kannst du nicht tun während du umzingelt bist...')->commit();
        $requirement_container->add()->identifier('must_have_control_hunter')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->beyond(true)->exploring(null)->isControlledOrTempControlled(true) )->text('Das kannst die <strong>Tarnkleidung</strong> nicht verwenden, solange die Zombies diese Zone kontrollieren!')->commit();

        $requirement_container->add()->identifier('zone_is_improvable')->type( Requirement::MessageOnFail )->add( (new LocationRequirement())->beyond(true)->maxLevel(50) )->text('Du bist der Ansicht, dass du diese Zone nicht besser ausbauen kannst, da du schon dein Bestes gegeben hast.')->commit();
        //</editor-fold>

        //<editor-fold desc="HomeRequirements">
        $requirement_container->add()->identifier('must_have_upgraded_home')->type( Requirement::CrossOnFail )->add( (new HomeRequirement())->minLevel(1) )->commit();

        $requirement_container->add()->identifier('must_have_home_lab_v1')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('lab')->minLevel(1)->maxLevel(1) )->commit();
        $requirement_container->add()->identifier('must_have_home_lab_v2')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('lab')->minLevel(2)->maxLevel(2) )->commit();
        $requirement_container->add()->identifier('must_have_home_lab_v3')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('lab')->minLevel(3)->maxLevel(3) )->commit();
        $requirement_container->add()->identifier('must_have_home_lab_v4')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('lab')->minLevel(4)->maxLevel(4) )->commit();

        $requirement_container->add()->identifier('must_have_home_kitchen_v1')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('kitchen')->minLevel(1)->maxLevel(1) )->commit();
        $requirement_container->add()->identifier('must_have_home_kitchen_v2')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('kitchen')->minLevel(2)->maxLevel(2) )->commit();
        $requirement_container->add()->identifier('must_have_home_kitchen_v3')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('kitchen')->minLevel(3)->maxLevel(3) )->commit();
        $requirement_container->add()->identifier('must_have_home_kitchen_v4')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('kitchen')->minLevel(4)->maxLevel(4) )->commit();

        $requirement_container->add()->identifier('must_have_home_rest_v1')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('rest')->minLevel(1)->maxLevel(1) )->commit();
        $requirement_container->add()->identifier('must_have_home_rest_v2')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('rest')->minLevel(2)->maxLevel(2) )->commit();
        $requirement_container->add()->identifier('must_have_home_rest_v3')->type( Requirement::HideOnFail )->add( (new HomeRequirement())->upgrade('rest')->minLevel(3)->maxLevel(3) )->commit();
        //</editor-fold>

        return $requirement_container;
    }
}