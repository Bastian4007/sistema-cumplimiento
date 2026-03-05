<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlantObligationsSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Permiso CRE (ALTA)',
            'Licencia de construcción',
            'Licencia Uso de suelo',
        ];

        foreach (\App\Models\Company::all() as $company) {
            foreach ($names as $name) {
                \App\Models\RequirementTemplate::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $name],
                    ['description' => null]
                );
            }
        }
    }
}
