<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Application\Compliance\ComplianceDashboardService;
use App\Models\AssetRequirement;
use App\Enums\RequirementStatus;

class ComplianceDashboardController extends Controller
{
    public function index(Request $request, ComplianceDashboardService $service)
    {
        $companyId = $request->user()->company_id;

        // KPIs + breakdowns (ya lo tienes implementado)
        $metrics = $service->metricsForCompany($companyId);

        // Próximos a vencer (ordenados por fecha)
        $upcoming = AssetRequirement::query()
            ->where('company_id', $companyId)
            ->whereNotNull('due_date')
            ->whereNotIn('status', [
                RequirementStatus::COMPLETED,
                RequirementStatus::CANCELLED,
            ])
            ->with(['asset.assetType', 'template'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // Críticos: danger + expired (usamos risk_level dinámico)
        $critical = AssetRequirement::query()
            ->where('company_id', $companyId)
            ->whereNotNull('due_date')
            ->whereNotIn('status', [
                RequirementStatus::COMPLETED,
                RequirementStatus::CANCELLED,
            ])
            ->with(['asset.assetType', 'template'])
            ->get()
            ->filter(fn ($r) => in_array($r->risk_level, ['expired', 'danger']))
            ->take(10);

        return view('dashboard', compact('metrics', 'upcoming', 'critical'));
    }
}