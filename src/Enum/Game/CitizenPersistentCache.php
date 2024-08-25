<?php

namespace App\Enum\Game;

enum CitizenPersistentCache: string
{
    case Ghoul_Aggression = 'gh_ag';
    case Profession = 'pro';

    public function isAccumulative(): bool {
        return match ($this) {
            CitizenPersistentCache::Ghoul_Aggression => true,
            default => false
        };
    }

}
