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

    const CONF_FEATURE_CAMPING        = 'features.camping';
    const CONF_FEATURE_WORDS_OF_HEROS = 'features.words_of_heros';
    const CONF_FEATURE_ESCORT         = 'features.escort.enabled';
    const CONF_FEATURE_ESCORT_SIZE    = 'features.escort.max';

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