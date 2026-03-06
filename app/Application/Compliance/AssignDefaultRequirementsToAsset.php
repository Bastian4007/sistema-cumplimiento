<?php

namespace App\Application\Compliance;

use App\Enums\RequirementStatus;
use App\Models\Asset;
use App\Models\AssetObligation;
use App\Models\AssetRequirement;
use App\Models\AssetTypeRequirementTemplate;
use Carbon\Carbon;

final class AssignDefaultRequirementsToAsset
{
    public function handle(Asset $asset): void
    {
        $rules = AssetTypeRequirementTemplate::query()
            ->where('company_id', $asset->company_id)
            ->where('asset_type_id', $asset->asset_type_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        // ✅ inicio (ancla)
        $start = $asset->compliance_start_date
            ? Carbon::parse($asset->compliance_start_date)->startOfDay()
            : now()->startOfDay();

        // ✅ vencimiento base (lo que eligieron al crear el activo)
        // si no existe, fallback a start (o hoy)
        $baseDue = $asset->compliance_due_date
            ? Carbon::parse($asset->compliance_due_date)->toDateString()
            : $start->toDateString();

        foreach ($rules as $rule) {

            // ✅ ya NO sumamos default_days
            $dueDate = $baseDue;

            if ($rule->applies_to_requirements) {
                AssetRequirement::firstOrCreate(
                    [
                        'company_id' => $asset->company_id,
                        'asset_id' => $asset->id,
                        'requirement_template_id' => $rule->requirement_template_id,
                    ],
                    [
                        'type' => $rule->requirement_type ?? 'permiso',
                        'status' => RequirementStatus::PENDING,
                        'due_date' => $dueDate,
                        'completed_at' => null,
                    ]
                );
            }

            if ($rule->applies_to_obligations) {
                AssetObligation::firstOrCreate(
                    [
                        'company_id' => $asset->company_id,
                        'asset_id' => $asset->id,
                        'requirement_template_id' => $rule->requirement_template_id,
                    ],
                    [
                        'issue_date' => $start->toDateString(),
                        'due_date' => $dueDate,
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}