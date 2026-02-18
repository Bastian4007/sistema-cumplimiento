<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\RequirementStatus;


class AssetRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'requirement_template_id',
        'type',
        'status',
        'due_date',
        'completed_at'
    ];

    protected $dates = [
        'due_date',
        'completed_at'
    ];

    protected $casts = [
        'status' => RequirementStatus::class,
        'due_date' => 'date',
    ];

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
}

