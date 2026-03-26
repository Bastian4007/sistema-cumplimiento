<?php

namespace Database\Seeders;

use App\Models\AssetType;
use App\Models\RequirementTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComercializacionRequirementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/comercializacion_checklist.csv');

        if (! file_exists($filePath)) {
            $this->command?->error("No se encontró el archivo: {$filePath}");
            return;
        }

        $assetType = AssetType::query()
            ->where('name', 'Comercialización')
            ->first();

        if (! $assetType) {
            $this->command?->error('No existe el asset type Comercialización.');
            return;
        }

        $handle = fopen($filePath, 'r');

        if (! $handle) {
            $this->command?->error('No se pudo abrir el archivo CSV.');
            return;
        }

        $headers = fgetcsv($handle, 0, ',');

        if (! $headers) {
            fclose($handle);
            $this->command?->error('El CSV no contiene encabezados.');
            return;
        }

        $headers = $this->normalizeHeaders($headers);

        $imported = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rowData = $this->mapRowToHeaders($headers, $row);

            $documentName = trim((string) ($rowData['documento'] ?? ''));
            $requiredValue = $this->normalizeValue($rowData['aplica_para'] ?? '');

            if ($documentName === '') {
                continue;
            }

            // Si quieres importar todo sin filtro, deja esto comentado.
            // Si luego agregas una columna tipo "Requerido en Comercialización", aquí se filtra.
            // if ($requiredValue === '') {
            //     continue;
            // }

            RequirementTemplate::updateOrCreate(
                [
                    'name' => $documentName,
                ],
                [
                    'asset_type_id' => $assetType->id,
                    'compliance_scope' => 'project',
                    'authority' => $this->normalizeAuthority($rowData['autoridad'] ?? null),
                    'description' => $this->buildDescription($rowData),
                ]
            );

            $imported++;
        }

        fclose($handle);

        $this->command?->info("Templates de Comercialización importados/actualizados: {$imported}");
    }

    private function normalizeHeaders(array $headers): array
    {
        return collect($headers)->map(function ($header) {
            $header = Str::of((string) $header)
                ->replace("\xEF\xBB\xBF", '')
                ->lower()
                ->ascii()
                ->replace(['#', '.', ',', ';', ':', '(', ')', '/'], ' ')
                ->replaceMatches('/\s+/', ' ')
                ->trim()
                ->value();

            return match ($header) {
                'documento' => 'documento',
                'frecuencia del permiso' => 'frecuencia_permiso',
                'aplica para' => 'aplica_para',
                'autoridad' => 'autoridad',
                'area responsable tramite' => 'area_responsable_tramite',
                default => $header,
            };
        })->toArray();
    }

    private function mapRowToHeaders(array $headers, array $row): array
    {
        $result = [];

        foreach ($headers as $index => $header) {
            $result[$header] = $row[$index] ?? null;
        }

        return $result;
    }

    private function normalizeValue(mixed $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    private function buildDescription(array $rowData): ?string
    {
        $parts = [];

        if (! empty($rowData['autoridad'])) {
            $parts[] = 'Autoridad: ' . trim((string) $rowData['autoridad']);
        }

        if (! empty($rowData['frecuencia_permiso'])) {
            $parts[] = 'Frecuencia: ' . trim((string) $rowData['frecuencia_permiso']);
        }

        if (! empty($rowData['area_responsable_tramite'])) {
            $parts[] = 'Área responsable: ' . trim((string) $rowData['area_responsable_tramite']);
        }

        return empty($parts) ? null : implode(' | ', $parts);
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeAuthority(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_strtoupper($value);
    }
}