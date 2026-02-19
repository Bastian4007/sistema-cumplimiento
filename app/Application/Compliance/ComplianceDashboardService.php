<?php

namespace App\Application\Compliance;

use App\Enums\RequirementStatus;
use App\Models\AssetRequirement;
use App\Models\Company;
use App\Models\User;

final class ComplianceDashboardService
{
    public function metricsForCompany(Company|int $company): array
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        // 🔹 KPIs
        $base = AssetRequirement::query()->forCompany($companyId);

        $total = (clone $base)->count();
        $expired = (clone $base)->expired()->count();
        $danger = (clone $base)->critical()->count();
        $dueSoon = (clone $base)->dueSoon()->count();
        $warning = max(0, $dueSoon - $danger);

        // 🔹 BREAKDOWN POR ASSET TYPE (ANTES DEL RETURN)
        $byAssetType = AssetRequirement::query()
            ->forCompany($companyId)
            ->with(['asset.assetType'])
            ->get()
            ->groupBy(fn($r) => $r->asset?->assetType?->name ?? 'Sin tipo')
            ->map(function ($items, $typeName) {
                return [
                    'asset_type' => $typeName,
                    'total' => $items->count(),
                    'expired' => $items->where('risk_level', 'expired')->count(),
                    'danger' => $items->where('risk_level', 'danger')->count(),
                    'warning' => $items->where('risk_level', 'warning')->count(),
                ];
            })
            ->values()
            ->all();

        // 🔹 RETURN AL FINAL
        return [
            'kpis' => [
                'total' => $total,
                'expired' => $expired,
                'danger' => $danger,
                'warning' => $warning,
            ],
            'breakdowns' => [
                'by_asset_type' => $byAssetType,
            ],
        ];
    }



    public function metricsForUser(User $user): array
    {
        return $this->metricsForCompany($user->company);
    }

    private function mapItem($r): array
    {
        return [
            'id' => $r->id,
            'status' => $r->status->value,
            'due_date' => optional($r->due_date)->toDateString(),
            'risk_level' => $r->risk_level,

            'asset' => [
                'id' => $r->asset?->id,
                'name' => $r->asset?->name,
                'asset_type' => [
                    'id' => $r->asset?->assetType?->id,
                    'name' => $r->asset?->assetType?->name,
                ],
            ],

            'template' => [
                'id' => $r->template?->id,
                'name' => $r->template?->name,
            ],
        ];
    }
}
