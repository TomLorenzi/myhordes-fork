<?php

namespace App\Enum;

enum ExternalAPIError {
   case UserKeyNotFound;
   case UserKeyInvalid;
   case AppKeyNotFound;
   case AppKeyInvalid;
   case HordeAttacking;
   case RateLimitReached;
}