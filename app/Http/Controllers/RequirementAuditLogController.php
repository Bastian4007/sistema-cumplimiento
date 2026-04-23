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
        abort_unless((int) $requirement->asset_id === (int) $asset->id, 404);

        $asset->loadMissing('company');

        $user = $request->user();

        abort_unless(
            ($user->isAdmin() || $user->isOperative())
            && $user->canAccessCompany($asset->company),
            403
        );

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