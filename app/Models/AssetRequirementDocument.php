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
        'company_id',
        'asset_requirement_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
        'issued_at',
        'expires_at',
        'uploaded_at',
        'is_current',
        'status',
        'version_number',
        'replaced_by_document_id',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'uploaded_at' => 'datetime',
        'is_current' => 'boolean',
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

    public function replacedBy()
{
    return $this->belongsTo(
        self::class,
        'replaced_by_document_id'
    );
}
}