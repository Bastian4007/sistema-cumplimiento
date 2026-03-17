<?php

namespace App\Enums;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En proceso',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
        };
    }
}