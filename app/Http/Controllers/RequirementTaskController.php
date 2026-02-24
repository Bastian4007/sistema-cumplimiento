<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\AssetRequirement;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class RequirementTaskController extends Controller
{
    private function guardRequirement(AssetRequirement $requirement): void
    {
        abort_unless($requirement->company_id === auth()->user()->company_id, 403);
        abort_unless(auth()->user()->isOperative(), 403);
    }

    private function guardTaskScope(AssetRequirement $requirement, Task $task): void
    {
        abort_unless($task->asset_requirement_id === $requirement->id, 404);
    }

    public function create(AssetRequirement $requirement)
    {
        $this->guardRequirement($requirement);

        return view('tasks.create', compact('requirement'));
    }

    public function store(StoreTaskRequest $request, AssetRequirement $requirement): RedirectResponse
    {
        $this->guardRequirement($requirement);

        Task::create([
            'asset_requirement_id' => $requirement->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'requires_document' => TRUE,

            // Opción 2: el sistema define status
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
        ]);

        return redirect()
            ->route('assets.requirements.show', [$requirement->asset_id, $requirement->id])
            ->with('status', 'Tarea creada.');
    }

    public function edit(AssetRequirement $requirement, Task $task)
    {
        $this->guardRequirement($requirement);
        $this->guardTaskScope($requirement, $task);

        return view('tasks.edit', compact('requirement', 'task'));
    }

    public function update(UpdateTaskRequest $request, AssetRequirement $requirement, Task $task): RedirectResponse
    {
        $this->guardRequirement($requirement);
        $this->guardTaskScope($requirement, $task);

        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'requires_document' => TRUE,
        ]);

        return redirect()
            ->route('assets.requirements.show', [$requirement->asset_id, $requirement->id])
            ->with('status', 'Tarea actualizada.');
    }

    public function destroy(AssetRequirement $requirement, Task $task): RedirectResponse
    {
        $this->guardRequirement($requirement);
        $this->guardTaskScope($requirement, $task);

        $task->delete();

        return redirect()
            ->route('assets.requirements.show', [$requirement->asset_id, $requirement->id])
            ->with('status', 'Tarea eliminada.');
    }

    public function complete(AssetRequirement $requirement, Task $task): RedirectResponse
    {
        $this->guardRequirement($requirement);
        $this->guardTaskScope($requirement, $task);

        if ($task->documents()->count() === 0) {
            return back()->withErrors([
                'task' => 'Debes subir al menos una evidencia para completar esta tarea.',
            ]);
        }

        // (Más adelante) aquí validaremos evidencias si requires_document = true
        $task->update([
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        return back()->with('status', 'Tarea completada.');
    }

    public function reopen(AssetRequirement $requirement, Task $task): RedirectResponse
    {
        $this->guardRequirement($requirement);
        $this->guardTaskScope($requirement, $task);

        $task->update([
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
        ]);

        return back()->with('status', 'Tarea reabierta.');
    }
}