<?php

namespace App\Enum;

use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Enum\Configuration\TownSetting;
use App\Structures\TownConf;

enum ZoneActivityMarkerType: int {
    case ShamanRain = 1;
    case RuinDig = 2;
    case ScoutVisit = 3;
    case ScoutMarker = 4;

    case DoorAutoClosed = 10;
    case DoorAutoCloseReported = 11;

    case ExplorableBlueprintU = 101;
    case ExplorableBlueprintR = 102;
    case ExplorableBlueprintE = 103;
    case ScavengeBlueprintBag = 104;

    public function daily(): bool {
        return match($this) {
            self::ScoutVisit, self::ExplorableBlueprintU, self::ExplorableBlueprintR, self::ExplorableBlueprintE, self::ScoutMarker => false,
            default => true
        };
    }

    public static function daylies(): array {
        return array_filter( self::cases(), fn(ZoneActivityMarkerType $t) => $t->daily() );
    }

    public static function scavengedItemIncurs(string|ItemPrototype|Item $item): ?ZoneActivityMarkerType {
        if (is_a( $item, Item::class )) $item = $item->getPrototype()->getName();
        elseif (is_a( $item, ItemPrototype::class )) $item = $item->getName();

        return match ($item) {
            'hbplan_u_#00', 'bbplan_u_#00', 'mbplan_u_#00' => self::ExplorableBlueprintU,
            'hbplan_r_#00', 'bbplan_r_#00', 'mbplan_r_#00' => self::ExplorableBlueprintR,
            'hbplan_e_#00', 'bbplan_e_#00', 'mbplan_e_#00' => self::ExplorableBlueprintE,
            'bplan_drop_#00' => self::ScavengeBlueprintBag,
            default => null
        };
    }

    public function configuredLimit( TownConf $conf ): int {
        return match($this) {
            ZoneActivityMarkerType::ExplorableBlueprintU => $conf->get( TownSetting::ERuinBPUnusual ),
            ZoneActivityMarkerType::ExplorableBlueprintR => $conf->get( TownSetting::ERuinBPRare ),
            ZoneActivityMarkerType::ExplorableBlueprintE => $conf->get( TownSetting::ERuinBPEpic ),
            ZoneActivityMarkerType::ScavengeBlueprintBag => $conf->get( TownConf::CONF_SCAVENGING_PLAN_LIMIT_B, -1 ),
            default => -1
        };
    }
}