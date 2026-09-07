<?php

namespace App\Enums;

enum InvestmentRequestStatus: string
{
    case PENDING_APPROVAL = 'pending_approval';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_APPROVAL => 'Pendiente de aprobación',
        };
    }
}
