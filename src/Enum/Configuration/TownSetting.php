<?php

namespace App\Enum\Configuration;

enum TownSetting: string implements Configuration
{


    //<editor-fold desc="Core Settings">
    case AllowLocalConfiguration = 'allow_local_conf';
    //</editor-fold>

    //<editor-fold desc="Town Start Meta Settings">
    case Section_TownStartMeta = '--section--/TownStartMeta';
    case CancelTownAfterDaysWithoutFilling = 'open_town_limit';
    case DoNotCancelAfterCitizensReached = 'open_town_grace';
    case SpawnStrangerAfterUnfilledDays = 'stranger_day_limit';
    case SpawnStrangerAfterCitizenCount = 'stranger_citizen_limit';
    case LockDoorUntilTownIsFull = 'lock_door_until_full';
    case PopulationMin = 'population.min';
    case PopulationMax = 'population.max';
    //</editor-fold>

    //<editor-fold desc="Town Well Settings">
    case Section_Well = '--section--/Well';
    case DefaultWellFillMin = 'well.min';
    case DefaultWellFillMax = 'well.max';
    //</editor-fold>

    //<editor-fold desc="Town Map Settings">
    case Section_Map = '--section--/Map';

    //<editor-fold desc="Town Map Beyond Settings">
    case Section_Map_Beyond = '--section--/Map/Beyond';
    case MapSizeMin = 'map.min';
    case MapSizeMax = 'map.max';
    case MapSafeMargin = 'map.margin';

    const MapUseCustomMargin = 'margin_custom.enabled';
    const MapCustomMarginNorth = 'margin_custom.north';
    const MapCustomMarginSouth = 'margin_custom.south';
    const MapCustomMarginWest = 'margin_custom.west';
    const MapCustomMarginEast = 'margin_custom.east';

    const MapZoneDropCountInitializer = 'zone_items.min';
    const MapZoneDropCountThreshold = 'zone_items.max';
    const MapZoneDropCountRefresh = 'zone_items.refresh_max';
    //</editor-fold>

    //</editor-fold>

    //<editor-fold desc="Town E-Ruin Settings">
    case Section_Explorable = '--section--/Explorable';

    case ERuinItemFillrate = 'explorable_ruin_params.item_fillrate';
    case ERuinMaxDistanceFromTown = 'explorable_ruin_params.max_distance';

    //<editor-fold desc="Town E-Ruin Room Settings">
    case Section_Explorable_Rooms = '--section--/Explorable/Rooms';
    case ERuinRoomLockDistance = 'explorable_ruin_params.room_config.lock';
    case ERuinRoomDistance = 'explorable_ruin_params.room_config.distance';
    case ERuinRoomSpacing = 'explorable_ruin_params.room_config.spacing';
    case ERuinRoomCountTotal = 'explorable_ruin_params.room_config.total';
    case ERuinRoomCountMinPerFloor = 'explorable_ruin_params.room_config.min';
    //</editor-fold>

    //<editor-fold desc="Town E-Ruin Space Settings">
    case Section_Explorable_Space = '--section--/Explorable/Space';
    case ERuinSpaceFloors = 'explorable_ruin_params.space.floors';
    case ERuinSpaceMaxSizeX = 'explorable_ruin_params.space.x';
    case ERuinSpaceMaxSizeY = 'explorable_ruin_params.space.y';
    case ERuinSpaceOffsetX = 'explorable_ruin_params.space.ox';
    //</editor-fold>

    //<editor-fold desc="Town E-Ruin Blueprint Settings">
    case Section_Explorable_BP = '--section--/Explorable/BP';
    case ERuinBPUnusual = 'explorable_ruin_params.plan_limits.unusual';
    case ERuinBPRare = 'explorable_ruin_params.plan_limits.rare';
    case ERuinBPEpic = 'explorable_ruin_params.plan_limits.epic';
    //</editor-fold>

    //<editor-fold desc="Town E-Ruin Zombie Settings">
    case Section_Explorable_Zombies = '--section--/Explorable/Zombies';
    case ERuinZombiesInitial = 'explorable_ruin_params.zombies.initial';
    case ERuinZombiesDaily = 'explorable_ruin_params.plan_limits.daily';
    //</editor-fold>

    //</editor-fold>

    //<editor-fold desc="Town Timing Settings">
    case Section_Timing = '--section--/Timing';

    //<editor-fold desc="Town Timing for Digging Settings">
    case Section_Timing_Digging = '--section--/Explorable/Digging';
    case TimingDiggingDefault = 'times.digging.normal';
    case TimingDiggingCollector = 'times.digging.collec';
    //</editor-fold>

    //<editor-fold desc="Town Timing for Exploration Settings">
    case Section_Timing_Exploration = '--section--/Explorable/Exploration';
    case TimingExplorationDefault = 'times.exploration.normal';
    case TimingExplorationCollector = 'times.exploration.collec';
    //</editor-fold>

    //</editor-fold>

    //<editor-fold desc="Town Reward Settings">
    case Section_Rewards = '--section--/Rewards';

    //<editor-fold desc="Town Timing for Digging Settings">
    case Section_Rewards_Pictos = '--section--/Rewards/Pictos';

    case PictoClassicCullMode = 'features.picto_classic_cull_mode';
    //</editor-fold>


    //</editor-fold>

