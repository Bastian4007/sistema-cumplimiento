<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\AssetRequirement;
use App\Models\Task;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\AuditLogger;

class RequirementTaskController extends Controller
{
    private function guardRequirement(AssetRequirement $requirement): void
    {
        abort_unless($requirement->company_id === auth()->user()->company_id, 403);
        abort_unless(auth()->user()->isOperative(), 403);

        $requirement->loadMissing('asset');
        abort_unless($requirement->asset && $requirement->asset->status === 'active', 403);
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

        $task = Task::create([
            'asset_requirement_id' => $requirement->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'requires_document' => TRUE,
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        AuditLogger::log(
            'task.created',
            $task,
            [
                'company_id' => $requirement->company_id,
                'asset_id' => $requirement->asset_id,
                'requirement_id' => $requirement->id,
                'task_id' => $task->id,
            ],
            [
                'title' => $task->title,
                'due_date' => $task->due_date,
                'requires_document' => $task->requires_document,
            ]
        );

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

        $before = $task->only(['title', 'description', 'due_date', 'requires_document']);

        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'requires_document' => TRUE,
        ]);

        AuditLogger::log(
            'task.updated',
            $task,
            [
                'company_id' => $requirement->company_id,
                'asset_id' => $requirement->asset_id,
                'requirement_id' => $requirement->id,
                'task_id' => $task->id,
            ],
            [
                'before' => $before,
                'after' => $task->only(['title', 'description', 'due_date', 'requires_document']),
            ]
        );

        return redirect()
            ->route('assets.requirements.show', [$requirement->asset_id, $requirement->id])
            ->with('status', 'Tarea actualizada.');
    }

    public function destroy(AssetRequirement $requirement, Task $task): RedirectResponse
    {
        $this->guardRequirement($requirement);
        $this->guardTaskScope($requirement, $task);

        AuditLogger::log(
            'task.deleted',
            $task,
            [
                'company_id' => $requirement->company_id,
                'asset_id' => $requirement->asset_id,
                'requirement_id' => $requirement->id,
                'task_id' => $task->id,
            ],
            [
                'title' => $task->title,
            ]
        );

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

        $before = $task->only(['status', 'completed_at', 'completed_by']);

        $task->update([
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'task.completed',
            $task,
            [
                'company_id' => $requirement->company_id,
                'asset_id' => $requirement->asset_id,
                'requirement_id' => $requirement->id,
                'task_id' => $task->id,
            ],
            [
                'title' => $task->title,
                'documents_count' => $task->documents()->count(),
                'before' => $before,
                'after' => $task->only(['status', 'completed_at', 'completed_by']),
            ]
        );

        return back()->with('status', 'Tarea completada.');
    }

    public function reopen(AssetRequirement $requirement, Task $task): RedirectResponse
    {
        $this->guardRequirement($requirement);
        $this->guardTaskScope($requirement, $task);

        $before = $task->only(['status', 'completed_at', 'completed_by']);

        $task->update([
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        AuditLogger::log(
            'task.reopened',
            $task,
            [
                'company_id' => $requirement->company_id,
                'asset_id' => $requirement->asset_id,
                'requirement_id' => $requirement->id,
                'task_id' => $task->id,
            ],
            [
                'title' => $task->title,
                'before' => $before,
                'after' => $task->only(['status', 'completed_at', 'completed_by']),
            ]
        );

        return back()->with('status', 'Tarea reabierta.');
    }

    public function checkout(Request $request, Asset $asset, AssetRequirement $requirement)
    {
        // Validación de rol
        abort_unless(auth()->user()->isOperative(), 403);

        // Validar pertenencia
        if ((int) $requirement->asset_id !== (int) $asset->id) abort(404);

        $hasOfficialDoc = $requirement->documents()->exists(); 

        abort_unless($hasOfficialDoc, 422);

        $data = $request->validate([
            'return_at' => ['required', 'date', 'after:now'],
        ]);

        $titleReq = $requirement->template?->name ?? $requirement->type;
        $title = "Check in - {$titleReq}";

        $alreadyOpen = Task::where('asset_requirement_id', $requirement->id)
            ->where('title', $title)
            ->whereNull('completed_at')
            ->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()))
            ->exists();

        if ($alreadyOpen) {
            return back()->with('error', 'Ya tienes un Check in pendiente para este requerimiento.');
        }

        $checkin = Task::create([
            'asset_requirement_id' => $requirement->id,
            'title' => $title,
            'description' => "Check in del requerimiento: {$titleReq}",
            'status' => TaskStatus::PENDING,
            'due_date' => $data['return_at'],
            'requires_document' => false,
        ]);

        $checkin->users()->syncWithoutDetaching([auth()->id()]);

        return back()->with('success', 'Check out registrado. Se creó una tarea de Check in.');
    }
}