<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En proceso',
            self::COMPLETED => 'Completado ',
            self::EXPIRED => 'Expirado',
            self::CANCELLED => 'Cancelado',
        };
    }
}
