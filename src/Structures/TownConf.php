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
    const CONF_BUILDINGS_CONSTRUCTED = 'initial_buildings';
    const CONF_BUILDINGS_UNLOCKED    = 'unlocked_buildings';
    const CONF_DISTRIBUTED_ITEMS     = 'distribute_items';
    const CONF_FEATURE_CAMPING        = 'features.camping';
    const CONF_FEATURE_WORDS_OF_HEROS = 'features.words_of_heros';

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