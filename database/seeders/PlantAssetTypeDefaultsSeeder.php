<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\RequirementTemplate;
use App\Models\AssetTypeRequirementTemplate;

class PlantsDefaultsProSeeder extends Seeder
{
    public function run(): void
    {
        $plantType = AssetType::where('name', 'Plantas')->firstOrFail();

        $base = [
            ['name' => 'Permiso CRE (ALTA)', 'requirement_type' => 'permiso', 'days' => 365, 'sort' => 10],
            ['name' => 'Licencia de construcción', 'requirement_type' => 'licencia', 'days' => 365, 'sort' => 20],
            ['name' => 'Licencia Uso de suelo', 'requirement_type' => 'licencia', 'days' => 365, 'sort' => 30],
        ];

        foreach (Company::all() as $company) {
            foreach ($base as $row) {
                $tpl = RequirementTemplate::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $row['name']],
                    ['description' => null]
                );

                AssetTypeRequirementTemplate::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'asset_type_id' => $plantType->id,
                        'requirement_template_id' => $tpl->id,
                    ],
                    [
                        'applies_to_requirements' => true,
                        'applies_to_obligations' => true,
                        'requirement_type' => $row['requirement_type'],
                        'default_days' => $row['days'],
                        'sort_order' => $row['sort'],
                    ]
                );
            }
        }
    }
}