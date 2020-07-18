<?php


namespace App\Structures;

class TownConf extends Conf
{
    const CONF_ALLOW_LOCAL = 'allow_local_conf';
    const CONF_WELL_MIN  = 'well.min';
    const CONF_WELL_MAX  = 'well.max';
    const CONF_MAP_MIN  = 'map.min';
    const CONF_MAP_MAX  = 'map.max';
    const CONF_POPULATION_MIN  = 'population.min';
    const CONF_POPULATION_MAX  = 'population.max';
    const CONF_NUM_RUINS = 'ruins';
    const CONF_NUM_EXPLORABLE_RUINS = 'explorable_ruins';
    const CONF_BUILDINGS_CONSTRUCTED = 'initial_buildings';
    const CONF_BUILDINGS_UNLOCKED    = 'unlocked_buildings';
    const CONF_DISTRIBUTED_ITEMS     = 'distribute_items';
    const CONF_DEFAULT_CHEST_ITEMS   = 'initial_chest';
    const CONF_DISTRIBUTION_DISTANCE = 'distribution_distance';

    const CONF_EXPLORABLES_COMPLEXITY   = 'explorable_ruin_params.complexity';
    const CONF_EXPLORABLES_CONVOLUTION  = 'explorable_ruin_params.convolution';
    const CONF_EXPLORABLES_CRUELTY      = 'explorable_ruin_params.cruelty';
    const CONF_EXPLORABLES_ROOMS        = 'explorable_ruin_params.rooms';
    const CONF_EXPLORABLES_ROOM_DIST    = 'explorable_ruin_params.room_spacing';
    const CONF_EXPLORABLES_LOCKDIST     = 'explorable_ruin_params.lock_distance';
    const CONF_EXPLORABLES_ITEM_RATE    = 'explorable_ruin_params.item_fillrate';
    const CONF_EXPLORABLES_MAX_DISTANCE = 'explorable_ruin_params.max_distance';
    const CONF_EXPLORABLES_ZOMBIES_INI  = 'explorable_ruin_params.zombies.initial';
    const CONF_EXPLORABLES_ZOMBIES_DAY  = 'explorable_ruin_params.zombies.daily';

    const CONF_TIMES_DIG_NORMAL     = 'times.digging.normal';
    const CONF_TIMES_DIG_COLLEC     = 'times.digging.collec';
    const CONF_TIMES_EXPLORE_NORMAL = 'times.exploration.normal';
    const CONF_TIMES_EXPLORE_COLLEC = 'times.exploration.collec';

    const CONF_BANK_ABUSE_LIMIT = 'bank_abuse_limit';

    const CONF_MODIFIER_POISON_STACK         = 'modifiers.poison.stack_poisoned_items';
    const CONF_MODIFIER_POISON_TRANS         = 'modifiers.poison.transgress';
    const CONF_MODIFIER_WT_THRESHOLD         = 'modifiers.watchtower_estimation_threshold';
    const CONF_MODIFIER_ALLOW_REDIGS         = 'modifiers.allow_redig';
    const CONF_MODIFIER_FLOOR_ASMBLY         = 'modifiers.assemble_items_from_floor';
    const CONF_MODIFIER_PRE_ASSEMBLY         = 'modifiers.preview_item_assemblage';
    const CONF_MODIFIER_INFECT_DEATH         = 'modifiers.infection_death_chance';
    const CONF_MODIFIER_WOUND_TERROR_PENALTY = 'modifiers.wound_terror_penalty';
    const CONF_MODIFIER_ATTACK_PROTECT       = 'modifiers.attack_protection';
    const CONF_MODIFIER_CARRY_EXTRA_BAG      = 'modifiers.carry_extra_bag';
    const CONF_MODIFIER_BONES_IN_TOWN        = 'modifiers.meaty_bones_within_town';
    const CONF_MODIFIER_BUILDING_DAMAGE      = 'modifiers.building_attack_damage';

    const CONF_FEATURE_CAMPING         = 'features.camping';
    const CONF_FEATURE_NIGHTMODE       = 'features.nightmode';
    const CONF_FEATURE_SHAMAN          = 'features.shaman';
    const CONF_FEATURE_WORDS_OF_HEROS  = 'features.words_of_heros';
    const CONF_FEATURE_ESCORT          = 'features.escort.enabled';
    const CONF_FEATURE_ESCORT_SIZE     = 'features.escort.max';
    const CONF_FEATURE_XML             = 'features.xml_feed';
    const CONF_FEATURE_GHOUL_MODE      = 'features.ghoul_mode';
    const CONF_FEATURE_ALL_POISON      = 'features.all_poison';
    const CONF_FEATURE_SHUN            = 'features.shun';
    const CONF_FEATURE_GHOUL           = 'features.ghoul';
    const CONF_FEATURE_NIGHTWATCH      = 'features.nightwatch';
    const CONF_FEATURE_IMPROVEDDUMP    = 'features.improveddump';
    const CONF_FEATURE_ATTACKS         = 'features.attacks';
    const CONF_FEATURE_GIVE_ALL_PICTOS = 'features.give_all_pictos';
    const CONF_FEATURE_GIVE_SOULPOINTS = 'features.give_soulpoints';

    public function __construct(array $data)
    {
        $first = false;
        foreach ( $data as $conf_block )
            if ($conf_block === null) continue;
            elseif (!$first) {
                parent::__construct( $conf_block );
                $first = true;
            }
            else $this->import( $conf_block );
    }
}