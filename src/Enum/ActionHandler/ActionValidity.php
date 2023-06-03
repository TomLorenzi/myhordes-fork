<?php

namespace App\Enum\ActionHandler;

use App\Entity\Requirement;

enum ActionValidity: int {
    case None = 1;
    case Hidden = 2;
    case Crossed = 3;
    case Allow = 4;
    case Full = 5;

    public function merge( ActionValidity $b ): ActionValidity {
        return ActionValidity::from( min( $this->value, $b->value ) );
    }

    public function thatOrBelow( ActionValidity $a ): bool {
        return $this->value <= $a->value;
    }

    public function thatOrAbove( ActionValidity $a ): bool {
        return $this->value >= $a->value;
    }

    public static function fromRequirement( int $r ): ActionValidity {
        return match ($r) {
           Requirement::MessageOnFail => self::Allow,
           Requirement::CrossOnFail => self::Crossed,
           Requirement::HideOnFail => self::Hidden,
            default => throw new \Exception('Invalid requirement constant.')
        };
    }

}