<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class AssetObligation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'requirement_template_id',
        'issue_date',
        'due_date',
        'status'
    ];

    protected $dates = [
        'issue_date',
        'due_date'
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

    public function documents()
    {
        return $this->hasMany(ObligationDocument::class);
    }
}

