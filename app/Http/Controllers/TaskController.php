<?php

namespace App\Http\Controllers;

use App\Models\AssetRequirement;
use App\Services\AuditLooger;

class TaskController extends Controller
{
    public function index(AssetRequirement $requirement)
    {
        // Seguridad multiempresa
        $requirement->loadMissing('company');

        abort_unless(auth()->user()->canAccessCompany($requirement->company), 403);

        $requirement->load([
            'asset',
            'template',
        ]);

        $tasks = $requirement->tasks()
            ->latest()
            ->withCount('documents')
            ->paginate(20);

        AuditLogger::log(
            'test.event',
            $task,
            context: [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'requirement_id' => $requirement->id,
                'task_id' => $task->id,
            ],
            meta: [
                'message' => 'Prueba de auditoría',
            ]
        );

        return view('tasks.index', compact('requirement', 'tasks'));
    }
}