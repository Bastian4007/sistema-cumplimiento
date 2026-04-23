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

        $cacheKey = $user->hasGroupScope()
            ? "dashboard:group:{$user->group_id}"
            : "dashboard:company:{$user->company_id}";

        [$stats, $dueSoon] = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($user) {
            $today = Carbon::today();
            $soonLimit = $today->copy()->addDays(30);

            $assetsCount = Asset::query()
                ->when($user->hasGroupScope(), function ($query) use ($user) {
                    $query->whereHas('company', function ($subQuery) use ($user) {
                        $subQuery->where('group_id', $user->group_id);
                    });
                }, function ($query) use ($user) {
                    $query->where('company_id', $user->company_id);
                })
                ->count();

            $pendingTasksCount = Task::query()
                ->where('status', 'pending')
                ->whereHas('requirement', function ($query) use ($user) {
                    $query->when($user->hasGroupScope(), function ($subQuery) use ($user) {
                        $subQuery->whereHas('company', function ($companyQuery) use ($user) {
                            $companyQuery->where('group_id', $user->group_id);
                        });
                    }, function ($subQuery) use ($user) {
                        $subQuery->where('company_id', $user->company_id);
                    });
                })
                ->count();

            $dueSoonCount = AssetRequirement::query()
                ->when($user->hasGroupScope(), function ($query) use ($user) {
                    $query->whereHas('company', function ($subQuery) use ($user) {
                        $subQuery->where('group_id', $user->group_id);
                    });
                }, function ($query) use ($user) {
                    $query->where('company_id', $user->company_id);
                })
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $soonLimit])
                ->count();

            $overdueCount = AssetRequirement::query()
                ->when($user->hasGroupScope(), function ($query) use ($user) {
                    $query->whereHas('company', function ($subQuery) use ($user) {
                        $subQuery->where('group_id', $user->group_id);
                    });
                }, function ($query) use ($user) {
                    $query->where('company_id', $user->company_id);
                })
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->count();

            $dueSoon = AssetRequirement::query()
                ->with([
                    'asset:id,name,code',
                    'template:id,name',
                    'company:id,name,group_id',
                ])
                ->when($user->hasGroupScope(), function ($query) use ($user) {
                    $query->whereHas('company', function ($subQuery) use ($user) {
                        $subQuery->where('group_id', $user->group_id);
                    });
                }, function ($query) use ($user) {
                    $query->where('company_id', $user->company_id);
                })
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $soonLimit])
                ->orderBy('due_date')
                ->limit(10)
                ->get()
                ->map(function ($requirement) use ($user) {
                    return [
                        'title' => $requirement->template?->name ?? $requirement->type,
                        'asset_name' => $requirement->asset?->name,
                        'asset_code' => $requirement->asset?->code,
                        'company_name' => $user->hasGroupScope() ? $requirement->company?->name : null,
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