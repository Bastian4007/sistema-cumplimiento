<?php

namespace Database\Seeders;

use App\Models\AssetType;
use App\Models\RequirementTemplate;
use Illuminate\Database\Seeder;

class VehiculosRequirementTemplateSeeder extends Seeder
{
    // Expediente applies to these vehicle types
    const ASSET_TYPE_NAMES = [
        'Tracto',
        'Semirremolque',
        'ATQ',
        'Carro tanque',
        'Cilindrera',
    ];

    // Alta/Modificación and Baja apply to these vehicle types
    const LIFECYCLE_ASSET_TYPE_NAMES = [
        'Tracto',
        'Semirremolque',
        'ATQ',
        'Carro tanque',
        'Cilindrera',
    ];

    // [name, description, authority, category, subtype]
    const REQUIREMENTS = [
        // --- Identificación del vehículo ---
        ['Número de permiso',                                          null,                                                       'CRE',   'expediente', 'permiso'],
        ['No. Económico',                                              null,                                                       null,    'expediente', 'identificacion'],
        ['Número de serie del vehículo / NIV',                        'Número de Identificación Vehicular',                       null,    'expediente', 'identificacion'],
        ['Modelo / Año',                                               null,                                                       null,    'expediente', 'identificacion'],
        ['Marca del vehículo',                                         null,                                                       null,    'expediente', 'identificacion'],
        ['Tarjeta de circulación vigente',                             null,                                                       'SICT',  'expediente', 'identificacion'],
        ['Núm. Permiso SICT / Núm. Certificado',                      'Solo si circula en área federal',                          'SICT',  'expediente', 'permiso'],
        ['Número de matrícula (placa)',                                null,                                                       null,    'expediente', 'identificacion'],

        // --- Recipiente ---
        ['Marca del recipiente',                                       null,                                                       null,    'expediente', 'recipiente'],
        ['Capacidad en litros',                                        null,                                                       null,    'expediente', 'recipiente'],
        ['Número de serie del recipiente',                             null,                                                       null,    'expediente', 'recipiente'],
        ['Fecha de fabricación del recipiente (mes/año)',              null,                                                       null,    'expediente', 'recipiente'],

        // --- Ubicación ---
        ['Central de guarda',                                          null,                                                       null,    'expediente', 'ubicacion'],
        ['Latitud y longitud de central de guarda (coordenadas)',      null,                                                       null,    'expediente', 'ubicacion'],

        // --- Combustible ---
        ['Carbura con: Gasolina',                                      null,                                                       null,    'expediente', 'combustible'],
        ['Carbura con: Diesel',                                        null,                                                       null,    'expediente', 'combustible'],
        ['Carbura con: GLP',                                           null,                                                       null,    'expediente', 'combustible'],

        // --- Póliza de seguro ---
        ['Póliza de seguro — Institución emisora',                     'Documento íntegro de la póliza de seguro vigente',         null,    'expediente', 'poliza'],
        ['Póliza de seguro — Número de póliza',                       'Documento íntegro de la póliza de seguro vigente',         null,    'expediente', 'poliza'],
        ['Póliza de seguro — Fecha de inicio de vigencia',             'Documento íntegro de la póliza de seguro vigente',         null,    'expediente', 'poliza'],
        ['Póliza de seguro — Fecha de término de vigencia',            'Documento íntegro de la póliza de seguro vigente',         null,    'expediente', 'poliza'],
        ['Póliza de seguro — Cobertura por responsabilidad civil',     null,                                                       null,    'expediente', 'poliza'],
        ['Póliza de seguro — Cobertura por daño ambiental',           null,                                                       null,    'expediente', 'poliza'],
        ['Póliza de seguro — Límite suma asegurada resp. civil',       null,                                                       null,    'expediente', 'poliza'],
        ['Póliza de seguro — Límite suma asegurada daño ambiental',   null,                                                       null,    'expediente', 'poliza'],

        // --- Dictámenes NOMs ---
        ['NOM-EM-007-ASEA-2025',                                       'Dictamen de cumplimiento vigente',                        'ASEA',  'expediente', 'nom'],
        ['NOM-005-SESH-2010',                                          'Dictamen de cumplimiento vigente. Solo si carbura con GLP', 'ASEA', 'expediente', 'nom'],
        ['NOM-013-SEDG-2002',                                          'Dictamen de cumplimiento vigente. Aplica cuando el recipiente tiene más de 10 años', 'ASEA', 'expediente', 'nom'],

        // --- Fotografías ---
        ['Fotografía — Frontal',                                       null,                                                       null,    'expediente', 'fotografia'],
        ['Fotografía — Trasera',                                       null,                                                       null,    'expediente', 'fotografia'],
        ['Fotografía — Lateral izquierda',                             null,                                                       null,    'expediente', 'fotografia'],
        ['Fotografía — Lateral derecha',                               null,                                                       null,    'expediente', 'fotografia'],
        ['Fotografía — Placa del tanque (recipiente no desmontable)',  null,                                                       null,    'expediente', 'fotografia'],
        ['Fotografía — Placa del chasis',                              null,                                                       null,    'expediente', 'fotografia'],

        // --- Cilíndreras ---
        ['Cantidad máxima de recipientes transportables y/o portátiles', 'Solo aplica para cilíndreras',                          null,    'expediente', 'otro'],
    ];

