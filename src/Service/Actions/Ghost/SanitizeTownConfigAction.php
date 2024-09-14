<?php

namespace App\Service\Actions\Ghost;

use Adbar\Dot;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenProfession;
use App\Entity\TownClass;
use App\Entity\User;
use App\Enum\Configuration\TownSetting;
use App\Service\ConfMaster;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;


class SanitizeTownConfigAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ConfMaster $conf,
    ) { }

    protected function elevation_needed( array &$head, array &$rules, ?int $trimTo = null ): int {

        $elevation = User::USER_LEVEL_BASIC;

        // Non-private town needs CROW permissions
        if ($head['townType'] !== 'custom') $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) $head['townType'] = 'custom';

        // Custom town name needs CROW permissions
        if (!empty($head['townName'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['townName']);

        // Custom town seed needs CROW permissions
        if (isset($head['townSeed'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['townSeed']);

        // Event tag needs CROW permissions
        if ($head['townEventTag'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['townEventTag']);

        // Custom event needs CROW permissions
        if ($head['event'] ?? null) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['event']);

        // Crow options
        if ($rules['features']['give_all_pictos'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['give_all_pictos']);
        if (!($rules['features']['picto_classic_cull_mode'] ?? true)) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['picto_classic_cull_mode']);
        if ($rules['features']['enable_pictos'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['enable_pictos']);
        if ($rules['features']['give_soulpoints'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['give_soulpoints']);
        if ($rules['modifiers']['strict_picto_distribution'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['modifiers']['strict_picto_distribution']);
        if (!($rules['lock_door_until_full'] ?? true)) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['lock_door_until_full']);
        if (isset($rules['open_town_limit'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['open_town_limit']);

        // Custom job and role settings require CROW permissions
        if (!empty($rules['disabled_jobs']) && $rules['disabled_jobs'] !== ['shaman']) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['disabled_jobs']);
        if (!empty($rules['disabled_roles']) && $rules['disabled_roles'] !== ['shaman']) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['disabled_roles']);

        // Custom building settings require CROW permissions
        if (!empty($rules['initial_buildings'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['initial_buildings']);
        if (!empty($rules['unlocked_buildings'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['unlocked_buildings']);
        if (!empty($rules['disabled_buildings'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['disabled_buildings']);

        // Using the town schedule setting requires CROW permissions
        if (!empty($head['townSchedule'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset( $head['townSchedule'] );

        // Using any other than the "incarnate" setting requires CROW permissions
        if (!empty($head['townIncarnation']) && $head['townIncarnation'] !== 'incarnate') $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) $head['townIncarnation'] = 'incarnate';

        // Deviating population numbers need CROW permissions
        if (($rules['population']['min'] ?? 40) !== 40 || ($rules['population']['max'] ?? 40) !== 40) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['population']['min'] = $rules['population']['max'] = 40;
        }

        // Maps larger than 27x27 need CROW permissions
        if (max($rules['map']['min'] ?? 0, $rules['map']['max'] ?? 0) > 27) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['map']['min'] = $rules['map']['max'] = 27;
        }

        // Explorable ruins with more than 3 floors need CROW permissions
        if ($rules['explorable_ruin_params']['space']['floors'] ?? 0 > 3) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['explorable_ruin_params']['space']['floors'] = 3;
        }

        // Explorable ruins with more than 20 rooms need CROW permissions
        if ($rules['explorable_ruin_params']['room_config']['total'] ?? 0 > 20) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['explorable_ruin_params']['room_config']['total'] = 20;
        }

        // Explorable ruins with custom resolution need CROW permissions
        if (($rules['explorable_ruin_params']['space']['x'] ?? 13) !== 13) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['explorable_ruin_params']['space']['x'] = 13;
        }
        if (($rules['explorable_ruin_params']['space']['y'] ?? 13) !== 13) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['explorable_ruin_params']['space']['y'] = 13;
        }

        // Maps with non-standard town position need CROW permissions
        if (($rules['map']['margin'] ?? 0.25) !== 0.25) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['map']['margin'] = 0.25;
        }

        // More than 3 explorable ruins need CROW permissions
        if (($rules['explorable_ruins'] ?? 0) > 3) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['explorable_ruins'] = 3;
        }

        // Well with more than 300 rations need CROW permissions
        if (max($rules['well']['min'] ?? 0, $rules['well']['max'] ?? 0) > 300) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['well']['min'] = $rules['well']['max'] = 300;
        }

        // Initial chest items need CROW permissions
        if (!empty( $rules['initial_chest'] )) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['initial_chest']);
        }

        // An open town limit other than 2 requires CROW permissions
        if ( ($rules['open_town_limit'] ?? 2) !== 2 ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['open_town_limit']);
        }

        // Citizen aliases require CROW permissions
        if ( ($rules['features']['citizen_alias'] ?? false) ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['citizen_alias']);
        }

        // FFA requires CROW permissions
        if ( ($rules['features']['free_for_all'] ?? false) ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['free_for_all']);
        }

        // FFT requires CROW permissions
        if ( ($rules['features']['free_from_teams'] ?? false) ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['free_from_teams']);
        }

        // Changed timing settings require CROW permissions
        if ( (($rules['times']['exploration']['collec'] ?? TownSetting::TimingExplorationCollector->default()) !== TownSetting::TimingExplorationCollector->default()) ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['times']['exploration']['collec']);
        }
        if ( (($rules['times']['exploration']['normal'] ?? TownSetting::TimingExplorationDefault->default()) !== TownSetting::TimingExplorationDefault->default()) ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['times']['exploration']['normal']);
        }

        return $elevation;
    }

    protected function move_lists( &$rules ): void
    {

        $lists = ['disabled_jobs', 'disabled_roles', 'initial_buildings', 'unlocked_buildings', 'disabled_buildings'];

        foreach ($lists as $list)
            if (isset( $rules[$list] ))
                $rules[$list] = ['replace' => $rules[$list]];
    }

    public function restore( $rules ): array
    {

        $lists = ['disabled_jobs', 'disabled_roles', 'initial_buildings', 'unlocked_buildings', 'disabled_buildings'];

        foreach ($lists as $list)
            if (isset( $rules[$list] ) && isset( $rules[$list]['replace'] ))
                $rules[$list] = $rules[$list]['replace'];

        $well_preset = match (true) {
            ($rules['well'] ?? null) === null => null,
            ($rules['well']['min'] ?? null) === 60 && ($rules['well']['max'] ?? null) === 90 => 'low',
            ($rules['well']['min'] ?? null) === 90 && ($rules['well']['max'] ?? null) === 180 => 'normal',
            ($rules['well']['min'] ?? null) === ($rules['well']['max'] ?? null) => '_fixed',
            default => '_range'
        };
        if ($well_preset !== null) $rules['wellPreset'] = $well_preset;

        $map_preset = match (true) {
            ($rules['map'] ?? null) === null && ($rules['ruins'] ?? null) === null && ($rules['explorable_ruins'] ?? null) === null => null,
            ($rules['map']['min'] ?? null) === 12 && ($rules['map']['max'] ?? null) === 14 && ($rules['ruins'] ?? null) === 7 && ($rules['explorable_ruins'] ?? null) === 0 => 'small',
            ($rules['map']['min'] ?? null) === 25 && ($rules['map']['max'] ?? null) === 27 && ($rules['ruins'] ?? null) === 20 && ($rules['explorable_ruins'] ?? null) === 1 => 'normal',
            ($rules['map']['min'] ?? null) === 32 && ($rules['map']['max'] ?? null) === 35 && ($rules['ruins'] ?? null) === 30 && ($rules['explorable_ruins'] ?? null) === 2 => 'large',
            default => '_custom'
        };
        if ($map_preset !== null) $rules['mapPreset'] = $map_preset;

        $map_margin_preset = match (true) {
            Arr::get( $rules, 'margin_custom.enabled' ) => '_custom',
            Arr::get( $rules, 'map.margin' ) === 0.25 => 'normal',
            Arr::get( $rules, 'map.margin' ) === 0.33 => 'close',
            Arr::get( $rules, 'map.margin' ) === 0.50 => 'central',
            default => '_custom'
        };

        if ($map_margin_preset !== null) $rules['mapMarginPreset'] = $map_margin_preset;
        if ($map_margin_preset === '_custom') {
            Arr::set( $rules, 'margin_custom.north', Arr::get( $rules, 'margin_custom.north', 0.25 ) * 100 );
            Arr::set( $rules, 'margin_custom.south', Arr::get( $rules, 'margin_custom.south', 0.25 ) * 100 );
            Arr::set( $rules, 'margin_custom.east', Arr::get( $rules, 'margin_custom.east', 0.25 ) * 100 );
            Arr::set( $rules, 'margin_custom.west', Arr::get( $rules, 'margin_custom.west', 0.25 ) * 100 );
        }

        return $rules;
    }

    public function sanitize_config(array $conf): array {
        static $unset = [
            'ruin_items',
            'zone_items',
            'map_params',
            'allow_local_conf',
            'bank_abuse',
            'spiritual_guide',
            'times',
            'distribute_items',
            'distribution_distance',
            'instant_pictos',
            'open_town_grace',
            'population',
            'stranger_citizen_limit',
            'stranger_day_limit',

            'explorable_ruin_params.max_distance',
            'explorable_ruin_params.plan_limits',
            'explorable_ruin_params.zombies',
            'explorable_ruin_params.room_config.lock',
            'explorable_ruin_params.room_config.distance',
            'explorable_ruin_params.room_config.spacing',
            'explorable_ruin_params.space.ox',

            'features.last_death',
            'features.last_death_day',
            'features.survival_picto',
            'features.words_of_heros',
            'features.escort.max',

            'modifiers.assemble_items_from_floor',
            'modifiers.citizen_attack',
            'modifiers.complaints',
            'modifiers.destroy_defense_objects_attack',
            'modifiers.ghoul_infection_begin',
            'modifiers.ghoul_infection_next',
            'modifiers.hide_home_upgrade',
            'modifiers.infection_death_chance',
            'modifiers.massive_respawn_factor',
            'modifiers.meaty_bones_within_town',
            'modifiers.preview_item_assemblage',
            'modifiers.red_soul_max_factor',
            'modifiers.sandball_nastyness',
            'modifiers.watchtower_estimation_offset',
            'modifiers.watchtower_estimation_threshold',
            'modifiers.wind_distance',
            'modifiers.wound_terror_penalty',
            'modifiers.camping',
            'modifiers.generosity',
            'modifiers.guard_tower',
        ];

        $dot = new Dot($conf);
        $dot->delete($unset);
        return $dot->all();
    }

    public function sanitize_outgoing_config(array $conf): array {
        static $unset = [
            'well',
            'map',
            'ruins'
        ];

        $dot = new Dot($this->sanitize_config( $conf ));
        $dot->delete($unset);
        return $dot->all();
    }

    public function sanitize_incoming_config(array $conf, TownClass $base): array {
        $conf = $this->sanitize_config($conf);

        $map_preset = $conf['mapPreset'] ?? null;
        unset( $conf['mapPreset'] );

        $explorable_preset = $conf['explorablePreset'] ?? null;
        unset( $conf['explorablePreset'] );

        $map_margin_preset = $conf['mapMarginPreset'] ?? null;
        unset( $conf['mapMarginPreset'] );
        unset( $conf['map']['margin'] );

        $margin_custom = $conf['margin_custom'] ?? null;
        unset( $conf['margin_custom'] );

        $well_preset = $conf['wellPreset'] ?? null;
        unset( $conf['wellPreset'] );

        $exploration_timing_preset = $conf['explorableTimingPreset'] ?? null;
        unset( $conf['explorableTimingPreset'] );

        if ($map_preset) {
            $conf['map'] = $conf['map'] ?? [];
            switch ($map_preset) {
                case 'small':
                    $tc = $this->conf->getTownConfigurationByType( TownClass::EASY )->getData();
                    $conf['map']['min'] = $tc['map']['min'] ?? 12;
                    $conf['map']['max'] = $tc['map']['max'] ?? 14;
                    $conf['ruins'] = $tc['ruins'] ?? 7;
                    $conf['explorable_ruins'] = $tc['explorable_ruins'] ?? 0;
                    break;
                case 'normal':
                    $tc = $this->conf->getTownConfigurationByType( $base )->getData();
                    $conf['map']['min'] = $tc['map']['min'] ?? 25;
                    $conf['map']['max'] = $tc['map']['max'] ?? 27;
                    $conf['ruins'] = $tc['ruins'] ?? 20;
                    $conf['explorable_ruins'] = $tc['explorable_ruins'] ?? 1;
                    break;
                case 'large':
                    $tc = $this->conf->getTownConfigurationByType( $base )->getData();
                    $conf['map']['min'] = 32;
                    $conf['map']['max'] = 35;
                    $conf['ruins'] = 30;
                    $conf['explorable_ruins'] = ($tc['explorable_ruins'] ?? 1) + 1;
                    break;
            }
        }

        if ($explorable_preset) {
            $conf['explorable_ruin_params'] = $conf['explorable_ruin_params'] ?? [];
            $conf['explorable_ruin_params']['space'] = $conf['explorable_ruin_params']['space'] ?? [];
            $conf['explorable_ruin_params']['room_config'] = $conf['explorable_ruin_params']['room_config'] ?? [];
            switch ($explorable_preset) {
                case 'classic':
                    $conf['explorable_ruin_params']['space']['floors'] = 1;
                    $conf['explorable_ruin_params']['room_config']['min'] = 10;
                    $conf['explorable_ruin_params']['room_config']['total'] = 10;
                    break;
                case 'normal':
                    unset($conf['explorable_ruin_params']);
                    break;
                case 'large':
                    $conf['explorable_ruin_params']['space']['floors'] = 3;
                    $conf['explorable_ruin_params']['room_config']['min'] = 6;
                    $conf['explorable_ruin_params']['room_config']['total'] = 20;
                    break;
            }

            $area = ($conf['explorable_ruin_params']['space']['x'] ?? 13) * ($conf['explorable_ruin_params']['space']['x'] ?? 13);
            if ($area !== 169) {
                $factor = max($area/169,0.1);
                $conf['explorable_ruin_params']['zombies'] = [
                    'initial' => (int)ceil(($conf['explorable_ruin_params']['zombies']['initial'] ?? 10) * $factor),
                    'daily' => (int)ceil(($conf['explorable_ruin_params']['zombies']['daily'] ?? 5) * $factor),
                ];
            }
        }

        if ($map_margin_preset) {
            $conf['map'] = $conf['map'] ?? [];
            switch ($map_margin_preset) {
                case 'normal':
                    $tc = $this->conf->getTownConfigurationByType( $base )->getData();
                    $conf['map']['margin'] = $tc['map']['margin'] ?? 0.25;
                    $margin_custom = null;
                    break;
                case 'close':
                    $conf['map']['margin'] = 0.33;
                    $margin_custom = null;
                    break;
                case 'central':
                    $conf['map']['margin'] = 0.50;
                    $margin_custom = null;
                    break;
                case '_custom':
                    if($margin_custom) {
                        $margin_custom['enabled'] = true;
                    }
            }
        }

        if($margin_custom && $margin_custom['enabled']) {
            $dirs = ['north', 'south', 'west', 'east'];
            // init the values to default if needed
            foreach($dirs as $dir_i => $dir) {
                $margin_custom[$dir] = $margin_custom[$dir] ?? 25;
            }
            // cap the margins to their opposed direction's margin and transform to %
            foreach($dirs as $dir_i => $dir) {
                $margin_custom[$dir] = min($margin_custom[$dir], 100 - $margin_custom[$dirs[$this->getOpposingDir($dir_i)]]) / 100;
            }

            $margin_custom['enabled'] = true;
            $conf['margin_custom'] = $margin_custom;
        }


        if ($well_preset) {
            $conf['well'] = $conf['well'] ?? [];
            switch ($well_preset) {
                case 'normal':
                    $tc = $this->conf->getTownConfigurationByType( TownClass::DEFAULT )->getData();
                    $conf['well']['min'] = $tc['well']['min'] ?? 90;
                    $conf['well']['max'] = $tc['well']['max'] ?? 180;
                    break;
                case 'low':
                    $tc = $this->conf->getTownConfigurationByType( TownClass::HARD )->getData();
                    $conf['well']['min'] = $tc['well']['min'] ?? 60;
                    $conf['well']['max'] = $tc['well']['max'] ?? 90;
                    break;
            }
        }

        if ($exploration_timing_preset) {
            $conf['times'] = $conf['times'] ?? [];
            $conf['times']['exploration'] = $conf['times']['exploration'] ?? [];
            switch ($exploration_timing_preset) {
                case 'low':
                    $conf['times']['exploration']['normal'] = '+3min';
                    $conf['times']['exploration']['collec'] = '+5min30sec';
                case 'normal':
                    $tc = $this->conf->getTownConfigurationByType( $base )->getData();
                    $conf['times']['exploration']['normal'] = $tc['times']['exploration']['normal'] ?? '+5min';
                    $conf['times']['exploration']['collec'] = $tc['times']['exploration']['collec'] ?? '+7min30sec';
                    break;
                case 'long':
                    $conf['times']['exploration']['normal'] = '+6min';
                    $conf['times']['exploration']['collec'] = '+8min';
                    break;
                case 'extra-long':
                    $conf['times']['exploration']['normal'] = '+8min';
                    $conf['times']['exploration']['collec'] = '+11min30sec';
                    break;
            }
        }

        return $conf;
    }

	private function getOpposingDir($dir_i): int {
		return $dir_i + (($dir_i % 2) === 1 ? -1 : 1);
	}

    private function building_prototype_is_selectable(?BuildingPrototype $prototype, bool $for_construction = false ): bool {
        return !(!$prototype || $prototype->getBlueprint() >= 5 || (!$for_construction && $prototype->getBlueprint() <= 0));
    }

    protected function fix_rules( array &$head, array &$rules ): void {
        // Apply town type settings
        $head['townType'] = $this->em->getRepository( TownClass::class )->find( $head['townType'] )?->getName() ?? 'custom';
        if ($head['townType'] !== 'custom') $head['townBase'] = $head['townType'];
        else $head['townBase'] = $this->em->getRepository( TownClass::class )->find( $head['townBase'] )?->getName() ?? TownClass::DEFAULT;
        if ($head['townBase'] === 'custom') $head['townBase'] = TownClass::DEFAULT;

        $lang = $head['townLang'] ?? 'multi';
        if ($lang !== 'multi' && !in_array( $lang, ['de','en','fr','es'] )) unset( $head['townLang'] );

        $lang_name = $head['townNameLang'] ?? $lang;
        if ($lang_name !== 'multi' && !in_array( $lang_name, ['de','en','fr','es'] )) unset( $head['townNameLang'] );

        // Make sure the event value is valid
        if (($head['event'] ?? 'auto') === 'auto') unset( $head['event'] );
        elseif ($head['event'] !== 'none' && !in_array( $head['event'], $this->conf->getAllEventNames() )) $head['event'] = 'none';

        // Remove setting objects for custom constructions / jobs if the option to use them is disabled
        if (!isset($head['customJobs'])) unset($rules['disabled_jobs']);
        if (!isset($head['customConstructions'])) {
            unset($rules['initial_buildings']);
            unset($rules['unlocked_buildings']);
            unset($rules['disabled_buildings']);
        }

        // Fix town schedule
        if ( !empty($head['townSchedule'] ) ) {
            try {
                $head['townSchedule'] = new \DateTime($head['townSchedule']);
                if ($head['townSchedule'] <= new \DateTime()) unset( $head['townSchedule'] );
            } catch (\Throwable) {
                unset( $head['townSchedule'] );
            }
        }

        // Town population
        if (!is_int( $head['townPop'] ?? 'x' )) unset( $head['townPop'] );
        if (isset($head['townPop'])) {
            $head['townPop'] = max(10, min($head['townPop'], 80));
            $rules['population']['min'] = $rules['population']['max'] = $head['townPop'];
        }

        // Town Seed
        if (!is_int( $head['townSeed'] ?? 'x' ) || (int)$head['townSeed'] <= 0) unset( $head['townSeed'] );

        // Ensure map min/max is between 10 and 35
        if (!is_int( $rules['map']['min'] ?? 'x' )) unset( $rules['map']['min'] );
        if (!is_int( $rules['map']['max'] ?? 'x' )) unset( $rules['map']['max'] );
        if ( ($rules['map']['min'] ?? 10) < 10 ) $rules['map']['min'] = 10; if ( ($rules['map']['max'] ?? 10) < 10 ) $rules['map']['max'] = 10;
        if ( ($rules['map']['min'] ?? 10) > 35 ) $rules['map']['min'] = 35; if ( ($rules['map']['max'] ?? 10) > 35 ) $rules['map']['max'] = 35;
        if ( ($rules['map']['min'] ?? 0) > ($rules['map']['max'] ?? 0) ) $rules['map']['min'] = $rules['map']['max'];

        // Ensure map margin is between 0.25 and 0.5
        if (!is_float( $rules['map']['margin'] ?? 'x' )) unset( $rules['map']['margin'] );
        if ( ($rules['map']['margin'] ?? 0.25) < 0.25 ) $rules['map']['margin'] = 0.25;
        if ( ($rules['map']['margin'] ?? 0.25) > 0.50 ) $rules['map']['margin'] = 0.50;

        // Ensure # of ruins / e-ruins is between 0-30 / 0-5
        if (!is_int( $rules['ruins'] ?? 'x' )) unset( $rules['ruins'] );
        if ( ($rules['ruins'] ?? 0) < 0 ) $rules['ruins'] = 0;
        if ( ($rules['ruins'] ?? 0) > 30 ) $rules['ruins'] = 30;
        if (!is_int( $rules['explorable_ruins'] ?? 'x' )) unset( $rules['explorable_ruins'] );
        if ( ($rules['explorable_ruins'] ?? 0) < 0 ) $rules['explorable_ruins'] = 0;
        if ( ($rules['explorable_ruins'] ?? 0) > 5 ) $rules['explorable_ruins'] = 5;

        // Ensure explorable ruin config is correct
        if (!is_int( $rules['explorable_ruin_params']['room_config']['total'] ?? 'x' )) unset( $rules['explorable_ruin_params']['room_config']['total'] );
        if (!is_int( $rules['explorable_ruin_params']['room_config']['min'] ?? 'x' )) unset( $rules['explorable_ruin_params']['room_config']['min'] );
        if (!is_int( $rules['explorable_ruin_params']['space']['floors'] ?? 'x' )) unset( $rules['explorable_ruin_params']['space']['floors'] );
        if (!is_int( $rules['explorable_ruin_params']['space']['x'] ?? 'x' )) unset( $rules['explorable_ruin_params']['space']['x'] );
        if (!is_int( $rules['explorable_ruin_params']['space']['y'] ?? 'x' )) unset( $rules['explorable_ruin_params']['space']['y'] );
        if ($rules['explorable_ruin_params']['space'] ?? null) {
            $rules['explorable_ruin_params']['space']['ox'] = (int)ceil(($rules['explorable_ruin_params']['space']['x'] ?? 13) / 2.0);
            if ( ($rules['explorable_ruin_params']['space']['x'] ?? 13) > 25 ) $rules['explorable_ruin_params']['space']['x'] = 25;
            if ( ($rules['explorable_ruin_params']['space']['y'] ?? 13) > 25 ) $rules['explorable_ruin_params']['space']['y'] = 25;
            if ( ($rules['explorable_ruin_params']['space']['x'] ?? 13) < 8 ) $rules['explorable_ruin_params']['space']['x'] = 8;
            if ( ($rules['explorable_ruin_params']['space']['y'] ?? 13) < 8 ) $rules['explorable_ruin_params']['space']['y'] = 8;
        }
        if (( $rules['explorable_ruin_params']['space']['floors'] ?? 2 ) > 5 || ( $rules['explorable_ruin_params']['space']['floors'] ?? 2 ) < 1) $rules['explorable_ruin_params']['space']['floors'] = 2;
        if ( ($rules['explorable_ruin_params']['space']['floors'] ?? 0) * ($rules['explorable_ruin_params']['room_config']['min'] ?? 0) > ( $rules['explorable_ruin_params']['room_config']['total'] ?? 0 ) ) {
            unset($rules['explorable_ruin_params']);
        };
        
        // Ensure well min/max is above 0
        if (!is_int( $rules['well']['min'] ?? 'x' )) unset( $rules['well']['min'] ); if (!is_int( $rules['well']['max'] ?? 'x' )) unset( $rules['well']['max'] );
        if ( ($rules['well']['min'] ?? 0) < 0 ) $rules['well']['min'] = 0; if ( ($rules['well']['max'] ?? 0) < 0 ) $rules['well']['max'] = 0;
        if ( ($rules['well']['min'] ?? 0) > ($rules['well']['max'] ?? 0) ) $rules['well']['min'] = $rules['well']['max'];

        // Ensure all jobs are valid, and no job is doubled
        if (isset( $rules['disabled_jobs'] ))
            $rules['disabled_jobs'] = array_filter( array_unique( $rules['disabled_jobs'] ), fn(string $job) => $job !== CitizenProfession::DEFAULT && $this->em->getRepository(CitizenProfession::class)->findOneBy(['name' => $job]) );

        // Ensure all disabled buildings are valid (exist), and no building is doubled
        if (isset( $rules['disabled_buildings'] ))
            $rules['disabled_buildings'] = array_filter( array_unique( $rules['disabled_buildings'] ), fn(string $building) => $this->em->getRepository(BuildingPrototype::class)->findOneBy(['name' => $building]) );

        // Ensure all unlocked buildings are valid (exist and are unlockable by a blueprint), and no building is doubled
        if (isset( $rules['unlocked_buildings'] ))
            $rules['unlocked_buildings'] = array_filter( array_unique( $rules['unlocked_buildings'] ), fn(string $building) => !in_array($building, $rules['disabled_buildings'] ?? []) && $this->building_prototype_is_selectable($this->em->getRepository(BuildingPrototype::class)->findOneBy(['name' => $building]) ) );

        // Ensure all initially constructed buildings are valid (exist and are either unlockable by a blueprint or unlocked by default), and no building is doubled
        if (isset( $rules['initial_buildings'] ))
            $rules['initial_buildings'] = array_filter( array_unique( $rules['initial_buildings'] ), fn(string $building) => !in_array($building, $rules['disabled_buildings'] ?? []) && $this->building_prototype_is_selectable($this->em->getRepository(BuildingPrototype::class)->findOneBy(['name' => $building]), true ) );
    }

    protected function scrub_config( array &$subject, array $reference ): void
    {

        if (empty($subject)) return;

        $ref_is_associative = !empty($reference) && array_keys($reference) !== range(0, count($reference) - 1);

        if (!$ref_is_associative) {
            $subject = array_values( $subject );
            $item_ref = array_reduce( $reference, fn( array $carry, $item ) => is_array( $item ) ? array_merge_recursive( $carry, $item ) : $carry, [] );

            // If the reference array does not contain objects, filter all object values from the subject
            if (empty($item_ref)) $subject = array_filter( $subject, fn($item) => !is_array($item) );
            else {
                // If the reference array contains objects, filter all non-object values from the subject
                // Then, scrub each element according to the item reference
                $subject = array_filter( $subject, fn($item) => is_array($item) );
                foreach ($subject as &$sub) $this->scrub_config($sub, $item_ref);
            }

        } else {

            $props = array_keys( $subject );

            foreach ( $props as $prop ) {
                // Remove all object keys not present in the reference array
                if (!array_key_exists($prop, $reference)) unset( $subject[$prop] );
                // Remove object keys where the object state mismatches between reference and subject
                elseif (is_array( $subject[$prop] ) !== is_array( $reference[$prop] )) unset( $subject[$prop] );
                // Recurse into sub-objects
                elseif (is_array( $subject[$prop] )) $this->scrub_config( $subject[$prop], $reference[$prop] );
            }
        }


    }

    public function __invoke(
        array &$header, array &$rules,
        ?array &$userSlots = null, ?User $creator = null
    ): bool
    {
        /** @var ?TownClass $primaryConf */
        $primaryConf = $this->em->getRepository( TownClass::class )->find( $header['townType'] ?? -1 );
        if (!$primaryConf) return false;

        /** @var ?TownClass $templateConf */
        $templateConf = $this->em->getRepository( TownClass::class )->find( $header['townBase'] ?? -1 );
        if (!$primaryConf->getHasPreset() && !$templateConf?->getHasPreset()) return false;

        $userSlots = array_filter($this->em->getRepository(User::class)->findBy(['id' => array_map(fn($a) => (int)$a, $header['reserve'] ?? [])]), function(User $u) {
            return $u->getEmail() !== 'crow' && $u->getEmail() !== $u->getUsername() && !str_ends_with($u->getName(), '@localhost');
        });

        if (count($userSlots) !== count($header['reserve'] ?? []))
            return false;

        $base = $primaryConf->getHasPreset() ? $primaryConf : $templateConf;
        $rules = $this->sanitize_incoming_config( $rules, $base );

        $template = $this->conf->getTownConfigurationByType( $base, !$primaryConf->getHasPreset() )->getData();
        $this->scrub_config( $rules, $template );
        $this->fix_rules( $header, $rules );
        $this->elevation_needed( $header, $rules, $creator?->getRightsElevation() ?? User::USER_LEVEL_CROW );

        $this->move_lists( $rules );

        return true;
    }
}