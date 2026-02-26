<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\Task;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Stats base
        $assetsCount = Asset::query()
            ->where('company_id', $companyId)
            ->count();

        $pendingTasksCount = Task::query()
            ->whereHas('requirement', fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending') // si usas enum, cambia a TaskStatus::PENDING->value
            ->count();

        $today = Carbon::today();
        $soonLimit = Carbon::today()->addDays(30);

        $dueSoonCount = AssetRequirement::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'completed') // o RequirementStatus::COMPLETED->value
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $soonLimit])
            ->count();

        $overdueCount = AssetRequirement::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->count();

        // Lista "Próximos a vencer"
        $dueSoon = AssetRequirement::query()
            ->with(['asset:id,name,code,asset_type_id', 'template:id,name'])
            ->where('company_id', $companyId)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $soonLimit])
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                $risk = method_exists($r, 'risk_level') ? $r->risk_level : null;

                return [
                    'title' => $r->template?->name ?? $r->type,
                    'asset_name' => $r->asset?->name,
                    'asset_code' => $r->asset?->code,
                    'due_date' => optional($r->due_date)->format('Y-m-d'),
                    'risk' => $risk,
                ];
            });

        $stats = [
            'assets' => $assetsCount,
            'tasks' => $pendingTasksCount,
            'due_soon' => $dueSoonCount,
            'overdue' => $overdueCount,
        ];

        return view('dashboard', compact('stats', 'dueSoon'));
    }
}