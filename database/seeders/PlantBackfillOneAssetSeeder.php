<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\AssetObligation;
use App\Models\RequirementTemplate;

class PlantBackfillOneAssetSeeder extends Seeder
{
    public function run(): void
    {
        $assetId = 11; // <-- por ahora fijo para validar
        $asset = Asset::findOrFail($assetId);

        $base = [
            ['name' => 'Permiso CRE (ALTA)', 'req_type' => 'permiso'],
            ['name' => 'Licencia de construcción', 'req_type' => 'licencia'],
            ['name' => 'Licencia Uso de suelo', 'req_type' => 'licencia'],
        ];

        foreach ($base as $item) {
            $tpl = RequirementTemplate::where('company_id', $asset->company_id)
                ->where('name', $item['name'])
                ->firstOrFail();

            // 1) Requirement (para la UI actual)
            AssetRequirement::firstOrCreate(
                [
                    'company_id' => $asset->company_id,
                    'asset_id' => $asset->id,
                    'requirement_template_id' => $tpl->id,
                ],
                [
                    'type' => $item['req_type'],
                    'status' => 'pending',
                    'due_date' => now()->addYear()->toDateString(), // por ahora default
                ]
            );

            // 2) Obligation (módulo nuevo)
            AssetObligation::firstOrCreate(
                [
                    'company_id' => $asset->company_id,
                    'asset_id' => $asset->id,
                    'requirement_template_id' => $tpl->id,
                ],
                [
                    'issue_date' => now()->toDateString(),
                    'due_date' => now()->addYear()->toDateString(),
                    'status' => 'pending',
                ]
            );
        }
    }
}