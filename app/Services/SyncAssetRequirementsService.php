<?php

namespace App\Services;

use App\Enums\RequirementStatus;
use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\RequirementTemplate;

class SyncAssetRequirementsService
{
    public function handle(Asset $asset, bool $removeObsolete = false): void
    {
        $templates = RequirementTemplate::query()
            ->where('asset_type_id', $asset->asset_type_id)
            ->orderBy('id')
            ->get();

        $validTemplateIds = $templates->pluck('id')->all();

        foreach ($templates as $template) {
            AssetRequirement::updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'requirement_template_id' => $template->id,
                ],
                [
                    'company_id' => $asset->company_id,
                    'status' => RequirementStatus::PENDING,
                    'due_date' => $this->resolveDueDate($asset),
                    'compliance_scope' => $template->compliance_scope ?? 'project',
                    'type' => 'initial',
                ]
            );
        }

        if ($removeObsolete) {
            $query = AssetRequirement::query()
                ->where('asset_id', $asset->id);

            if (empty($validTemplateIds)) {
                $query->delete();
            } else {
                $query->whereNotIn('requirement_template_id', $validTemplateIds)->delete();
            }
        }
    }

    private function resolveDueDate(Asset $asset): string
    {
        if ($asset->compliance_due_date) {
            return $asset->compliance_due_date instanceof \Carbon\CarbonInterface
                ? $asset->compliance_due_date->toDateString()
                : (string) $asset->compliance_due_date;
        }

        return now()->addYear()->toDateString();
    }
}