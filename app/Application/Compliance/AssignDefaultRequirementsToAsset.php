<?php

namespace App\Application\Compliance;

use App\Enums\RequirementStatus;
use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\RequirementTemplate;

final class AssignDefaultRequirementsToAsset
{
    public function handle(Asset $asset): void
    {
        // ✅ Defaults demo (puedes cambiarlos luego)
        $defaults = [
            [
                'name' => 'Permiso ambiental anual',
                'description' => 'Cumplimiento anual ambiental (demo).',
                'type' => 'permiso',
                'days' => 365,
            ],
            [
                'name' => 'Licencia de operación',
                'description' => 'Renovación/validación de licencia (demo).',
                'type' => 'licencia',
                'days' => 180,
            ],
            [
                'name' => 'Bitácora de mantenimiento',
                'description' => 'Registro periódico de mantenimiento (demo).',
                'type' => 'mantenimiento',
                'days' => 30,
            ],
        ];

        foreach ($defaults as $d) {
            // 1) Asegurar template (si no existe, se crea)
            $tpl = RequirementTemplate::firstOrCreate(
                [
                    'company_id' => $asset->company_id,
                    'name' => $d['name'],
                ],
                [
                    'description' => $d['description'],
                ]
            );

            // 2) Crear carpeta del activo (si no existe ya)
            AssetRequirement::firstOrCreate(
                [
                    'company_id' => $asset->company_id,
                    'asset_id' => $asset->id,
                    'requirement_template_id' => $tpl->id,
                ],
                [
                    'type' => $d['type'],
                    'status' => RequirementStatus::PENDING,
                    'due_date' => now()->addDays($d['days'])->toDateString(),
                    'completed_at' => null,
                ]
            );
        }
    }
}