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
    //</editor-fold>

    //</editor-fold>

    public function abstract(): bool
    {
        return match ($this) {
            self::Section_TownStartMeta,
            self::Section_Well,
            self::Section_Map,
            self::Section_Map_Beyond => true,

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
            self::MapCustomMarginEast => self::Section_Map_Beyond,

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

    public function default(): null|bool|int|float
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

            default => null,
        };
    }

    public function fallback(): array
    {
        return [];
    }
}