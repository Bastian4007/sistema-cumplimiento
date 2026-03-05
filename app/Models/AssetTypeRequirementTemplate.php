<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTypeRequirementTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'asset_type_id',
        'requirement_template_id',
        'applies_to_requirements',
        'applies_to_obligations',
        'requirement_type',
        'default_days',
        'sort_order',
    ];

    protected $casts = [
        'applies_to_requirements' => 'bool',
        'applies_to_obligations' => 'bool',
    ];
}