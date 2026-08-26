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
    ],
];
