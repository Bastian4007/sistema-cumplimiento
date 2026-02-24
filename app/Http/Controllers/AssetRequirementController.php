<?php

namespace App\Http\Controllers;

use App\Enums\RequirementStatus;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreAssetRequirementRequest;
use App\Http\Requests\UpdateAssetRequirementRequest;
use App\Models\Asset;
use App\Models\AssetRequirement;
use Illuminate\Support\Facades\DB;

class AssetRequirementController extends Controller
{
    public function show(Asset $asset, AssetRequirement $requirement)
    {
        abort_unless($requirement->asset_id === $asset->id, 404);
        abort_unless($asset->company_id === auth()->user()->company_id, 403);

        $requirement->load([
            'template',
            'tasks' => function ($q) {
                $q->withCount('documents')
                    ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END")
                    ->orderBy('due_date')
                    ->latest();
            },
        ])->loadCount([
            'tasks as tasks_total',
            'tasks as tasks_done' => fn ($t) => $t->whereNotNull('completed_at'),
        ]);

        return view('requirements.show', compact('asset', 'requirement'));
    }

    /**
     * Crear requirement manual (MVP) + crear una task principal obligatoria.
     */
    public function store(StoreAssetRequirementRequest $request, Asset $asset)
    {
        abort_unless($asset->company_id === auth()->user()->company_id, 403);

        $data = $request->validated();

        $data['company_id'] = auth()->user()->company_id;
        $data['asset_id'] = $asset->id;
        $data['completed_at'] = null;

        // ✅ status del Requirement (NO TaskStatus)
        $data['status'] = RequirementStatus::PENDING;

        $requirement = DB::transaction(function () use ($data) {

            $requirement = AssetRequirement::create($data);

            // ✅ Crear task principal obligatoria (todas requieren documento)
            $requirement->tasks()->create([
                'title' => 'Subir documento principal (permiso/obligación)',
                'description' => 'Adjunta el documento oficial requerido para esta obligación.',
                'status' => TaskStatus::PENDING,
                'due_date' => $requirement->due_date,
                'requires_document' => true, // 🔒 SIEMPRE true
                'completed_at' => null,
            ]);

            return $requirement;
        });

        return redirect()
            ->route('assets.requirements.show', [$asset, $requirement])
            ->with('success', 'Requirement creado.');
    }

    public function update(UpdateAssetRequirementRequest $request, Asset $asset, AssetRequirement $requirement)
    {
        abort_unless($requirement->asset_id === $asset->id, 404);
        abort_unless($asset->company_id === auth()->user()->company_id, 403);

        $beforeStatus = $requirement->status;

        $data = $request->validated();
        unset($data['company_id'], $data['asset_id']);

        DB::transaction(function () use ($requirement, $data, $beforeStatus) {

            $requirement->update($data);

            $afterStatus = $requirement->fresh()->status;

            $completedNow =
                $beforeStatus !== RequirementStatus::COMPLETED &&
                $afterStatus === RequirementStatus::COMPLETED;

            if ($completedNow) {
                if (is_null($requirement->completed_at)) {
                    $requirement->update(['completed_at' => now()]);
                }

                $this->renewIfRecurrent($requirement);
            }
        });

        return back()->with('success', 'Requirement actualizado.');
    }

    private function renewIfRecurrent(AssetRequirement $requirement): ?AssetRequirement
    {
        if (!$requirement->isRecurrent()) {
            return null;
        }

        $nextDue = $requirement->nextDueDate();
        if (!$nextDue) {
            return null;
        }

        return AssetRequirement::create([
            'company_id' => $requirement->company_id,
            'asset_id' => $requirement->asset_id,
            'requirement_template_id' => $requirement->requirement_template_id,
            'type' => $requirement->type,

            'status' => RequirementStatus::PENDING,
            'due_date' => $nextDue->toDateString(),
            'completed_at' => null,

            'recurrence_interval' => $requirement->recurrence_interval,
            'recurrence_unit' => $requirement->recurrence_unit,
            'recurrence_anchor' => $requirement->recurrence_anchor,
        ]);
    }

    public function complete(Asset $asset, AssetRequirement $requirement)
    {
        abort_unless($requirement->asset_id === $asset->id, 404);
        abort_unless($asset->company_id === auth()->user()->company_id, 403);

        if (!$requirement->canBeCompleted()) {
            return back()->withErrors([
                'complete' => 'No puedes completar esta carpeta: faltan tareas por completar o evidencias requeridas.',
            ]);
        }

        DB::transaction(function () use ($requirement) {

            if ($requirement->status === RequirementStatus::COMPLETED) {
                return;
            }

            $requirement->update([
                'status' => RequirementStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            $this->renewIfRecurrent($requirement);
        });

        return back()->with('success', 'Requirement completado.');
    }
}