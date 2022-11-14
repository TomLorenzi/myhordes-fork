<?php

namespace App\Enum;

enum SemaphoreScope: string {

    case Global = 'global';
    case User   = 'user';
    case Town   = 'town';
    case None   = 'none';

    public function order(): int {
        return match ($this) {
            self::None      => 0,
            self::Global    => 1,
            self::User      => 50,
            self::Town      => 60,
        };
    }

}