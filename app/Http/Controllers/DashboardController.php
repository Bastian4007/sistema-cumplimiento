<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $cacheKey = "dashboard:company:{$companyId}";

        [$stats, $dueSoon] = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($companyId) {
            $today = Carbon::today();
            $soonLimit = $today->copy()->addDays(30);

            $assetsCount = Asset::query()
                ->where('company_id', $companyId)
                ->count();

            $pendingTasksCount = Task::query()
                ->where('status', 'pending')
                ->whereHas('requirement', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->count();

            $dueSoonCount = AssetRequirement::query()
                ->where('company_id', $companyId)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $soonLimit])
                ->count();

            $overdueCount = AssetRequirement::query()
                ->where('company_id', $companyId)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->count();

            $dueSoon = AssetRequirement::query()
                ->with([
                    'asset:id,name,code',
                    'template:id,name',
                ])
                ->where('company_id', $companyId)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $soonLimit])
                ->orderBy('due_date')
                ->limit(10)
                ->get()
                ->map(function ($requirement) {
                    return [
                        'title' => $requirement->template?->name ?? $requirement->type,
                        'asset_name' => $requirement->asset?->name,
                        'asset_code' => $requirement->asset?->code,
                        'due_date' => optional($requirement->due_date)->format('Y-m-d'),
                        'risk' => $requirement->risk_level ?? null,
                    ];
                });

            $stats = [
                'assets' => $assetsCount,
                'tasks' => $pendingTasksCount,
                'due_soon' => $dueSoonCount,
                'overdue' => $overdueCount,
            ];

            return [$stats, $dueSoon];
        });

        return view('dashboard', compact('stats', 'dueSoon'));
    }
}