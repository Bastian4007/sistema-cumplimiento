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

        $headers = null;
        $createdOrUpdated = [];

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            if ($headers === null) {
                $candidateHeaders = $this->normalizeHeaders($row);

                if (! $this->looksLikeHeaderRow($candidateHeaders)) {
                    continue;
                }

                $headers = $candidateHeaders;
                continue;
            }

            $rowData = $this->mapRowToHeaders($headers, $row);

            $documentName = trim((string) ($rowData['documento'] ?? ''));

            if ($documentName === '') {
                continue;
            }

            $scopes = $this->extractScopes($rowData['aplica_para'] ?? null);

            foreach ($scopes as $scope) {
                $template = RequirementTemplate::updateOrCreate(
                    [
                        'name' => $documentName,
                        'asset_type_id' => $assetType->id,
                        'compliance_scope' => $scope,
                    ],
                    [
                        'authority' => $this->normalizeAuthority($rowData['autoridad'] ?? null),
                        'description' => $this->buildDescription($rowData),
                    ]
                );

                $createdOrUpdated[$template->id] = true;
            }
        }

        fclose($handle);

        if ($headers === null) {
            $this->command?->error('No se encontró una fila válida de encabezados en el CSV.');
            return;
        }

        $count = count($createdOrUpdated);

        $this->command?->info("Templates de Comercialización importados/actualizados: {$count}");
    }

    private function normalizeHeaders(array $headers): array
    {
        return collect($headers)->map(function ($header) {
            $normalized = Str::of((string) $header)
                ->replace("\xEF\xBB\xBF", '')
                ->lower()
                ->ascii()
                ->replace(['#', '.', ',', ';', ':', '(', ')'], ' ')
                ->replaceMatches('/\s+/', ' ')
                ->trim()
                ->value();

            return match ($normalized) {
                'dependencia', 'dependencia no', 'dependencia numero' => 'dependencia_numero',
                'documento' => 'documento',
                'frecuencia', 'frecuencia del permiso' => 'frecuencia_permiso',
                'aplica para', 'aplica' => 'aplica_para',
                'autoridad' => 'autoridad',
                'area responsable tramite' => 'area_responsable_tramite',
                default => $normalized,
            };
        })->toArray();
    }

    private function looksLikeHeaderRow(array $headers): bool
    {
        $headers = collect($headers);

        return $headers->contains('documento')
            && $headers->contains('frecuencia_permiso')
            && $headers->contains('aplica_para')
            && $headers->contains('autoridad');
    }

    private function mapRowToHeaders(array $headers, array $row): array
    {
        $result = [];

        foreach ($headers as $index => $header) {
            $result[$header] = isset($row[$index])
                ? trim((string) $row[$index])
                : null;
        }

        return $result;
    }

    private function extractScopes(?string $value): array
    {
        $value = Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replace(['/', ';', '|'], ',')
            ->replace(' y ', ',')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        if ($value === '') {
            return ['project'];
        }

        $scopes = collect(explode(',', $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->map(function ($item) {
                return match ($item) {
                    'cn', 'proyecto', 'project' => 'project',
                    'op', 'operacion', 'operation' => 'operation',
                    default => null,
                };
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($scopes) ? ['project'] : $scopes;
    }

    private function buildDescription(array $rowData): ?string
    {
        $parts = [];

        if (! empty($rowData['dependencia_numero'])) {
            $parts[] = 'Dependencia #: ' . trim((string) $rowData['dependencia_numero']);
        }

        if (! empty($rowData['frecuencia_permiso'])) {
            $parts[] = 'Frecuencia: ' . trim((string) $rowData['frecuencia_permiso']);
        }

        if (! empty($rowData['autoridad'])) {
            $parts[] = 'Autoridad: ' . trim((string) $rowData['autoridad']);
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