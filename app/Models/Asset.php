<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_type_id',
        'name',
        'code',
        'location',
        'responsible_user_id'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function type()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
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
}

