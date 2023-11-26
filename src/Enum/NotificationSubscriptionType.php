<?php

namespace App\Enum;

enum NotificationSubscriptionType: int
{
    case Invalid = 0;
    case WebPush = 1;
}
