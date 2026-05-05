<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version_number',
        'is_current',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'issued_at',
        'valid_from',
        'valid_until',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'issued_at' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function isNearExpiration(int $days = 60): bool
    {
        return $this->valid_until
            && $this->valid_until->lte(now()->addDays($days))
            && !$this->valid_until->isPast();
    }
}