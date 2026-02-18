<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
