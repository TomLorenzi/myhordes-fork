<?php

namespace App\Enum;

enum DomainBlacklistType: int {
    case EmailDomain        = 0;
    case EmailAddress       = 1;
    case EternalTwinID      = 2;

    public function convert(string $value): string {
        return match($this) {
            self::EmailDomain => $value,
            self::EmailAddress, self::EternalTwinID => hash('sha256', trim($value)),
        };
    }
}