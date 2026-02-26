<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class RequirementAuditLogController extends Controller
{
    public function index(Request $request, Asset $asset, AssetRequirement $requirement)
    {
        // Jerarquía
        abort_unless($requirement->asset_id === $asset->id, 404);

        // Multiempresa + rol
        abort_unless($asset->company_id === auth()->user()->company_id, 403);
        abort_unless(auth()->user()->isOperative(), 403);

        $logs = AuditLog::query()
            ->where('company_id', $asset->company_id)
            ->where('asset_id', $asset->id)
            ->where('requirement_id', $requirement->id)
            ->with('actor:id,name,email')
            ->latest('id')
            ->paginate(25);

        return response()->json($logs);
    }
}