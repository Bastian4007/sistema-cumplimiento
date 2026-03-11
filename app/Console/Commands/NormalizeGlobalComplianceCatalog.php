<?php

namespace App\Console\Commands;

use App\Models\AssetObligation;
use App\Models\AssetRequirement;
use App\Models\AssetTypeRequirementTemplate;
use App\Models\RequirementTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeGlobalComplianceCatalog extends Command
{
    protected $signature = 'compliance:normalize-global-catalog {--dry-run : Solo muestra lo que haría, sin guardar cambios}';

    protected $description = 'Consolida requirement templates duplicados por nombre y remapea referencias para preparar el catálogo global de cumplimiento.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Iniciando normalización del catálogo global de cumplimiento...');
        if ($dryRun) {
            $this->warn('Modo dry-run activado. No se harán cambios en base de datos.');
        }

        $groups = RequirementTemplate::query()
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        if ($groups->isEmpty()) {
            $this->info('No se encontraron requirement_templates duplicados por nombre.');
            return self::SUCCESS;
        }

        $this->line('Templates duplicados detectados:');
        foreach ($groups as $name) {
            $count = RequirementTemplate::query()
                ->where('name', $name)
                ->count();

            $this->line("- {$name} ({$count})");
        }

        $summary = [
            'template_groups_processed' => 0,
            'duplicate_templates_processed' => 0,
            'asset_requirements_updated' => 0,
            'asset_obligations_updated' => 0,
            'mapping_rows_updated' => 0,
            'duplicate_templates_deleted' => 0,
            'duplicate_mappings_deleted' => 0,
        ];

        $runner = function () use ($groups, &$summary, $dryRun) {
            foreach ($groups as $name) {
                $templates = RequirementTemplate::query()
                    ->where('name', $name)
                    ->orderBy('id')
                    ->get();

                if ($templates->count() <= 1) {
                    continue;
                }

                $summary['template_groups_processed']++;

                $canonical = $templates->first();
                $duplicates = $templates->slice(1);

                $this->newLine();
                $this->info("Procesando template: {$name}");
                $this->line("Canónico: ID {$canonical->id}");

                foreach ($duplicates as $duplicate) {
                    $summary['duplicate_templates_processed']++;

                    $this->line(" - Duplicado: ID {$duplicate->id}");

                    $arCount = AssetRequirement::query()
                        ->where('requirement_template_id', $duplicate->id)
                        ->count();

                    $aoCount = AssetObligation::query()
                        ->where('requirement_template_id', $duplicate->id)
                        ->count();

                    $mapCount = AssetTypeRequirementTemplate::query()
                        ->where('requirement_template_id', $duplicate->id)
                        ->count();

                    $this->line("   AssetRequirements a remapear: {$arCount}");
                    $this->line("   AssetObligations a remapear: {$aoCount}");
                    $this->line("   Reglas asset_type_requirement_templates a remapear: {$mapCount}");

                    // El dry-run también debe reflejar el impacto esperado.
                    $summary['asset_requirements_updated'] += $arCount;
                    $summary['asset_obligations_updated'] += $aoCount;

                    if (! $dryRun) {
                        AssetRequirement::query()
                            ->where('requirement_template_id', $duplicate->id)
                            ->update([
                                'requirement_template_id' => $canonical->id,
                            ]);

                        AssetObligation::query()
                            ->where('requirement_template_id', $duplicate->id)
                            ->update([
                                'requirement_template_id' => $canonical->id,
                            ]);
                    }

                    $mappingRows = AssetTypeRequirementTemplate::query()
                        ->where('requirement_template_id', $duplicate->id)
                        ->orderBy('id')
                        ->get();

                    foreach ($mappingRows as $mappingRow) {
                        $alreadyExists = AssetTypeRequirementTemplate::query()
                            ->where('company_id', $mappingRow->company_id)
                            ->where('asset_type_id', $mappingRow->asset_type_id)
                            ->where('requirement_template_id', $canonical->id)
                            ->where('id', '!=', $mappingRow->id)
                            ->exists();

                        if ($alreadyExists) {
                            $this->line(
                                "   Mapping duplicado detectado para company_id={$mappingRow->company_id}, asset_type_id={$mappingRow->asset_type_id}, template_id={$canonical->id}; se "
                                . ($dryRun ? 'eliminaría' : 'eliminó')
                                . " ID {$mappingRow->id}"
                            );

                            $summary['duplicate_mappings_deleted']++;

                            if (! $dryRun) {
                                $mappingRow->delete();
                            }

                            continue;
                        }

                        $this->line(
                            "   Mapping ID {$mappingRow->id}: template {$duplicate->id} -> {$canonical->id}"
                        );

                        $summary['mapping_rows_updated']++;

                        if (! $dryRun) {
                            $mappingRow->update([
                                'requirement_template_id' => $canonical->id,
                            ]);
                        }
                    }

                    $summary['duplicate_templates_deleted']++;

                    $this->line(
                        "   Template duplicado ID {$duplicate->id} "
                        . ($dryRun ? 'se eliminaría' : 'eliminado')
                    );

                    if (! $dryRun) {
                        $duplicate->delete();
                    }
                }
            }

            // Limpieza final defensiva por si ya existían duplicados exactos
            // en asset_type_requirement_templates.
            $duplicateMappingGroups = AssetTypeRequirementTemplate::query()
                ->select(
                    'company_id',
                    'asset_type_id',
                    'requirement_template_id',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('company_id', 'asset_type_id', 'requirement_template_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($duplicateMappingGroups->isNotEmpty()) {
                $this->newLine();
                $this->info('Limpiando reglas duplicadas en asset_type_requirement_templates...');

                foreach ($duplicateMappingGroups as $group) {
                    $rows = AssetTypeRequirementTemplate::query()
                        ->where('company_id', $group->company_id)
                        ->where('asset_type_id', $group->asset_type_id)
                        ->where('requirement_template_id', $group->requirement_template_id)
                        ->orderBy('id')
                        ->get();

                    $keep = $rows->first();
                    $drop = $rows->slice(1);

                    $this->line(
                        " - company_id={$group->company_id}, asset_type_id={$group->asset_type_id}, requirement_template_id={$group->requirement_template_id}"
                    );
                    $this->line("   Se conserva ID {$keep->id}; se eliminan " . $drop->pluck('id')->implode(', '));

                    foreach ($drop as $row) {
                        $summary['duplicate_mappings_deleted']++;

                        if (! $dryRun) {
                            $row->delete();
                        }
                    }
                }
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
                : 'Normalización completada correctamente.'
        );

        return self::SUCCESS;
    }
}