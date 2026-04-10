<?php

namespace App\Policies;

use App\Models\AssetRequirement;
use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\BlocksInactiveAssets;

class TaskPolicy
{
    use BlocksInactiveAssets;

    protected function canManageTasks(User $user): bool
    {
        return $user->isAdmin() || $user->isOperative();
    }

    public function create(User $user, AssetRequirement $requirement): bool
    {
        if (! $this->canManageTasks($user)) return false;
        if ($requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($requirement->asset)) return false;

        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if (! $this->canManageTasks($user)) return false;
        if ($task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($task->requirement->asset)) return false;

        return true;
    }

    public function complete(User $user, Task $task): bool
    {
        if (! $this->canManageTasks($user)) return false;
        if ($task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($task->requirement->asset)) return false;

        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $task->requirement->company_id === $user->company_id;
    }

    public function delete(User $user, Task $task): bool
    {
        if (! $this->canManageTasks($user)) return false;
        if ($task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($task->requirement->asset)) return false;

        return true;
    }
}