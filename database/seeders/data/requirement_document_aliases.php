<?php

// Alias de nombres de archivo -> nombre exacto del RequirementTemplate en el catálogo.
// Se usa cuando el documento que entregan no trae el nombre exacto del requerimiento,
// pero sabemos que corresponde a ese mismo requerimiento (confirmado manualmente),
// y no queremos depender de que renombren el archivo cada vez que llega una carpeta nueva.
// Se agrupa por nombre de AssetType (EC, ES, Plantas, Tracto, Semirremolque, ATQ, ...).

return [
    'ES' => [
        'Informe Preventivo' => 'Manifiesto de Impacto Ambiental / Informe Preventivo',
        'Resolutivo del Manifestación de Impacto Social en el Sector Energético, MISSE o Evaluación de Impacto'
            => 'Resolutivo del Manifestación de Impacto Social en el Sector Energético, MISSE o Evaluación de Impacto Social, EVIS',
        // Los paréntesis finales se quitan antes de buscar el alias (mismo criterio que con
        // archivos, ver Título de Permiso), así que aquí va sin el "(EVIS)".
        'Resolutivo de Evaluación de Impacto Social'
            => 'Resolutivo del Manifestación de Impacto Social en el Sector Energético, MISSE o Evaluación de Impacto Social, EVIS',

        // Nombres del Excel de vigencias (fechas de emisión/vigencia por documento) que no
        // calzan literal con el catálogo — confirmado manualmente que es el mismo requerimiento.
        'Pólizas de Seguros RC y RCA - Determinación de Límites' => 'Pólizas de seguros RC y RCA+ Determinación de los Límites',
        'Vo.Bo. Inicio de Operaciones - Protección Civil' => 'Vobo de Inicio de Operaciones Protección Civil',
        'Pruebas de Integridad Mecánica PH' => 'Pruebas de integridad mecánica',
        'SASISOPA - Implementación' => 'SASISOPA Implementación',

        // Confirmado con negocio: mismo requerimiento con nombre abreviado en la carpeta
        // entregada por la estación ES Linares 3.
        'Controles volumetricos' => 'ANEXO 21-22 Certificado de Control Volúmetrico',
        'Manifiesto de Impacto Ambiental' => 'Manifiesto de Impacto Ambiental / Informe Preventivo',
        'Pólizas de seguros RC' => 'Pólizas de seguros RC y RCA+ Determinación de los Límites',
        'NOM-016-CRE-2016 Muestreo de Laboratorio' => 'NOM-016-CRE-2016 Muestreo de laboratorio de Calidad de Petroliferos',
        'Escritura de inmueble o Contrato de Arrendamiento'
            => 'Escritura de inmueble o Contrato de Arrendamiento que avale propiedad o posesión del inmueble',

        // Confirmado con negocio: "PH" en el alias de arriba es "Prueba de Hermeticidad", así
        // que la prueba de hermeticidad se archiva bajo el mismo requerimiento.
        'Prueba de hermeticidad' => 'Pruebas de integridad mecánica',
    ],
];
