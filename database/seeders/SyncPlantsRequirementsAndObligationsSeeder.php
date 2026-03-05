<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Application\Compliance\AssignDefaultRequirementsToAsset;

class SyncPlantsRequirementsAndObligationsSeeder extends Seeder
{
    public function run(): void
    {
        $plantTypeId = \App\Models\AssetType::where('name', 'Plantas')->value('id');

        $assets = Asset::where('asset_type_id', $plantTypeId)->get();

        $service = app(AssignDefaultRequirementsToAsset::class);

        foreach ($assets as $asset) {
            $service->handle($asset);
        }
    }
}