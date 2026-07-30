<?php

namespace App\Console\Commands;

use App\Models\AssetRequirement;
use App\Models\AssetType;
use App\Models\RequirementTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupLegacyEcEsRequirementTemplates extends Command
{
    protected $signature = 'compliance:cleanup-legacy-ec-es {--dry-run : Solo muestra lo que haría, sin guardar cambios}';

    protected $description = 'Elimina los requirement_templates de EC/ES del checklist anterior (sin priority/responsible_area) que ya no existen en el checklist nuevo, reasignando primero cualquier asset_requirement con documento oficial cargado. Debe correrse DESPUÉS de migrar y de sembrar ECRequirementTemplateSeeder/EsRequirementTemplateSeeder con los CSV nuevos.';

    /**
     * Documentos del checklist viejo que corresponden al mismo trámite en el
     * checklist nuevo, solo que redactado distinto (por eso el emparejamiento
     * por nombre exacto no los conectó). Se agregan aquí conforme se detecten.
     */
    private const KNOWN_RENAMES = [
        'Resolución de Evaluación de Impacto Social/ Evaluación de Manifestación de Impacto Social'
            => 'Resolutivo del Manifestación de Impacto Social en el Sector Energético, MISSE o Evaluación de Impacto Social, EVIS',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Iniciando limpieza de requirement_templates de EC/ES del checklist anterior...');
        if ($dryRun) {
            $this->warn('Modo dry-run activado. No se harán cambios en base de datos.');
        }

        $ecEsTypeIds = AssetType::whereIn('name', ['EC', 'ES'])->pluck('id');

        $orphans = RequirementTemplate::query()
            ->whereIn('asset_type_id', $ecEsTypeIds)
            ->whereNull('priority')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No hay templates huérfanos de EC/ES por limpiar.');
            return self::SUCCESS;
        }

        $this->line("Templates huérfanos detectados: {$orphans->count()}");

        $summary = [
            'evidence_reassigned' => 0,
            'asset_requirements_deleted' => 0,
            'templates_deleted' => 0,
        ];

        $runner = function () use ($orphans, $ecEsTypeIds, &$summary, $dryRun) {
            foreach (self::KNOWN_RENAMES as $oldName => $newName) {
                // El mismo nombre viejo puede existir tanto en EC como en ES
                // (documento compartido) — hay que resolver cada uno dentro de
                // su propio asset_type_id, no solo el primero que aparezca.
                $oldTemplatesByType = $orphans->where('name', $oldName)->keyBy('asset_type_id');

                foreach ($oldTemplatesByType as $assetTypeId => $oldTemplate) {
                    $newTemplate = RequirementTemplate::query()
                        ->where('asset_type_id', $assetTypeId)
                        ->where('name', $newName)
                        ->whereNotNull('priority')
                        ->first();

                    if (! $newTemplate) {
                        $this->warn("  No se encontró el template nuevo '{$newName}' (asset_type_id={$assetTypeId}) para reasignar evidencia de '{$oldName}'.");
                        continue;
                    }

                    $withEvidence = AssetRequirement::query()
                        ->where('requirement_template_id', $oldTemplate->id)
                        ->whereNotNull('current_document_id')
                        ->get();

                    foreach ($withEvidence as $requirement) {
                        $this->line("  Reasignando evidencia: asset_requirement ID {$requirement->id} (asset_id={$requirement->asset_id}) de template {$oldTemplate->id} -> {$newTemplate->id}");

                        $summary['evidence_reassigned']++;

                        if (! $dryRun) {
                            $requirement->update(['requirement_template_id' => $newTemplate->id]);
                        }
                    }
                }
            }

            $orphanIds = $orphans->pluck('id');

            $remainingCount = AssetRequirement::query()
                ->whereIn('requirement_template_id', $orphanIds)
                ->count();

            $this->line("  asset_requirements restantes a eliminar (sin evidencia): {$remainingCount}");
            $summary['asset_requirements_deleted'] = $remainingCount;

            if (! $dryRun) {
                AssetRequirement::query()->whereIn('requirement_template_id', $orphanIds)->delete();
            }

            $summary['templates_deleted'] = $orphanIds->count();

            if (! $dryRun) {
                RequirementTemplate::query()->whereIn('id', $orphanIds)->delete();
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        $this->newLine();
        $this->info('Resumen:');
        foreach ($summary as $key => $value) {
            $this->line(" - {$key}: {$value}");
        }

        $this->newLine();
        $this->info(
            $dryRun
                ? 'Dry-run completado. No se guardaron cambios.'
                : 'Limpieza completada correctamente.'
        );

        return self::SUCCESS;
    }
}
