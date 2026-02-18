<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class RequirementTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assetRequirements()
    {
        return $this->hasMany(AssetRequirement::class);
    }
}

