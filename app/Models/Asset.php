<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class Asset extends Model
{
    use HasFactory;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_id',
        'asset_type_id',
        'name',
        'code',
        'location',
        'responsible_user_id',
        'status',
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function requirements()
    {
        return $this->hasMany(AssetRequirement::class);
    }

    public function obligations()
    {
        return $this->hasMany(AssetObligation::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($q)
    {
        return $q->where('status', self::STATUS_INACTIVE);
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }
}

