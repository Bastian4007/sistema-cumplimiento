<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        // Operativo y Solo Vista pueden listar
        return true;
    }

    public function view(User $user, Asset $asset): bool
    {
        // Solo puede ver assets de su empresa
        return $asset->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperative();
    }

    public function update(User $user, Asset $asset): bool
    {
        return ($user->isAdmin() || $user->isOperative())
            && $asset->company_id === $user->company_id;
    }

    public function delete(User $user, Asset $asset): bool
    {
        return ($user->isAdmin() || $user->isOperative())
            && $asset->company_id === $user->company_id;
    }
}
