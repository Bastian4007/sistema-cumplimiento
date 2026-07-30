<?php

namespace App\Console\Commands;

use App\Enums\RequirementStatus;
use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\AssetType;
use App\Models\RequirementTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillEcEsRequirements extends Command
{
    protected $signature = 'compliance:backfill-ec-es-requirements {--dry-run : Solo muestra lo que haría, sin guardar cambios}';

    protected $description = 'Crea los asset_requirements faltantes para activos EC/ES existentes contra el checklist nuevo, sin tocar los que ya existen (evita resetear progreso ya registrado).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Buscando pares (activo, template) faltantes para EC/ES...');
        if ($dryRun) {
            $this->warn('Modo dry-run activado. No se harán cambios en base de datos.');
        }

        $created = 0;

        foreach (['EC', 'ES'] as $typeName) {
            $assetType = AssetType::where('name', $typeName)->first();

            if (! $assetType) {
                $this->warn("Asset type no encontrado: {$typeName}");
                continue;
            }

            $templates = RequirementTemplate::where('asset_type_id', $assetType->id)->get();
            $assets = Asset::where('asset_type_id', $assetType->id)->get();

            $existingPairs = AssetRequirement::query()
                ->whereIn('asset_id', $assets->pluck('id'))
                ->whereIn('requirement_template_id', $templates->pluck('id'))
                ->get(['asset_id', 'requirement_template_id'])
                ->map(fn ($r) => "{$r->asset_id}:{$r->requirement_template_id}")
                ->flip();

            $rows = [];
            $now = now();

            foreach ($assets as $asset) {
                $dueDate = $asset->compliance_due_date
                    ? ($asset->compliance_due_date instanceof \Carbon\CarbonInterface
                        ? $asset->compliance_due_date->toDateString()
                        : (string) $asset->compliance_due_date)
                    : $now->copy()->addYear()->toDateString();

                foreach ($templates as $template) {
                    if (isset($existingPairs["{$asset->id}:{$template->id}"])) {
                        continue;
                    }

                    $rows[] = [
                        'company_id' => $asset->company_id,
                        'asset_id' => $asset->id,
                        'requirement_template_id' => $template->id,
                        'status' => RequirementStatus::PENDING->value,
                        'due_date' => $dueDate,
                        'type' => 'initial',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $this->line("{$typeName}: " . count($rows) . ' asset_requirements ' . ($dryRun ? 'a crear' : 'creados'));
            $created += count($rows);

            if (! $dryRun) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('asset_requirements')->insert($chunk);
                }
            }
        }

        $this->newLine();
        $this->info($dryRun ? "Dry-run completado. Se crearían {$created} filas." : "Completado. {$created} filas creadas.");

        return self::SUCCESS;
    }
}
