<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\User;
use App\Policies\Concerns\BlocksInactiveAssets;

class AssetRequirementPolicy
{
    use BlocksInactiveAssets;

    public function create(User $user, Asset $asset): bool
    {
        if (! $user->canAccessCompany($asset->company)) return false;
        if ($this->denyIfAssetInactive($asset)) return false;

        return true;
    }

    public function update(User $user, AssetRequirement $requirement): bool
    {
        if (! $user->canAccessCompany($requirement->company)) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }

    public function complete(User $user, AssetRequirement $requirement): bool
    {
        if (! $user->canAccessCompany($requirement->company)) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }

    public function view(User $user, AssetRequirement $requirement): bool
    {
        return $user->canAccessCompany($requirement->company);
    }

    public function delete(User $user, AssetRequirement $requirement): bool
    {
        if (! $user->canAccessCompany($requirement->company)) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }
}