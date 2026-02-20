<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AssetType;

class AssetTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'ATQ',
            'Pipa',
            'Plantas',
            'Muelles',
            'ES',
            'EC',
            'Terminal',
            'Transporte',
            'Documentos',
        ];

        foreach ($types as $name) {
            AssetType::updateOrCreate(
                ['name' => $name],
                [
                    'priority_level' => 1,
                    'warning_days' => 60,
                    'danger_days' => 30,
                ]
            );
        }
    }
}
