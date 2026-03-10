<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AssetRequirement;
use App\Models\User;
use App\Models\Company;

class AssetRequirementDocument extends Model
{
    protected $fillable = [
        'asset_requirement_id',
        'company_id',
        'file_path',
        'original_name',
        'uploaded_by',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(AssetRequirement::class, 'asset_requirement_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}