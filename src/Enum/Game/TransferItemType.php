<?php

namespace App\Enum\Game;

enum TransferItemType
{
    case Unknown;
    case Spawn;
    case Consume;
    case Rucksack;
    case Bank;
    case Home;
    case Steal;
    case Local;
    case Escort;
    case Tamer;
    case Impound;
    
    public function getValidTargets(): array {
        return match ($this) {
            self::Spawn => [ self::Rucksack, self::Bank, self::Home, self::Local, self::Tamer ],
            self::Rucksack => [ self::Bank, self::Local, self::Home, self::Consume, self::Steal, self::Tamer ],
            self::Tamer => [ self::Rucksack ],
            self::Bank,
            self::Home => [ self::Rucksack, self::Consume ],
            self::Steal => [ self::Rucksack, self::Home ],
            self::Local => [ self::Rucksack, self::Escort, self::Consume ],
            self::Escort => [ self::Local, self::Consume ],
            self::Impound => [ self::Tamer ],

            self::Unknown,
            self::Consume => [],
        };
    }

    public function checkTarget( self $target ): bool {
        return in_array($target, $this->getValidTargets());
    }

    public function isValidEssentialSource(): bool {
        return $this === self::Spawn;
    }

    public function isValidEssentialTarget(): bool {
        return $this === self::Consume;
    }

    public function isRucksack(): bool {
        return $this === self::Rucksack || $this === self::Escort;
    }
}


