<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
