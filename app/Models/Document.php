<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    public const DOCUMENT_TYPES = [
        'Poderes',
        'Escrituras de terrenos',
        'Acta constitutiva',
        'Acta de asamblea',
        'Contratos',
        'Libros sociales',
        'Libros de accionistas',
        'Constancias del IMPI',
        'Títulos de concesión',
        'Registros de UMA',
        'Otros',
    ];

    protected $fillable = [
        'group_id',
        'company_id',
        'document_folder_id',
        'name',
        'document_type',
        'reference',
        'bodega',
        'responsible_name',
        'is_required',
        'is_active',
        'uploaded_by',
        'deleted_by',
        'permanently_delete_at',
    ];

    protected $casts = [
        'permanently_delete_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function folder()
    {
        return $this->belongsTo(DocumentFolder::class, 'document_folder_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)
            ->orderByDesc('version_number');
    }

    public function currentVersion()
    {
        return $this->hasOne(DocumentVersion::class)
            ->where('is_current', true)
            ->latestOfMany();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function authorizedUsers()
    {
        return $this->belongsToMany(User::class, 'document_authorized_users')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function hasFile(): bool
    {
        return $this->currentVersion()->exists();
    }

    public function isExpired(): bool
    {
        $version = $this->currentVersion;

        return $version?->valid_until && $version->valid_until->isPast();
    }

    public function isNearExpiration(int $days = 60): bool
    {
        $version = $this->currentVersion;

        return $version?->valid_until
            && $version->valid_until->lte(now()->addDays($days))
            && !$version->valid_until->isPast();
    }

    public function vigenciaStatus(): string
    {
        if (! $this->currentVersion?->valid_until) {
            return 'sin_vencimiento';
        }

        if ($this->isExpired()) {
            return 'vencido';
        }

        if ($this->isNearExpiration()) {
            return 'por_vencer';
        }

        return 'vigente';
    }
}