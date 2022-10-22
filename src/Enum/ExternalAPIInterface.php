<?php

namespace App\Enum;

enum ExternalAPIInterface: int {
    case GENERIC = 0;
    case JSONv1  = 1001;
    case XMLv1   = 2001;
    case XMLv2   = 2002;
}