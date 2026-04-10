<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\User;
use App\Services\SyncAssetRequirementsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EC_Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::where('name', 'Empresa Demo')->firstOrFail();
            $responsibleUser = User::where('email', 'test@example.com')->firstOrFail();
            $assetType = AssetType::where('name', 'EC')->firstOrFail();
            $syncService = app(SyncAssetRequirementsService::class);
            $defaultDate = Carbon::now()->addMonths(6)->startOfDay();

            $csvPath = database_path('seeders/examples/EC_ejemplos.csv');

            if (! file_exists($csvPath)) {
                throw new \RuntimeException("No se encontró el archivo CSV en: {$csvPath}");
            }

            $handle = fopen($csvPath, 'r');

            if ($handle === false) {
                throw new \RuntimeException("No se pudo abrir el archivo CSV.");
            }

            // Fila 1: título
            fgetcsv($handle);

            // Fila 2: encabezados reales
            $headers = fgetcsv($handle);

            if ($headers === false) {
                fclose($handle);
                throw new \RuntimeException('El CSV no tiene encabezados válidos.');
            }

            $headers = array_map(function ($value) {
                $value = trim((string) $value);
                $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
                return $value;
            }, $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $data = $this->combineRow($headers, $row);

                $code = trim((string) ($data['code'] ?? ''));
                $station = trim((string) ($data['station'] ?? ''));
                $location = trim((string) ($data['location'] ?? ''));
                $vaultLocation = trim((string) ($data['vault_location'] ?? ''));

                if ($code === '' || $station === '') {
                    continue;
                }

                $asset = Asset::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $code,
                    ],
                    [
                        'asset_type_id' => $assetType->id,
                        'name' => 'EC ' . $station,
                        'location' => $location !== '' ? $location : null,
                        'vault_location' => $vaultLocation !== '' ? $vaultLocation : null,
                        'responsible_user_id' => $responsibleUser->id,
                        'status' => 'active',
                        'compliance_start_date' => $defaultDate,
                        'compliance_due_date' => $defaultDate,
                        'parent_asset_id' => null,
                    ]
                );

                $syncService->handle($asset, removeObsolete: true);
            }

            fclose($handle);
        });
    }

    protected function combineRow(array $headers, array $row): array
    {
        $row = array_pad($row, count($headers), null);
        $raw = array_combine($headers, $row);

        return [
            'code' => $this->findValue($raw, [
                'PERMISO CRE',
            ]),
            'station' => $this->findValue($raw, [
                'Estación',
                'Estacion',
            ]),
            'location' => $this->findValue($raw, [
                'Estado',
                'ESTADO',
            ]),
            'vault_location' => $this->findValue($raw, [
                'DIRECCION',
                'Dirección',
                'Direccion',
            ]),
        ];
    }

    protected function findValue(array $row, array $possibleHeaders): ?string
    {
        foreach ($possibleHeaders as $header) {
            if (array_key_exists($header, $row)) {
                return $row[$header];
            }
        }

        return null;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}