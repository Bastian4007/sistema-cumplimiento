<?php

namespace App\Http\Controllers;

use App\Models\AssetRequirement;

class TaskController extends Controller
{
    public function index(AssetRequirement $requirement)
    {
        // Seguridad multiempresa
        abort_unless($requirement->company_id === auth()->user()->company_id, 403);

        $requirement->load([
            'asset',
            'template',
        ]);

        $tasks = $requirement->tasks()
            ->latest()
            ->withCount('documents')
            ->paginate(20);

        return view('tasks.index', compact('requirement', 'tasks'));
    }
}