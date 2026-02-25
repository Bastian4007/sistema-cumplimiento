<?php

namespace App\Policies\Concerns;

use App\Models\Asset;

trait BlocksInactiveAssets
{
    protected function denyIfAssetInactive(?Asset $asset): bool
    {
        // true => DENEGAR
        return !$asset || $asset->status !== 'active';
    }
}