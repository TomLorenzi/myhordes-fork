<?php

namespace App\Enum;

use DateTimeImmutable;

enum StatisticType: int {
    // 1 - 9: Player Stats
    case PlayerStatsDaily   = 1;
    case PlayerStatsWeekly  = 2;
    case PlayerStatsMonthly = 3;
    case PlayerStatsYearly  = 4;

    public function isPlayerStat(): bool {
        return $this->value > 0 && $this->value < 10;
    }

    /**
     * @return StatisticType[]
     */
    public static function playerStatTypes(): array {
        return array_filter( self::cases(), fn(self $s) => $s->isPlayerStat() );
    }

    public function cutoffDate(DateTimeImmutable $dateTime = new DateTimeImmutable('now')): ?DateTimeImmutable {
        return match ($this) {
            self::PlayerStatsDaily   => $dateTime->modify('-24hour'),
            self::PlayerStatsWeekly  => $dateTime->modify('-7day'),
            self::PlayerStatsMonthly => $dateTime->modify('-30day'),
            self::PlayerStatsYearly  => $dateTime->modify('-1year'),
            default => null
        };
    }
}