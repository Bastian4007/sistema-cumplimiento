<?php

namespace App\Policies;

use App\Models\AssetRequirement;
use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\BlocksInactiveAssets;

class TaskPolicy
{
    use BlocksInactiveAssets;

    // Crear task bajo un requirement (pasamos requirement como 2do arg)
    public function create(User $user, AssetRequirement $requirement): bool
    {
        if ($requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($task->requirement->asset)) return false;

        return true;
    }

    public function complete(User $user, Task $task): bool
    {
        if ($task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($task->requirement->asset)) return false;

        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $task->requirement->company_id === $user->company_id;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($task->requirement->asset)) return false;

        return true;
    }
}