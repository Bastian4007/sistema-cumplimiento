<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\RequirementStatus;
use Carbon\Carbon;

class AssetRequirement extends Model
{
    use HasFactory;
    private const WARNING_DAYS = 60;
    private const DANGER_DAYS = 30;

    protected $fillable = [
        'company_id',
        'asset_id',
        'requirement_template_id',
        'type',
        'status',
        'due_date',
        'completed_at'
    ];

    protected $casts = [
        'status' => RequirementStatus::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::retrieved(function ($requirement) {
            $requirement->refreshExpirationStatus();
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function template()
    {
        return $this->belongsTo(RequirementTemplate::class, 'requirement_template_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function refreshExpirationStatus(): void
    {
        if (!$this->due_date) {
            return;
        }

        if (
            $this->due_date->lt(now()) &&
            !in_array($this->status, [
                RequirementStatus::COMPLETED,
                RequirementStatus::CANCELLED,
                RequirementStatus::EXPIRED
            ])
        ) {
            $this->updateQuietly([
                'status' => RequirementStatus::EXPIRED
            ]);
        }
    }

    public function getRiskLevelAttribute(): string
    {
        if (!$this->due_date) {
            return 'normal';
        }

        if ($this->status === RequirementStatus::EXPIRED) {
            return 'expired';
        }

        $today = Carbon::today();
        $daysLeft = $today->diffInDays($this->due_date, false);

        if ($daysLeft < 0) {
            return 'expired';
        }

        if ($daysLeft <= self::DANGER_DAYS) {
            return 'danger';
        }

        if ($daysLeft <= self::WARNING_DAYS) {
            return 'warning';
        }

        return 'normal';
    }

    public function scopeExpired($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [
                RequirementStatus::COMPLETED,
                RequirementStatus::CANCELLED
            ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', RequirementStatus::PENDING);
    }

    public function scopeDueSoon($query, int $days = self::WARNING_DAYS)
    {
        return $query->whereBetween('due_date', [
            now(),
            now()->addDays($days)
        ])->whereNotIn('status', [
            RequirementStatus::COMPLETED,
            RequirementStatus::CANCELLED
        ]);
    }

    public function scopeCritical($query)
    {
        return $query->whereBetween('due_date', [
            now(),
            now()->addDays(self::DANGER_DAYS)
        ])->whereNotIn('status', [
            RequirementStatus::COMPLETED,
            RequirementStatus::CANCELLED
        ]);
    }

    public function scopeForCompany($query, $company)
    {
        $companyId = $company instanceof \App\Models\Company ? $company->id : $company;
        return $query->where('company_id', $companyId);
    }

}
