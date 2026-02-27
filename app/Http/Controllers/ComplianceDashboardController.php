<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Application\Compliance\ComplianceDashboardService;
use App\Models\Asset;
use App\Models\Task;
use App\Models\AssetRequirement;
use App\Enums\RequirementStatus;

class ComplianceDashboardController extends Controller
{
    public function index(Request $request, ComplianceDashboardService $service)
    {
        $companyId = (int) $request->user()->company_id;

        // KPIs (vienen del service)
        $metrics = $service->metricsForCompany($companyId);

        // Stats para las cards (lo que tu Blade espera como $stats)
        $stats = [
            'assets'   => Asset::query()->where('company_id', $companyId)->count(),
            'tasks'    => Task::query()
                ->whereHas('requirement', fn ($q) => $q->where('company_id', $companyId))
                ->count(),
            'due_soon' => $metrics['kpis']['warning'] + $metrics['kpis']['danger'], // próximas incluye warning + danger
            'overdue'  => $metrics['kpis']['expired'],
        ];

        // Próximos a vencer (lista)
        $upcoming = AssetRequirement::query()
            ->whereHas('asset', fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('due_date')
            ->whereNotIn('status', [
                RequirementStatus::COMPLETED,
                RequirementStatus::CANCELLED,
            ])
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays(30)->toDateString()) // ventana “próximo”
            ->with(['asset.assetType', 'template'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // Críticos (danger + expired)
        $critical = AssetRequirement::query()
            ->whereHas('asset', fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('due_date')
            ->whereNotIn('status', [
                RequirementStatus::COMPLETED,
                RequirementStatus::CANCELLED,
            ])
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
            ->with(['asset.assetType', 'template'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return view('dashboard', compact('metrics', 'stats', 'upcoming', 'critical'));
    }
}