<?php

namespace App\DataFixtures;

use App\Entity\ActionCounter;
use App\Entity\AffectAP;
use App\Entity\AffectBlueprint;
use App\Entity\AffectCP;
use App\Entity\AffectDeath;
use App\Entity\AffectHome;
use App\Entity\AffectItemConsume;
use App\Entity\AffectItemSpawn;
use App\Entity\AffectMessage;
use App\Entity\AffectOriginalItem;
use App\Entity\AffectPicto;
use App\Entity\AffectPM;
use App\Entity\AffectResultGroup;
use App\Entity\AffectResultGroupEntry;
use App\Entity\AffectStatus;
use App\Entity\AffectTown;
use App\Entity\AffectWell;
use App\Entity\AffectZombies;
use App\Entity\AffectZone;
use App\Entity\BuildingPrototype;
use App\Entity\CampingActionPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\EscortActionGroup;
use App\Entity\HeroicActionPrototype;
use App\Entity\HomeActionPrototype;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\PictoPrototype;
use App\Entity\RequireAP;
use App\Entity\RequireBuilding;
use App\Entity\RequireConf;
use App\Entity\RequireCounter;
use App\Entity\RequireCP;
use App\Entity\RequireDay;
use App\Entity\RequireHome;
use App\Entity\RequireItem;
use App\Entity\RequireLocation;
use App\Entity\Requirement;
use App\Entity\RequirePM;
use App\Entity\RequireStatus;
use App\Entity\RequireZombiePresence;
use App\Entity\RequireZone;
use App\Entity\Result;
use App\Entity\SpecialActionPrototype;
use App\Structures\TownConf;
use App\Translation\T;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActionFixtures extends Fixture implements DependentFixtureInterface
{
    public static $item_actions = [
        'meta_requirements' => [
            'feature_camping' => [ 'type' => Requirement::HideOnFail, 'collection' => ['conf' => [ 'value' => TownConf::CONF_FEATURE_CAMPING, 'bool' => true ] ] ],

            'never_cross'  => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => true, 'status' => 'tg_never' ] ]],

            'drink_cross'  => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'hasdrunk' ] ]],
            'drink_hide'   => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'hasdrunk' ] ]],
            'drink_rhide'  => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'enabled' => true,  'status' => 'hasdrunk' ] ]],

            'drink_tl0a'  => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'thirst1' ] ]],
            'drink_tl0b'  => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'thirst2' ] ]],

            'drink_tl1'   => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'enabled' => true, 'status' => 'thirst1' ] ]],
            'drink_tl2'   => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'enabled' => true, 'status' => 'thirst2' ] ]],

            'profession_basic'       => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'basic', 'enabled' => true ] ]],
            'profession_collec'      => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'collec', 'enabled' => true ] ]],
            'profession_guardian'    => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'guardian', 'enabled' => true ] ]],
            'profession_hunter'      => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'hunter', 'enabled' => true ] ]],
            'profession_tamer'       => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'tamer', 'enabled' => true ] ]],
            'profession_tech'        => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'tech', 'enabled' => true ] ]],
            'profession_shaman'      => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'shaman', 'enabled' => true ] ]],
            'profession_survivalist' => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'survivalist', 'enabled' => true ] ]],
            'not_profession_tech'    => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'profession' => 'tech', 'enabled' => false ] ]],
            'role_shaman'            => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'role' => 'shaman', 'enabled' => true ] ]],
            'role_ghoul'             => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'role' => 'ghoul', 'enabled' => true ] ]],
            'not_role_ghoul'         => [ 'type' => Requirement::HideOnFail,  'collection' => [ 'status' => [ 'role' => 'ghoul', 'enabled' => false ] ]],

            'no_bonus_ap'    => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'ap' => [ 'min' => 0, 'max' => 0,  'relative' => true ] ], 'text_key' => 'already_full_ap'],
            'no_full_ap'     => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'ap' => [ 'min' => 0, 'max' => -1, 'relative' => true ] ], 'text_key' => 'already_full_ap'],
            'no_full_ap_msg' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'ap' => [ 'min' => 0, 'max' => -1, 'relative' => true ] ], 'text' => 'Das brauchst du gerade nicht ...'],
            'min_6_ap'       => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'ap' => [ 'min' => 6, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 6 AP.'],
            'min_5_ap'       => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'ap' => [ 'min' => 5, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 5 AP.'],
            'min_1_ap'       => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'ap' => [ 'min' => 1, 'max' => 999999, 'relative' => true ] ]],
            'min_1_cp'       => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'cp' => [ 'min' => 1, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 1 CP.'],
            'min_1_pm'       => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'pm' => [ 'min' => 1, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 1 PM.'],
            'min_2_pm'       => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'pm' => [ 'min' => 2, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 2 PM.'],
            'min_3_pm'       => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'pm' => [ 'min' => 3, 'max' => 999999, 'relative' => true ] ], 'text' => 'Hierfür brauchst du mindestens 3 PM.'],
            'not_yet_dice'   => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_dice' ]  ], 'text_key' => 'once_a_day'],
            'not_yet_card'   => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_cards' ] ], 'text_key' => 'once_a_day'],
            'not_yet_guitar' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_guitar' ] ], 'text' => 'Vorsicht, zu viel Musik ist schädlich, und einer deiner Mitbürger hat dieses Instrument heute bereits benutzt. Deine Ohren würden das nicht überleben.'],
            'not_yet_beta'   => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_betadrug' ] ]],
            'not_yet_sbook'  => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_sbook' ] ]],
            'not_yet_hero'   => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_hero' ] ]],
            'not_yet_home_cleaned'  => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_home_clean'  ] ]],
            'not_yet_home_showered' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_home_shower' ] ]],
            'not_yet_home_heal_1'   => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_home_heal_1' ] ]],
            'not_yet_home_heal_2'   => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_home_heal_2' ] ]],
            'not_yet_home_defbuff'  => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_home_defbuff' ] ]],
            'not_yet_rested'   => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_rested' ]  ]],
            'not_yet_immune'   => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_shaman_immune' ]  ]],
            'immune'           => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => true, 'status' => 'tg_shaman_immune' ]  ]],

            'eat_ap'      => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'haseaten' ] ], 'text_key' => 'once_a_day'],

            'drug_1'  => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'drugged' ] ]],
            'drug_2'  => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => true,  'status' => 'drugged' ] ]],

            'not_tired' =>  [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tired' ] ]],

            'is_wounded'      => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => true,  'status' => 'tg_meta_wound' ] ]],
            'is_not_wounded'  => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_meta_wound' ] ]],
            'is_not_wounded_hands'  => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'wound2' ] ]],
            'is_not_bandaged' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'healed' ] ]],

            'is_wounded_h'       => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => true,  'status' => 'tg_meta_wound' ] ]],
            'is_not_wounded_h'   => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_meta_wound' ] ]],
            'is_infected_h'      => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => true,  'status' => 'infection' ] ]],
            'is_not_infected_h'  => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'infection' ] ]],

            'not_drunk'    => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'drunk' ] ]],
            'not_hungover' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'hungover' ] ]],

            'have_can_opener'    => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => null, 'prop' => 'can_opener' ] ], 'text' => 'Du hast nichts, mit dem du dieses Ding aufbekommen könntest..' ],
            'have_box_opener'    => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => null, 'prop' => 'box_opener' ] ], 'text' => 'Du hast nichts, mit dem du dieses Ding aufbekommen könntest..' ],
            'have_can_opener_hd' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'item' => [ 'item' => null, 'prop' => 'can_opener' ] ] ],
            'have_box_opener_hd' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'item' => [ 'item' => null, 'prop' => 'box_opener' ] ] ],
            'not_have_can_opener_hd' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'item' => [ 'item' => null, 'prop' => 'can_opener', 'count' => 0 ] ] ],
            'not_have_box_opener_hd' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'item' => [ 'item' => null, 'prop' => 'box_opener', 'count' => 0 ] ] ],
            'have_water'        => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'water_#00', 'prop' => null ] ],    'text_key' => 'item_needed_generic' ],
            'have_water_shaman' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'water_#00', 'prop' => null ] ],    'text' => 'Du musst etwas Wasser zum Umwandeln haben, um den Trank vorzubereiten.' ],
            'have_canister'   => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'jerrycan_#00', 'prop' => null ] ], 'text_key' => 'item_needed_generic' ],
            'have_battery'    => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'pile_#00',  'prop' => null ] ],    'text_key' => 'item_needed_generic' ],
            'have_matches'    => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'lights_#00', 'prop' => null ] ],   'text_key' => 'item_needed_generic' ],
            'have_2_pharma'   => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'pharma_#00', 'prop' => null, 'count' => 2 ] ], 'text_key' => 'item_needed_generic' ],

            'lab_counter_below_1' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeLab, 'max' => 0 ] ] ],
            'lab_counter_below_4' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeLab, 'max' => 3 ] ] ],
            'lab_counter_below_6' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeLab, 'max' => 5 ] ] ],
            'lab_counter_below_9' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeLab, 'max' => 8 ] ] ],

            'kitchen_counter_below_1' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeKitchen, 'max' => 0 ] ] ],
            'kitchen_counter_below_2' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeKitchen, 'max' => 1 ] ] ],
            'kitchen_counter_below_3' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeKitchen, 'max' => 2 ] ] ],
            'kitchen_counter_below_4' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeKitchen, 'max' => 3 ] ] ],
            'kitchen_counter_below_5' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeKitchen, 'max' => 4 ] ] ],
            'kitchen_counter_below_6' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'counter' => [ 'type' => ActionCounter::ActionTypeHomeKitchen, 'max' => 5 ] ] ],

            'must_be_terrorized'     => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'status' => [ 'enabled' => true,  'status' => 'terror' ] ], 'text' => 'Das brauchst du gerade nicht ...' ],
            'must_be_terrorized_hd'  => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => true,  'status' => 'terror' ] ], 'text' => 'Das brauchst du gerade nicht ...' ],
            'must_not_be_terrorized' => [ 'type' => Requirement::HideOnFail,    'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'terror' ] ], 'text' => 'Das brauchst du gerade nicht ...' ],

            'must_be_outside'              => [ 'type' => Requirement::HideOnFail,    'collection' => [ 'location' => [ RequireLocation::LocationOutside ] ]],
            'must_be_outside_or_exploring' => [ 'type' => Requirement::HideOnFail,    'collection' => [ 'location' => [ RequireLocation::LocationOutsideOrExploring ] ]],
            'must_be_exploring'            => [ 'type' => Requirement::HideOnFail,    'collection' => [ 'location' => [ RequireLocation::LocationExploring ] ]],
            'must_be_inside'               => [ 'type' => Requirement::HideOnFail,    'collection' => [ 'location' => [ RequireLocation::LocationInTown  ] ]],
            'must_be_at_buried_ruin'       => [ 'type' => Requirement::CrossOnFail,   'collection' => [ 'location' => [ RequireLocation::LocationOutsideBuried ] ]],
            'must_be_outside_not_at_doors' => [ 'type' => Requirement::HideOnFail,    'collection' => [ 'location' => [ 'min' => 1 ] ] ],
            'must_be_outside_3km'          => [ 'type' => Requirement::HideOnFail,    'collection' => [ 'location' => [ 'min' => 3 ] ] ],
            'must_be_outside_within_11km'  => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'location' => [ 'max' => 11 ] ], 'text' => 'Hierfür bist du zu weit von der Stadt entfernt ...'],

            'must_have_zombies'   => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'zombies' => [ 'min' => 1, 'block' => null  ] ], 'text' => 'Zum Glück sind hier keine Zombies...'],
            'must_be_blocked'     => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'zombies' => [ 'min' => 1, 'block' => true ] ], 'text' => 'Das kannst du nicht tun während du umzingelt bist...'],
            'must_not_be_blocked' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'zombies' => [ 'min' => 0, 'block' => false ] ], 'text' => 'Das kannst du nicht tun während du umzingelt bist...'],
            'must_have_control'   => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'zombies' => [ 'min' => 0, 'block' => false, 'temp' => true ] ], 'text' => 'Das kannst du nicht tun während du umzingelt bist...'],

            'must_have_micropur' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'water_cleaner_#00', 'prop' => null ] ], 'text_key' => 'item_needed_generic'],
            'must_have_micropur_in' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'water_cleaner_#00', 'prop' => null ] ], 'text_key' => 'water_purification_impossible'],
            'must_have_drug'     => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'item' => [ 'item' => 'drug_#00',          'prop' => null ] ], 'text_key' => 'item_needed_generic'],

            'must_not_be_banished'   => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'ban' => false ] ] ],
            'must_not_be_banished_w' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'status' => [ 'ban' => false ] ], 'text_key' => 'water_purification_impossible' ],
            'must_be_banished'       => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'ban' => true ] ] ],

            'must_have_purifier'     => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'item_jerrycan_#00', 'complete' => true  ] ] ],
            'must_not_have_purifier' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'item_jerrycan_#00', 'complete' => false ] ] ],
            'must_have_filter'       => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'item_jerrycan_#01', 'complete' => true  ] ] ],
            'must_not_have_filter'   => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'item_jerrycan_#01', 'complete' => false ] ] ],

            'must_have_shower'     => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_shower_#00', 'complete' => true  ] ] ],
            'must_have_slaughter'  => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'item_meat_#00', 'complete' => true  ] ] ],
            'must_have_hospital'   => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_infirmary_#00', 'complete' => true  ] ] ],
            'must_have_guardtower' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_watchmen_#00', 'complete' => true  ] ] ],
            'must_have_crowsnest'  => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_watchmen_#01', 'complete' => true  ] ] ],
            'must_have_valve'      => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_valve_#00', 'complete' => true  ] ] ],
            'must_have_cinema'     => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_cinema_#00', 'complete' => true  ] ] ],
            'must_have_hammam'     => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_spa4souls_#00', 'complete' => true  ] ]],

            'must_have_lab'         => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'item_acid_#00', 'complete' => true  ] ]],
            'must_not_have_lab'     => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'building' => [ 'prototype' => 'item_acid_#00', 'complete' => false  ] ], 'text' => 'Vielleicht solltest du stattdessen dein Labor benutzen...' ],
            'must_have_canteen'     => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_cafet_#01', 'complete' => true  ] ]],
            'must_not_have_canteen' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_cafet_#01', 'complete' => false  ] ]],

            'must_not_have_valve'  => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'building' => [ 'prototype' => 'small_valve_#00', 'complete' => false  ] ], 'text' => 'Vielleicht solltest du das mithilfe des Wasserhahns füllen...' ],

            'must_have_upgraded_home' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'home' => [ 'min_level' => 1 ] ]],

            'must_have_home_lab_v1' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 1, 'max_level' => 1, 'upgrade' => 'lab' ] ]],
            'must_have_home_lab_v2' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 2, 'max_level' => 2, 'upgrade' => 'lab' ] ]],
            'must_have_home_lab_v3' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 3, 'max_level' => 3, 'upgrade' => 'lab' ] ]],
            'must_have_home_lab_v4' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 4, 'max_level' => 4, 'upgrade' => 'lab' ] ]],

            'must_have_home_kitchen_v1' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 1, 'max_level' => 1, 'upgrade' => 'kitchen' ] ]],
            'must_have_home_kitchen_v2' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 2, 'max_level' => 2, 'upgrade' => 'kitchen' ] ]],
            'must_have_home_kitchen_v3' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 3, 'max_level' => 3, 'upgrade' => 'kitchen' ] ]],
            'must_have_home_kitchen_v4' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 4, 'max_level' => 4, 'upgrade' => 'kitchen' ] ]],

            'must_have_home_rest_v1' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 1, 'max_level' => 1, 'upgrade' => 'rest' ] ]],
            'must_have_home_rest_v2' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 2, 'max_level' => 2, 'upgrade' => 'rest' ] ]],
            'must_have_home_rest_v3' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'home' => [ 'min_level' => 3, 'max_level' => 3, 'upgrade' => 'rest' ] ]],

            'zone_is_improvable' => [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'zone' => [ 'max_level' => 10 ] ], 'text' => 'Du bist der Ansicht, dass du diese Zone nicht besser ausbauen kannst, da du schon dein Bestes gegeben hast.' ],

            'must_not_be_hidden' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_hide' ] ] ],
            'must_not_be_tombed' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_tomb' ] ] ],
            'must_be_hidden' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => true, 'status' => 'tg_hide' ] ] ],
            'must_be_tombed' => [ 'type' => Requirement::HideOnFail, 'collection' => [ 'status' => [ 'enabled' => true, 'status' => 'tg_tomb' ] ] ],
            'not_before_day_2' => [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'day' => [ 'min' => 2, 'max' => 99 ] ] ],

            'must_be_day' => [ 'collection' =>    [ 'custom' => [1] ] ],
            'must_be_night'  => [ 'collection' => [ 'custom' => [2] ] ],
            'must_be_aprils_fools'  => [ 'collection' => [ 'custom' => [3] ] ],

            'custom_vote_shaman' => [ 'collection' => [ 'custom' => [18] ] ],
            'custom_vote_guide'  => [ 'collection' => [ 'custom' => [19] ] ],
        ],

        'requirements' => [

            'ap' => [],
            'status' => [],
            'item' => [],
            'location' => [],
            'zombies' => [],
            'building' => [],
            'day' => [],
        ],

        'meta_results' => [
            'do_nothing' => [],
            'do_nothing_attack' => ['message' => ['text' => 'Mit aller Kraft schlägst du mehrmals auf einen Zombie ein, aber <strong>es scheint ihm nichts anzuhaben</strong>!']],
            'do_nothing_taser' => ['message' => ['text' => 'Sie greifen einen Zombie mit Ihrem {item} an, aber <strong>er reagiert nicht einmal</strong> und macht weiter!']],

            'contaminated_zone_infect'  => [ 'collection' => [ 'custom' => [22] ] ],

            'consume_item'    => [ 'item' => [ 'consume' => true,  'morph' => null, 'break' => null, 'poison' => null ] ],
            'break_item'      => [ 'item' => [ 'consume' => false, 'morph' => null, 'break' => true, 'poison' => null ], "message" => ['text' => 'Deine Waffe ist durch den harten Aufschlag <strong>kaputt</strong> gegangen...', 'ordering' => 99999] ],
            'cleanse_item'    => [ 'item' => [ 'consume' => false, 'morph' => null, 'break' => true, 'poison' => false ] ],
            'empty_jerrygun'  => [ 'item' => [ 'consume' => false, 'morph' => 'jerrygun_off_#00', 'break' => null, 'poison' => null ] ],

            'consume_water'     => [ 'consume' => [ 'water_#00' ] ],
            'consume_matches'   => [ 'consume' => [ 'lights_#00' ] ],
            'consume_battery'   => [ 'consume' => [ 'pile_#00'  ] ],
            'consume_micropur'  => [ 'consume' => [ 'water_cleaner_#00'  ] ],
            'consume_drug'      => [ 'consume' => [ 'drug_#00'  ] ],
            'consume_jerrycan'  => [ 'consume' => [ 'jerrycan_#00'  ] ],

            'spawn_target'    => [ 'target' => [ 'consume' => false, 'morph' => null, 'break' => null, 'poison' => null ] ],
            'consume_target'  => [ 'target' => [ 'consume' => true, 'morph' => null, 'break' => null, 'poison' => null ] ],
            'repair_target'   => [ 'target' => [ 'consume' => false, 'morph' => null, 'break' => false, 'poison' => null ] ],
            'poison_target'   => [ 'target' => [ 'consume' => false, 'morph' => null, 'break' => null, 'poison' => true  ] ],

            'drink_ap_1'  => [ 'status' => 'add_has_drunk', 'ap' => 'to_max_plus_0' ],
            'drink_ap_2'  => [ 'status' => 'remove_thirst' ],
            'drink_no_ap' => [ 'status' => 'replace_dehydration' ],
            'reset_thirst_counter' => [ 'status' => 'reset_thirst_counter' ],

            'eat_ap6'     => [ 'status' => 'add_has_eaten', 'ap' => 'to_max_plus_0', 'message' => ['escort' => false, 'text' => 'Es schmeckt wirklich komisch... aber es erfüllt seinen Zweck: Dein Hunger ist gestillt. Glaub aber nicht, dass du dadurch zusätzliche APs erhältst...'] ],
            'eat_ap6_silent' => [ 'status' => 'add_has_eaten', 'ap' => 'to_max_plus_0' ],
            'eat_ap7'     => [ 'status' => 'add_has_eaten', 'ap' => 'to_max_plus_1', 'message' => ['escort' => false, 'text' => 'Einmal ist zwar keinmal, dennoch genießt du dein(e) <span class="tool">{item}</span>. Das ist mal ne echte Abwechslung zu dem sonstigen Fraß... Du spürst deine Kräfte wieder zurückkehren.{hr}Du hast <strong>1 zusätzlichen AP erhalten!</strong>'] ],

            'drunk' => [ 'status' => 'add_drunk', 'picto' => ['r_alcool_#00']],

            'drug_any'              => [ 'status' => 'add_is_drugged', 'picto' => ['r_drug_#00'] ],
            'drug_addict'           => [ 'status' => 'add_addicted', 'picto' => ['r_drug_#00'], 'message' => ['text' => '<t-stat-up-addict>Schlechte Neuigkeiten! Du bist jetzt abhängig! Von nun an musst du jeden Tag eine Droge nehmen... oder STERBEN!</t-stat-up-addict>', 'ordering' => 1000] ],
            'drug_addict_no_msg'    => [ 'status' => 'add_addicted', 'picto' => ['r_drug_#00'] ],
            'terrorize'             => [ 'status' => 'add_terror' ],
            'unterrorize'           => [ 'status' => 'remove_terror' ],

            'infect'        => [ 'status' => 'add_infection', 'message' => ['text' => 'Schlechte Nachrichten, das hättest du nicht in den Mund nehmen sollen... Du bist infiziert!'] ],
            'infect_no_msg' => [ 'status' => 'add_infection' ],
            'disinfect'     => [ 'status' => 'remove_infection' ],
            'immune'        => [ 'status' => 'add_immune' ],
            'give_shaman_immune'  => [ 'status' => 'shaman_immune'],

            'minus_1ap'    => [ 'ap' => 'minus_1' ],
            'minus_5ap'    => [ 'ap' => 'minus_5' ],
            'minus_6ap'    => [ 'ap' => 'minus_6' ],
            'minus_1pm'    => [ 'pm' => 'minus_1' ],
            'minus_2pm'    => [ 'pm' => 'minus_2' ],
            'minus_3pm'    => [ 'pm' => 'minus_3' ],
            'minus_1cp'    => [ 'cp' => 'minus_1' ],
            'plus_4ap'     => [ 'ap' => 'plus_4' ],
            'plus_2ap'     => [ 'ap' => 'plus_2' ],
            'plus_2ap_7'   => [ 'ap' => 'plus_2_7' ],
            'just_ap6'     => [ 'ap' => 'to_max_plus_0' ],
            'just_ap7'     => [ 'ap' => 'to_max_plus_1' ],
            'just_ap8'     => [ 'ap' => 'to_max_plus_2' ],
            'plus_ap8_30'  => [ 'ap' => 'plus_8_30' ],

            'april' => [ 'status' => [ 'from' => null, 'to' => 'tg_april_ooze' ] ],

            'produce_watercan3' => [ 'item' => [ 'consume' => false, 'morph' => 'water_can_3_#00' ] ],
            'produce_watercan2' => [ 'item' => [ 'consume' => false, 'morph' => 'water_can_2_#00' ] ],
            'produce_watercan1' => [ 'item' => [ 'consume' => false, 'morph' => 'water_can_1_#00' ] ],
            'produce_watercan0' => [ 'item' => [ 'consume' => false, 'morph' => 'water_can_empty_#00', 'break' => null, 'poison' => false ] ],

            'kill_1_zombie'   => [ 'zombies' => 'kill_1z', 'message' =>  ['text_key' => 'weapon_use'] ],
            'kill_1_zombie_s' => [ 'zombies' => 'kill_1z' ],
            'kill_1_2_zombie' => [ 'zombies' => 'kill_1z_2z', 'message' => ['text_key' => 'weapon_use'] ],
            'kill_2_zombie' => [ 'zombies' => 'kill_2z', 'message' => ['text_key' => 'weapon_use'] ],
            'kill_3_zombie' => [ 'zombies' => 'kill_3z', 'message' => ['text_key' => 'weapon_use'] ],
            'kill_all_zombie' => [ 'zombies' => 'kill_all_z' ],

            'find_rp' => [ 'rp' => [true] ],

            'casino_dice'   => [ 'custom' => [1], 'status' => [ 'from' => null, 'to' => 'tg_dice' ] ],
            'casino_card'   => [ 'custom' => [2], 'status' => [ 'from' => null, 'to' => 'tg_cards' ] ],
            'casino_guitar' => [ 'custom' => [3] ],
            'casino_banned_note' => [ 'custom' => [15] ],

            'heal_wound'  => [ 'status' => 'heal_wound' ],
            'add_bandage' => [ 'status' => 'add_bandage' ],
            'inflict_wound' => [ 'status' => 'inflict_wound' ],

            'zonemarker' => [ 'zone' => ['scout' => true] ],
            'nessquick'  => [ 'zone' => ['uncover' => true] ],

            'cyanide' => [ 'death' => [ CauseOfDeath::Cyanide ] ],
            'death_poison' => [ 'death' => [ CauseOfDeath::Poison ] ],

            'hero_tamer_1'  => [ 'custom' => [4] ],
            'hero_tamer_2'  => [ 'custom' => [5] ],
            'hero_tamer_1b' => [ 'custom' => [16] ],
            'hero_tamer_2b' => [ 'custom' => [17] ],
            'hero_tamer_3' => [ 'item' => [ 'consume' => false, 'morph' => 'tamed_pet_drug_#00' ] ],

            'hero_surv_0' => [ 'status' => [ 'from' => null, 'to' => 'tg_sbook' ] ],
            'hero_surv_1' => [ 'custom' => [6] ],
            'hero_surv_2' => [ 'custom' => [7] ],

            'hero_act'    => [ 'status' => [ 'from' => null, 'to' => 'tg_hero' ] ],
            'hero_immune' => [ 'status' => [ 'from' => null, 'to' => 'hsurvive' ] ],

            'hero_hunter' => [ 'item' => [ 'consume' => false, 'morph' => 'vest_on_#00' ] ],

            'camp_hide' => [ 'status' => [ 'from' => null, 'to' => 'tg_hide' ] ],
            'camp_tomb' => [ 'status' => [ 'from' => null, 'to' => 'tg_tomb' ] ],
            'camp_unhide' => [ 'status' => [ 'from' => 'tg_hide', 'to' => null ] ],
            'camp_untomb' => [ 'status' => [ 'from' => 'tg_tomb', 'to' => null ] ],

            'home_lab_success' => [ 'spawn' => 'lab_success_drugs', 'picto' => ['r_drgmkr_#00'] ],
            'home_lab_failure' => [ 'spawn' => 'lab_fail_drugs' ],

            'home_kitchen_success' => [ 'spawn' => 'kitchen_success_food', 'picto' => ['r_cookr_#00'] ],
            'home_kitchen_failure' => [ 'spawn' => 'kitchen_fail_food' ],

            'become_ghoul'    => [ 'status' => 'become_ghoul'    ],
            'become_ghoul_5'  => [ 'status' => 'become_ghoul_5'  ],
            'become_ghoul_25' => [ 'status' => 'become_ghoul_25' ],
        ],

        'results' => [
            'ap' => [
                'to_max_plus_0' => [ 'max' => true,  'num' => 0 ],
                'to_max_plus_1' => [ 'max' => true,  'num' => 1 ],
                'to_max_plus_2' => [ 'max' => true,  'num' => 2 ],
                'to_max_plus_3' => [ 'max' => true,  'num' => 3 ],
                'plus_4'        => [ 'max' => false, 'num' => 4 ],
                'plus_2'        => [ 'max' => false, 'num' => 2 ],
                'plus_2_7'      => [ 'max' => false, 'num' => 2, 'bonus' => 1  ],
                'plus_8_30'     => [ 'max' => false, 'num' => 8, 'bonus' => 24 ],
                'minus_1'       => [ 'max' => false, 'num' => -1 ],
                'minus_5'       => [ 'max' => false, 'num' => -5 ],
                'minus_6'       => [ 'max' => false, 'num' => -6 ],
            ],
            'pm' => [
                'to_max_plus_0' => [ 'max' => true,  'num' => 0 ],
                'minus_1'       => [ 'max' => false, 'num' => -1 ],
                'minus_2'       => [ 'max' => false, 'num' => -2 ],
                'minus_3'       => [ 'max' => false, 'num' => -3 ],
            ],
            'cp' => [
                'minus_1'       => [ 'max' => false, 'num' => -1 ],
            ],
            'status' => [
                'replace_dehydration' => [ 'from' => 'thirst2', 'to' => 'thirst1' ],
                'add_has_drunk' => [ 'from' => null, 'to' => 'hasdrunk' ],
                'remove_thirst' => [ 'from' => 'thirst1', 'to' => null ],
                'remove_dehydration' => [ 'from' => 'thirst2', 'to' => null ],
                'reset_thirst_counter' => [ 'reset_thirst' => true ],

                'add_infection'   => [ 'from' => null, 'to' => 'infection' ],
                'remove_infection'=> [ 'from' => 'infection', 'to' => null ],
                'add_immune'      => [ 'from' => null, 'to' => 'immune'],

                'add_drunk' => [ 'from' => null, 'to' => 'drunk' ],

                'add_has_eaten'  => [ 'from' => null, 'to' => 'haseaten' ],
                'add_is_drugged' => [ 'from' => null, 'to' => 'drugged' ],
                'add_addicted'   => [ 'from' => null, 'to' => 'addict' ],
                'add_terror'     => [ 'from' => null, 'to' => 'terror' ],
                'remove_terror'  => [ 'from' => 'terror', 'to' => null ],

                'inflict_wound' => [ 'from' => null, 'to' => 'tg_meta_wound' ],
                'heal_wound'    => [ 'from' => 'tg_meta_wound', 'to' => null ],
                'add_bandage'   => [ 'from' => null, 'to' => 'healed' ],
                'shaman_immune'   => [ 'from' => null, 'to' => 'tg_shaman_immune' ],

                'increase_lab_counter'     => [ 'counter' => ActionCounter::ActionTypeHomeLab ],
                'increase_kitchen_counter' => [ 'counter' => ActionCounter::ActionTypeHomeKitchen ],

                'become_ghoul'    => [ 'role' => 'ghoul', 'enabled' => true  ],
                'become_ghoul_5'  => [ 'role' => 'ghoul', 'enabled' => true, 'hunger' => 5, 'force' => true  ],
                'become_ghoul_25' => [ 'role' => 'ghoul', 'enabled' => true, 'hunger' => 25, 'force' => true ],
                'heal_ghoul'   => [ 'role' => 'ghoul', 'enabled' => false, 'hunger' => -9999999, 'force' => true ],
                'satisfy_ghoul_50' => [ 'hunger' => -50 ],
                'satisfy_ghoul_30' => [ 'hunger' => -30 ],
                'satisfy_ghoul_10' => [ 'hunger' => -15 ],
            ],
            'item' => [],

            'spawn' => [
                'xmas_dv' => [ ['omg_this_will_kill_you_#00', 8], ['pocket_belt_#00', 8], ['christmas_candy_#00', 8], 'rp_scroll_#00', 'rp_manual_#00', 'rp_sheets_#00', 'rp_letter_#00', 'rp_scroll_#00', 'rp_book_#00', 'rp_book_#01', 'rp_book2_#00' ],
                'xmas_3'  => [ 'omg_this_will_kill_you_#00' ],
                'xmas_2'  => [ 'christmas_candy_#00' ],
                'xmas_1'  => [ 'rp_letter_#00' ],
                'matbox'  => [ 'wood2_#00', 'metal_#00' ],

                'metalbox'  => [ 'drug_#00', 'bandage_#00', /*'vodka_de_#00',*/ 'vodka_#00', ['explo_#00', 2], 'lights_#00', 'drug_hero_#00', 'rhum_#00' ],
                'metalbox2' => [ ['watergun_opt_part_#00',2], ['pilegun_upkit_#00',2], ['pocket_belt_#00',2], ['cutcut_#00',2], ['chainsaw_part_#00',2], ['mixergun_part_#00',2], 'big_pgun_part_#00', ['lawn_part_#00',2] ],
                'catbox'    => [ 'poison_part_#00', 'pet_cat_#00', 'angryc_#00' ],
                'toolbox'   => [ 'pile_#00', ['meca_parts_#00', 2], 'rustine_#00', 'tube_#00', 'pharma_#00', ['explo_#00', 2], 'lights_#00' ],
                'foodbox'   => [ 'food_bag_#00', 'can_#00', 'meat_#00', 'hmeat_#00', ['vegetable_#00', 2] ],

                'phone'  => [ 'what' => ['deto_#00', 'metal_bad_#00', 'pile_broken_#00', 'electro_#00'], 'where' => AffectItemSpawn::DropTargetFloor ],
                'proj'   => [ 'lens_#00' ],
                'empty_battery' => [ 'what' => ['pile_broken_#00'], 'where' => AffectItemSpawn::DropTargetFloor ],
                'battery' => [ 'what' => ['pile_#00'], 'where' => AffectItemSpawn::DropTargetFloor ],
                'safe'  => [ ['watergun_opt_part_#00',10], ['big_pgun_part_#00',5], ['lawn_part_#00',10], ['chainsaw_part_#00',10], ['mixergun_part_#00',10], ['cutcut_#00',10], ['pilegun_upkit_#00',10], ['book_gen_letter_#00',5], ['pocket_belt_#00',15], ['meca_parts_#00',10] ],
                'asafe' => [ 'bplan_e_#00' ],

                'lab_fail_drugs'    => [ 'what' => ['drug_#00', 'xanax_#00', 'drug_random_#00', 'drug_water_#00', 'water_cleaner_#00'], "where" => AffectItemSpawn::DropTargetFloor  ],
                'lab_success_drugs' => [ 'what' => ['drug_hero_#00'], "where" => AffectItemSpawn::DropTargetFloor ],

                'kitchen_fail_food'    => [ 'what' => ['dish_#00'], 'where' => AffectItemSpawn::DropTargetFloor ],
                'kitchen_success_food' => [ 'what' => ['dish_tasty_#00'], 'where' => AffectItemSpawn::DropTargetFloor ],

                'meat_4xs' => [ 'what' => [ ['meat_#00',  4] ], 'where' => AffectItemSpawn::DropTargetFloor],
                'meat_4x'  => [ 'what' => [ ['undef_#00', 4] ], 'where' => AffectItemSpawn::DropTargetFloor],
                'meat_2xs' => [ 'what' => [ ['meat_#00',  2] ], 'where' => AffectItemSpawn::DropTargetFloor],
                'meat_2x'  => [ 'what' => [ ['undef_#00', 2] ], 'where' => AffectItemSpawn::DropTargetFloor],
                'meat_bmb' => [ 'what' => [ ['flesh_#00', 2] ], 'where' => AffectItemSpawn::DropTargetFloor],

                'potion'   => [ 'what' => [ [ 'potion_#00', 1] ], "where" => AffectItemSpawn::DropTargetFloor  ],
            ],

            'consume' => [
                '2_pharma' => [ 'pharma_#00', 2 ]
            ],

            'bp' => [],

            'group' => [
                'g_break_15' => [[['do_nothing'], 85], [['break_item'], 15]],
                'g_break_20' => [[['do_nothing'], 80], [['break_item'], 20]],
                'g_break_25' => [[['do_nothing'], 75], [['break_item'], 25]],
                'g_break_30' => [[['do_nothing'], 70], [['break_item'], 30]],
                'g_break_33' => [[['do_nothing'], 67], [['break_item'], 33]],
                'g_break_40' => [[['do_nothing'], 60], [['break_item'], 40]],
                'g_break_50' => [[['do_nothing'], 50], [['break_item'], 50]],
                'g_break_60' => [[['do_nothing'], 40], [['break_item'], 60]],
                'g_break_66' => [[['do_nothing'], 34], [['break_item'], 66]],
                'g_break_75' => [[['do_nothing'], 25], [['break_item'], 75]],
                'g_break_80' => [[['do_nothing'], 20], [['break_item'], 80]],
                'g_break_90' => [[['do_nothing'], 10], [['break_item'], 90]],

                'g_kill_1z_10' => [[['do_nothing_attack'], 90], [['kill_1_zombie'], 10]],
                'g_kill_1z_20' => [[['do_nothing_attack'], 80], [['kill_1_zombie'], 20]],
                'g_kill_1z_25' => [[['do_nothing_attack'], 75], [['kill_1_zombie'], 25]],
                'g_kill_1z_33' => [[['do_nothing_attack'], 67], [['kill_1_zombie'], 33]],
                'g_kill_1z_50' => [[['do_nothing_attack'], 50], [['kill_1_zombie'], 50]],
                'g_kill_1z_60' => [[['do_nothing_attack'], 40], [['kill_1_zombie'], 60]],
                'g_kill_1z_75' => [[['do_nothing_attack'], 25], [['kill_1_zombie'], 75]],
                'g_kill_1z_80' => [[['do_nothing_attack'], 20], [['kill_1_zombie'], 80]],
                'g_kill_1z_85' => [[['do_nothing_attack'], 15], [['kill_1_zombie'], 85]],
                'g_kill_1z_95' => [[['do_nothing_attack'],  5], [['kill_1_zombie'], 95]],

                'g_kill_1z_50_taser' => [[['do_nothing_taser'], 50], [['kill_1_zombie'], 50]],

                'g_kill_2z_80' => [[['do_nothing_attack'], 20], [['kill_2_zombie'], 80]],
                'g_immune_90' => [[['do_nothing'], 10], [['give_shaman_immune'], 90]],

                'g_empty_jerrygun'  => [[['do_nothing'], 85], [['empty_jerrygun'], 15]],
            ],

            'zombies' => [
                'kill_1z_2z'    => [ 'min' => 1, 'max' => 2 ],
                'kill_1z' => [ 'num' => 1 ],
                'kill_2z' => [ 'num' => 2 ],
                'kill_3z' => [ 'num' => 3 ],
                'kill_all_z' => [ 'num' => 999999 ],
            ],

            'well' => [],
        ],

        'actions' => [
            'water_tl0'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'no_full_ap', 'drink_cross', 'drink_tl0a', 'drink_tl0b' ], 'result' => ['contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1',                'consume_item' ], 'escort_message_key' => 'escort_water_drink' ],
            'water_tl1a' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_hide',  'drink_tl1'                ], 'result' => ['contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1', 'drink_ap_2',  'consume_item' ], 'escort_message_key' => 'escort_water_drink' ],
            'water_tl1b' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_rhide', 'drink_tl1'                ], 'result' => ['contaminated_zone_infect', 'reset_thirst_counter',               'drink_ap_2',  'consume_item' ], 'escort_message_key' => 'escort_water_drink' ],
            'water_tl2'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',                              'drink_tl2'                ], 'result' => ['contaminated_zone_infect', 'reset_thirst_counter',               'drink_no_ap', 'consume_item' ], 'message_key' => 'water_drink_dehydration' ],
            'water_g'    => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'role_ghoul', 'is_not_wounded', 'drink_cross' ], 'result' => [ 'inflict_wound', 'consume_item' ], 'message_key' => 'water_drink_ghoul' ],

            'potion_tl0a'        => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'not_yet_immune',               'drink_hide',  'drink_tl0a', 'drink_tl0b'  ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1',                'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Du hast soeben den mystischen Trank getrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl0b'        => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'not_yet_immune',               'drink_rhide', 'drink_tl0a', 'drink_tl0b'  ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',                              'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Du hast soeben den mystischen Trank getrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl1a'        => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'not_yet_immune',               'drink_hide',   'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1', 'drink_ap_2',  'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Du hast soeben den mystischen Trank getrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl1b'        => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'not_yet_immune',               'drink_rhide',  'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_ap_2',  'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Du hast soeben den mystischen Trank getrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl2'         => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'not_yet_immune',                               'drink_tl2'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_no_ap', 'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Du hast soeben den mystischen Trank getrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_g'           => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'role_ghoul', 'not_yet_immune', 'drink_cross' ], 'result' => [ 'inflict_wound', 'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Du hast soeben den mystischen Trank getrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],

            'potion_tl0a_immune' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'immune',                       'drink_hide',  'drink_tl0a', 'drink_tl0b' ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1',                'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Tja, Vertrauen ist gut, Kontrolle ist besser... Ja, du wurdest bereits geschützt!', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl0b_immune' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'immune',                       'drink_rhide', 'drink_tl0a', 'drink_tl0b' ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',                              'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Tja, Vertrauen ist gut, Kontrolle ist besser... Ja, du wurdest bereits geschützt!', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl1a_immune' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'immune',                       'drink_hide',  'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1', 'drink_ap_2',  'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Tja, Vertrauen ist gut, Kontrolle ist besser... Ja, du wurdest bereits geschützt!', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl1b_immune' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'immune',                       'drink_rhide', 'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_ap_2',  'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Tja, Vertrauen ist gut, Kontrolle ist besser... Ja, du wurdest bereits geschützt!', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_tl2_immune'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'immune',                                      'drink_tl2'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_no_ap', 'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Tja, Vertrauen ist gut, Kontrolle ist besser... Ja, du wurdest bereits geschützt!', 'escort_message' => 'Der Bürger hat den mystischen Trank in einem Zug ausgetrunken. Hoffen wir, dass dieser Schamane weiß, was er tut...' ],
            'potion_g_immune'    => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'role_ghoul', 'immune', 'drink_cross' ], 'result' => [ 'inflict_wound', 'consume_item', ['group' => 'g_immune_90'], ], 'message' => 'Tja, Vertrauen ist gut, Kontrolle ist besser... Ja, du wurdest bereits geschützt!' ],

            'watercan3_tl0'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'no_full_ap', 'drink_cross', 'drink_tl0a', 'drink_tl0b' ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1',                'produce_watercan2' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan3_tl1a' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_hide',  'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1', 'drink_ap_2',  'produce_watercan2' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan3_tl1b' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_rhide', 'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_ap_2',  'produce_watercan2' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan3_tl2'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',                              'drink_tl2'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_no_ap', 'produce_watercan2' ], 'message_key' => 'water_drink_dehydration' ],
            'watercan3_g'    => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'role_ghoul', 'is_not_wounded', 'drink_cross' ], 'result' => [ 'inflict_wound', 'produce_watercan2' ], 'message_key' => 'water_drink_ghoul' ],

            'watercan2_tl0'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'no_full_ap', 'drink_cross', 'drink_tl0a', 'drink_tl0b' ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1',                'produce_watercan1' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan2_tl1a' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_hide',  'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1', 'drink_ap_2',  'produce_watercan1' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan2_tl1b' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_rhide', 'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_ap_2',  'produce_watercan1' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan2_tl2'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',                              'drink_tl2'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_no_ap', 'produce_watercan1' ], 'message_key' => 'water_drink_dehydration' ],
            'watercan2_g'    => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'role_ghoul', 'is_not_wounded', 'drink_cross' ], 'result' => [ 'inflict_wound', 'produce_watercan1' ], 'message_key' => 'water_drink_ghoul' ],

            'watercan1_tl0'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul', 'no_full_ap', 'drink_cross', 'drink_tl0a', 'drink_tl0b' ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1',                'produce_watercan0' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan1_tl1a' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_hide',  'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter', 'drink_ap_1', 'drink_ap_2',  'produce_watercan0' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan1_tl1b' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',               'drink_rhide', 'drink_tl1'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_ap_2',  'produce_watercan0' ], 'escort_message_key' => 'escort_water_drink' ],
            'watercan1_tl2'  => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_role_ghoul',                              'drink_tl2'                ], 'result' => [ 'contaminated_zone_infect', 'reset_thirst_counter',               'drink_no_ap', 'produce_watercan0' ], 'message_key' => 'water_drink_dehydration' ],
            'watercan1_g'    => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'role_ghoul', 'is_not_wounded', 'drink_cross' ], 'result' => [ 'inflict_wound', 'produce_watercan0' ], 'message_key' => 'water_drink_ghoul' ],

            'water_no_effect' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ ], 'result' => [ 'contaminated_zone_infect', 'consume_item' ], 'message' => 'Du hast {item} getrunken, aber scheinbar geschieht nichts...'  ],

            'alcohol'    => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_drunk', 'not_hungover' ], 'result' => [ 'contaminated_zone_infect', 'just_ap6', 'drunk', 'consume_item' ], 'message' => 'Dir ist schwindelig und du würdest dich am liebsten übergeben... Egal was, Hauptsache <strong>du bekommst wieder einen klaren Kopf</strong>.' ],
            'alcohol_dx' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ ], 'result' => [ 'contaminated_zone_infect', 'just_ap6', 'drunk', 'consume_item' ], 'message' => 'Dir ist schwindelig und du würdest dich am liebsten übergeben... Egal was, Hauptsache <strong>du bekommst wieder einen klaren Kopf</strong>.' ],

            'coffee' => [ 'label' => 'Trinken', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ ], 'result' => [ 'contaminated_zone_infect', 'plus_4ap', 'consume_item' ], 'message' => 'Dieses Gefühl des Wohlbefindens, das dieser kleine Kaffee hervorruft, bringt dich sofort wieder auf die Beine. Aah!' ],

            'special_dice'   => [ 'label' => 'Werfen',       'cover' => true, 'at00' => true, 'meta' => [ 'not_yet_dice', 'no_bonus_ap' ],      'result' => [ 'casino_dice'   ], 'message' => '{casino}' ],
            'special_card'   => [ 'label' => 'Karte ziehen', 'cover' => true, 'at00' => true, 'meta' => [ 'not_yet_card', 'no_bonus_ap' ],      'result' => [ 'casino_card'   ], 'message' => '{casino}' ],
            'special_guitar' => [ 'label' => 'Spielen',      'meta' => [ 'not_yet_guitar', 'must_be_inside' ], 'result' => [ 'casino_guitar' ], 'message' => '{casino}' ],

            'can'       => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'not_profession_tech', 'have_can_opener', 'is_not_wounded_hands' ], 'result' => [ [ 'item' => [ 'consume' => false, 'morph' => 'can_open_#00' ] ] ], 'message_key' => 'container_open_tool' ],
            'can_t1'    => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'profession_tech', 'have_can_opener_hd', 'is_not_wounded_hands' ], 'result' => [ [ 'item' => [ 'consume' => false, 'morph' => 'can_open_#00' ] ] ], 'message_key' => 'container_open_tool' ],
            'can_t2'    => [ 'label' => 'Öffnen (1 BP)', 'at00' => true, 'meta' => [ 'profession_tech', 'not_have_can_opener_hd', 'min_1_cp' ], 'result' => [ 'minus_1cp', [ 'item' => [ 'consume' => false, 'morph' => 'can_open_#00' ] ] ], 'message_key' => 'container_open' ],

            'eat_6ap'   => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'no_full_ap' ], 'result' => [ 'contaminated_zone_infect', 'eat_ap6', 'consume_item' ], 'escort_message_key' => 'escort_food_eat' ],
            'eat_7ap'   => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'no_full_ap' ], 'result' => [ 'contaminated_zone_infect', 'eat_ap7', 'consume_item' ], 'escort_message_key' => 'escort_food_eat' ],

            'drug_xana1' => [ 'label' => 'Einsetzen', 'cover' => true, 'at00' => true, 'allow_when_terrorized' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'must_be_terrorized_hd' ],  'result' => [ 'contaminated_zone_infect', 'drug_any', 'unterrorize', 'consume_item' ], 'message_key' => 'drug_xanax' ],
            'drug_xana2' => [ 'label' => 'Einsetzen', 'cover' => true, 'at00' => true, 'allow_when_terrorized' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'must_be_terrorized_hd' ],  'result' => [ 'contaminated_zone_infect', 'drug_addict', 'unterrorize', 'consume_item' ], 'message_key' => 'drug_xanax' ],
            'drug_xana3' => [ 'label' => 'Einsetzen', 'cover' => true, 'at00' => true, 'allow_when_terrorized' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'must_not_be_terrorized' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'consume_item' ], 'message_key' => 'drug_no_use' ],
            'drug_xana4' => [ 'label' => 'Einsetzen', 'cover' => true, 'at00' => true, 'allow_when_terrorized' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'must_not_be_terrorized' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'consume_item' ], 'message_key' => 'drug_no_use' ],
            'drug_par_1' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'is_not_infected_h' ], 'result' => [ ['message' => ['text' => 'Die Medizin gibt dir Kraft: Du bist jetzt immun gegen Infektionen und kannst nicht in einen Ghul verwandelt werden. Diese Wirkung lässt nach dem Angriff nach.']], 'contaminated_zone_infect', 'drug_any', 'immune', 'consume_item' ], 'message_key' => 'drug_no_use_2' ],
            'drug_par_2' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'is_not_infected_h' ], 'result' => [ ['message' => ['text' => 'Die Medizin gibt dir Kraft: Du bist jetzt immun gegen Infektionen und kannst nicht in einen Ghul verwandelt werden. Diese Wirkung lässt nach dem Angriff nach.']], 'contaminated_zone_infect', 'drug_addict', 'immune', 'consume_item' ], 'message_key' => 'drug_no_use_2' ],
            'drug_par_3' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'is_infected_h' ], 'result' => [ ['message' => ['text' => 'Die Medizin gibt dir Kraft: Du bist jetzt immun gegen Infektionen und kannst nicht in einen Ghul verwandelt werden. Diese Wirkung lässt nach dem Angriff nach.']], 'contaminated_zone_infect', 'drug_any', 'disinfect', 'immune', 'consume_item' ], 'message_key' => 'drug_para' ],
            'drug_par_4' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'is_infected_h' ], 'result' => [ ['message' => ['text' => 'Die Medizin gibt dir Kraft: Du bist jetzt immun gegen Infektionen und kannst nicht in einen Ghul verwandelt werden. Diese Wirkung lässt nach dem Angriff nach.']], 'contaminated_zone_infect', 'drug_addict', 'disinfect', 'immune', 'consume_item' ], 'message_key' => 'drug_para' ],
            'drug_6ap_1' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'just_ap6', 'consume_item' ], 'message_key' => 'drug_normal_ap' ],
            'drug_6ap_2' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'just_ap6', 'consume_item' ], 'message_key' => 'drug_normal_ap' ],
            'drug_7ap_1' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'just_ap7', 'consume_item' ] ],
            'drug_7ap_2' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'just_ap7', 'consume_item' ] ],
            'drug_8ap_1' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'just_ap8', 'consume_item' ], 'message_key' => 'drug_twin_ap' ],
            'drug_8ap_2' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'just_ap8', 'consume_item' ], 'message_key' => 'drug_twin_ap' ],

            'drug_april_1' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'must_be_aprils_fools' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'just_ap8', 'april', 'consume_item' ], 'message_key' => 'drug_no_use_3' ],
            'drug_april_2' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'must_be_aprils_fools' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'just_ap8', 'april', 'consume_item' ], 'message_key' => 'drug_no_use_3' ],

            'drug_hyd_1' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'drink_tl0a', 'drink_tl0b' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'consume_item' ], 'message_key' => 'drug_no_use' ],
            'drug_hyd_2' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'drink_tl0a', 'drink_tl0b' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'consume_item' ], 'message_key' => 'drug_no_use' ],
            'drug_hyd_3' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'drink_tl1' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'drink_ap_2', 'consume_item' ], 'message_key' => 'drug_hyd' ],
            'drug_hyd_4' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'drink_tl1' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'drink_ap_2', 'consume_item' ], 'message_key' => 'drug_hyd' ],
            'drug_hyd_5' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1', 'drink_tl2' ], 'result' => [ 'contaminated_zone_infect', 'drug_any', 'drink_no_ap', 'consume_item' ], 'message_key' => 'drug_hyd' ],
            'drug_hyd_6' => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2', 'drink_tl2' ], 'result' => [ 'contaminated_zone_infect', 'drug_addict', 'drink_no_ap', 'consume_item' ], 'message_key' => 'drug_hyd' ],

            'drug_beta'  => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_yet_beta' ], 'result' => [ ['ap' => [ 'max' => true,  'num' => 20 ], 'status' => [ 'from' => null, 'to' => 'tg_betadrug' ]] ] ],
            'cyanide'    => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'not_before_day_2' ], 'result' => [ 'contaminated_zone_infect', 'cyanide', 'consume_item' ] ],

            'bandage' => [ 'label' => 'Verbinden', 'at00' => true, 'meta' => [ 'is_wounded', 'is_not_bandaged' ], 'result' => [ 'heal_wound', 'consume_item', 'add_bandage' ], 'message' => 'So, zur Desinfektion nur noch draufspucken und hopp: Sieht wie neu aus!' ],
            'emt'     => [ 'label' => 'Einsetzen', 'cover' => true, 'at00' => true, 'meta' => [ 'is_not_wounded' ], 'result' => [ 'just_ap6', 'inflict_wound', ['item' => [ 'consume' => false, 'morph' => 'sport_elec_empty_#00' ]], ['picto' => ['r_maso_#00']] ], 'message' => 'Es geht doch nichts über einen schönen Stromstoß in die Wirbelsäule, um so richtig wach zu werden! Aber irgendwie riecht es jetzt hier nach verbranntem Fleisch...' ],

            'drug_rand_1'  => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_1' ], 'result' => [ 'contaminated_zone_infect', 'consume_item', ['picto' => ['r_cobaye_#00']], ['group' => [
                [ ['drug_any', 'just_ap6', ['message' => [ 'text_key' => 'drug_normal_ap' ]]], 5 ],
                [ ['drug_any', 'terrorize', ['message' => [ 'text_key' => 'drug_terror' ]]], 2 ],
                [ ['drug_any', 'drug_addict', 'just_ap7', ['message' => [ 'text_key' => 'drug_addict_ap' ]]], 2 ],
                [ ['do_nothing', ['message' => [ 'text_key' => 'drug_no_effect' ]]], 1 ],
            ]] ] ] ,
            'drug_rand_2'  => [ 'label' => 'Einnehmen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'drug_2' ], 'result' => [ 'contaminated_zone_infect', 'consume_item', ['picto' => ['r_cobaye_#00']], ['group' => [
                [ ['drug_addict', 'just_ap6', ['message' => [ 'text_key' => 'drug_normal_ap' ]]], 5 ],
                [ ['drug_addict', 'terrorize', ['message' => [ 'text_key' => 'drug_terror' ]]], 2 ],
                [ ['drug_addict', 'just_ap7', ['message' => [ 'text_key' => 'drug_addict_ap' ]]], 2 ],
                [ ['do_nothing', ['message' => [ 'text_key' => 'drug_no_effect' ]]], 1 ],
            ]] ] ] ,
            'drug_rand_xmas'  => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap' ], 'result' => [ 'contaminated_zone_infect', 'consume_item', ['picto' => ['r_cobaye_#00']], ['group' => [
                [ ['plus_ap8_30', ['message' => ['text' => 'Du schluckst das Bonbon mit einem Lächeln auf den Lippen herunter.']]], 22 ],
                [ ['plus_ap8_30', 'drug_addict_no_msg', ['message' => ['text' => 'Du schluckst das Bonbon mit einem Lächeln auf den Lippen herunter... das jedoch schnell wieder verschwindet! Die Füllung besteht aus einem <strong>starken psychoaktiven Gift!</strong><t-stat-up-addict>{hr}Du bist jetzt ein Süchtiger!</t-stat-up-addict>']]], 13 ],
                [ ['plus_ap8_30', 'terrorize', ['message' => ['text' => 'Du schluckst das Bonbon mit einem Lächeln auf den Lippen herunter... das jedoch schnell wieder verschwindet! Die Füllung besteht aus einem <strong>starken psychoaktiven Gift!</strong><t-stat-up-terror>{hr}Du bist vor Angst erstarrt!</t-stat-up-terror>']]], 57 ],
                [ ['plus_ap8_30', 'infect_no_msg', ['message' => ['text' => 'Du schluckst das Bonbon mit einem Lächeln auf den Lippen herunter... das jedoch schnell wieder verschwindet! Die Füllung besteht aus einem <strong>starken psychoaktiven Gift!</strong><t-stat-up-infection>{hr}Du bist jetzt infiziert!</t-stat-up-infection>']]], 29 ],
                [ ['death_poison'], 2 ],
            ]] ] ] ,

            'open_doggybag'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'food_pims_#00', 'food_tarte_#00', 'food_chick_#00', 'food_biscuit_#00', 'food_bar3_#00', 'food_bar1_#00', 'food_sandw_#00', 'food_bar2_#00' ] ] ], 'message' => 'Du hast dein <span class="tool">{item}</span> ausgepackt und <span class="tool">{items_spawn}</span> erhalten!' ],
            'open_lunchbag'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'food_candies_#00', 'food_noodles_hot_#00', 'vegetable_tasty_#00', 'meat_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_c_chest'   => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'pile_#00', 'radio_off_#00', 'pharma_#00', 'lights_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_h_chest'   => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'watergun_empty_#00', 'pilegun_empty_#00', 'flash_#00', 'repair_one_#00', 'smoke_bomb_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_postbox'   => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'money_#00', 'rp_book_#00', 'rp_book_#01', 'rp_sheets_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_postbox_xl'=> [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'machine_gun_#00', 'rsc_pack_2_#00', 'rhum_#00', 'vibr_empty_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_letterbox' => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'rp_book2_#00', 'rp_manual_#00', 'rp_scroll_#00', 'rp_scroll_#01', 'rp_sheets_#00', 'rp_letter_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_justbox'   => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'money_#00', 'rp_book_#00', 'rp_book_#01', 'rp_sheets_#00' ] ] ], 'message_key' => 'container_open' ],

            'open_gamebox'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'dice_#00', 'cards_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_abox'     => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ 'bplan_r_#00' ] ] ], 'message_key' => 'container_open' ],
            'open_cbox'     => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => [ ['bplan_c_#00',50], ['bplan_u_#00',35], ['bplan_r_#00',10], ['bplan_e_#00',5]] ] ], 'message_key' => 'container_open_cbox' ],

            'open_matbox3'   => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ [ 'item' => [ 'consume' => false, 'morph' => 'rsc_pack_2_#00' ],  'spawn' => 'matbox' ] ], 'message_key' => 'container_open_not_empty' ],
            'open_matbox2'   => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ [ 'item' => [ 'consume' => false, 'morph' => 'rsc_pack_1_#00' ],  'spawn' => 'matbox' ] ], 'message_key' => 'container_open_not_empty' ],
            'open_matbox1'   => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => 'matbox' ] ], 'message_key' => 'container_open_empty' ],

            'open_xmasbox3'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ [ 'item' => [ 'consume' => false, 'morph' => 'chest_christmas_2_#00' ],  'spawn' => 'xmas_3' ] ], 'message_key' => 'container_open_not_empty' ],
            'open_xmasbox2'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ [ 'item' => [ 'consume' => false, 'morph' => 'chest_christmas_1_#00' ],  'spawn' => 'xmas_2' ] ], 'message_key' => 'container_open_not_empty' ],
            'open_xmasbox1'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => ['is_not_wounded_hands'], 'result' => [ 'consume_item', [ 'spawn' => 'xmas_1' ] ], 'message_key' => 'container_open_empty' ],

            'open_metalbox'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'not_profession_tech', 'have_can_opener', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'metalbox' ] ], 'message_key' => 'container_open_tool' ],
            'open_metalbox2' => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'not_profession_tech', 'have_can_opener', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'metalbox2' ] ], 'message_key' => 'container_open_tool' ],
            'open_catbox'    => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'not_profession_tech', 'have_can_opener', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'catbox' ] ], 'message_key' => 'container_open_tool' ],
            'open_toolbox'    => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'not_profession_tech', 'have_box_opener', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'toolbox' ] ], 'message_key' => 'container_open_weapon' ],
            'open_foodbox'    => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'not_profession_tech', 'have_box_opener', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'foodbox' ] ], 'message_key' => 'container_open_weapon' ],

            'open_metalbox_t1'  => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'profession_tech', 'have_can_opener_hd', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'metalbox' ] ], 'message_key' => 'container_open_tool' ],
            'open_metalbox2_t1' => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'profession_tech', 'have_can_opener_hd', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'metalbox2' ] ], 'message_key' => 'container_open_tool' ],
            'open_catbox_t1'    => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'profession_tech', 'have_can_opener_hd', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'catbox' ] ], 'message_key' => 'container_open_tool' ],
            'open_toolbox_t1'    => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'profession_tech', 'have_box_opener_hd', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'toolbox' ] ], 'message_key' => 'container_open_weapon' ],
            'open_foodbox_t1'    => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'profession_tech', 'have_box_opener_hd', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', [ 'spawn' => 'foodbox' ] ], 'message_key' => 'container_open_weapon' ],

            'open_metalbox_t2'  => [ 'label' => 'Öffnen (1 BP)', 'at00' => true, 'meta' => [ 'profession_tech', 'not_have_can_opener_hd', 'min_1_cp' ], 'result' => [ 'minus_1cp', 'consume_item', [ 'spawn' => 'metalbox' ] ], 'message_key' => 'container_open' ],
            'open_metalbox2_t2' => [ 'label' => 'Öffnen (1 BP)', 'at00' => true, 'meta' => [ 'profession_tech', 'not_have_can_opener_hd', 'min_1_cp' ], 'result' => [ 'minus_1cp', 'consume_item', [ 'spawn' => 'metalbox2' ] ], 'message_key' => 'container_open' ],
            'open_catbox_t2'    => [ 'label' => 'Öffnen (1 BP)', 'at00' => true, 'meta' => [ 'profession_tech', 'not_have_can_opener_hd', 'min_1_cp' ], 'result' => [ 'minus_1cp', 'consume_item', [ 'spawn' => 'catbox' ] ], 'message_key' => 'container_open' ],
            'open_toolbox_t2'    => [ 'label' => 'Öffnen (1 BP)', 'at00' => true, 'meta' => [ 'profession_tech', 'not_have_box_opener_hd', 'min_1_cp' ], 'result' => [ 'minus_1cp', 'consume_item', [ 'spawn' => 'toolbox' ] ], 'message_key' => 'container_open' ],
            'open_foodbox_t2'    => [ 'label' => 'Öffnen (1 BP)', 'at00' => true, 'meta' => [ 'profession_tech', 'not_have_box_opener_hd', 'min_1_cp' ], 'result' => [ 'minus_1cp', 'consume_item', [ 'spawn' => 'foodbox' ] ], 'message_key' => 'container_open' ],

            'open_safe'      => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'min_1_ap', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'minus_1ap', ['group' => [ [['do_nothing'], 95], [ ['consume_item', [ 'spawn' =>  'safe' ]], 5 ] ]] ], 'message_key' => 'container_optional' ],
            'open_asafe'     => [ 'label' => 'Öffnen', 'at00' => true, 'meta' => [ 'min_1_ap', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'minus_1ap', ['group' => [ [['do_nothing'], 95], [ ['consume_item', [ 'spawn' => 'asafe' ]], 5 ] ]] ], 'message_key' => 'container_optional' ],

            'load_pilegun'   => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'pilegun_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_pilegun2'  => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'pilegun_up_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_pilegun3'  => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'big_pgun_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_mixergun'  => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'mixergun_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_chainsaw'  => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'chainsaw_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_taser'     => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'taser_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_lpointer'  => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'lpoint4_#00' ] ] ], 'message_key' => 'item_load' ],

            'load_lamp'      => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'lamp_on_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_dildo'     => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'vibr_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_rmk2'      => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'radius_mk2_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_maglite'   => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'maglite_2_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_radio'     => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'radio_on_#00' ] ] ], 'message_key' => 'item_load' ],
            'load_emt'       => [ 'label' => 'Laden', 'at00' => true, 'meta' => [ 'have_battery' ], 'result' => [ 'consume_battery', [ 'item' => [ 'consume' => false, 'morph' => 'sport_elec_#00' ] ] ], 'message_key' => 'item_load' ],

            'light_cig' => [ 'label' => 'Rauchen', 'allow_when_terrorized' => true, 'meta' => [ 'have_matches', 'must_be_terrorized' ], 'result' => [ ['group' => [ [['do_nothing'],20], [['consume_matches'],80]]], ['group' => [ [['do_nothing'],90], [['consume_item'],10]]], 'unterrorize' ], 'message' => 'Du zündest eine Zigarette an. Der Rauch lässt dich kräftig Husten, vermutlich weil du daran nicht gewöhnt bist... Nach ein paar Minuten <strong>gelingt es dir dich zu beruhigen</strong>. Dieser Kurze Moment des Friedens ermöglicht lässt dich deinen Kummer vergessen und bringt dich zurück in die Wirklichkeit.<hr />Du drückst den Stummel auf dem Boden aus und bist bereit für einen weiteren Tag des Überlebens.!<t-consumed><hr />Dir fällt auf dass <strong>die Packung leer ist</strong>!</t-consumed>' ],

            'fill_jsplash'   => [ 'label' => 'Befüllen', 'at00' => true, 'meta' => [ 'have_canister' ], 'result' => [ 'consume_jerrycan', [ 'item' => [ 'consume' => false, 'morph' => 'jerrygun_#00' ] ] ], 'message_key' => 'item_fill' ],

            'fill_asplash1'   => [ 'label' => 'Befüllen', 'meta' => [ 'have_water', 'must_not_have_valve', 'must_be_inside' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'watergun_opt_5_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_asplash2'   => [ 'label' => 'Befüllen', 'at00' => true, 'meta' => [ 'have_water', 'must_be_outside_or_exploring' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'watergun_opt_5_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_splash1'    => [ 'label' => 'Befüllen', 'meta' => [ 'have_water', 'must_not_have_valve', 'must_be_inside' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'watergun_3_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_splash2'    => [ 'label' => 'Befüllen', 'at00' => true, 'meta' => [ 'have_water', 'must_be_outside_or_exploring' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'watergun_3_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_ksplash1'   => [ 'label' => 'Befüllen', 'meta' => [ 'have_water', 'must_not_have_valve', 'must_be_inside' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'kalach_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_ksplash2'   => [ 'label' => 'Befüllen', 'at00' => true, 'meta' => [ 'have_water', 'must_be_outside_or_exploring' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'kalach_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_grenade1'   => [ 'label' => 'Befüllen', 'meta' => [ 'have_water', 'must_not_have_valve', 'must_be_inside' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'grenade_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_grenade2'   => [ 'label' => 'Befüllen', 'at00' => true, 'meta' => [ 'have_water', 'must_be_outside_or_exploring' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'grenade_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_exgrenade1' => [ 'label' => 'Befüllen', 'meta' => [ 'have_water', 'must_not_have_valve', 'must_be_inside' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'bgrenade_#00' ] ] ], 'message_key' => 'item_fill' ],
            'fill_exgrenade2' => [ 'label' => 'Befüllen', 'at00' => true, 'meta' => [ 'have_water', 'must_be_outside_or_exploring' ], 'result' => [ 'consume_water', [ 'item' => [ 'consume' => false, 'morph' => 'bgrenade_#00' ] ] ], 'message_key' => 'item_fill' ],

            'fill_watercan0' => [ 'label' => 'Befüllen', 'at00' => true, 'poison' => ItemAction::PoisonHandlerTransgress, 'meta' => [ 'have_water' ], 'result' => [ 'consume_water', 'produce_watercan1' ], 'message_key' => 'item_fill' ],
            'fill_watercan1' => [ 'label' => 'Befüllen', 'at00' => true, 'poison' => ItemAction::PoisonHandlerTransgress, 'meta' => [ 'have_water' ], 'result' => [ 'consume_water', 'produce_watercan2' ], 'message_key' => 'item_fill' ],
            'fill_watercan2' => [ 'label' => 'Befüllen', 'at00' => true, 'poison' => ItemAction::PoisonHandlerTransgress, 'meta' => [ 'have_water' ], 'result' => [ 'consume_water', 'produce_watercan3' ], 'message_key' => 'item_fill' ],

            'fire_pilegun'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'spawn' => 'empty_battery', 'item' => ['morph' => 'pilegun_empty_#00',    'consume' => false], 'group' => 'g_kill_1z_95', 'message' => [ 'text_key' => 'battery_destroyed', 'ordering' => 1000]  ] ] ],
            'fire_pilegun2'  => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'group' => [ [[ ['spawn' => 'battery',       'item' => ['morph' => 'pilegun_up_empty_#00', 'consume' => false], 'message' => [ 'text_key' => 'battery_dropped', 'ordering' => 1000]] ], 8],  [[ ['spawn' => 'empty_battery', 'item' => ['morph' => 'pilegun_up_empty_#00', 'consume' => false], 'message' => [ 'text_key' => 'battery_destroyed', 'ordering' => 1000]] ], 2 ] ] ], 'kill_1_zombie' ] ],
            'fire_pilegun3'  => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'group' => [ [[ ['spawn' => 'empty_battery', 'item' => ['morph' => 'big_pgun_empty_#00',   'consume' => false], 'message' => [ 'text_key' => 'battery_destroyed', 'ordering' => 1000]] ], 51], [[ ['spawn' => 'battery',     'item' => ['morph' => 'big_pgun_empty_#00',   'consume' => false], 'message' => [ 'text_key' => 'battery_dropped', 'ordering' => 1000]] ], 49] ] ], 'kill_2_zombie' ] ],
            'fire_mixergun'  => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'group' => [ [['do_nothing'],  6], [[ [ 'item' => ['morph' => 'mixergun_empty_#00', 'consume' => false], 'message' => [ 'text_key' => 'battery_use', 'ordering' => 1000] ] ], 4] ] ], 'kill_1_zombie' ] ],
            'fire_chainsaw'  => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'group' => [ [['do_nothing'],  7], [[ [ 'item' => ['morph' => 'chainsaw_empty_#00', 'consume' => false], 'message' => [ 'text_key' => 'battery_use', 'ordering' => 1000] ] ], 3] ] ], 'kill_3_zombie' ] ],
            'fire_taser'     => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'group' => 'g_kill_1z_50_taser' ], [ 'group' => [ [['do_nothing'],  2], [[ [ 'item' => ['morph' => 'taser_empty_#00', 'consume' => false], 'message' => [ 'text_key' => 'battery_use'] ]], 8] ] ] ] ],
            'fire_lpointer4' => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'lpoint3_#00', 'consume' => false] ], 'kill_2_zombie' ] ],
            'fire_lpointer3' => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'lpoint2_#00', 'consume' => false] ], 'kill_2_zombie' ] ],
            'fire_lpointer2' => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'lpoint1_#00', 'consume' => false] ], 'kill_2_zombie' ] ],
            'fire_lpointer1' => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'lpoint_#00',  'consume' => false] ], 'kill_2_zombie' ] ],

            'fire_asplash5'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_opt_4_#00',     'consume' => false] ], 'kill_1_zombie' ] ],
            'fire_asplash4'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_opt_3_#00',     'consume' => false] ], 'kill_1_zombie' ] ],
            'fire_asplash3'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_opt_2_#00',     'consume' => false] ], 'kill_1_zombie' ] ],
            'fire_asplash2'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_opt_1_#00',     'consume' => false] ], 'kill_1_zombie' ] ],
            'fire_asplash1'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_opt_empty_#00', 'consume' => false] ], 'kill_1_zombie' ] ],
            'fire_splash3'    => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_2_#00',         'consume' => false] ], 'kill_1_zombie' ] ],
            'fire_splash2'    => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_1_#00',         'consume' => false] ], 'kill_1_zombie' ] ],
            'fire_splash1'    => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'item' => ['morph' => 'watergun_empty_#00',     'consume' => false] ], 'kill_1_zombie' ] ],

            'throw_animal'        => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ 'consume_item', 'kill_1_zombie_s', ['picto' => ['r_animal_#00']] ], 'message_key' => 'throw_animal' ],
            'throw_animal_cat'    => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'group' => [ [['do_nothing'], 88], [['consume_item', ['picto' => ['r_animal_#00']]], 12] ] ], 'kill_1_zombie_s' ], 'message_key' => 'throw_animal' ],
            'throw_animal_dog'    => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ [ 'group' => [ [['do_nothing'], 95], [['consume_item', ['picto' => ['r_animal_#00']]], 5] ] ], 'kill_1_zombie_s' ], 'message_key' => 'throw_animal' ],
            'throw_animal_angryc' => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside', 'must_have_zombies' ], 'result' => [ 'consume_item', [ 'group' => [ [['inflict_wound'], 1], [[ 'kill_all_zombie'], 2] ] ], ['picto' => ['r_animal_#00']] ], 'message' => '<t-stat-up-tg_meta_wound>Diese Katze ist unglaublich! Sie scheint keine Angst zu haben, nicht einmal vor dir. Das Tier springt dir an die Kehle und vergräbt seine Krallen tief in deinem Fleisch.</t-stat-up-tg_meta_wound><t-kills>Diese Katze ist unglaublich! Sie scheint keine Angst zu haben, nicht einmal vor dem abartigen Gestank der Zombies. Mit mehr Zerstörungskraft als der Duracell-Hase und das Killer-Kaninchen von Caerbannog hat sie die Zone komplett bereinigt.</t-kills>' ],

            'throw_b_machine_1'     => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_30'], 'kill_1_zombie' ] ],
            'throw_b_bone'          => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_75'], 'kill_1_zombie' ] ],
            'throw_b_can_opener'    => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'break_item', ['group' => 'g_kill_1z_33'] ] ],
            'throw_b_chair_basic'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_30'], ['group' => 'g_kill_1z_85'] ] ],
            'throw_b_torch'         => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired' ], 'result' => [ ['item' => ['morph' => 'torch_off_#00', 'consume' => false]], 'kill_1_zombie' ] ],
            'throw_b_chain'         => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_30'], ['group' => 'g_kill_1z_50'] ] ],
            'throw_b_staff'         => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => [[['do_nothing'], 50], [[ [ 'item' => [ 'consume' => false, 'morph' => 'staff2_#00']], ['message' => ['text' => 'Deine Waffe ist durch den harten Aufschlag <strong>kaputt</strong> gegangen...', 'ordering' => 99999]] ], 70]]], ['group' => 'g_kill_1z_33'] ] ],
            'throw_b_knife'         => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_20'], 'kill_1_zombie' ] ],
            'throw_b_machine_2'     => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_30'], 'kill_1_zombie' ] ],
            'throw_b_small_knife'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_50'], ['group' => 'g_kill_1z_33'] ] ],
            'throw_b_cutcut'        => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_25'], 'kill_2_zombie' ] ],
            'throw_b_machine_3'     => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_50'], 'kill_1_zombie' ] ],
            'throw_b_pc'            => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_50'], 'kill_1_zombie' ] ],
            'throw_b_lawn'          => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_15'], 'kill_2_zombie' ] ],
            'throw_b_screw'         => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_50'], ['group' => 'g_kill_1z_25'] ] ],
            'throw_b_swiss_knife'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_50'], ['group' => 'g_kill_1z_33'] ] ],
            'throw_b_cutter'        => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_90'], ['group' => 'g_kill_1z_50'] ] ],
            'throw_b_concrete_wall' => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_20'], 'kill_1_zombie' ] ],
            'throw_b_torch_off'     => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_50'], ['group' => 'g_kill_1z_10'] ] ],
            'throw_b_wrench'        => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_33'], ['group' => 'g_kill_1z_50'] ] ],
            'throw_hurling_stick'   => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ ['group' => 'g_break_15'], ['group' => 'g_kill_1z_60'] ] ],
            'throw_phone'           => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', ['spawn' => 'phone'] , 'kill_1_2_zombie' ] ],
            'throw_projector'       => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'consume_item', ['spawn' => 'proj'] , 'kill_1_zombie' ] ],

            'throw_grenade'         => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ 'consume_item', ['zombies' => [ 'min' => 2, 'max' =>  4 ]] ] ],
            'throw_exgrenade'       => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ 'consume_item', ['zombies' => [ 'min' => 6, 'max' => 10 ]] ] ],
            'throw_boomfruit'       => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ 'consume_item', ['zombies' => [ 'min' => 5, 'max' =>  9 ]] ] ],
            'throw_jerrygun'        => [ 'label' => 'Waffe einsetzen', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies' ], 'result' => [ 'kill_1_zombie', ['group' => 'g_empty_jerrygun', 'message' => ['text' => '<nt-morphed>Gute Nachrichten: Es ist noch Wasser im Kanister!</nt-morphed><t-morphed><strong>Der Kanister ist LEER</strong>!</t-morphed>']]] ],

            'bp_generic_1'          => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => [1] ] ], 'message_key' => 'read_blueprint' ],
            'bp_generic_2'          => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => [2] ] ], 'message_key' => 'read_blueprint' ],
            'bp_generic_3'          => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => [3] ] ], 'message_key' => 'read_blueprint' ],
            'bp_generic_4'          => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => [4] ] ], 'message_key' => 'read_blueprint' ],

            'bp_hotel_2'    => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['small_bamba_#00', 'small_catapult3_#00','small_howlingbait_#00', 'small_trash_#01', 'small_trash_#02', 'small_trash_#04', 'small_court_#00', 'item_plate_#03'] ] ],                     'message_key' => 'read_blueprint' ],
            'bp_hotel_3'    => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['small_sprinkler_#00', 'item_digger_#00', 'item_shield_#00', 'small_city_up_#00', 'small_falsecity_#00', 'small_lastchance_#00', 'small_lighthouse_#00', 'small_strategy_#00', 'small_valve_#00'] ] ], 'message_key' => 'read_blueprint' ],
            'bp_hotel_4'    => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['small_cinema_#00', 'small_derrick_#01', 'small_trash_#06', 'small_castle_#00', 'small_coffin_#00'] ] ],                                              'message_key' => 'read_blueprint' ],

            'bp_bunker_2'   => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['item_bgrenade_#00', 'item_bgrenade_#01', 'small_trash_#03', 'small_trash_#05', 'small_watercanon_#00', 'small_tourello_#00', 'small_armor_#00'] ] ],                                             'message_key' => 'read_blueprint' ],
            'bp_bunker_3'   => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['item_home_def_#00', 'item_tube_#00', 'small_labyrinth_#00', 'small_eden_#00', 'small_rocket_#00', 'small_rocketperf_#00', 'small_trashclean_#00', 'small_valve_#00', 'item_jerrycan_#01'] ] ], 'message_key' => 'read_blueprint' ],
            'bp_bunker_4'   => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['small_waterdetect_#00', 'small_arma_#00', 'small_slave_#00', 'small_trash_#06', 'small_wheel_#00'] ] ],                                                                                         'message_key' => 'read_blueprint' ],

            'bp_hospital_2' => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['small_ikea_#00', 'item_hmeat_#00', 'small_tourello_#00', 'small_watchmen_#00'] ] ],                                                                            'message_key' => 'read_blueprint' ],
            'bp_hospital_3' => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['item_digger_#00', 'item_jerrycan_#01', 'item_shield_#00', 'small_appletree_#00', 'small_chicken_#00', 'small_infirmary_#00', 'small_trashclean_#00', 'small_lighthouse_#00', 'small_rocketperf_#00'] ] ], 'message_key' => 'read_blueprint' ],
            'bp_hospital_4' => [ 'label' => 'Lesen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['bp' => ['small_strategy_#01', 'small_balloon_#00', 'small_crow_#00', 'small_derrick_#01', 'small_pmvbig_#00'] ] ],                                                                                               'message_key' => 'read_blueprint' ],

            'read_rp' => [ 'label' => 'Lesen', 'cover' => true, 'at00' => true, 'meta' => [], 'result' => [ 'consume_item', 'find_rp' ], 'message' => 'Der Text ist überschrieben mit {rp_text}. Du beginnst, ihn zu lesen<t-rp_ok>! Der Text wurde deinem Archiv hinzugefügt.</t-rp_ok><t-rp_fail>... Leider stellst du fest, dass du diesen Text bereits kennst.</t-rp_fail>' ],

            'read_banned_note' => [ 'label' => 'Lesen', 'cover' => true, 'at00' => true, 'meta' => [], 'result' => [ 'consume_item', 'casino_banned_note' ], 'message' => 'Der Text ist überschrieben mit {item}. Du beginnst, ihn zu lesen.<t-bannote_ok>Diese gekritzelte Notiz gehörte früher einem verbanntem Bürger... Das einzige, was du lesen kannst, ist "{zone}"... Seltsam. Du zerstörst die Seite, nur um sicherzugehen, dass niemand die Nachricht liest...</t-bannote_ok><t-bannote_fail>Leider ist der Inhalt dieses Manuskripts völlig unleserlich.</t-bannote_fail>' ],

            'vibrator' => [ 'label' => 'Verwenden', 'meta' => [ 'must_be_inside', 'must_be_terrorized' ], 'result' => [ 'unterrorize', ['item' => ['morph' => 'vibr_empty_#00', 'consume' => false]], ['picto' => ['r_maso_#00']] ], 'message' => 'Du machst es dir daheim gemütlich und entspannst dich... doch dann erlebst du ein böse Überraschung: Dieses Ding ist unglaublich schmerzhaft! Du versuchst es weiter bis du Stück für Stück Gefallen daran findest. Die nach wenige Minuten einsetzende Wirkung ist berauschend! Du schwitzt und zitterst und ein wohlig-warmes Gefühl breitet sich in dir aus...Die Batterie ist komplett leer.' ],

            'watercup_1' => [ 'label' => 'Reinigen (Wasser)', 'meta' => [ 'must_be_inside', 'must_have_micropur_in', 'must_not_have_purifier', 'must_not_have_filter', 'must_not_be_banished' ], 'result' => [ 'consume_micropur', 'consume_item', ['spawn' => [ ['water_cup_#00', 2] ], 'picto' => ['r_solban_#00'] ] ], 'message_key' => 'item_clean_watercup' ],
            'watercup_2' => [ 'label' => 'Reinigen (Wasser)', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_micropur' ], 'result' => [ 'consume_micropur', 'consume_item', ['spawn' => [ ['water_cup_#00', 2] ], 'picto' => ['r_solban_#00'] ] ], 'message_key' => 'item_clean_watercup' ],
            'watercup_3' => [ 'label' => 'In den Brunnen schütten', 'meta' => [ 'must_be_inside', 'must_have_purifier', 'must_not_be_banished' ], 'result' => [ 'consume_item', [ 'well' => [ 'min' => 2, 'max' => 2 ] ] ], 'message_key' => 'water_to_well' ],
            'jerrycan_1' => [ 'label' => 'Reinigen (Wasser)', 'meta' => [ 'must_be_inside', 'must_have_micropur_in', 'must_not_have_purifier', 'must_not_be_banished' ], 'result' => [ 'consume_micropur', 'consume_item', ['group' => [
                [ [ ['spawn' => [ ['water_#00', 2] ] ] ], 1 ],
                [ [ ['spawn' => [ ['water_#00', 3] ] ] ], 1 ]
            ]] ], 'message_key' => 'item_clean' ],
            'jerrycan_2' => [ 'label' => 'In den Brunnen schütten', 'meta' => [ 'must_be_inside', 'must_have_purifier', 'must_not_have_filter', 'must_not_be_banished' ], 'result' => [ 'consume_item', [ 'well' => [ 'min' => 1, 'max' => 3 ] ] ], 'message_key' => 'water_to_well' ],
            'jerrycan_3' => [ 'label' => 'In den Brunnen schütten', 'meta' => [ 'must_be_inside', 'must_have_filter', 'must_not_be_banished' ], 'result' => [ 'consume_item', [ 'well' => [ 'min' => 4, 'max' => 9 ] ] ], 'message_key' => 'water_to_well' ],

            'watercup_1b' => [ 'label' => 'Reinigen (Wasser)', 'meta' => [ 'must_be_inside',  'must_have_micropur_in', 'must_be_banished' ], 'result' => [ 'consume_micropur', 'consume_item', ['spawn' => [ ['water_cup_#00', 2] ], 'picto' => ['r_solban_#00'] ] ], 'message_key' => 'item_clean_watercup' ],
            'jerrycan_1b' => [ 'label' => 'Reinigen (Wasser)', 'meta' => [ 'must_be_inside', 'must_have_micropur_in', 'must_be_banished' ], 'result' => [ 'consume_micropur', 'consume_item', ['group' => [
                [ [ ['spawn' => [ ['water_#00', 2] ] ] ], 1 ],
                [ [ ['spawn' => [ ['water_#00', 3] ] ] ], 1 ]
            ]] ], 'message_key' => 'item_clean' ],

            'home_def_plus'    => [ 'label' => 'Aufstellen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['home' => ['def' => 1]], ['picto' => ['r_hbuild_#00']], ['message' => ['text' => 'Sorgfältig befestigst du bei dir daheim ein(e) {item}. So und das hält jetzt, so viel steht schon mal fest.{hr}Dieser Gegenstand gibt deinem Haus permament <strong>{home_defense} zusätzliche Verteidigungspunkt(e).</strong>']] ] ],
            'home_store_plus'  => [ 'label' => 'Aufstellen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['home' => ['store' => 1]], ['picto' => ['r_hbuild_#00']], ['message' => ['text' => 'Du stellst den(die) {item} bei dir daheim auf. Zugegeben, es sieht nicht gerade ästhetisch aus, aber mal ganz ehrlich: Wen kümmert das?{hr}Dieser Gegenstand erweitert deine Truhe dauerhaft um soviele freie Plätze: <strong>{home_storage}</strong>.']] ] ],
            'home_store_plus2' => [ 'label' => 'Aufstellen', 'meta' => [ 'must_be_inside' ], 'result' => [ 'consume_item', ['home' => ['store' => 2]], ['picto' => ['r_hbuild_#00']], ['message' => ['text' => 'Du stellst den(die) {item} bei dir daheim auf. Zugegeben, es sieht nicht gerade ästhetisch aus, aber mal ganz ehrlich: Wen kümmert das?{hr}Dieser Gegenstand erweitert deine Truhe dauerhaft um soviele freie Plätze: <strong>{home_storage}</strong>.']] ] ],

            'repair_1' => [ 'label' => 'Reparieren mit', 'at00' => true, 'target' => ['broken' => true], 'meta' => [ 'min_1_ap', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'minus_1ap', 'consume_item', 'repair_target', ['picto' => ['r_repair_#00'] ] ], 'message' => 'Du hast das {item} verbraucht, um damit {target} zu reparieren. Dabei hast du {minus_ap} AP eingesetzt.' ],
            'repair_2' => [ 'label' => 'Reparieren mit', 'at00' => true, 'target' => ['broken' => true], 'meta' => [ 'min_1_ap', 'not_tired', 'is_not_wounded_hands' ], 'result' => [ 'minus_1ap', ['item' => ['consume' => false, 'morph' => 'repair_kit_part_#00'], 'picto' => ['r_repair_#00'] ], 'repair_target' ], 'message' => 'Du hast das {item} verbraucht, um damit {target} zu reparieren. Dabei hast du {minus_ap} AP eingesetzt.' ],
            'poison_1' => [ 'label' => 'Vergiften mit', 'at00' => true,  'target' => ['type' => ItemTargetDefinition::ItemSelectionTypePoison, 'property' => 'can_poison', 'poison' => false], 'meta' => [ ],               'result' => [ 'consume_item', 'poison_target' ], 'message' => 'Du hast {target} mit {item} kombiniert und {target} erzeugt.{hr}Achtung: Du hast {target} vergiftet. Es ist <strong>nahezu unmöglich, es vom Original zu unterscheiden</strong>, sei also vorsichtig... Es liegt ganz an dir, was du damit jetzt tun möchtest.' ],

            'zonemarker_1' => [ 'label' => 'Einsetzen', 'cover' => true, 'at00' => true, 'meta' => [ ], 'result' => [ 'consume_item', 'zonemarker' ], 'message' => 'Mithilfe des {item} hast du die Umgebung gescannt.' ],
            'zonemarker_2' => [ 'label' => 'Einsetzen', 'cover' => true, 'at00' => true, 'meta' => [ ], 'result' => [ ['group' => [ ['do_nothing', 2], [ [['item' => ['consume' => false, 'morph' => 'radius_mk2_part_#00'] ]], 1 ] ]], 'zonemarker' ], 'message' => 'Mithilfe des {item} hast du die Umgebung gescannt.' ],
            'nessquick'    => [ 'label' => 'Einsetzen', 'meta' => [ 'must_be_outside', 'must_be_at_buried_ruin' ], 'result' => [ 'consume_item', 'nessquick' ], 'message' => 'Du hast das Gebiet mit deinem {item} teilweise geräumt ({bury_count} Punkte geräumt).' ],

            'bomb_1'    => [ 'label' => 'Werfen', 'cover' => true, 'meta' => [ 'must_be_outside', 'must_be_blocked' ], 'result' => [ 'consume_item', [ 'zone' => ['escape' =>  40] ] ], 'message_key' => 'escape_item' ],
            'bomb_2'    => [ 'label' => 'Werfen', 'cover' => true, 'meta' => [ 'must_be_outside', 'must_be_blocked' ], 'result' => [ 'consume_item', [ 'zone' => ['escape' => 300] ] ], 'message_key' => 'escape_item' ],

            'smokebomb' => [ 'label' => 'Werfen', 'meta' => [ 'must_be_outside_not_at_doors' ], 'result' => [ 'consume_item', [ 'zone' => ['chatSilence' => 60] ] ], 'message' => 'Du wirfst eine Rauchbombe in diese Zone und ein Großes Durcheinander bricht aus!{hr}Deine <strong>nächste Bewegungsaktion</strong> wird night in das Register eingetragen, wenn sie <strong>innerhalb von 1 Minute</strong> erfolgt.' ],

            'eat_fleshroom_1'  => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'no_full_ap', 'not_role_ghoul' ], 'result' => [ 'contaminated_zone_infect', 'eat_ap6', 'consume_item', ['group' => [ ['do_nothing', 9], ['become_ghoul_25', 1] ]] ] ],
            'eat_fleshroom_2'  => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'no_full_ap', 'role_ghoul' ],     'result' => [ 'contaminated_zone_infect', 'eat_ap6', 'consume_item' ], ],

            'eat_meat_1'    => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'no_full_ap', 'not_role_ghoul' ], 'result' => [ 'contaminated_zone_infect', 'eat_ap6_silent', 'consume_item', ['picto' => ['r_cannib_#00'], 'group' => [ ['do_nothing', 9], ['become_ghoul_25', 1] ]] ], 'message_key' => 'eat_human_meat' ],
            'eat_meat_2'    => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'role_ghoul' ],     'result' => [ 'contaminated_zone_infect', 'eat_ap6', 'consume_item', ['picto' => ['r_cannib_#00'], 'status' => 'satisfy_ghoul_10' ] ], ],

            'eat_bone_1'    => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'no_full_ap', 'not_role_ghoul' ], 'result' => [ 'contaminated_zone_infect', 'eat_ap6_silent', ['picto' => ['r_cannib_#00'], 'item' => ['consume' => false, 'morph' => 'bone_#00'], 'group' => [ ['do_nothing', 9], [ 'infect', 10 ], ['become_ghoul_25', 1] ]] ], 'message_key' => 'eat_human_meat' ],
            'eat_bone_2'    => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'role_ghoul' ],     'result' => [ 'contaminated_zone_infect', 'eat_ap6', ['picto' => ['r_cannib_#00'], 'item' => ['consume' => false, 'morph' => 'bone_#00'], 'status' => 'satisfy_ghoul_10' ] ], ],

            'eat_cadaver_1' => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'no_full_ap', 'not_role_ghoul' ], 'result' => [ 'contaminated_zone_infect', 'eat_ap6', ['picto' => ['r_cannib_#00'], 'item' => ['consume' => false, 'morph' => 'cadaver_remains_#00'] ], ['group' => [ [ 'infect', 1 ], ['become_ghoul_5', 9] ]] ] ],
            'eat_cadaver_2' => [ 'label' => 'Essen', 'cover' => true, 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'eat_ap', 'role_ghoul' ],     'result' => [ 'contaminated_zone_infect', 'eat_ap6', ['picto' => ['r_cannib_#00'], 'item' => ['consume' => false, 'morph' => 'cadaver_remains_#00'], 'status' => 'satisfy_ghoul_30' ] ] ],

            'ghoul_serum' => [ 'label' => 'Einnehmen', 'at00' => true, 'poison' => ItemAction::PoisonHandlerConsume, 'meta' => [ 'role_ghoul' ], 'result' => [ 'consume_item', ['status' => 'heal_ghoul' ] ], 'message' => 'Unglaublich! Die ganze Gier, die dich innerlich aufgefressen hat, verschwindet langsam. Ist es wirklich möglich, dass du wieder ein Mensch geworden bist?' ],

            'cuddle_teddy_1' => [ 'label' => 'Knuddeln', 'allow_when_terrorized' => true, 'meta' => [ 'must_be_terrorized', [ 'type' => Requirement::CrossOnFail, 'collection' => [ 'status' => [ 'enabled' => false, 'status' => 'tg_teddy' ] ]] ], 'result' => [ [ 'status' => [ 'from' => null, 'to' => 'tg_teddy' ], 'group' => [ ['do_nothing', 1], ['unterrorize', 1] ] ] ], 'message' => 'Du drückst den {item} eng an deine Brust... <t-stat-down-terror>Tränen laufen über deine Wange, als du an die Hölle denkst, in der du lebst. Nach ein paar Minuten fühlst du dich besser!</t-stat-down-terror><nt-stat-down-terror>Aber nichts geschieht!</nt-stat-down-terror>' ],
            'cuddle_teddy_2' => [ 'label' => 'Knuddeln', 'meta' => [ 'must_not_be_terrorized' ], 'result' => [ 'terrorize' ], 'message' => 'Du drückst den {item} eng an deine Brust... <t-stat-up-terror>Panik steigt in dir auf!</t-stat-up-terror><nt-stat-up-terror>Aber nichts geschieht!</nt-stat-up-terror>' ],

            'clean_clothes' => [ 'label' => 'Reinigen (Kleidung)', 'meta' => [ 'must_be_inside' ], 'result' => [ [ 'status' => [ 'from' => null, 'to' => 'tg_clothes', 'counter' => ActionCounter::ActionTypeClothes ], 'item' => ['consume' => false, 'morph' => 'basic_suit_#00'] ] ], 'message' => 'Du nimmst dir ein paar Minuten, um deine {item} zu reinigen. Du schrubbst sorgfältig die Blutflecken ab und flickst ein paar kleine Löcher.' ],

            'flash_photo_3' => [ 'label' => 'Benutzen', 'meta' => [ 'must_be_outside_not_at_doors', 'must_have_zombies', 'must_be_blocked' ], 'result' => [ ['item' => ['consume' => false, 'morph' => 'photo_2_#00'] ,   'zone' => ['escape' => 120] ] ],  'message_key' => 'escape_item_camera' ],
            'flash_photo_2' => [ 'label' => 'Benutzen', 'meta' => [ 'must_be_outside_not_at_doors', 'must_have_zombies', 'must_be_blocked' ], 'result' => [ ['item' => ['consume' => false, 'morph' => 'photo_1_#00'] ,   'group' => [ [['do_nothing'], 30], [[ ['zone' => ['escape' => 60] ]], 60]] ] ],   'message_key' => 'escape_item_camera' ],
            'flash_photo_1' => [ 'label' => 'Benutzen', 'meta' => [ 'must_be_outside_not_at_doors', 'must_have_zombies', 'must_be_blocked' ], 'result' => [ ['item' => ['consume' => false, 'morph' => 'photo_off_#00'] , 'group' => [ [['do_nothing'], 60], [[ ['zone' => ['escape' => 30] ]], 30]] ] ],   'message_key' => 'escape_item_camera' ],

            'alarm_clock' => [ 'label' => 'Benutzen', 'at00' => true, 'meta' => [  ], 'result' => [ ['item' => ['consume' => false, 'morph' => 'alarm_on_#00'] ] ],  'message' => 'Du hast {item_from} in {item_to} verwandelt.' ],

            'pumpkin' => ['label' => 'Einsetzen', 'meta' => [ ], 'result' => [ [ 'item' => ['consume' => false, 'morph' => 'pumpkin_off_#00'] ] ] ],
            'flare' => [ 'label' => 'Benutzen', 'meta' => [], 'result' => [ ['custom' => [21]] ], 'message' => '<t-flare_ok>Du hast die Zone {zone} entdeckt.</t-flare_ok><t-flare_fail>Es gibt keine weitere Zone zu entdecken.</t-flare_fail>' ],

            'hero_tamer_1'   => [ 'label' => 'Zur Bank schicken', 'at00' => true, 'meta' => [ 'must_be_outside_or_exploring' ], 'result' => [ 'hero_tamer_1' ], 'confirm' => true, 'message'  => '<t-fail><t-door-closed>Das Stadttor ist geschlossen...</t-door-closed><t-no-items>{tamer_dog} kann nichts mitnehmen, da du <strong>nichts im Rucksack hast</strong>.</t-no-items><t-too-heavy>{tamer_dog} kann deinen Rucksackinhalt nicht mitnehmen, da du einen <strong>sperrigen Gegenstand</strong> mit dir rumschleppt. Das ist dann wohl etwas zu viel des Guten für den Kleinen!</t-too-heavy></t-fail><nt-fail>Du befiehlst {tamer_dog} deinen Rucksackinhalt in die Stadt zu bringen.{hr}<strong>Deine Mitbürger werden sich über all diese Gegenstände in der Bank mächtig freuen.</strong></nt-fail>' ],
            'hero_tamer_2'   => [ 'label' => 'Zur Bank schicken', 'at00' => true,  'meta' => [ 'must_be_outside_or_exploring' ], 'result' => [ 'hero_tamer_2' ], 'confirm' => true, 'message'  => '<t-fail><t-door-closed>Das Stadttor ist geschlossen...</t-door-closed><t-no-items>{tamer_dog} kann nichts mitnehmen, da du <strong>nichts im Rucksack hast</strong>.</t-no-items></t-fail><nt-fail>Du befiehlst {tamer_dog} deinen Rucksackinhalt in die Stadt zu bringen.{hr}<strong>Deine Mitbürger werden sich über all diese Gegenstände in der Bank mächtig freuen.</strong></nt-fail>' ],
            'hero_tamer_1b'  => [ 'label' => 'Zur Truhe schicken', 'at00' => true, 'meta' => [ 'must_be_outside_or_exploring' ], 'result' => [ 'hero_tamer_1b' ], 'confirm' => true, 'message' => '<t-fail><t-no-room>Deine Truhe kann nicht <strong>{items_count} Gegenstände</strong> aufnehmen: er hat <strong>{size} Platz(e)</strong> übrig.</t-no-room><t-door-closed>Das Stadttor ist geschlossen...</t-door-closed><t-no-items>{tamer_dog} kann nichts mitnehmen, da du <strong>nichts im Rucksack hast</strong>.</t-no-items><t-too-heavy>{tamer_dog} kann deinen Rucksackinhalt nicht mitnehmen, da du einen <strong>sperrigen Gegenstand</strong> mit dir rumschleppt. Das ist dann wohl etwas zu viel des Guten für den Kleinen!</t-too-heavy></t-fail><nt-fail>Du befiehlst {tamer_dog} deinen Rucksackinhalt in die Stadt zu bringen.{hr}Genau wie du es gewünscht hast, <strong>hat er alles zur Truhe in deinem Haus gebracht</strong>.{hr}Braver Junge!</nt-fail>' ],
            'hero_tamer_2b'  => [ 'label' => 'Zur Truhe schicken', 'at00' => true, 'meta' => [ 'must_be_outside_or_exploring' ], 'result' => [ 'hero_tamer_2b' ], 'confirm' => true, 'message' => '<t-fail><t-no-room>Deine Truhe kann nicht <strong>{items_count} Gegenstände</strong> aufnehmen: er hat <strong>{size} Platz(e)</strong> übrig.</t-no-room><t-door-closed>Das Stadttor ist geschlossen...</t-door-closed><t-no-items>{tamer_dog} kann nichts mitnehmen, da du <strong>nichts im Rucksack hast</strong>.</t-no-items></t-fail><nt-fail>Du befiehlst {tamer_dog} deinen Rucksackinhalt in die Stadt zu bringen.{hr}Genau wie du es gewünscht hast, <strong>hat er alles zur Truhe in deinem Haus gebracht</strong>.{hr}Braver Junge!</nt-fail>' ],
            'hero_tamer_3'  => [ 'label' => 'Dopen', 'at00' => true, 'meta' => [ 'must_be_outside_or_exploring', 'must_have_drug' ], 'result' => [ 'consume_drug', 'hero_tamer_3' ], 'message' => 'Du gibst deinem {tamer_dog} {items_consume}, die er gierig hinunterschlingt.{hr}Es vergeht keine Minute bis die Drogen Wirkung zeigen: Dein Fifi hat nun leuchtende Augen, ein glänzendes Fell und einen wedelnden Schwanz.' ],

            'hero_surv_1' => [ 'label' => 'Wasser suchen', 'meta' => [ 'must_be_outside_3km', 'not_yet_sbook' ],                         'result' => [ 'contaminated_zone_infect', 'hero_surv_0', 'hero_surv_1' ], 'message' => '{casino}' ],
            'hero_surv_2' => [ 'label' => 'Essen suchen',  'meta' => [ 'no_full_ap', 'must_be_outside_3km', 'not_yet_sbook', 'eat_ap' ], 'result' => [ 'contaminated_zone_infect', 'hero_surv_0', 'hero_surv_2' ], 'message' => '{casino}' ],

            'hero_hunter_1' => [ 'label' => 'Tarnen', 'at00' => true, 'meta' => [ 'must_be_outside', [ 'type' => Requirement::MessageOnFail, 'collection' => [ 'zombies' => [ 'min' => 0, 'block' => false, 'temp' => true ] ], 'text' => 'Das kannst die <strong>Tarnkleidung</strong> nicht verwenden, solange die Zombies diese Zone kontrollieren!'] ], 'result' => [ 'hero_hunter' ], 'message' => 'Du bist ab sofort getarnt.' ],
            'hero_hunter_2' => [ 'label' => 'Tarnen', 'at00' => true, 'meta' => [ 'must_be_inside' ], 'result' => [ 'hero_hunter' ], 'message' => 'Du bist nun getarnt.' ],

            'hero_generic_return'       => [ 'label' => 'Die Rückkehr des Helden', 'tooltip' => 'Wenn du 11 km oder weniger von der Stadt entfernt bist, kehrst du sofort in die Stadt zurück!', 'cover' => true, 'at00' => true, 'meta' => [ 'must_be_outside_or_exploring', 'must_be_outside_within_11km', 'not_yet_hero'], 'result' => [ 'hero_act', ['custom' => [8]] ] ],
            'hero_generic_find'         => [ 'label' => 'Fund', 'tooltip' => 'Wie durch ein Wunder treibst du einen nützlichen Gegenstand auf.', 'cover' => true, 'at00' => true, 'target' => ['type' => ItemTargetDefinition::ItemTypeSelectionType, 'property' => 'hero_find'], 'meta' => [ 'not_yet_hero' ], 'result' => [ 'hero_act', 'spawn_target', ['message' => ['text' => 'So was nennt man wohl <strong>Glück</strong>! <t-inside>Du hast soeben {items_spawn} in einem Abfallberg neben deinem Haus gefunden!</t-inside><t-outside>Du hast soeben {items_spawn} im Wüstensand gefunden!</t-outside> Genau das, was du gebraucht hast!']] ] ],
            'hero_generic_find_lucky'   => [ 'label' => 'Schönes Fundstück', 'tooltip' => 'Wie durch ein Wunder treibst du einen nützlichen Gegenstand auf.', 'cover' => true, 'at00' => true, 'target' => ['type' => ItemTargetDefinition::ItemTypeSelectionType, 'property' => 'hero_find_lucky'], 'meta' => [ 'not_yet_hero' ], 'result' => [ 'hero_act', 'spawn_target', ['message' => ['text' => 'So was nennt man wohl <strong>Glück</strong>! <t-inside>Du hast soeben {items_spawn} in einem Abfallberg neben deinem Haus gefunden!</t-inside><t-outside>Du hast soeben {items_spawn} im Wüstensand gefunden!</t-outside> Genau das, was du gebraucht hast!']] ] ],
            'hero_generic_punch'        => [ 'label' => 'Wildstyle Uppercut', 'tooltip' => 'Damit kannst du mit einem Schlag 2 Zombies umbringen!', 'meta' => [ 'must_be_outside_or_exploring', 'must_have_zombies', 'not_yet_hero'], 'result' => [ 'hero_act', ['zombies' => 'kill_2z'], ['message' => [ 'text' => 'Mit Hilfe deiner übermenschlichen Heldenkräfte hast du <strong>{kills} Zombie(s)</strong> platt gemacht!' ]] ] ],
            'hero_generic_ap'           => [ 'label' => 'Zweite Lunge', 'tooltip' => 'Stellt deine AP wieder her und beseitigt deine Müdigkeit.', 'cover' => true, 'at00' => true, 'meta' => [ 'no_full_ap_msg', 'not_yet_hero'], 'result' => [ 'hero_act', 'just_ap6', ['message' => ['text' => 'Du atmest tief durch und drückst den Rücken durch. Auf geht\'s! Ich werde nicht hier sterben!{hr}Du hast soeben Kraft getankt und <strong>6 neue AP erhalten</strong>.']] ] ],
            'hero_generic_immune'       => [ 'label' => 'Den Tod besiegen', 'tooltip' => 'Beim nächsten Angriff wird der Durst-, Infektions- und Abhängigkeitszustand außer Kraft gesetzt.', 'cover' => true, 'at00' => true, 'meta' => [ 'not_yet_hero'], 'result' => [ 'hero_act', 'hero_immune', ['message' => ['text' => 'Du versucht nochmal alle deine Kräfte für heute Abend zu mobilisieren. Die Anspannung steht dir ins Gesicht geschrieben. Du schwitzt und deine Hände zittern.{hr}Beim heutigen Angriff wirst du weder weder Durst, noch Krankheitssymptome (Infektion), noch Entzugserscheinungen verspüren.']] ] ],
            'hero_generic_rescue'       => [ 'label' => 'Rettung', 'tooltip' => 'Du bringst einen anderen Spieler nach Hause (dieser darf max. 2 Felder von der Stadt entfernt sein).', 'confirm' => true, 'confirmMsg' => 'Möchtest du {target} heimbringen?' ,'target' => ['type' => ItemTargetDefinition::ItemHeroicRescueType], 'meta' => [ 'must_be_inside', 'not_yet_hero'], 'result' => [ 'hero_act', ['custom' => [9]] ], 'message' => 'Du hast {citizen} auf heldenhafte Weise in die Stadt gebracht!' ],

            'throw_sandball' => [ 'label' => 'Werfen', /* 'target' => ['type' => ItemTargetDefinition::ItemCitizenOnZoneSBType], */ 'meta' => [ 'must_be_outside'], 'result' => [ ['custom' => [20]] ], 'message' => '<nt-fail>Du hast einen Sandball in {citizen}s Gesicht geworfen.</nt-fail><t-fail>Hier ist niemand, auf den du den Sandball werfen könntest...</t-fail>' ],

            'special_armag'        => [ 'label' => 'Durchgang in Kraft', 'tooltip_key' => 'heroic_arma_tooltip', 'allow_when_terrorized' => true, 'meta' => [ 'must_be_outside', 'must_be_blocked' ],   'result' => [ ['group' => [ [['do_nothing', ['message' => ['text_key' => 'heroic_arma_fail']]], 50], [[ ['zone' => ['escape' => 600] ], ['zombies' => 'kill_1z'], ['message' => ['text_key' => 'heroic_arma_success']] ], 50]]] ] ],
            'special_armag_d'      => [ 'label' => 'Durchgang in Kraft', 'tooltip_key' => 'heroic_arma_tooltip', 'allow_when_terrorized' => true, 'meta' => [ 'must_be_outside', 'must_be_blocked', 'must_be_day'],   'result' => [ ['group' => [ [['do_nothing', ['message' => ['text_key' => 'heroic_arma_fail']]], 50], [[ ['zone' => ['escape' => 600] ], ['zombies' => 'kill_1z'], ['message' => ['text_key' => 'heroic_arma_success']] ], 50]]] ] ],
            'special_armag_n'      => [ 'label' => 'Durchgang in Kraft', 'tooltip_key' => 'heroic_arma_tooltip', 'allow_when_terrorized' => true, 'meta' => [ 'must_be_outside', 'must_be_blocked', 'must_be_night'], 'result' => [ ['group' => [ [['do_nothing', ['message' => ['text_key' => 'heroic_arma_fail']]], 25], [[ ['zone' => ['escape' => 600] ], ['zombies' => 'kill_1z'], ['message' => ['text_key' => 'heroic_arma_success']] ], 75]]] ] ],
            'special_vote_shaman'  => [ 'label' => 'Den Shamane wählen', 'target' => ['type' => ItemTargetDefinition::ItemCitizenVoteType], 'meta' => [ 'must_be_outside', 'custom_vote_shaman'] , 'result' => [ ['custom' => [18]] ] ],
            'special_vote_guide'   => [ 'label' => 'Den Reiseleiter in der Außenwelt wählen', 'target' => ['type' => ItemTargetDefinition::ItemCitizenVoteType], 'meta' => [ 'must_be_outside', 'custom_vote_guide'], 'result' => [ ['custom' => [19]] ] ],

            'improve' => [ 'label' => 'Aufbauen', 'meta' => [ 'must_be_outside', 'zone_is_improvable', 'min_1_ap', 'must_be_outside_not_at_doors', 'feature_camping' ], 'result' => [ 'minus_1ap', 'consume_item', [ 'zone' => ['improve' =>  1.8] ] ], 'message' => 'Du befestigst den {item} und bedeckst ihn zur Tarnung mit herumliegendem Müll und vertrockneten Zweigen. Na bitte, das sollte hoffentlich deine Überlebenschancen heute Nacht verbessern. Du hast dafür 1 Aktionspunkt verbraucht.' ],

            'cm_campsite_hide'    => [ 'label' => 'Sich verstecken und die Nacht hier schlafen!', 'meta' => [ 'must_be_outside', 'must_not_be_hidden', 'must_not_be_tombed' ], 'result' => [ 'camp_hide', ['custom' => [10]] ], 'message' => 'Du hast Dich notdürftig versteckt.' ],
            'cm_campsite_improve' => [ 'label' => 'Schlafplatz verbessern (schwacher permanenter Bonus, 1AP)', 'meta' => [ 'min_1_ap', 'not_tired', 'must_be_outside', 'must_not_be_hidden', 'must_not_be_tombed', 'zone_is_improvable' ], 'result' => [ 'minus_1ap', [ 'zone' => ['improve' =>  1] ] ], 'message' => 'Du hast das hiesige Versteck verbessert.' ],
            'cm_campsite_tomb'    => [ 'label' => '"Grab" schaufeln (mittelmäßiger vorübergehender Bonus, 1AP)', 'meta' => [ 'min_1_ap', 'not_tired', 'must_be_outside', 'must_not_be_hidden', 'must_not_be_tombed' ], 'result' => [ 'minus_1ap', 'camp_tomb', ['custom' => [10]] ], 'message' => 'Du hast Dir Dein eigenes Grab geschaufelt. Oh welche Ironie!' ],
            'cm_campsite_unhide'  => [ 'label' => 'Versteck verlassen', 'meta' => [ 'must_be_outside', 'must_be_hidden' ], 'result' => [ 'camp_unhide', ['custom' => [11]] ], 'message' => 'Du hast Dein Versteck verlassen.' ],
            'cm_campsite_untomb'  => [ 'label' => 'Grab verlassen', 'meta' => [ 'must_be_outside', 'must_be_tombed' ], 'result' => [ 'camp_untomb', ['custom' => [11]] ], 'message' => 'Du hast Dein Grab verlassen. Die schöne Arbeit umsonst!' ],

            'home_clean'     => [ 'label' => 'Haus aufräumen und putzen', 'meta' => [ 'must_be_inside', 'not_yet_home_cleaned' ], 'result' => [ [ 'status' => [ 'from' => null, 'to' => 'tg_home_clean', 'counter' => ActionCounter::ActionTypeHomeCleanup ] ] ], 'message' => 'Du räumst deinen ganzen Plunder auf und machst ein wenig Ordnung, damit es hier etwas aufgeräumter aussieht. Auch wenn\'s ne Bruchbude ist, es ist DEIN Zuhause...' ],
            'home_shower'    => [ 'label' => 'Duschen', 'meta' => [ 'must_be_inside', 'must_have_shower', 'not_yet_home_showered' ], 'result' => [ [ 'status' => [ 'from' => null, 'to' => 'tg_home_shower', 'counter' => ActionCounter::ActionTypeShower ] ] ], 'message' => 'Du springst unter die hausgemachte Dusche ohne weiter darüber nachzudenken. Das eiskalte Wasser erschreckt dich, aber dennoch bleibst du für einige Augenblicke unter dem schwachen Wasserstrahl stehen. In Ermangelung von Seife reibst du dich mit einem glatten Stein ab und versuchst, den Schlamm und die Blutflecken abzuwaschen. Dabei versuchst du, dir einzureden, dass es sich gut anfühlt.' ],
            'home_heal_1'    => [ 'label' => 'Heilen', 'meta' => [ 'min_5_ap', 'must_be_inside', 'must_have_hospital', 'not_yet_home_heal_1', 'is_wounded_h', 'is_not_infected_h' ], 'result' => ['minus_5ap', 'heal_wound', [ 'status' => [ 'from' => null, 'to' => 'tg_home_heal_1' ] ] ], 'message_key' => 'home_heal_wound' ],
            'home_heal_2'    => [ 'label' => 'Heilen', 'meta' => [ 'min_5_ap', 'must_be_inside', 'must_have_hospital', 'not_yet_home_heal_2', 'is_not_wounded_h', 'is_infected_h' ], 'result' => ['minus_5ap', 'disinfect',  [ 'status' => [ 'from' => null, 'to' => 'tg_home_heal_2' ] ] ], 'message_key' => 'home_heal_infect' ],
            'home_heal_3'    => [ 'label' => 'Heilen', 'meta' => [ 'min_5_ap', 'must_be_inside', 'must_have_hospital', 'not_yet_home_heal_2', 'is_wounded_h', 'is_infected_h' ],     'result' => ['minus_5ap', 'disinfect',  [ 'status' => [ 'from' => null, 'to' => 'tg_home_heal_2' ] ] ], 'message_key' => 'home_heal_infect' ],
            'home_defbuff'   => [ 'label' => 'Verteidigung organisieren', 'meta' => [ 'profession_guardian', 'min_1_ap', 'must_be_inside', 'must_have_guardtower', 'not_yet_home_defbuff' ], 'result' => ['minus_1ap', [ 'custom' => [13], 'status' => [ 'from' => null, 'to' => 'tg_home_defbuff' ] ] ], 'message' => 'Du hast dir etwas Zeit genommen und zur Verteidigung der Stadt beigetragen.' ],
            'home_crows'     => [ 'label' => 'Nach Ruinen Ausschau halten', 'meta' => [ 'profession_hunter', 'must_be_inside', 'must_have_crowsnest', 'not_yet_home_defbuff' ], 'result' => [[ 'custom' => [12], 'status' => [ 'from' => null, 'to' => 'tg_home_defbuff' ] ] ], 'message' => '<t-zone>Du hast eine neue Ruine auf {zone} entdeckt!</t-zone><nt-zone>Es scheint, als gäbe es nichts mehr zu entdecken ...</nt-zone>' ],
            'home_fillwater' => [ 'label' => 'Wasserwaffen füllen', 'meta' => [ 'must_be_inside', 'must_have_valve' ], 'result' => [[ 'custom' => [14]]], 'message' => '<t-fail>Du hast <strong>keine Wasserwaffen zum Befüllen</strong> in deinem Rucksack oder deiner Truhe.</t-fail><nt-fail>Du hast {items_spawn} dank des Wasserhahns kostenlos auffüllen können.</nt-fail>' ],
            'home_cinema'    => [ 'label' => 'Ins Kino gehen', 'meta' => [ 'must_be_inside', 'must_have_cinema', 'must_be_terrorized' ], 'result' => [ 'unterrorize'] ],

            'home_lab_1a' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v1', 'must_not_have_lab', 'have_2_pharma', 'lab_counter_below_1' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma', 'group' => [ ['home_lab_success', 25], [ 'home_lab_failure', 75 ] ]],  ], 'message_key' => 'use_lab' ],
            'home_lab_2a' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v2', 'must_not_have_lab', 'have_2_pharma', 'lab_counter_below_1' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma', 'group' => [ ['home_lab_success', 50], [ 'home_lab_failure', 50 ] ]],  ], 'message_key' => 'use_lab' ],
            'home_lab_3a' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v3', 'must_not_have_lab', 'have_2_pharma', 'lab_counter_below_1' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma', 'group' => [ ['home_lab_success', 75], [ 'home_lab_failure', 25 ] ]],  ], 'message_key' => 'use_lab' ],
            'home_lab_4a' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v4', 'must_not_have_lab', 'have_2_pharma', 'lab_counter_below_4' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma' ], 'home_lab_success' ], 'message_key' => 'use_lab' ],
            'home_lab_1b' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v1', 'must_have_lab',     'have_2_pharma', 'lab_counter_below_6' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma', 'group' => [ ['home_lab_success', 25], [ 'home_lab_failure', 75 ] ]],  ], 'message_key' => 'use_lab' ],
            'home_lab_2b' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v2', 'must_have_lab',     'have_2_pharma', 'lab_counter_below_6' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma', 'group' => [ ['home_lab_success', 50], [ 'home_lab_failure', 50 ] ]],  ], 'message_key' => 'use_lab' ],
            'home_lab_3b' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v3', 'must_have_lab',     'have_2_pharma', 'lab_counter_below_6' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma', 'group' => [ ['home_lab_success', 75], [ 'home_lab_failure', 25 ] ]],  ], 'message_key' => 'use_lab' ],
            'home_lab_4b' => [ 'label' => 'Droge herstellen', 'meta' => [ 'must_be_inside', 'must_have_home_lab_v4', 'must_have_lab',     'have_2_pharma', 'lab_counter_below_9' ], 'result' => [ [ 'status' => 'increase_lab_counter', 'consume' => '2_pharma' ], 'home_lab_success' ], 'message_key' => 'use_lab' ],

            'home_kitchen_1a' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v1', 'must_not_have_canteen', 'kitchen_counter_below_1' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter', 'group' => [ ['home_kitchen_success', 33], [ 'home_kitchen_failure', 66 ] ]],  ], 'message_key' => 'use_kitchen' ],
            'home_kitchen_2a' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v2', 'must_not_have_canteen', 'kitchen_counter_below_1' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter', 'group' => [ ['home_kitchen_success', 66], [ 'home_kitchen_failure', 33 ] ]],  ], 'message_key' => 'use_kitchen' ],
            'home_kitchen_3a' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v3', 'must_not_have_canteen', 'kitchen_counter_below_2' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter' ], 'home_kitchen_success' ], 'message_key' => 'use_kitchen' ],
            'home_kitchen_4a' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v4', 'must_not_have_canteen', 'kitchen_counter_below_3' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter' ], 'home_kitchen_success' ], 'message_key' => 'use_kitchen' ],
            'home_kitchen_1b' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v1', 'must_have_canteen',     'kitchen_counter_below_4' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter', 'group' => [ ['home_kitchen_success', 33], [ 'home_kitchen_failure', 66 ] ]],  ], 'message_key' => 'use_kitchen' ],
            'home_kitchen_2b' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v2', 'must_have_canteen',     'kitchen_counter_below_4' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter', 'group' => [ ['home_kitchen_success', 66], [ 'home_kitchen_failure', 33 ] ]],  ], 'message_key' => 'use_kitchen' ],
            'home_kitchen_3b' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v3', 'must_have_canteen',     'kitchen_counter_below_5' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter' ], 'home_kitchen_success' ], 'message_key' => 'use_kitchen' ],
            'home_kitchen_4b' => [ 'label' => 'Kochen', 'target' => ['property' => 'can_cook', 'poison' => false, 'broken' => false], 'meta' => [ 'must_be_inside', 'must_have_home_kitchen_v4', 'must_have_canteen',     'kitchen_counter_below_6' ], 'result' => [ 'consume_target', [ 'status' => 'increase_kitchen_counter' ], 'home_kitchen_success' ], 'message_key' => 'use_kitchen' ],

            'slaughter_4xs' => [ 'label' => 'Ausweiden', 'meta' => [ 'must_be_inside', 'must_have_slaughter' ], 'result' => [ 'consume_item', [ 'spawn' => 'meat_4xs' ], ['picto' => ['r_animal_#00']] ], 'message_key' => 'use_butcher' ],
            'slaughter_2xs' => [ 'label' => 'Ausweiden', 'meta' => [ 'must_be_inside', 'must_have_slaughter' ], 'result' => [ 'consume_item', [ 'spawn' => 'meat_2xs' ], ['picto' => ['r_animal_#00']] ], 'message_key' => 'use_butcher' ],
            'slaughter_4x'  => [ 'label' => 'Ausweiden', 'meta' => [ 'must_be_inside', 'must_have_slaughter' ], 'result' => [ 'consume_item', [ 'spawn' => 'meat_4x'  ], ['picto' => ['r_animal_#00']] ], 'message_key' => 'use_butcher' ],
            'slaughter_2x'  => [ 'label' => 'Ausweiden', 'meta' => [ 'must_be_inside', 'must_have_slaughter' ], 'result' => [ 'consume_item', [ 'spawn' => 'meat_2x'  ], ['picto' => ['r_animal_#00']] ], 'message_key' => 'use_butcher' ],
            'slaughter_bmb' => [ 'label' => 'Ausweiden', 'meta' => [ 'must_be_inside', 'must_have_slaughter' ], 'result' => [ 'consume_item', [ 'spawn' => 'meat_bmb' ], ['picto' => ['r_animal_#00']] ], 'message_key' => 'use_butcher' ],
            'purify_soul' => [ 'label' => 'Läutern', 'meta' => [ 'must_be_inside', 'must_have_hammam' ], 'result' => [ 'consume_item', [ 'town' => ['def' => 5]], ['picto' => ['r_collec_#00']], ['globalpicto' => ['r_mystic_#00']]],  'message' => "Du hast die Seele gereinigt und sie friedlich gemacht."],
            'brew_shamanic_potion' => ['label' => 'Herstellung eines Mystischern Trank', 'tooltip' => 'Du kannst einen schamanischen Trank zubereiten, der den Rezipienten vor bösen Geistern schützt.', 'meta' => [ 'must_be_inside', 'have_water_shaman', 'min_1_pm', 'role_shaman' ], 'result' => ['consume_water', 'minus_1pm', ['spawn' => 'potion']], 'message' => 'Das ist ein Musterbeispiel eines schamanischen Tranks! Nun liegt es an die, der Stadt dessen Wirksamkeit zu vermitteln und sie von deinen schamanischen Fähigkeiten zu überzeugen.' ],

            'home_rest_1'     => [ 'label' => 'Nickerchen machen', 'meta' => [ 'must_be_inside', 'must_have_home_rest_v1', 'not_yet_rested', 'no_full_ap' ], 'result' => [ [ 'status' => [ 'from' => null, 'to' => 'tg_rested' ], 'group' => [ ['plus_2ap_7', 33], [ 'do_nothing', 66 ] ] ] ], 'message_key' => 'use_bed' ],
            'home_rest_2'     => [ 'label' => 'Nickerchen machen', 'meta' => [ 'must_be_inside', 'must_have_home_rest_v2', 'not_yet_rested', 'no_full_ap' ], 'result' => [ [ 'status' => [ 'from' => null, 'to' => 'tg_rested' ], 'group' => [ ['plus_2ap_7', 66], [ 'do_nothing', 33 ] ] ] ], 'message_key' => 'use_bed' ],
            'home_rest_3'     => [ 'label' => 'Nickerchen machen', 'meta' => [ 'must_be_inside', 'must_have_home_rest_v3', 'not_yet_rested', 'no_full_ap' ], 'result' => [ [ 'status' => [ 'from' => null, 'to' => 'tg_rested' ], 'group' => [ ['plus_2ap_7', 100], [ 'do_nothing', 0 ] ] ] ], 'message_key' => 'use_bed' ],

            'nw_break'   => [ 'label' => '', 'meta' => [], 'result' => [ 'break_item' ] ],
            'nw_destroy' => [ 'label' => '', 'meta' => [], 'result' => [ 'consume_item' ] ],
            'nw_empty_big_pgun'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'big_pgun_empty_#00',      'consume' => false]] ] ],
            'nw_empty_pilegun_up'   => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'pilegun_up_empty_#00',    'consume' => false]] ] ],
            'nw_empty_pilegun'      => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'pilegun_empty_#00',       'consume' => false]] ] ],
            'nw_empty_taser'        => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'taser_empty_#00',         'consume' => false]] ] ],
            'nw_empty_mixergun'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'mixergun_empty_#00',      'consume' => false]] ] ],
            'nw_empty_chainsaw'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'chainsaw_empty_#00',      'consume' => false]] ] ],
            'nw_empty_phone'        => [ 'label' => '', 'meta' => [], 'result' => [ 'consume_item', ['spawn' => 'phone'] ] ],
            'nw_empty_watergun'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'watergun_empty_#00',      'consume' => false]] ] ],
            'nw_empty_watergun_opt' => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'watergun_opt_empty_#00',  'consume' => false]] ] ],
            'nw_empty_torch'        => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'torch_off_#00',           'consume' => false]] ] ],
            'nw_empty_staff'        => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'staff2_#00',              'consume' => false]] ] ],
            'nw_empty_dildo'        => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'vibr_empty_#00',          'consume' => false]] ] ],
            'nw_empty_watercan'     => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'water_can_empty_#00',     'consume' => false]] ] ],
            'nw_empty_kalach'       => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'kalach_#01',              'consume' => false]] ] ],
            'nw_meat'               => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'undef_#00',               'consume' => false]], ['picto' => ['r_animal_#00']] ] ],
            'nw_meat_tasty'         => [ 'label' => '', 'meta' => [], 'result' => [ ['item' => ['morph' => 'meat_#00',                'consume' => false]], ['picto' => ['r_animal_#00']] ] ],
        ],

        'heroics' => [
            ['name' => 'hero_generic_return', 'unlockable' => false],
            ['name' => 'hero_generic_find', 'unlockable' => false],
            ['name' => 'hero_generic_punch', 'unlockable' => false],
            ['name' => 'hero_generic_ap', 'unlockable' => true],
            ['name' => 'hero_generic_immune', 'unlockable' => true],
            ['name' => 'hero_generic_find_lucky', 'unlockable' => true],
            ['name' => 'hero_generic_rescue', 'unlockable' => false],
        ],

        'specials' => [
            ['name' => 'special_armag', 'icon' => 'armag', 'consumable' => true],
            ['name' => 'special_armag_d', 'icon' => 'armag', 'consumable' => true],
            ['name' => 'special_armag_n', 'icon' => 'armag', 'consumable' => true],
            ['name' => 'special_vote_shaman', 'icon' => 'hero', 'consumable' => false],
            ['name' => 'special_vote_guide', 'icon' => 'hero', 'consumable' => false],
        ],

        'camping' => [
            'cm_campsite_hide', 'cm_campsite_improve', 'cm_campsite_tomb', 'cm_campsite_unhide', 'cm_campsite_untomb'
        ],

        'home' => [
            ['home_clean', 'sort'], ['home_shower', 'shower'], ['home_heal_1', 'heal_wound'], ['home_heal_2', 'heal_infection'], ['home_heal_3', 'heal_infection'], ['home_defbuff', 'watchmen'], ['home_crows', 'watchmen'], ['home_fillwater', 'water'], ['home_cinema', 'cinema'],

            ['home_lab_1a', 'home_lab'], ['home_lab_2a', 'home_lab'], ['home_lab_3a', 'home_lab'], ['home_lab_4a', 'home_lab'],
            ['home_lab_1b', 'lab'], ['home_lab_2b', 'lab'], ['home_lab_3b', 'lab'], ['home_lab_4b', 'lab'],
            ['home_kitchen_1a', 'kitchen'], ['home_kitchen_2a', 'kitchen'], ['home_kitchen_3a', 'kitchen'], ['home_kitchen_4a', 'kitchen'],
            ['home_kitchen_1b', 'canteen'], ['home_kitchen_2b', 'canteen'], ['home_kitchen_3b', 'canteen'], ['home_kitchen_4b', 'canteen'],
            ['brew_shamanic_potion', 'shaman'], ['home_rest_1', 'rest'], ['home_rest_2', 'rest'], ['home_rest_3', 'rest']
        ],

        'escort' => [
            'ex_drink' => [ 'icon' => 'drink', 'label' => 'Trinken', 'tooltip' => '{citizen} befehlen etwas zu trinken.', 'actions' => [
                'water_tl0', 'water_tl1a', 'water_tl1b', 'water_tl2',
                'potion_tl0a', 'potion_tl0b', 'potion_tl1a', 'potion_tl1b', 'potion_tl2',
                'potion_g', 'potion_tl0a_immune', 'potion_tl0b_immune', 'potion_tl1a_immune', 'potion_tl1b_immune', 'potion_tl2_immune', 'potion_g_immune',
                'watercan3_tl0', 'watercan3_tl1a', 'watercan3_tl1b', 'watercan3_tl2',
                'watercan2_tl0', 'watercan2_tl1a', 'watercan2_tl1b', 'watercan2_tl2',
                'watercan1_tl0', 'watercan1_tl1a', 'watercan1_tl1b', 'watercan1_tl2'
            ]],
            'ex_eat'   => [ 'icon' => 'eat', 'label' => 'Essen', 'tooltip' => '{citizen} befehlen etwas zu essen.', 'actions' => [
                'eat_6ap', 'eat_7ap'
            ]],
        ],

        'items' => [
            'water_#00'           => [ 'water_tl0', 'water_tl1a', 'water_tl1b', 'water_tl2', 'water_g' ],
            'water_cup_#00'       => [ 'water_tl0', 'water_tl1a', 'water_tl1b', 'water_tl2', 'water_g' ],
            'potion_#00'          => [ 'potion_tl0a', 'potion_tl0b', 'potion_tl1a', 'potion_tl1b', 'potion_tl2', 'potion_g', 'potion_tl0a_immune', 'potion_tl0b_immune', 'potion_tl1a_immune', 'potion_tl1b_immune', 'potion_tl2_immune', 'potion_g_immune' ],

            'water_can_3_#00'     => [                   'watercan3_tl0', 'watercan3_tl1a', 'watercan3_tl1b', 'watercan3_tl2', 'watercan3_g' ],
            'water_can_2_#00'     => [ 'fill_watercan2', 'watercan2_tl0', 'watercan2_tl1a', 'watercan2_tl1b', 'watercan2_tl2', 'watercan2_g' ],
            'water_can_1_#00'     => [ 'fill_watercan1', 'watercan1_tl0', 'watercan1_tl1a', 'watercan1_tl1b', 'watercan1_tl2', 'watercan1_g' ],
            'water_can_empty_#00' => [ 'fill_watercan0' ],

            'can_#00'             => [ 'can', 'can_t1', 'can_t2' ],

            'can_open_#00'        => [ 'eat_6ap'],
            'bretz_#00'           => [ 'eat_6ap'],
            'undef_#00'           => [ 'eat_6ap'],
            'dish_#00'            => [ 'eat_6ap'],
            'vegetable_#00'       => [ 'eat_6ap'],
            'food_bar1_#00'       => [ 'eat_6ap'],
            'food_bar2_#00'       => [ 'eat_6ap'],
            'food_bar3_#00'       => [ 'eat_6ap'],
            'food_biscuit_#00'    => [ 'eat_6ap'],
            'food_chick_#00'      => [ 'eat_6ap'],
            'food_pims_#00'       => [ 'eat_6ap'],
            'food_tarte_#00'      => [ 'eat_6ap'],
            'food_sandw_#00'      => [ 'eat_6ap'],
            'food_noodles_#00'    => [ 'eat_6ap'],
            'wood_xmas_#00'       => [ 'eat_6ap'],
            'fruit_#00'           => [ 'eat_fleshroom_1', 'eat_fleshroom_2'],
            'hmeat_#00'           => [ 'eat_meat_1', 'eat_meat_2' ],
            'bone_meat_#00'       => [ 'eat_bone_1', 'eat_bone_2' ],
            'cadaver_#00'         => [ 'eat_cadaver_1', 'eat_cadaver_2' ],
            'vagoul_#00'          => [ 'ghoul_serum'],

            'food_noodles_hot_#00'=> [ 'eat_7ap'],
            'meat_#00'            => [ 'eat_7ap'],
            'vegetable_tasty_#00' => [ 'eat_7ap'],
            'dish_tasty_#00'      => [ 'eat_7ap'],
            'food_candies_#00'    => [ 'eat_7ap'],
            'chama_tasty_#00'     => [ 'eat_7ap'],
            'woodsteak_#00'       => [ 'eat_7ap'],
            'egg_#00'             => [ 'eat_7ap'],
            'apple_#00'           => [ 'eat_7ap'],

            'disinfect_#00'       => [ 'drug_par_1', 'drug_par_2', 'drug_par_3', 'drug_par_4' ],
            'drug_#00'            => [ 'drug_6ap_1', 'drug_6ap_2' ],
            'drug_hero_#00'       => [ 'drug_8ap_1', 'drug_8ap_2' ],
            'drug_random_#00'     => [ 'drug_rand_1', 'drug_rand_2' ],
            'lsd_#00'             => [ 'drug_rand_1', 'drug_rand_2' ],
            'beta_drug_bad_#00'   => [ 'drug_rand_1', 'drug_rand_2' ],
            'beta_drug_#00'       => [ 'drug_beta' ],
            'xanax_#00'           => [ 'drug_xana1', 'drug_xana2', 'drug_xana3', 'drug_xana4' ],
            'drug_water_#00'      => [ 'drug_hyd_1', 'drug_hyd_2', 'drug_hyd_3', 'drug_hyd_4', 'drug_hyd_5', 'drug_hyd_6' ],
            'april_drug_#00'      => [ 'drug_april_1', 'drug_april_2' ],

            'food_bag_#00'        => [ 'open_doggybag' ],
            'food_armag_#00'      => [ 'open_lunchbag' ],
            'chest_citizen_#00'   => [ 'open_c_chest' ],
            'chest_hero_#00'      => [ 'open_h_chest' ],
            'postal_box_#00'      => [ 'open_postbox' ],
            'postal_box_#01'      => [ 'open_postbox' ],
            'postal_box_xl_#00'   => [ 'open_postbox_xl' ],
            'book_gen_letter_#00' => [ 'open_letterbox' ],
            'book_gen_box_#00'    => [ 'open_justbox' ],

            'game_box_#00'        => [ 'open_gamebox' ],
            'bplan_box_#00'       => [ 'open_abox' ],
            'bplan_drop_#00'      => [ 'open_cbox' ],

            'rsc_pack_3_#00'         => [ 'open_matbox3' ],
            'rsc_pack_2_#00'         => [ 'open_matbox2' ],
            'rsc_pack_1_#00'         => [ 'open_matbox1' ],
            'chest_christmas_3_#00'  => [ 'open_xmasbox3' ],
            'chest_christmas_2_#00'  => [ 'open_xmasbox2' ],
            'chest_christmas_1_#00'  => [ 'open_xmasbox1' ],

            'chest_#00'           => [ 'open_metalbox', 'open_metalbox_t1', 'open_metalbox_t2' ],
            'chest_xl_#00'        => [ 'open_metalbox2', 'open_metalbox2_t1', 'open_metalbox2_t2' ],
            'catbox_#00'          => [ 'open_catbox', 'open_catbox_t1', 'open_catbox_t2' ],
            'chest_tools_#00'     => [ 'open_toolbox', 'open_toolbox_t1', 'open_toolbox_t2' ],
            'chest_food_#00'      => [ 'open_foodbox', 'open_foodbox_t1', 'open_foodbox_t2' ],

            'safe_#00'             => [ 'open_safe' ],
            'bplan_box_e_#00'      => [ 'open_asafe' ],

            'pilegun_empty_#00'      => [ 'load_pilegun'  ],
            'pilegun_up_empty_#00'   => [ 'load_pilegun2' ],
            'big_pgun_empty_#00'     => [ 'load_pilegun3' ],
            'mixergun_empty_#00'     => [ 'load_mixergun' ],
            'chainsaw_empty_#00'     => [ 'load_chainsaw' ],
            'taser_empty_#00'        => [ 'load_taser' ],
            'lpoint_#00'             => [ 'load_lpointer' ],

            'lamp_#00'             => [ 'load_lamp' ],
            'vibr_empty_#00'       => [ 'load_dildo' ],
            'radius_mk2_part_#00'  => [ 'load_rmk2' ],
            'maglite_off_#00'      => [ 'load_maglite' ],
            'radio_off_#00'        => [ 'load_radio' ],
            'sport_elec_empty_#00' => [ 'load_emt' ],

            'watergun_opt_empty_#00' => [ 'fill_asplash1','fill_asplash2' ],
            'watergun_empty_#00'     => [ 'fill_splash1', 'fill_splash2' ],
            'jerrygun_off_#00'       => [ 'fill_jsplash'],
            'jerrygun_#00'           => [ 'throw_jerrygun'],
            'kalach_#01'             => [ 'fill_ksplash1', 'fill_ksplash2'],
            'grenade_empty_#00'      => [ 'fill_grenade1', 'fill_grenade2'],
            'bgrenade_empty_#00'     => [ 'fill_exgrenade1', 'fill_exgrenade2'],

            'grenade_#00'      => [ 'throw_grenade'],
            'bgrenade_#00'     => [ 'throw_exgrenade'],
            'boomfruit_#00'    => [ 'throw_boomfruit'],

            'pilegun_#00'    => [ 'fire_pilegun'  ],
            'pilegun_up_#00' => [ 'fire_pilegun2' ],
            'big_pgun_#00'   => [ 'fire_pilegun3' ],
            'mixergun_#00'   => [ 'fire_mixergun' ],
            'chainsaw_#00'   => [ 'fire_chainsaw' ],
            'taser_#00'      => [ 'fire_taser' ],
            'lpoint4_#00'    => [ 'fire_lpointer4' ],
            'lpoint3_#00'    => [ 'fire_lpointer3' ],
            'lpoint2_#00'    => [ 'fire_lpointer2' ],
            'lpoint1_#00'    => [ 'fire_lpointer1' ],

            'watergun_opt_5_#00' => [ 'fire_asplash5' ],
            'watergun_opt_4_#00' => [ 'fire_asplash4' ],
            'watergun_opt_3_#00' => [ 'fire_asplash3' ],
            'watergun_opt_2_#00' => [ 'fire_asplash2' ],
            'watergun_opt_1_#00' => [ 'fire_asplash1' ],
            'watergun_3_#00'     => [ 'fire_splash3' ],
            'watergun_2_#00'     => [ 'fire_splash2' ],
            'watergun_1_#00'     => [ 'fire_splash1' ],

            'pet_chick_#00' => [ 'slaughter_2x' , 'throw_animal'     ],
            'pet_rat_#00'   => [ 'slaughter_2x' , 'throw_animal'     ],
            'pet_pig_#00'   => [ 'slaughter_4x' , 'throw_animal'     ],
            'pet_snake_#00' => [ 'slaughter_4xs', 'throw_animal'     ],
            'pet_cat_#00'   => [ 'slaughter_2xs', 'throw_animal_cat' ],
            'tekel_#00'     => [ 'slaughter_2xs', 'throw_animal_dog' ],
            'pet_dog_#00'   => [ 'slaughter_2xs', 'throw_animal_dog' ],
            'angryc_#00'    => [ 'slaughter_bmb', 'throw_animal_angryc' ],

            'machine_1_#00'     => ['throw_b_machine_1'    ],
            'bone_#00'          => ['throw_b_bone'         ],
            'can_opener_#00'    => ['throw_b_can_opener'   ],
            'chair_basic_#00'   => ['throw_b_chair_basic'  ],
            'torch_#00'         => ['throw_b_torch'        ],
            'chain_#00'         => ['throw_b_chain'        ],
            'staff_#00'         => ['throw_b_staff'        ],
            'knife_#00'         => ['throw_b_knife'        ],
            'machine_2_#00'     => ['throw_b_machine_2'    ],
            'small_knife_#00'   => ['throw_b_small_knife'  ],
            'cutcut_#00'        => ['throw_b_cutcut'       ],
            'machine_3_#00'     => ['throw_b_machine_3'    ],
            'pc_#00'            => ['throw_b_pc'           ],
            'lawn_#00'          => ['throw_b_lawn'         ],
            'screw_#00'         => ['throw_b_screw'        ],
            'swiss_knife_#00'   => ['throw_b_swiss_knife'  ],
            'cutter_#00'        => ['throw_b_cutter'       ],
            'concrete_wall_#00' => ['throw_b_concrete_wall'],
            'torch_off_#00'     => ['throw_b_torch_off'    ],
            'wrench_#00'        => ['throw_b_wrench'       ],
            'hurling_stick_#00' => ['throw_hurling_stick'  ],
            'iphone_#00'        => ['throw_phone'          ],
            'cinema_#00'        => ['throw_projector'      ],

            'bplan_c_#00'  => [ 'bp_generic_1' ],
            'bplan_u_#00'  => [ 'bp_generic_2' ],
            'bplan_r_#00'  => [ 'bp_generic_3' ],
            'bplan_e_#00'  => [ 'bp_generic_4' ],
            'hbplan_u_#00' => [ 'bp_hotel_2' ],
            'hbplan_r_#00' => [ 'bp_hotel_3' ],
            'hbplan_e_#00' => [ 'bp_hotel_4' ],
            'bbplan_u_#00' => [ 'bp_bunker_2' ],
            'bbplan_r_#00' => [ 'bp_bunker_3' ],
            'bbplan_e_#00' => [ 'bp_bunker_4' ],
            'mbplan_u_#00' => [ 'bp_hospital_2' ],
            'mbplan_r_#00' => [ 'bp_hospital_3' ],
            'mbplan_e_#00' => [ 'bp_hospital_4' ],

            'rp_book_#00'   => ['read_rp'],
            'rp_book_#01'   => ['read_rp'],
            'rp_book2_#00'  => ['read_rp'],
            'rp_scroll_#00' => ['read_rp'],
            'rp_scroll_#01' => ['read_rp'],
            'rp_sheets_#00' => ['read_rp'],
            'rp_letter_#00' => ['read_rp'],
            'rp_manual_#00' => ['read_rp'],
            'lilboo_#00'    => ['read_rp'],
            'rp_twin_#00'   => ['read_rp'],

            'banned_note_#00' => ['read_banned_note'],

            'dice_#00'   => ['special_dice'],
            'cards_#00'  => ['special_card'],
            'guitar_#00' => ['special_guitar'],

            'rhum_#00'     => ['alcohol'],
            'vodka_#00'    => ['alcohol'],
            'vodka_de_#00' => ['alcohol'],
            'fest_#00'     => ['alcohol'],
            'guiness_#00'  => ['alcohol'],
            'hmbrew_#00'   => ['alcohol_dx'],

            'coffee_#00'   => ['coffee'],
            'vibr_#00'     => ['vibrator'],

            'home_def_#00'    => ['home_def_plus'],
            'home_box_#00'    => ['home_store_plus'],
            'home_box_xl_#00' => ['home_store_plus2'],

            'bandage_#00'    => ['bandage'],
            'sport_elec_#00' => ['emt'],

            'jerrycan_#00'       => ['jerrycan_1', 'jerrycan_1b', 'jerrycan_2', 'jerrycan_3'],
            'water_cup_part_#00' => ['watercup_1', 'watercup_1b', 'watercup_2', 'watercup_3'],

            'cyanure_#00' => ['cyanide'],

            'repair_one_#00' => ['repair_1'],
            'repair_kit_#00' => ['repair_2'],
            'poison_#00'     => ['poison_1'],

            'tagger_#00'         => ['zonemarker_1'],
            'radius_mk2_#00'     => ['zonemarker_2'],
            'digger_#00'         => ['nessquick'],
            'flesh_#00'          => ['bomb_1'],
            'flash_#00'          => ['bomb_2'],

            'smoke_bomb_#00'     => ['smokebomb'],

            'teddy_#00'          => ['cuddle_teddy_1'],
            'teddy_#01'          => ['cuddle_teddy_2'],

            'cigs_#00'          => ['light_cig'],

            'basic_suit_dirt_#00' => [ 'clean_clothes'], // 'campsite_improve', 'campsite_hide', 'campsite_tomb', 'campsite_unhide', 'campsite_untomb' ],

            'tamed_pet_#00'      => [ 'hero_tamer_1', 'hero_tamer_1b', 'hero_tamer_3' ],
            'tamed_pet_drug_#00' => [ 'hero_tamer_2', 'hero_tamer_2b' ],

            'surv_book_#00' => [ 'hero_surv_1', 'hero_surv_2' ],

            'vest_off_#00' => [ 'hero_hunter_1', 'hero_hunter_2' ],

            'door_#00'       => [ 'improve' ],
            'plate_#00'      => [ 'improve' ],
            'trestle_#00'    => [ 'improve' ],
            'bed_#00'        => [ 'improve' ],
            'wood_plate_#00' => [ 'improve' ],
            'out_def_#00'    => [ 'improve' ],
            'table_#00'      => [ 'improve' ],

            'soul_blue_#00' => ["purify_soul"],
            'soul_red_#00'  => ["purify_soul"],
            'soul_blue_#01' => ['purify_soul'],

            'photo_3_#00' => ['flash_photo_3'],
            'photo_2_#00' => ['flash_photo_2'],
            'photo_1_#00' => ['flash_photo_1'],

            'omg_this_will_kill_you_#00' => ['water_no_effect'],
            'christmas_candy_#00' => ['drug_rand_xmas'],
            'sand_ball_#00' => ['throw_sandball'],

            'flare_#00' => ['flare'],

            'alarm_off_#00' => ['alarm_clock'],

            'pumpkin_on_#00' => ['pumpkin'],
        ],

        'items_nw' => [
            'can_open_#00'       => 'nw_destroy',
            'pilegun_#00'        => 'nw_empty_pilegun',
            'taser_#00'          => 'nw_empty_taser',
            'mixergun_#00'       => 'nw_empty_mixergun',
            'chainsaw_#00'       => 'nw_empty_chainsaw',
            'lawn_#00'           => 'nw_break',
            'wrench_#00'         => 'nw_break',
            'screw_#00'          => 'nw_break',
            'staff_#00'          => 'nw_empty_staff',
            'knife_#00'          => 'nw_break',
            'cutcut_#00'         => 'nw_break',
            'small_knife_#00'    => 'nw_break',
            'swiss_knife_#00'    => 'nw_break',
            'cutter_#00'         => 'nw_break',
            'cart_#00'           => 'nw_destroy',
            'can_opener_#00'     => 'nw_break',
            'chair_#00'          => 'nw_destroy',
            'bed_#00'            => 'nw_destroy',
            'lamp_#00'           => 'nw_destroy',
            'carpet_#00'         => 'nw_destroy',
            'engine_#00'         => 'nw_destroy',
            'pet_chick_#00'      => 'nw_meat',
            'pet_pig_#00'        => 'nw_meat',
            'pet_rat_#00'        => 'nw_meat',
            'pet_cat_#00'        => 'nw_meat_tasty',
            'pet_snake_#00'      => 'nw_meat_tasty',
            'vibr_#00'           => 'nw_empty_dildo',
            'meat_#00'           => 'nw_destroy',
            'undef_#00'          => 'nw_destroy',
            'sheet_#00'          => 'nw_destroy',
            'grenade_#00'        => 'nw_destroy',
            'hmeat_#00'          => 'nw_destroy',
            'bgrenade_#00'       => 'nw_destroy',
            'chest_#00'          => 'nw_destroy',
            'chest_xl_#00'       => 'nw_destroy',
            'chest_tools_#00'    => 'nw_destroy',
            'lamp_on_#00'        => 'nw_destroy',
            'music_#00'          => 'nw_destroy',
            'radio_on_#00'       => 'nw_destroy',
            'door_#00'           => 'nw_destroy',
            'watergun_opt_3_#00' => 'nw_empty_watergun_opt',
            'watergun_opt_2_#00' => 'nw_empty_watergun_opt',
            'watergun_opt_1_#00' => 'nw_empty_watergun_opt',
            'big_pgun_#00'       => 'nw_empty_big_pgun',
            'flare_#00'          => 'nw_destroy',
            'chair_basic_#00'    => 'nw_break',
            'bone_meat_#00'      => 'nw_destroy',
            'bone_#00'           => 'nw_break',
            'deco_box_#00'       => 'nw_destroy',
            'trestle_#00'        => 'nw_destroy',
            'table_#00'          => 'nw_destroy',
            'machine_1_#00'      => 'nw_break',
            'machine_2_#00'      => 'nw_break',
            'machine_3_#00'      => 'nw_break',
            'chain_#00'          => 'nw_break',
            'dish_#00'           => 'nw_destroy',
            'dish_tasty_#00'     => 'nw_destroy',
            'home_box_xl_#00'    => 'nw_destroy',
            'home_box_#00'       => 'nw_destroy',
            'watergun_3_#00'     => 'nw_empty_watergun',
            'watergun_2_#00'     => 'nw_empty_watergun',
            'watergun_1_#00'     => 'nw_empty_watergun',
            'watergun_opt_5_#00' => 'nw_empty_watergun_opt',
            'watergun_opt_4_#00' => 'nw_empty_watergun_opt',
            'pilegun_up_#00'     => 'nw_empty_pilegun_up',
            'car_door_#00'       => 'nw_destroy',
            'torch_#00'          => 'nw_empty_torch',
            'torch_off_#00'      => 'nw_break',
            'pc_#00'             => 'nw_break',
            'water_can_1_#00'    => 'nw_empty_watercan',
            'water_can_2_#00'    => 'nw_empty_watercan',
            'water_can_3_#00'    => 'nw_empty_watercan',
            'iphone_#00'         => 'nw_empty_phone',
            'boomfruit_#00'      => 'nw_destroy',
            'rlaunc_#00'         => 'nw_destroy',
            'kalach_#00'         => 'nw_empty_kalach',
            'bureau_#00'         => 'nw_destroy',
            'distri_#00'         => 'nw_destroy',
            'renne_#00'          => 'nw_destroy',
            'paques_#00'         => 'nw_destroy',
            'badge_#00'          => 'nw_destroy',
            'claymo_#00'         => 'nw_destroy',
            'guitar_#00'         => 'nw_break',
            'chkspk_#00'         => 'nw_destroy',
        ]
    ];

    public static $message_keys = [
        'escort_water_drink'        => 'Der Bürger hat das Wasser in einem Zug ausgetrunken. Das tut gut!',
        'water_drink_dehydration'   => 'Gierig schluckst du deine Wasserration runter, ohne auch nur einen Tropfen zu verschwenden... Die Zunge klebt dir noch immer am Gaumen und dein Hals ist noch genauso trocken wie vorher. Du hast deinen Verdurstungstod erfolgreich verhindert.{hr}<strong>Da du davor schon dehydriert warst, hast du keine neuen APs erhalten.</strong>',
        'water_drink_ghoul'         => 'Gierig schluckst du deine Wasserration runter, ohne auch nur einen Tropfen zu verschwenden: aber das Ergebnis ist nicht, was du erwartet hast... Ohne Vorwarnung beginnt deine Kehle fürchterlich zu brennen und verursacht einen fürchterlich schmerzhaften Husten! Die sinkst auf die Knie und schnappst nach Luft. Als Ghul kannst du nicht länger Wasser konsumieren: <strong>du bist nun verletzt</strong>.',
        'water_to_well'             => 'Du hast den Inhalt des {item} in den Brunnen geschüttet. Der Brunnen wurde um {well} Rationen Wasser aufgefüllt..',

        'escort_food_eat'           => 'Du befielst diesem Bürger sich zu <strong>stärken</strong>, damit ihr gemeinsam weiterziehen könnt. Er ist einverstanden und holt eine(e,n) {item} aus seinem Sack... Ihr macht ne kleine Brotzeit...{hr}{user} hat seine AP aufgefüllt!',
    
        'container_open_tool'       => 'Du hast mit dem(r) {item_tool} ein(e) {item_from} geöffnet und darin folgenden Gegenstand gefunden: {item_to}.',    
        'container_open_weapon'     => 'Du hast ein(e) {item_from} mit einer(m) {item_tool} zerstört. In den Resten auf dem Boden findest du: {item_to}.',
        'container_open'            => 'Du hast ein(e) {item_from} geöffnet und darin folgenden Gegenstand gefunden: {items_spawn}.',
        'container_open_not_empty'  => 'Du hast ein(e) {item_from} geöffnet und darin folgenden Gegenstand gefunden: {items_spawn}.{hr}Die Kiste ist nicht leer...',
        'container_open_empty'      => 'Du hast ein(e) {item_from} geöffnet und darin folgenden Gegenstand gefunden: {items_spawn}.{hr}{item_from} ist <strong>leer</strong>.',
        'container_open_cbox'       => 'Verloren inmitten einer lächerlichen Menge sinnloser Dokumente hast du einen {item_to} entdeckt.',
        'container_optional'        => '<nt-spawned>Trotz aller Anstrengungen ist es dir nicht gelungen, den {item} zu öffnen...</nt-spawned><t-spawned>Du hast die {item} geöffnet und darin {items_spawn} gefunden!</t-spawned>',
        
        'drug_no_use'               => 'Du hast {item} eingenommen, verspürst aber keine nennenswerte Wirkung... Vielleicht hast du das gar nicht gebraucht?',
        'drug_no_use_2'             => 'Du nimmst das {item} ein, aber es scheint keinen Effekt zu haben... Vielleicht hast du das gar nicht gebraucht?',
        'drug_no_use_3'             => 'Merkwürdig... anscheinend ist der erwartete Effekt ausgeblieben. Du sagst dir selbst, das dies eine unglaubliche Chance ist...',
        'drug_xanax'                => 'Die Droge zeigt sofort Wirkung: Du bist auf einmal ganz <strong>ruhig</strong> geworden, so ausgeglichen... Die schrecklichen Ereignisse der letzten Tage erscheinen dir plötzlich bedeutungslos und ganz weit weg... Es geht dir schon viel besser.',
        'drug_para'                 => 'Das {item} beginnt rasch zu wirken. Das Fieber klingt ab, dein Herz beginnt wieder in einem halbwegs normalen Takt zu schlagen... Du warst nicht weit von einem schrecklichen Tod entfernt.',
        'drug_normal_ap'            => 'Die Nebenwirkungen der Droge lassen nicht lange auf sich warten: Übelkeit, Zittern, Schweißausbrüche, das ganze Programm... Gleichzeitig spürst du jedoch einen <strong>unglaublichen Kraftschub</strong>. Übertreibe es aber nicht!',
        'drug_twin_ap'              => 'So \'ne Quali bekommt man nicht jeden Tag in die Finger! Sicher, Übelkeit, Zittern und Schweißausbrüche hast du auch damit bekommen, aber die Wirkung ist weitaus stärker als bei \'ner normalen Droge (Du hast einen <strong>AP Bonus</strong> erhalten)... Übertreibe es aber nicht!',
        'drug_hyd'                  => 'Kaum hast du deine Pille geschluckt, da verschwindet auch schon dein Durst. Hoffen wir mal, das alles gut geht...',
        'drug_no_effect'            => 'Du schluckst die {item} hinunter. Nach einigen Minuten beginnt dein Kopf zu pochen und deine Nase beginnt zu bluten. Der Schmerz ist nicht unerträglich, aber er ist ziemlich unangenehm...',
        'drug_addict_ap'            => 'Sofort bereust du, dieses {item} heruntergeschluckt zu haben... Das Produkt entpuppt sich als ein starkes Steroid für Tiere (die Art, die üblicherweise Pferden verabreicht wird). Deine APs wurden wiederhergestellt, aber du bist ab sofort <strong>drogenabhängig</strong>!',
        'drug_terror'               => 'Du schluckst das {item}... Nur ein paar Sekunden später ergreift dich die Panik... Kalte Schweißausbrüche, Paranoia... Du rollst dich zu einem Ball zusammen, außer Atem, und wartest darauf, dass die Wirkung nachlässt... aber das passiert nicht.<hr />Du bist <strong>vor Angst erstarrt</strong>.',

        'item_load'                 => 'Du hast eine {items_consume} in dein/e/n {item_from} eingelegt und {item_to} erhalten!',
        'item_fill'                 => 'Du hast eine {items_consume} in dein/e/n {item_from} gefüllt und {item_to} erhalten!',
        'item_clean'                => 'Du hast den Inhalt des {item} gereinigt und {items_spawn} erhalten.',
        'item_clean_watercup'       => 'Du hast {items_consume_1} verwendet, um das Wasser in {item_from} zu reinigen. Du hast {items_spawn} erhalten...',

        'weapon_use'                => '<t-kill-latest>Du hast alle Zombies in dieser Zone mit dieser Waffe umgebracht: {item} ! Entspann dich mal... oder vielleicht besser nicht?</t-kill-latest><nt-kill-latest>Du hast mit dieser Waffe: {item} {kills} Zombie getötet. Ha! Ha! Ha! Das tut wirklich gut, ab und zu mal ein paar Zombies fertig zu machen...</nt-kill-latest>',
        'battery_use'               => 'Die Waffe hat <strong>keine Batterie mehr</strong>.',
        'battery_dropped'           => 'Die Batterie fällt ein paar Meter entfernt auf den Boden.',
        'battery_destroyed'         => 'Die Batterie wurde beim Aufprall vollständig zerstört.',
        'throw_animal'              => '<t-consumed>Du hetzt das/den/die {item} auf einen Zombie. Der Untote greift sich deinen tierischen Begleiter, zerquetscht es mit seinen Griffen und würgt es ganz langsam herunter... Dumm gelaufen.. aber wenigstens hast ihn damit eine Zeit lang beschäftigt...</t-consumed><nt-consumed>Dein Tier <strong>geht einem Zombie sofort an die Gurgel</strong> und reißt sich ein paar schöne Happen! Bravo!</nt-consumed>',

        'read_blueprint'            => '<t-bp_ok>Du liest den {item} und stellst fest, dass es sich um einen Plan für {bp_spawn} handelt.</t-bp_ok><t-bp_parent>{hr}Dafür ist das Bauprojekt {bp_parent} nötig.</t-bp_parent><t-bp_fail>Du versuchst den {item} zu lesen, kannst seinen Inhalt aber nicht verstehen ...</t-bp_fail>',
    
        'use_lab'                   => 'In deinem Labor hast du {items_consume} in {items_spawn} umgewandelt.',
        'use_kitchen'               => 'In deiner Küche hast du {items_consume} in {items_spawn} umgewandelt.',
        'use_butcher'               => 'Der Metzger hat sich gut um {item} gekümmert... Dafür hast du nun {items_spawn} erhalten. Auf wiedersehen, mein Freund!',
        'use_bed'                   => 'Du versuchst dich ein paar Minuten auszuruhen.<t-ap-up>Nach einer kurzen Pause fühlst du dich nun viel besser. Du hast 2 AP erhalten !</t-ap-up><nt-ap-up>Leider bekommst du kein Auge zu: Der Gedanke an heute Abend, deinen Tod, sowie deine geringen Überlebenschancen lassen dir keine Ruhe...</nt-ap-up>',
        
        'escape_item'               => 'Mithilfe der {item} hast du dir etwas Zeit erkauft ... du solltest diesen Ort schnell verlassen!',
        'escape_item_camera'        => '<nt-any-escape>Ein stumpfes Klicken ertönt und ein wenig grünlicher Rauch entschwebt der {item} ...</nt-any-escape><t-escape>Du setzt {item} in Gang. Ein gewaltiger Blitz schießt heraus, mitten in die Reihen der Zombies! Geblendet stolpern sie umher und sind nicht länger in der Lage, dich zu finden.</t-escape><t-reverse-escape>Du setzt {item} in Gang. Ein gewaltiger Blitz schießt heraus, mitten in die Reihen der Zombies! Geblendet ergreifen {zombies} Zombies die Flucht.</t-reverse-escape>',

        'heroic_arma_tooltip'       => 'Du hast die Chance, 1 Zombie in der Zone zu töten; wenn du erfolgreich bist, erhältst du die Kontrolle über die Zone für 10 Minuten.<br /><em>Angesichts des <strong>Armageddon</strong> kannst du diese Spezialaktion <strong>einmal pro Spiel</strong> durchführen.</em>',
        'heroic_arma_fail'          => 'Du versuchst dich einem Zombie zu nähern, aber <strong>der Zombie reißt dir fast das Gesicht mit seinen Zähnen ab</strong>! Unmöglich zu bestehen...',
        'heroic_arma_success'       => 'Du rennst schreiend in den ersten Zombie in Reichweite! Mit einem kraftvollen, gut angepassten Schulterschlag <strong>schickst du ihn ein paar Meter weiter in den Staub</strong>...{hr}Du kannst diese Gelegenheit zur Flucht nutzen!',

        'home_heal_wound'           => 'Deine Wunde wurde geheilt, zumindest oberflächlich...',
        'home_heal_infect'          => 'Deine unselige Infektion wurde kuriert!',

        'item_needed_generic'           => 'Du benötigst {items_required}.',
        'water_purification_impossible' => 'Um dieses Wasser trinkbar zu machen, brauchst du <strong>irgendein Reinigungsmittel</strong> oder deine Stadt muss über einen <strong>Wasserreiniger</strong> verfügen. Die zweite Variante ist nicht verfügbar, wenn Du verbannt bist.',
        'once_a_day'                    => 'Du kannst diesen Gegenstand nur <strong>einmal am Tag</strong> verwenden...',
        'already_full_ap'               => 'Du hast bereits volle AP.',

        'eat_human_meat' => '<nt-stat-up-infection><nt-role-up-ghoul>Nach ein paar Sekunden spürst du den furchtbaren Nachgeschmack...</nt-role-up-ghoul></nt-stat-up-infection>',
    ];
    
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $sub_cache
     * @param array|null $data
     * @return Requirement
     * @throws Exception
     */
    private function process_meta_requirement(        
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array &$sub_cache, ?array &$data = null): Requirement
    {
        if (!isset($cache[$id])) {
            if ($data === null && !isset(static::$item_actions['meta_requirements'][$id])) throw new Exception('Requirement definition not found: ' . $id);

            $data = $data ?: static::$item_actions['meta_requirements'][$id];
            $requirement = $manager->getRepository(Requirement::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t<comment>Update</comment> meta condition <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new Requirement();
                $out->writeln( "\t\t<comment>Create</comment> meta condition <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->clear()
                ->setName( $id )
                ->setFailureMode( $data['type'] ?? Requirement::HideOnFail )
                ->setFailureText( isset($data['text_key']) ? static::$message_keys[$data['text_key']] : ($data['text'] ?? null) );

            foreach ($data['collection'] as $sub_id => $sub_req) {
                if (is_array($sub_req)) {
                    $sub_data = $sub_req;
                    $sub_req = "{$id}_i_{$sub_id}";
                }

                else {
                    if (!isset( static::$item_actions['requirements'][$sub_id] ))
                        throw new Exception('Requirement type definition not found: ' . $sub_id);
                    if (!isset( static::$item_actions['requirements'][$sub_id][$sub_req] ))
                        throw new Exception('Requirement entry definition not found: ' . $sub_id . '/' . $sub_req);

                    $sub_data = static::$item_actions['requirements'][$sub_id][$sub_req];
                }

                if (!isset($sub_cache[$sub_id])) $sub_cache[$sub_id] = [];
                                
                switch ($sub_id) {
                    case 'ap':
                        $requirement->setAp( $this->process_ap_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'status':
                        $requirement->setStatusRequirement( $this->process_status_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'item':
                        $requirement->setItem( $this->process_item_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'location':
                        $requirement->setLocation( $this->process_location_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'zombies':
                        $requirement->setZombies( $this->process_zombie_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'home':
                        $requirement->setHome( $this->process_home_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'counter':
                        $requirement->setCounter( $this->process_counter_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'building':
                        $requirement->setBuilding( $this->process_building_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'zone':
                        $requirement->setZone( $this->process_zone_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'pm':
                        $requirement->setPm( $this->process_pm_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'cp':
                        $requirement->setCp( $this->process_cp_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'conf':
                        $requirement->setConf( $this->process_conf_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'day':
                        $requirement->setDay( $this->process_day_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'custom':
                        $requirement->setCustom( $sub_data[0] );
                        break;
                    default:
                        throw new Exception('No handler for requirement type ' . $sub_id);
                }
            }

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t<comment>Skip</comment> meta condition <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireAP
     * @throws Exception
     */
    private function process_ap_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireAP
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireAP::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireAP();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] )->setRelativeMax( $data['relative'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequirePM
     * @throws Exception
     */
    private function process_pm_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequirePM
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequirePM::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequirePM();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] )->setRelativeMax( $data['relative'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireDay
     * @throws Exception
     */
    private function process_day_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireDay
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireDay::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>day/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireDay();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>day/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>day/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireCP
     * @throws Exception
     */
    private function process_cp_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireCP
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireCP::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireCP();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] )->setRelativeMax( $data['relative'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireConf
     * @throws Exception
     */
    private function process_conf_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireConf
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireConf::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>conf/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireConf();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>conf/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setConf( $data['value'] )->setBoolVal( $data['bool'] ?? null );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>conf/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireStatus
     * @throws Exception
     */
    private function process_status_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out, 
        array &$cache, string $id, array $data): RequireStatus
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireStatus::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireStatus();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $status = isset($data['status']) ? $manager->getRepository(CitizenStatus::class)->findOneBy(['name' => $data['status']]) : null;
            $prof = isset($data['profession']) ? $manager->getRepository(CitizenProfession::class)->findOneBy(['name' => $data['profession']]) : null;
            $role = isset($data['role']) ? $manager->getRepository(CitizenRole::class)->findOneBy(['name' => $data['role']]) : null;
            if (isset($data['status']) && !$status) throw new Exception('Status condition not found: ' . $data['status']);
            if (isset($data['profession']) && !$prof) throw new Exception('Profession not found: ' . $data['profession']);
            if (isset($data['role']) && !$role) throw new Exception('Role not found: ' . $data['role']);

            $requirement->setName( $id )->setEnabled( $data['enabled'] ?? null )->setStatus( $status ?? null )->setProfession( $prof ?? null )->setRole( $role ?? null)->setBanished( $data['ban'] ?? null );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireItem
     * @throws Exception
     */
    private function process_item_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireItem
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireItem::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireItem();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $prototype = empty($data['item']) ? null : $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $data['item']]);
            if (!empty($data['item']) && ! $prototype) {
                throw new Exception('Item prototype not found: ' . $data['item']);
            }

            $property  = empty($data['prop']) ? null : $manager->getRepository(ItemProperty::class )->findOneBy(['name' => $data['prop']]);
            if (!empty($data['prop']) && ! $property)
                throw new Exception('Item property not found: ' . $data['prop']);

            if (!$prototype && !$property)
                throw new Exception('Item condition must have a prototype or property attached. not found: ' . $data['status']);

            $requirement->setName( $id )->setPrototype( $prototype )->setProperty( $property )->setCount( $data['count'] ?? 1 )->setAllowPoison( $data['allowPoison'] ?? false );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireLocation
     * @throws Exception
     */
    private function process_location_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireLocation
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireLocation::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>location/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireLocation();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>location/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id );
            if (count($data) === 1 && isset( $data[0] ))
                $requirement->setLocation( $data[0] );
            else $requirement
                ->setMinDistance( $data['min'] ?? null )
                ->setMaxDistance( $data['max'] ?? null )
                ->setLocation( $data['type'] ?? ( ( isset($data['min']) || isset($data['max']) ) ? RequireLocation::LocationOutsideOrExploring : RequireLocation::LocationInTown ) );

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>location/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireZombiePresence
     * @throws Exception
     */
    private function process_zombie_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireZombiePresence
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireZombiePresence::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>zombies/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireZombiePresence();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>zombies/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )
                ->setNumber( $data['min'] )
                ->setMustBlock( $data['block'] ?? null )
                ->setTempControlAllowed( $data['temp'] ?? null );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>zombies/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireHome
     * @throws Exception
     */
    private function process_home_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireHome
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireHome::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireHome();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement
                ->setName( $id )
                ->setMinLevel( $data['min_level'] ?? null )
                ->setMaxLevel( $data['max_level'] ?? null );

            if (isset($data['upgrade'])) {
                $proto = $manager->getRepository(CitizenHomeUpgradePrototype::class)->findOneBy(['name' => $data['upgrade']]);
                if (!$proto) throw new Exception('Home upgrade prototype not found: ' . $data['upgrade']);
                $requirement->setUpgrade( $proto );
            }

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireCounter
     * @throws Exception
     */
    private function process_counter_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireCounter
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireCounter::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>counter/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireCounter();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>counter/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement
                ->setName( $id )
                ->setType( $data['type'] )
                ->setMin( $data['min'] ?? null )
                ->setMax( $data['max'] ?? null );

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>counter/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireZone
     * @throws Exception
     */
    private function process_zone_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireZone
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireZone::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireZone();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id );
            if ($data['max_level']) $requirement->setMaxLevel( $data['max_level'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireBuilding
     * @throws Exception
     */
    private function process_building_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireBuilding
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireBuilding::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>building/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireBuilding();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>building/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $prototype = $manager->getRepository(BuildingPrototype::class)->findOneByName($data['prototype'], false );
            if (!$prototype)
                throw new Exception('Building prototype not found: ' . $data['item']);

            $requirement->setName( $id )->setBuilding( $prototype )
                ->setFound( $data['found'] ?? null )
                ->setComplete( $data['complete'] ?? null )
                ->setMinLevel( $data['minLevel'] ?? null )
                ->setMaxLevel( $data['maxLevel'] ?? null )
                ;
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>building/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }


    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $sub_cache
     * @param array|null $data
     * @return Result
     * @throws Exception
     */
    private function process_meta_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array &$sub_cache, ?array &$data = null): Result
    {
        if (!isset($cache[$id])) {
            if ($data === null && !isset(static::$item_actions['meta_results'][$id])) throw new Exception('Result definition not found: ' . $id);
            $data = $data ?: static::$item_actions['meta_results'][$id];

            $result = $manager->getRepository(Result::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t<comment>Update</comment> meta effect <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new Result();
                $out->writeln( "\t\t<comment>Create</comment> meta effect <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->clear();

            $collection = isset($data['collection']) ? $data['collection'] : $data;
            foreach ($collection as $sub_id => $sub_res) {
                if (is_array($sub_res)) {
                    $sub_data = $sub_res;
                    $sub_res = "{$id}_i_{$sub_id}";
                } else {
                    if (!isset( static::$item_actions['results'][$sub_id] ))
                        throw new Exception('Result type definition not found: ' . $sub_id);
                    if (!isset( static::$item_actions['results'][$sub_id][$sub_res] ))
                        throw new Exception('Result entry definition not found: ' . $sub_id . '/' . $sub_res);

                    $sub_data = static::$item_actions['results'][$sub_id][$sub_res];
                }

                if (!isset($sub_cache[$sub_id])) $sub_cache[$sub_id] = [];

                switch ($sub_id) {
                    case 'status':
                        $result->setStatus( $this->process_status_effect($manager,$out,$sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'ap':
                        $result->setAp( $this->process_ap_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'bp':
                        $result->setBlueprint( $this->process_blueprint_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'pm':
                        $result->setPm( $this->process_pm_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'cp':
                        $result->setCp( $this->process_cp_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'death':
                        $result->setDeath( $this->process_death_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'item':
                        $result->setItem( $this->process_item_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'target':
                        $result->setTarget( $this->process_item_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'spawn':
                        $result->setSpawn( $this->process_spawn_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'consume':
                        $result->setConsume( $this->process_consume_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'zombies':
                        $result->setZombies( $this->process_zombie_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'home':
                        $result->setHome( $this->process_home_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'zone':
                        $result->setZone( $this->process_zone_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'well':
                        $result->setWell( $this->process_well_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'group':
                        $result->setResultGroup( $this->process_group_effect($manager, $out, $sub_cache[$sub_id], $cache, $sub_cache, $sub_res, $sub_data) );
                        break;
                    case 'rp':
                        $result->setRolePlayText( $sub_data[0] );
                        break;
                    case 'picto':
                        $result->setPicto( $this->process_picto_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'globalpicto':
                        $result->setGlobalPicto( $this->process_picto_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'message':
                        $result->setMessage( $this->process_message_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'town':
                        $result->setTown( $this->process_town_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'custom':
                        $result->setCustom( $sub_data[0] );
                        break;
                    default:
                        throw new Exception('No handler for effect type ' . $sub_id);
                }
            }

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t<comment>Skip</comment> meta effect <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }
    
    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectStatus
     * @throws Exception
     */
    private function process_status_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectStatus
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectStatus::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectStatus();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $status_from = empty($data['from']) ? null : $manager->getRepository(CitizenStatus::class)->findOneBy(['name' => $data['from']]);
            if (!$status_from && !empty($data['from'])) throw new Exception('Status effect not found: ' . $data['from']);
            $status_to = empty($data['to']) ? null : $manager->getRepository(CitizenStatus::class)->findOneBy(['name' => $data['to']]);
            if (!$status_to && !empty($data['to'])) throw new Exception('Status effect not found: ' . $data['to']);

            $role = (empty($data['role']) || !isset( $data['enabled'] ) || $data['enabled'] === null) ? null : $manager->getRepository(CitizenRole::class)->findOneBy(['name' => $data['role']]);

            $result
                ->setResetThirstCounter( $data['reset_thirst'] ?? null )
                ->setCitizenHunger( $data['hunger'] ?? null )
                ->setForced( $data['force'] ?? false )
                ->setCounter( $data['counter'] ?? null );

            if (!$status_from && !$status_to && !$result->getResetThirstCounter() && !$result->getCitizenHunger() && $result->getCounter() === null && $role === null) {
                throw new Exception('Status effects must have at least one attached status.');
            }

            $result->setName( $id )->setInitial( $status_from )->setResult( $status_to )->setRole($role)->setRoleAdd( $data['enabled'] ?? null);
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectAP
     */
    private function process_ap_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectAP
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectAP::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectAP();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( $data['max'] )->setAp( $data['num'] );
            if ($data['max']) $result->setBonus( $data['num'] );
            else $result->setBonus( isset($data['bonus']) ? $data['bonus'] : 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectPM
     */
    private function process_pm_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectPM
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectPM::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectPM();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( $data['max'] )->setPm( $data['num'] );
            if ($data['max']) $result->setBonus( $data['num'] );
            else $result->setBonus( isset($data['bonus']) ? $data['bonus'] : 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectCP
     */
    private function process_cp_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectCP
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectCP::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectCP();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( $data['max'] )->setCp( $data['num'] );
            if ($data['max']) $result->setBonus( $data['num'] );
            else $result->setBonus( isset($data['bonus']) ? $data['bonus'] : 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectDeath
     */
    private function process_death_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectDeath
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectDeath::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>death/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectDeath();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>death/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setCause(  $manager->getRepository(CauseOfDeath::class)->findOneBy( ['ref' => $data[0]] ));
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>death/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectBlueprint
     * @throws Exception
     */
    private function process_blueprint_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectBlueprint
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectBlueprint::class)->findOneBy(['name' => $id]);
            if ($result) {
                $result->getList()->clear();
                $out->writeln( "\t\t\t<comment>Update</comment> effect <info>blueprint/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            else {
                $result = new AffectBlueprint();
                $out->writeln("\t\t\t<comment>Create</comment> effect <info>blueprint/{$id}</info>", OutputInterface::VERBOSITY_DEBUG);
            }

            $result->setName( $id );
            if (count($data) === 1 && is_numeric( $data[0] ))
                $result->setType( $data[0] );
            else {
                $result->setType( -1 );
                foreach ($data as $proto) {

                    $bpp = $manager->getRepository(BuildingPrototype::class)->findOneByName($proto, false );
                    if (!$bpp) throw new Exception("Building Prototype not found: {$proto}");

                    $result->addList( $bpp );
                }
            }


            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>blueprint/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectOriginalItem
     * @throws Exception
     */
    private function process_item_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectOriginalItem 
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectOriginalItem::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectOriginalItem();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $morph_to = empty($data['morph']) ? null : $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $data['morph']]);
            if (!$morph_to && !empty($data['morph'])) throw new Exception('Item prototype not found: ' . $data['morph']);

            if ($morph_to && $data['consume']) throw new Exception('Item effects cannot morph and consume at the same time!');

            $result->setName( $id )->setConsume( $data['consume'] )->setMorph( $morph_to )
                ->setBreak( isset($data['break']) ? $data['break'] : null )
                ->setPoison( isset($data['poison']) ? $data['poison'] : null );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectItemSpawn
     * @throws Exception
     */
    private function process_spawn_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectItemSpawn
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectItemSpawn::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>spawn/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = (new AffectItemSpawn())->setName( $id );
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>spawn/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            if (isset($data['where']))
                $actual_data = $data['what'];
            else $actual_data = $data['what'] ?? $data;
            $target = $data['where'] ?? AffectItemSpawn::DropTargetDefault;

            if (count($actual_data) === 1) {
                $name = is_array($actual_data[0]) ? $actual_data[0][0] : $actual_data[0];
                $count =  is_array($actual_data[0]) ? $actual_data[0][1] : 1;
                $prototype = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $name]);
                if (!$prototype) throw new Exception('Item prototype not found: ' . $name);
                $result->setPrototype( $prototype )->setCount( $count )->setSpawnTarget($target);
            } else {
                $g_name = "efg_{$id}";
                $group = $manager->getRepository( ItemGroup::class )->findOneBy(['name' => $g_name]);
                if ($group) $group->getEntries()->clear();
                else $group = (new ItemGroup())->setName( $g_name );

                foreach ($actual_data as $entry) {
                    [$p,$c] = is_array($entry) ? $entry : [$entry,1];
                    $prototype = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $p]);

                    if (!$prototype) {
                        print_r($data);
                        throw new Exception('Item prototype not found4: ' . $p);
                    }
                    $group->addEntry( (new ItemGroupEntry())->setChance($c)->setPrototype( $prototype ) );
                }

                $result->setItemGroup( $group )->setCount( 1 )->setSpawnTarget($target);
                $manager->persist( $group );
            }

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>spawn/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectItemConsume
     * @throws Exception
     */
    private function process_consume_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectItemConsume
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectItemConsume::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>consume/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = (new AffectItemConsume())->setName( $id );
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>consume/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            [$name,$count] = count($data) > 1 ? $data : [$data[0],1];
            $prototype = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $name]);
            if (!$prototype) throw new Exception('Item prototype not found: ' . $name);
            $result->setPrototype( $prototype )->setCount( $count );

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>consume/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectZombies
     */
    private function process_zombie_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectZombies
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectZombies::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>zombie/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectZombies();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>zombie/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( isset($data['max']) ? $data['max'] : $data['num'] )->setMin( isset($data['min']) ? $data['min'] : $data['num'] );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>zombie/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectHome
     */
    private function process_home_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectHome
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectHome::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectHome();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setAdditionalDefense( $data['def'] ?? 0 )->setAdditionalStorage( $data['store'] ?? 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectWell
     */
    private function process_well_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectWell
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectWell::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>well/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectWell();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>well/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setFillMax( $data['max'] )->setFillMin( $data['min'] );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>well/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectZone
     */
    private function process_zone_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectZone
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectZone::class)->findOneBy(['name' => $id]);
            /** @var AffectZone $result */
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectZone();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )
                ->setUncoverZones( $data['scout'] ?? false )
                ->setUncoverRuin( $data['uncover'] ?? false )
                ->setEscape( $data['escape'] ?? null )
                ->setImproveLevel( $data['improve'] ?? null )
                ->setChatSilence( $data['chatSilence'] ?? null);
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param array $meta_cache
     * @param array $sub_cache
     * @param string $id
     * @param array $data
     * @return AffectResultGroup
     * @throws Exception
     */
    private function process_group_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, array &$meta_cache, array &$sub_cache, string $id, array $data): AffectResultGroup
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectResultGroup::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>group/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = (new AffectResultGroup())->setName( $id );
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>group/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            foreach ( $result->getEntries() as $entry ) $manager->remove( $entry ); $result->getEntries()->clear();
            foreach ( $data as $k => $entry ) {

                $entry_obj = new AffectResultGroupEntry();
                $entry_obj->setCount( $entry[1] );

                if (!is_array($entry[0])) $entry[0] = [$entry[0]];
                foreach ( $entry[0] as $n => $nested_action ) {
                    if (is_array($nested_action))
                        $entry_obj->addResult($this->process_meta_effect($manager, $out, $meta_cache, "{$id}_{$k}_{$n}", $sub_cache, $nested_action));
                    else $entry_obj->addResult($this->process_meta_effect($manager, $out, $meta_cache, $nested_action, $sub_cache));
                }
                $result->addEntry( $entry_obj );
                $manager->persist( $entry_obj );
            }

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>group/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectPicto
     */
    private function process_picto_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectPicto
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectPicto::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>picto/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectPicto();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>picto/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setPrototype(  $manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $data[0]]));
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>picto/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectMessage
     */
    private function process_message_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectMessage
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectMessage::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>message/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectMessage();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>message/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setText(isset($data['text_key']) ? static::$message_keys[$data['text_key']] : $data['text'])->setEscort($data['escort'] ?? null);
            if(isset($data['ordering']))
                  $result->setOrdering($data['ordering']);
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>message/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectTown
     */
    private function process_town_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectTown
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectTown::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>town/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectTown();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setAdditionalDefense( $data['def'] ?? 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    public function generate_action( ObjectManager $manager, ConsoleOutputInterface $out, string $action,
                                     array &$set_meta_requirements, array &$set_sub_requirements,
                                     array &$set_meta_results, array &$set_sub_results,
                                     array &$set_actions): ItemAction {

        if (!isset($set_actions[$action])) {
            if (!isset(static::$item_actions['actions'][$action])) throw new Exception('Action definition not found: ' . $action);

            $data = static::$item_actions['actions'][$action];
            $new_action = $manager->getRepository(ItemAction::class)->findOneBy(['name' => $action]);
            if ($new_action) $out->writeln( "\t<comment>Update</comment> action <info>$action</info> ('<info>{$data['label']}</info>')", OutputInterface::VERBOSITY_DEBUG );
            else {
                $new_action = new ItemAction();
                $out->writeln( "\t<comment>Create</comment> action <info>$action</info> ('<info>{$data['label']}</info>')", OutputInterface::VERBOSITY_DEBUG );
            }

            $new_action->setName( $action )->setLabel( $data['label'] )->clearRequirements()->clearResults()
                ->setMessage( isset($data['message_key']) ? static::$message_keys[$data['message_key']] : ($data['message'] ?? null) )
                ->setEscortMessage( isset($data['escort_message_key']) ? static::$message_keys[$data['escort_message_key']] : ($data['escort_message'] ?? null) )
                ->setConfirm( $data['confirm'] ?? false )
                ->setConfirmMsg( $data['confirmMsg'] ?? null );

            $new_action->setTooltip(isset($data['tooltip_key']) ? static::$message_keys[$data['tooltip_key']] : ($data["tooltip"] ?? null));

            if ($new_action->getTarget() && !isset($data['target'])) {
                $manager->remove( $new_action->getTarget() );
                $new_action->setTarget(null);
            }

            if (isset($data['target'])) {
                if (!$new_action->getTarget()) $new_action->setTarget( new ItemTargetDefinition() );
                $new_action->getTarget()
                    ->setSpawner( $data['target']['type'] ?? ItemTargetDefinition::ItemSelectionType )
                    ->setHeavy( $data['target']['heavy'] ?? null )
                    ->setPoison( $data['target']['poison'] ?? null )
                    ->setBroken( $data['target']['broken'] ?? null );
                if (isset( $data['target']['property'] )) {
                    $prop = $manager->getRepository(ItemProperty::class)->findOneBy(['name' => $data['target']['property']]);
                    if (!$prop) throw new Exception("Item property not found: '{$data['target']['property']}'");
                    $new_action->getTarget()->setTag($prop);
                } else $new_action->getTarget()->setTag(null);
                if (isset( $data['target']['prototype'] )) {
                    $proto = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $data['target']['prototype']]);
                    if (!$proto) throw new Exception("Item prototype not found: '{$data['target']['prototype']}'");
                    $new_action->getTarget()->setPrototype($proto);
                } else $new_action->getTarget()->setPrototype(null);
            }

            $new_action
                ->setAllowWhenTerrorized( $data['allow_when_terrorized'] ?? false )
                ->setKeepsCover( $data['cover'] ?? false )
                ->setAllowedAtGate( $data['at00'] ?? false )
                ->setPoisonHandler( $data['poison'] ?? ItemAction::PoisonHandlerIgnore );

            foreach ( $data['meta'] as $num => $requirement ) {
                if (is_array($requirement))
                    $new_action->addRequirement( $this->process_meta_requirement( $manager, $out, $set_meta_requirements, "{$action}_{$num}", $set_sub_requirements, $requirement ) );
                else $new_action->addRequirement( $this->process_meta_requirement( $manager, $out, $set_meta_requirements, $requirement, $set_sub_requirements ) );
            }

            foreach ( $data['result'] as $num => $result ) {
                if (is_array($result))
                    $new_action->addResult( $this->process_meta_effect($manager,$out, $set_meta_results, "{$action}_{$num}", $set_sub_results, $result) );
                else $new_action->addResult( $this->process_meta_effect($manager,$out, $set_meta_results, $result, $set_sub_results) );
            }

            $manager->persist( $set_actions[$action] = $new_action );
        } else $out->writeln( "\t<comment>Skip</comment> action <info>$action</info> ('<info>{$set_actions[$action]->getLabel()}</info>')", OutputInterface::VERBOSITY_DEBUG );

        return $set_actions[$action];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    public function insert_item_actions(ObjectManager $manager, ConsoleOutputInterface $out) {

        $out->writeln( '<comment>Compiling item action fixtures.</comment>', OutputInterface::VERBOSITY_DEBUG );

        $set_meta_requirements = [];
        $set_sub_requirements = [];

        $set_meta_results = [];
        $set_sub_results = [];

        $set_actions = [];

        foreach (static::$item_actions['items'] as $item_name => $actions) {

            $item = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $item_name]);
            if (!$item) throw new Exception('Item prototype not found: ' . $item_name);

            $item->getActions()->clear();
            $out->writeln( "Compiling action set for item <info>{$item->getLabel()}</info>...", OutputInterface::VERBOSITY_DEBUG );

            foreach ($actions as $action)
                $item->addAction( $this->generate_action( $manager, $out, $action, $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $item );
        }

        foreach (static::$item_actions['heroics'] as $action) {

            $action_proto = $manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $action['name']]);
            if (!$action_proto) $action_proto = (new HeroicActionPrototype)->setName( $action['name'] );
            $action_proto->setUnlockable($action['unlockable']);

            $out->writeln( "Compiling action set for heroic action <info>{$action['name']}</info>...", OutputInterface::VERBOSITY_DEBUG);

            $action_proto->setAction( $this->generate_action( $manager, $out, $action['name'], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $action_proto );
        }

        foreach (static::$item_actions['specials'] as $action) {
            $action_proto = $manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => $action['name']]);
            if (!$action_proto) $action_proto = (new SpecialActionPrototype)->setName( $action['name'] );
            $action_proto
                ->setIcon($action['icon'])
                ->setConsumable($action['consumable'] ?? true);

            $out->writeln( "Compiling action set for special action <info>{$action['name']}</info>...", OutputInterface::VERBOSITY_DEBUG);
            $action_proto->setAction( $this->generate_action( $manager, $out, $action['name'], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $action_proto );
        }

        foreach (static::$item_actions['camping'] as $action) {

            $action_proto = $manager->getRepository(CampingActionPrototype::class)->findOneBy(['name' => $action]);
            if (!$action_proto) $action_proto = (new CampingActionPrototype)->setName( $action );

            $action_proto->setAction( $this->generate_action( $manager, $out, $action, $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $action_proto );
        }
        foreach ($manager->getRepository(CampingActionPrototype::class)->findAll() as $existing_action)
            if (!in_array( $existing_action->getName(), static::$item_actions['camping'] )) {
                $out->writeln("Removing obsolete camping action <info>{$existing_action->getName()}</info>.");
                $manager->remove( $existing_action );
            }

        foreach (static::$item_actions['home'] as $action_group) {

            $action_proto = $manager->getRepository(HomeActionPrototype::class)->findOneBy(['name' => $action_group[0]]);
            if (!$action_proto) $action_proto = (new HomeActionPrototype)->setName( $action_group[0] );
            $action_proto->setIcon( $action_group[1] );

            $action_proto->setAction( $this->generate_action( $manager, $out, $action_group[0], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $action_proto );
        }

        foreach (static::$item_actions['escort'] as $escort_key => $escort_group) {

            $escort_proto = $manager->getRepository(EscortActionGroup::class)->findOneBy(['name' => $escort_key]);
            if (!$escort_proto) $escort_proto = (new EscortActionGroup);
            $escort_proto
                ->setName( $escort_key )
                ->setIcon( $escort_group['icon'] )
                ->setLabel( $escort_group['label'] )
                ->setTooltip( $escort_group['tooltip'] ?? null )
                ->getActions()->clear();

            foreach ($escort_group['actions'] as $action_id)
                if (isset($set_actions[$action_id]))
                    $escort_proto->addAction( $set_actions[$action_id] );

            $manager->persist( $escort_proto );
        }

        /** @var ItemPrototype[] $all_prototypes */
        $all_prototypes = $this->entityManager->getRepository(ItemPrototype::class)->findAll();
        foreach ($all_prototypes as $prototype) {

            if ($prototype->getWatchpoint() !== 0 && !isset(static::$item_actions['items_nw'][$prototype->getName()]))
                throw new Exception("Item prototype '{$prototype->getName()}' ({$prototype->getLabel()}) has {$prototype->getWatchpoint()} watch points, but no night watch action!");
            else if (isset(static::$item_actions['items_nw'][$prototype->getName()])) {
                $prototype->setNightWatchAction( $this->generate_action( $manager, $out, static::$item_actions['items_nw'][$prototype->getName()], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );
                $this->entityManager->persist($prototype);
            }
        }

        foreach (static::$item_actions['items'] as $item_name => $actions) {

            $item = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $item_name]);
            if (!$item) throw new Exception('Item prototype not found: ' . $item_name);

            $item->getActions()->clear();
            $out->writeln( "Compiling action set for item <info>{$item->getLabel()}</info>...", OutputInterface::VERBOSITY_DEBUG );

            foreach ($actions as $action)
                $item->addAction( $this->generate_action( $manager, $out, $action, $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $item );
        }

        $manager->flush();
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Actions</info>' );
        $output->writeln("");

        $this->insert_item_actions( $manager, $output );

        $output->writeln("");
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [ ItemFixtures::class, RecipeFixtures::class, CitizenFixtures::class ];
    }
}
