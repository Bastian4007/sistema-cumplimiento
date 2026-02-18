<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ObligationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_obligation_id',
        'file_path',
        'uploaded_by'
    ];

    public function obligation()
    {
        return $this->belongsTo(AssetObligation::class, 'asset_obligation_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
