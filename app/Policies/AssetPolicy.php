<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->canAccessCompany($asset->company);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperative();
    }

    public function update(User $user, Asset $asset): bool
    {
        return ($user->isAdmin() || $user->isOperative())
            && $user->canAccessCompany($asset->company);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return ($user->isAdmin() || $user->isOperative())
            && $user->canAccessCompany($asset->company);
    }

    public function activate(User $user, Asset $asset): bool
    {
        return ($user->isAdmin() || $user->isOperative())
            && $user->canAccessCompany($asset->company);
    }

    public function deactivate(User $user, Asset $asset): bool
    {
        return ($user->isAdmin() || $user->isOperative())
            && $user->canAccessCompany($asset->company);
    }
}