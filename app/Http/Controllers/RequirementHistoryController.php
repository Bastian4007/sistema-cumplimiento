<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\Task;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class RequirementHistoryController extends Controller
{
    private function guard(Asset $asset, AssetRequirement $requirement): void
    {
        abort_unless($requirement->asset_id === $asset->id, 404);
        abort_unless($asset->company_id === auth()->user()->company_id, 403);
    }

    public function index(Asset $asset, AssetRequirement $requirement)
    {
        $this->guard($asset, $requirement);

        $logs = AuditLog::query()
            ->where('company_id', $asset->company_id)
            ->where('asset_id', $asset->id)
            ->where('requirement_id', $requirement->id)
            ->with('actor:id,name')
            ->latest('id')
            ->paginate(25);

        return view('requirements.history', compact(
            'asset',
            'requirement',
            'logs'
        ));
    }

    public function task(Asset $asset, AssetRequirement $requirement, Task $task)
    {
        $this->guard($asset, $requirement);
        abort_unless($task->asset_requirement_id === $requirement->id, 404);

        $logs = AuditLog::query()
            ->where('company_id', $asset->company_id)
            ->where('task_id', $task->id)
            ->with('actor:id,name')
            ->latest('id')
            ->paginate(25);

        return view('requirements.history', compact(
            'asset',
            'requirement',
            'task',
            'logs'
        ));
    }
}