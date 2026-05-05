<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/Normativa_ALCOM.csv');

        if (! file_exists($path)) {
            $this->command->error("Archivo no encontrado: {$path}");
            return;
        }

        $company = Company::where('name', 'ALCOM')->first();

        if (! $company) {
            $this->command->error('No se encontró la empresa ALCOM.');
            return;
        }

        $groupId = (int) $company->group_id;
        $companyId = (int) $company->id;

        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->command->error("No se pudo abrir el archivo: {$path}");
            return;
        }

        DB::beginTransaction();

        try {
            $cache = [];

            $currentFolder = null;
            $currentCategory = null;

            $rowNumber = 0;

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $rowNumber++;

                // Saltar filas de título/espaciado
                if ($rowNumber < 4) {
                    continue;
                }

                // Fila de encabezados reales
                if ($rowNumber === 4) {
                    continue;
                }

                $row = array_pad($row, 13, null);

                $folderName   = trim((string) ($row[0] ?? ''));
                $categoryName = trim((string) ($row[1] ?? ''));
                $subCategory  = trim((string) ($row[2] ?? '')); // por ahora no se usa
                $docName      = trim((string) ($row[6] ?? ''));
                $date         = trim((string) ($row[7] ?? ''));
                $expiration   = trim((string) ($row[8] ?? ''));
                $oficio       = trim((string) ($row[9] ?? ''));
                $access       = trim((string) ($row[10] ?? ''));
                $type         = trim((string) ($row[11] ?? ''));
                $responsible  = trim((string) ($row[12] ?? ''));

                // arrastrar carpeta/categoría
                if ($folderName !== '') {
                    $currentFolder = $folderName;
                }

                if ($categoryName !== '') {
                    $currentCategory = $categoryName;
                }

                // si no hay contexto o no hay nombre de documento, saltar
                if (! $currentFolder || ! $currentCategory || $docName === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Folder raíz
                |--------------------------------------------------------------------------
                */
                $folderKey = "folder_{$currentFolder}";

                if (! isset($cache[$folderKey])) {
                    $folder = DocumentFolder::firstOrCreate(
                        [
                            'group_id' => $groupId,
                            'company_id' => $companyId,
                            'parent_id' => null,
                            'name' => $currentFolder,
                            'level' => 'folder',
                        ],
                        [
                            'sort_order' => 0,
                            'is_active' => true,
                        ]
                    );

                    $cache[$folderKey] = $folder;
                }

                $folder = $cache[$folderKey];

                /*
                |--------------------------------------------------------------------------
                | Categoría hija
                |--------------------------------------------------------------------------
                */
                $categoryKey = "cat_{$currentFolder}_{$currentCategory}";

                if (! isset($cache[$categoryKey])) {
                    $category = DocumentFolder::firstOrCreate(
                        [
                            'group_id' => $groupId,
                            'company_id' => $companyId,
                            'parent_id' => $folder->id,
                            'name' => $currentCategory,
                            'level' => 'category',
                        ],
                        [
                            'sort_order' => 0,
                            'is_active' => true,
                        ]
                    );

                    $cache[$categoryKey] = $category;
                }

                $category = $cache[$categoryKey];

                /*
                |--------------------------------------------------------------------------
                | Documento esperado
                |--------------------------------------------------------------------------
                */
                Document::firstOrCreate(
                    [
                        'group_id' => $groupId,
                        'company_id' => $companyId,
                        'document_folder_id' => $category->id,
                        'name' => $docName,
                    ],
                    [
                        'document_type' => $type !== '' && strtoupper($type) !== 'N/A' ? $type : null,
                        'reference' => $oficio !== '' && strtoupper($oficio) !== 'N/A' ? $oficio : null,
                        'authorized_access_notes' => $access !== '' && strtoupper($access) !== 'N/A' ? $access : null,
                        'responsible_name' => $responsible !== '' && strtoupper($responsible) !== 'N/A' ? $responsible : null,
                        'is_required' => true,
                        'is_active' => true,
                        'uploaded_by' => null,
                    ]
                );
            }

            fclose($handle);
            DB::commit();

            $this->command->info('Seeder ejecutado correctamente 🚀');
        } catch (\Throwable $e) {
            DB::rollBack();

            if (is_resource($handle)) {
                fclose($handle);
            }

            $this->command->error($e->getMessage());
        }
    }
}