    public function abstract(): bool
    {
        return match ($this) {
            self::Section_TownStartMeta,
            self::Section_Well,
            self::Section_Map,
            self::Section_Map_Beyond,
            self::Section_Explorable,
            self::Section_Explorable_Rooms,
            self::Section_Explorable_Space,
            self::Section_Explorable_BP,
            self::Section_Explorable_Zombies,
            self::Section_Timing,
            self::Section_Timing_Digging,
            self::Section_Timing_Exploration,
            self::Section_Rewards,
            self::Section_Rewards_Pictos,
                => true,

            default => false
        };
    }

    public function parent(): ?TownSetting {
        return match ($this) {
            self::CancelTownAfterDaysWithoutFilling,
            self::DoNotCancelAfterCitizensReached,
            self::SpawnStrangerAfterUnfilledDays,
            self::SpawnStrangerAfterCitizenCount,
            self::LockDoorUntilTownIsFull,
            self::PopulationMin,
            self::PopulationMax => self::Section_TownStartMeta,

            self::DefaultWellFillMin,
            self::DefaultWellFillMax => self::Section_Well,

            self::Section_Map_Beyond => self::Section_Map,
            self::MapSizeMin,
            self::MapSizeMax,
            self::MapSafeMargin,
            self::MapUseCustomMargin,
            self::MapCustomMarginNorth,
            self::MapCustomMarginSouth,
            self::MapCustomMarginWest,
            self::MapCustomMarginEast,
            self::MapZoneDropCountInitializer,
            self::MapZoneDropCountThreshold,
            self::MapZoneDropCountRefresh => self::Section_Map_Beyond,

            self::ERuinItemFillrate,
            self::ERuinMaxDistanceFromTown,
            self::Section_Explorable_Rooms,
            self::Section_Explorable_Space,
            self::Section_Explorable_BP,
            self::Section_Explorable_Zombies => self::Section_Explorable,

            self::ERuinRoomLockDistance,
            self::ERuinRoomDistance,
            self::ERuinRoomSpacing,
            self::ERuinRoomCountTotal,
            self::ERuinRoomCountMinPerFloor => self::Section_Explorable_Rooms,

            self::ERuinSpaceFloors,
            self::ERuinSpaceMaxSizeX,
            self::ERuinSpaceMaxSizeY,
            self::ERuinSpaceOffsetX => self::Section_Explorable_Space,

            self::ERuinBPUnusual,
            self::ERuinBPRare,
            self::ERuinBPEpic => self::Section_Explorable_BP,

            self::ERuinZombiesInitial,
            self::ERuinZombiesDaily => self::Section_Explorable_Zombies,

            self::Section_Timing_Digging,
            self::Section_Timing_Exploration => self::Section_Timing,

            self::TimingDiggingDefault,
            self::TimingDiggingCollector => self::Section_Timing_Digging,

            self::TimingExplorationDefault,
            self::TimingExplorationCollector => self::Section_Timing_Exploration,

            self::Section_Rewards_Pictos => self::Section_Rewards,
            self::PictoClassicCullMode => self::Section_Rewards_Pictos,

            default => null
        };
    }

    public function children(): array
    {
        return array_filter(self::cases(), fn(self $setting) => $setting->parent() === $this);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function key(): string {
        return $this->value;
    }

    public function default(): null|bool|int|float|string
    {
        /** @noinspection PhpDuplicateMatchArmBodyInspection */
        return match ($this) {
            self::AllowLocalConfiguration               => false,
            self::CancelTownAfterDaysWithoutFilling     => -1,
            self::DoNotCancelAfterCitizensReached       => 40,
            self::SpawnStrangerAfterUnfilledDays        => -1,
            self::SpawnStrangerAfterCitizenCount        =>  0,
            self::LockDoorUntilTownIsFull               =>  false,
            self::PopulationMin                         =>  0,
            self::PopulationMax                         =>  0,

            self::DefaultWellFillMin => 0,
            self::DefaultWellFillMax => 0,

            self::MapSizeMin            => 0,
            self::MapSizeMax            => 0,
            self::MapSafeMargin         => 0.25,
            self::MapUseCustomMargin    => false,
            self::MapCustomMarginNorth  => 0,
            self::MapCustomMarginSouth  => 0,
            self::MapCustomMarginWest   => 0,
            self::MapCustomMarginEast   => 0,

            self::MapZoneDropCountInitializer => 5,
            self::MapZoneDropCountThreshold   => 10,
            self::MapZoneDropCountRefresh     => 5,

            self::ERuinItemFillrate => 7,
            self::ERuinMaxDistanceFromTown => 10,

            self::ERuinRoomLockDistance     => 10,
            self::ERuinRoomDistance         => 5,
            self::ERuinRoomSpacing          => 4,
            self::ERuinRoomCountTotal       => 15,
            self::ERuinRoomCountMinPerFloor => 10,

            self::ERuinSpaceFloors    => 2,
            self::ERuinSpaceMaxSizeX  => 13,
            self::ERuinSpaceMaxSizeY  => 13,
            self::ERuinSpaceOffsetX   => 7,

            self::ERuinBPUnusual,
            self::ERuinBPRare,
            self::ERuinBPEpic => -1,

            self::ERuinZombiesInitial => 10,
            self::ERuinZombiesDaily   => 5,

            self::TimingDiggingDefault => '+2hour',
            self::TimingDiggingCollector => '+1hour30min',

            self::TimingExplorationDefault => '+5min',
            self::TimingExplorationCollector => '+7min30sec',

            self::PictoClassicCullMode => true,

            default => null,
        };
    }

    public function fallback(): array
    {
        return [];
    }
}