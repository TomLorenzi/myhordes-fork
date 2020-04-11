<?php


namespace App\Structures;

class TownConf
{
    private $data;
    private $flat;
    private $is_complete = false;

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

    private function deep_merge( array &$base, array $inc ) {
        foreach ($inc as $key => $data) {
            if (!isset($base[$key])) $base[$key] = $data;
            elseif ( is_array( $base[$key] ) === is_array( $data ) ) {
                if (is_array($data) && array_keys($data) === ['replace'])  $base[$key] = $data['replace'];
                elseif (is_array($data) && array_keys($data) === ['merge']) $base[$key] = array_merge( $base[$key], $data['merge'] );
                elseif (is_array($data)) $this->deep_merge( $base[$key], $data );
                else $base[$key] = $data;
            }
        }
    }

    private function flatten(array &$data, ?array &$lines = [], string $prefix = '' ) {
        foreach ($data as $key => $entry) {
            $go_deeper = is_array($entry) && !empty($entry) && count(array_filter( array_keys( $entry ), function ($k) { return is_numeric($k); } )) !== count($entry);

            $current_key = empty($prefix) ? $key : "{$prefix}.{$key}";
            if ($go_deeper) $this->flatten( $entry, $lines, $current_key );
            else $lines[$current_key] = $entry;
        }
    }

    public function __construct(array $data)
    {
        $this->data = null;
        foreach ( $data as $conf_block )
            if ($conf_block === null) continue;
            elseif ($this->data === null)
                $this->data = $conf_block;
            else $this->import( $conf_block );
    }

    public function import( array $data ): self {
        if (!$this->is_complete)
            $this->deep_merge( $this->data, $data );
        return $this;
    }

    public function complete(): self {
        if ($this->is_complete) return $this;
        $this->is_complete = true;
        $this->flatten($this->data, $this->flat);
        return $this;
    }

    public function raw(): array {
        return $this->flat;
    }

    public function get(string $key, $default = null) {
        return $this->flat[$key] ?? $default;
    }
}