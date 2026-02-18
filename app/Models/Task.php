<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\TaskStatus;


class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_requirement_id',
        'title',
        'description',
        'status',
        'due_date',
        'completed_at',
        'requires_document'
    ];

    protected $dates = [
        'due_date',
        'completed_at'
    ];

    protected $casts = [
        'status' => TaskStatus::class,
    ];

    public function requirement()
    {
        return $this->belongsTo(AssetRequirement::class, 'asset_requirement_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function documents()
    {
        return $this->hasMany(TaskDocument::class);
    }
}

