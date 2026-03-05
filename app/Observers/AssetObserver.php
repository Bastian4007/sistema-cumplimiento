<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\AssetObligation;
use App\Models\RequirementTemplate;

class AssetObserver
{
    public function created(Asset $asset): void
    {
        \Log::info('AssetObserver fired', [
            'asset_id' => $asset->id,
            'asset_type_id' => $asset->asset_type_id,
            'assetTypeRelation' => optional($asset->assetType)->name,
        ]);
        
        $typeName = mb_strtolower(optional($asset->assetType)->name ?? '');

        // tu UI muestra "Plantas", cubrimos ambos
        if (!in_array($typeName, ['planta', 'plantas'], true)) {
            return;
        }

        $base = [
            ['name' => 'Permiso CRE (ALTA)', 'req_type' => 'permiso'],
            ['name' => 'Licencia de construcción', 'req_type' => 'licencia'],
            ['name' => 'Licencia Uso de suelo', 'req_type' => 'licencia'],
        ];

        foreach ($base as $item) {
            $tpl = RequirementTemplate::firstOrCreate(
                ['company_id' => $asset->company_id, 'name' => $item['name']],
                ['description' => null]
            );

            // A) Carpeta (AssetRequirement)
            AssetRequirement::firstOrCreate(
                [
                    'company_id' => $asset->company_id,
                    'asset_id' => $asset->id,
                    'requirement_template_id' => $tpl->id,
                ],
                [
                    'type' => $item['req_type'],
                    'status' => 'pending',
                    'due_date' => now()->addYear()->toDateString(),
                ]
            );

            // B) Obligación (AssetObligation)
            AssetObligation::firstOrCreate(
                [
                    'company_id' => $asset->company_id,
                    'asset_id' => $asset->id,
                    'requirement_template_id' => $tpl->id,
                ],
                [
                    'issue_date' => now()->toDateString(),
                    'due_date' => now()->addYear()->toDateString(),
                    'status' => 'active', 
                ]
            );
        }
    }
}