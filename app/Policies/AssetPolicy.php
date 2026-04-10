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
        return $user->isAdmin()
            || $asset->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperative();
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->isAdmin()
            || ($user->isOperative() && $asset->company_id === $user->company_id);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->isAdmin()
            || ($user->isOperative() && $asset->company_id === $user->company_id);
    }

    public function activate(User $user, Asset $asset): bool
    {
        return $user->isAdmin()
            || ($user->isOperative() && $asset->company_id === $user->company_id);
    }

    public function deactivate(User $user, Asset $asset): bool
    {
        return $user->isAdmin()
            || ($user->isOperative() && $asset->company_id === $user->company_id);
    }
}