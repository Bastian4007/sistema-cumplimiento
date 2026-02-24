<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\AssetRequirement;

class AssetRequirementController extends Controller
{
    public function show(Asset $asset, AssetRequirement $requirement)
    {
        // ✅ seguridad: evitar que abran requirements que no son del asset
        abort_unless($requirement->asset_id === $asset->id, 404);

        // ✅ seguridad multiempresa extra (defensa en profundidad)
        abort_unless($asset->company_id === auth()->user()->company_id, 403);

        // Cargar tasks + documentos (si ya tienes relación documents)
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
}
