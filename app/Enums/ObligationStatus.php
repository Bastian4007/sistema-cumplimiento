<?php

namespace App\Enums;

enum ObligationStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::EXPIRED => 'Expirado',
            self::SUSPENDED => 'Suspendido',
            self::CANCELLED => 'Cancelado',
        };
    }
}
