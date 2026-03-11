<?php

namespace App\Observers;

use App\Models\Asset;

class AssetObserver
{
    public function created(Asset $asset): void
    {
        \Log::info('AssetObserver fired', [
            'asset_id' => $asset->id,
            'asset_type_id' => $asset->asset_type_id,
        ]);

        app(\App\Application\Compliance\AssignDefaultRequirementsToAsset::class)
            ->handle($asset);
    }
}