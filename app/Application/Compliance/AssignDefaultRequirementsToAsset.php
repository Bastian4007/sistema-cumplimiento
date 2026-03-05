<?php

namespace App\Application\Compliance;

use App\Enums\RequirementStatus;
use App\Models\Asset;
use App\Models\AssetObligation;
use App\Models\AssetRequirement;
use App\Models\AssetTypeRequirementTemplate;

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

        // Si no hay configuración, no precargamos nada (o puedes meter fallback demo)
        if ($rules->isEmpty()) {
            return;
        }

        foreach ($rules as $rule) {
            $dueDate = now()->addDays((int) $rule->default_days)->toDateString();

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
                        'issue_date' => now()->toDateString(),
                        'due_date' => $dueDate,
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}