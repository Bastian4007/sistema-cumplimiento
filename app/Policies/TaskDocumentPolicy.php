<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskDocument;
use App\Models\User;
use App\Policies\Concerns\BlocksInactiveAssets;

class TaskDocumentPolicy
{
    use BlocksInactiveAssets;

    protected function canManageDocuments(User $user): bool
    {
        return $user->isAdmin() || $user->isOperative();
    }

    public function create(User $user, Task $task): bool
    {
        if (! $this->canManageDocuments($user)) return false;
        if ($task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($task->requirement->asset)) return false;

        return true;
    }

    public function view(User $user, TaskDocument $doc): bool
    {
        if ($doc->task->requirement->company_id !== $user->company_id) return false;

        return true;
    }

    public function download(User $user, TaskDocument $doc): bool
    {
        return $this->view($user, $doc);
    }

    public function delete(User $user, TaskDocument $doc): bool
    {
        if (! $this->canManageDocuments($user)) return false;
        if ($doc->task->requirement->company_id !== $user->company_id) return false;
        if ($this->denyIfAssetInactive($doc->task->requirement->asset)) return false;

        return true;
    }
}