    // [name, description, authority, category, subtype]
    // Modificación usa los mismos requisitos que Alta
    const ALTA_REQUIREMENTS = [
        ['Número de permiso',                                              null,                                                                            'CRE',  'alta', 'permiso'],
        ['Número de serie del vehículo / NIV',                            'Número de Identificación Vehicular',                                            null,   'alta', 'identificacion'],
        ['Número de placa o matrícula y fecha de cambio (SICT)',          'Secretaría de Infraestructura, Comunicaciones y Transportes',                   'SICT', 'alta', 'identificacion'],
        ['Tarjeta de circulación vigente',                                 null,                                                                            'SICT', 'alta', 'identificacion'],
        ['Póliza de seguro — Institución emisora',                        'Documento íntegro de la póliza de seguro vigente',                              null,   'alta', 'poliza'],
        ['Póliza de seguro — Número de póliza',                          'Documento íntegro de la póliza de seguro vigente',                              null,   'alta', 'poliza'],
        ['Póliza de seguro — Fecha de inicio de vigencia',               'Documento íntegro de la póliza de seguro vigente',                              null,   'alta', 'poliza'],
        ['Póliza de seguro — Fecha de término de vigencia',              'Documento íntegro de la póliza de seguro vigente',                              null,   'alta', 'poliza'],
        ['Póliza de seguro — Cobertura por responsabilidad civil',        null,                                                                            null,   'alta', 'poliza'],
        ['Póliza de seguro — Cobertura por daño ambiental',              null,                                                                            null,   'alta', 'poliza'],
        ['Póliza de seguro — Límite suma asegurada resp. civil',          null,                                                                            null,   'alta', 'poliza'],
        ['Póliza de seguro — Límite suma asegurada daño ambiental',      null,                                                                            null,   'alta', 'poliza'],
        ['NOM-EM-007-ASEA-2025',                                          'Dictamen de cumplimiento vigente',                                             'ASEA', 'alta', 'nom'],
        ['NOM-005-SESH-2010',                                             'Dictamen de cumplimiento vigente. Solo si carbura con GLP',                    'ASEA', 'alta', 'nom'],
        ['NOM-013-SEDG-2002',                                             'Dictamen de cumplimiento vigente. Aplica cuando el recipiente tiene más de 10 años', 'ASEA', 'alta', 'nom'],
        ['Historial de modificaciones',                                   'Solo aplica si es unidad rehabilitada',                                        null,   'alta', 'otro'],
    ];

    const BAJA_REQUIREMENTS = [
        ['Permiso — Número del permiso',                  null,                                  null, 'baja', 'permiso'],
        ['Permiso — Actividad regulada',                  null,                                  null, 'baja', 'permiso'],
        ['Parque vehicular — Tipo de vehículo',           null,                                  null, 'baja', 'identificacion'],
        ['Parque vehicular — ID asignado por la Comisión', null,                                 null, 'baja', 'identificacion'],
        ['Número de serie del vehículo / NIV',            'Número de Identificación Vehicular',  null, 'baja', 'identificacion'],
        ['Historial de modificaciones',                   'Solo aplica si es unidad rehabilitada', null, 'baja', 'otro'],
    ];

    public function run(): void
    {
        $total = 0;

        // --- Expediente ---
        foreach (self::ASSET_TYPE_NAMES as $typeName) {
            $total += $this->seedRequirements($typeName, self::REQUIREMENTS);
        }

        // --- Alta / Modificación y Baja (incluye Cilindrera) ---
        foreach (self::LIFECYCLE_ASSET_TYPE_NAMES as $typeName) {
            $total += $this->seedRequirements($typeName, self::ALTA_REQUIREMENTS);
            $total += $this->seedRequirements($typeName, self::BAJA_REQUIREMENTS);
        }

        $this->command?->info("Total: {$total} templates procesados.");
    }

    private function seedRequirements(string $typeName, array $requirements): int
    {
        $assetType = AssetType::where('name', $typeName)->first();

        if (! $assetType) {
            $this->command?->warn("Asset type no encontrado: {$typeName}");
            return 0;
        }

        $category = $requirements[0][3] ?? 'expediente';

        foreach ($requirements as [$name, $description, $authority, $cat, $subtype]) {
            RequirementTemplate::updateOrCreate(
                [
                    'asset_type_id' => $assetType->id,
                    'name'          => $name,
                    'category'      => $cat,
                ],
                [
                    'description' => $description,
                    'authority'   => $authority,
                    'subtype'     => $subtype,
                ]
            );
        }

        $count = count($requirements);
        $this->command?->info("✓ {$typeName} [{$category}]: {$count} templates.");

        return $count;
    }
}
