<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\User;
use App\Policies\Concerns\BlocksInactiveAssets;

class AssetRequirementPolicy
{
    use BlocksInactiveAssets;

    // Crear requirement sobre un Asset (pasamos Asset como 2do arg)
    public function create(User $user, Asset $asset): bool
    {
        if ($asset->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($asset)) return false;

        return true;
    }

    // Acciones sobre un requirement existente
    public function update(User $user, AssetRequirement $requirement): bool
    {
        if ($requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }

    public function complete(User $user, AssetRequirement $requirement): bool
    {
        if ($requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }

    public function view(User $user, AssetRequirement $requirement): bool
    {
        return $requirement->company_id === $user->company_id;
    }

    public function delete(User $user, AssetRequirement $requirement): bool
    {
        if ($requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }
}