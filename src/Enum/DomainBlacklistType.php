<?php

namespace App\Enum;

enum DomainBlacklistType: int {
    case EmailDomain        = 0;
    case EmailAddress       = 1;
    case EternalTwinID      = 2;
    case IPAddress          = 3;

    public function convert(string $value): string {
        return match($this) {
            self::EmailDomain => $value,
            self::EmailAddress, self::EternalTwinID, self::IPAddress => hash('sha256', trim($value)),
        };
    }
}