<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetType;

class AssetTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Almacenamiento',
            'Comercialización',
            'EC',
            'ES',
            'Importación',
            'Plantas',
            'Transporte',
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