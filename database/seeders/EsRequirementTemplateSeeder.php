<?php

namespace Database\Seeders;

use App\Models\AssetType;
use App\Models\RequirementTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EsRequirementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/es_checklist.csv');

        if (! file_exists($filePath)) {
            $this->command?->error("No se encontró el archivo: {$filePath}");
            return;
        }

        $assetType = AssetType::query()
            ->where('name', 'ES')
            ->first();

        if (! $assetType) {
            $this->command?->error('No existe el asset type ES.');
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

            $permissionName = trim((string) ($rowData['permiso'] ?? ''));
            $requiredInEs = $this->normalizeValue($rowData['requerido_en_es'] ?? '');

            if ($permissionName === '') {
                continue;
            }

            if ($requiredInEs !== 'si') {
                continue;
            }

            RequirementTemplate::updateOrCreate(
                [
                    'name' => $permissionName,
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

        $this->command?->info("Templates ES importados/actualizados: {$imported}");
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
                'permiso' => 'permiso',
                'frecuencia del permiso' => 'frecuencia_permiso',
                'aplica para' => 'aplica_para',
                'autoridad' => 'autoridad',
                'requerido en es' => 'requerido_en_es',
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