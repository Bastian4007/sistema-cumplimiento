<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(
        string $action,
        Model $auditable,
        array $context,
        array $meta = []
    ): AuditLog {
        return AuditLog::create([
            'company_id'     => $context['company_id'],
            'actor_id'       => Auth::id(),
            'action'         => $action,
            'auditable_type' => $auditable::class,
            'auditable_id'   => $auditable->getKey(),
            'asset_id'       => $context['asset_id'] ?? null,
            'requirement_id' => $context['requirement_id'] ?? null,
            'task_id'        => $context['task_id'] ?? null,
            'meta'           => $meta ?: null,
        ]);
    }
}