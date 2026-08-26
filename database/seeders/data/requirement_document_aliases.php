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

        // El catálogo de producción no lleva el año de la norma en el nombre del
        // requerimiento (el año queda registrado por documento/versión, no en el
        // catálogo). Los archivos entregados sí traen el año en el nombre de la
        // norma, así que se mapean aquí al nombre real (sin año) del requerimiento.
        'NOM-004-ASEA-2017 Informe de Prueba Periódica del SRV' => 'NOM-004-ASEA- Informe de Prueba Periódica del SRV',
        'NOM-004-ASEA-2017 Informe Inicial del SRV' => 'NOM-004-ASEA- Informe Inicial del SRV',
        'NOM-004-ASEA-2017 Proyecto Ejecutivo SRV' => 'NOM-004-ASEA- Proyecto Ejecutivo SRV',
        'NOM-005-ASEA-2016 Dictamen de Construcción' => 'NOM-005-ASEA- Dictamen de Construcción',
        'NOM-005-ASEA-2016 Dictamen de Diseño' => 'NOM-005-ASEA- Dictamen de Diseño',
        'NOM-005-ASEA-2016 Dictamen de operación y mantenimiento' => 'NOM-005-ASEA- Dictamen de operación y mantenimiento',
        'NOM-005-ASEA-2016 Dossier de obra' => 'NOM-005-ASEA- Dossier de obra',
        'NOM-005-ASEA-2016 Proyecto Ejecutivo' => 'NOM-005-ASEA- Proyecto Ejecutivo',
        'NOM-016-CRE-2016 Dictamen de Calidad de Petroliferos' => 'NOM-016-- Dictamen de Calidad de Petroliferos',
        'NOM-016-CRE-2016 Muestreo de laboratorio de Calidad de Petroliferos' => 'NOM-016-- Muestreo de laboratorio de Calidad de Petroliferos',
        'NOM-185-SCFI-2011 Modelo prototipo software de dispensarios' => 'NOM-185-SCFI- Modelo prototipo software de dispensarios',
    ],
];
