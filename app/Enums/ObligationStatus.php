<?php

namespace App\Enums;

enum ObligationStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
}
