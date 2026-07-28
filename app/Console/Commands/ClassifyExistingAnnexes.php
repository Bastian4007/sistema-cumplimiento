<?php

namespace App\Console\Commands;

use App\Models\Regulation;
use Illuminate\Console\Command;

/**
 * Clasifica como anexos los documentos que ya existían antes de que se agregara la
 * columna is_annex (jul 2026), detectándolos por la palabra "anexo" en su nombre o
 * código. No los vincula a ningún documento padre — esa vinculación se hace a mano
 * desde el popover de anexos en el índice de Procesos, ya que el nombre por sí solo
 * no dice de forma confiable a qué proceso pertenece cada anexo.
 */
class ClassifyExistingAnnexes extends Command
{
    protected $signature = 'processes:classify-existing-annexes
                            {--dry-run : Muestra qué registros cambiarían sin guardar nada}';

    protected $description = 'Marca is_annex=true en documentos ya existentes cuyo nombre o código contiene "anexo"';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // LOWER(...) LIKE en vez de ilike/LIKE simple: ilike no existe en MySQL (producción)
        // y el LIKE simple de PostgreSQL (local) es sensible a mayúsculas por defecto, a
        // diferencia de MySQL con collation *_ci — LOWER() nivela el comportamiento en ambos.
        $candidates = Regulation::withTrashed()
            ->where('is_annex', false)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%anexo%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%anexo%']);
            })
            ->get(['id', 'code', 'name', 'company_id', 'is_annex']);

        if ($candidates->isEmpty()) {
            $this->info('No se encontraron documentos por clasificar como anexo.');

            return self::SUCCESS;
        }

        foreach ($candidates as $regulation) {
            $label = "#{$regulation->id} [{$regulation->company_id}] {$regulation->code} — {$regulation->name}";

            if ($dryRun) {
                $this->line("  [dry-run] {$label}");

                continue;
            }

            $regulation->is_annex = true;
            $regulation->timestamps = false;
            $regulation->save();

            $this->line("  {$label}");
        }

        $this->info(
            ($dryRun ? '[dry-run] ' : '') .
            "Total de documentos " . ($dryRun ? 'a marcar' : 'marcados') . " como anexo: {$candidates->count()}"
        );

        if ($dryRun) {
            $this->comment('Vuelve a ejecutar sin --dry-run para aplicar los cambios.');
        }

        return self::SUCCESS;
    }
}
