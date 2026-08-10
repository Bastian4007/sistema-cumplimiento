<?php

namespace App\Services;

/**
 * Fuerza el estilo visual EXACTO del diagrama de flujo (colores por tipo de nodo, fondo de
 * carriles, numeración de pasos) descrito en resources/ai-reference/documento_condiciones.docx —
 * la IA solo decide la estructura del flujo (pasos, decisiones, carriles), nunca el color ni el
 * formato. Mismo principio que RegulationBodyHtmlBuilder aplica al cuerpo del documento: se
 * post-procesa el Mermaid crudo con reglas fijas, en vez de confiarle el estilo a la IA o al
 * tema por defecto de Mermaid.
 *
 * Detecta los nodos por su sintaxis de forma (estándar de Mermaid, no algo que inventemos):
 * - id{texto}    → rombo de decisión
 * - id([texto])  → óvalo/píldora — inicio o fin, según el nombre del id o el texto
 * - id[texto]    → rectángulo — paso de actividad normal
 */
class MermaidDiagramStyler
{
    /**
     * El documento final siempre topa el diagrama a 6.5in de ancho (imageDimensionAttrs() en
     * AiProcedureGenerationService) para que no se recorte en Word — con diagramas de varios
     * carriles eso encoge mucho el ancho nativo, y con el fontSize/espaciado por defecto de
     * Mermaid (~16px, 50px entre nodos/rangos) el texto queda demasiado pequeño para leerse.
     * Subir el fontSize y apretar el espaciado fijo entre nodos/carriles hace que el texto ocupe
     * una porción más grande del ancho total del diagrama, y que las etiquetas largas envuelvan
     * a varias líneas dentro del nodo en vez de estirar el diagrama a lo ancho — el resultado, ya
     * encogido a 6.5in, sale considerablemente más legible (probado con diagramas de 6 carriles).
     */
    private const INIT_DIRECTIVE = '%%{init: {"flowchart": {"nodeSpacing": 15, "rankSpacing": 25, '
        . '"padding": 6}, "themeVariables": {"fontSize": "26px"}}}%%';

    private const CIRCLED_DIGITS = [
        1 => '①', 2 => '②', 3 => '③', 4 => '④', 5 => '⑤',
        6 => '⑥', 7 => '⑦', 8 => '⑧', 9 => '⑨', 10 => '⑩',
        11 => '⑪', 12 => '⑫', 13 => '⑬', 14 => '⑭', 15 => '⑮',
        16 => '⑯', 17 => '⑰', 18 => '⑱', 19 => '⑲', 20 => '⑳',
    ];

    public function style(string $mermaid): string
    {
        $decisionIds = [];
        $startIds = [];
        $endIds = [];
        $activityIds = [];
        $subgraphIds = [];

        // Se procesa línea por línea porque una declaración de carril ("subgraph ID[\"Nombre\"]")
        // usa la MISMA sintaxis "id[...]" que un rectángulo de paso normal — sin esto, cada carril
        // se contaría (y coloraría) también como si fuera un paso de actividad más.
        $lines = explode("\n", $mermaid);

        foreach ($lines as &$line) {
            if (preg_match('/^\s*subgraph\s+([A-Za-z_]\w*)/', $line, $sm)) {
                $subgraphIds[] = $sm[1];

                continue;
            }

            // Rombos de decisión: se les quita el número de paso si lo traen (el ejemplo no numera decisiones).
            // La etiqueta puede venir entre comillas ("6. ¿Aprueba?") — se conserva la comilla de apertura.
            $line = preg_replace_callback('/\b([A-Za-z_]\w*)\{([^{}]*)\}/', function (array $m) use (&$decisionIds) {
                $decisionIds[] = $m[1];
                $label = preg_replace_callback('/^(\s*"?)\s*\d+\.\s*/', fn (array $n) => $n[1], $m[2]);

                return "{$m[1]}{{$label}}";
            }, $line);

            // Óvalos/píldoras: inicio o fin según el nombre del id o el texto de la etiqueta.
            $line = preg_replace_callback('/\b([A-Za-z_]\w*)\(\[([^\[\]]*)\]\)/', function (array $m) use (&$startIds, &$endIds) {
                $needle = $m[1] . ' ' . $m[2];

                if (preg_match('/inicio|start/i', $needle)) {
                    $startIds[] = $m[1];
                } elseif (preg_match('/\bfin\b|\bend\b/i', $needle)) {
                    $endIds[] = $m[1];
                }

                return "{$m[1]}([{$m[2]}])";
            }, $line);

            // Rectángulos (pasos normales): "N. Texto" → "① Texto" (círculo numerado en vez del número plano).
            // La etiqueta puede venir entre comillas ("3. Validar días disponibles") — se conserva la comilla de apertura.
            $line = preg_replace_callback('/\b([A-Za-z_]\w*)\[([^\[\]{}]*)\]/', function (array $m) use (&$activityIds) {
                $activityIds[] = $m[1];

                $label = preg_replace_callback('/^(\s*"?)\s*(\d+)\.\s*/', function (array $n) {
                    $num = (int) $n[2];

                    return $n[1] . (self::CIRCLED_DIGITS[$num] ?? "{$num}.") . ' ';
                }, $m[2]);

                return "{$m[1]}[{$label}]";
            }, $line);
        }
        unset($line);

        $mermaid = implode("\n", $lines);
        $subgraphIds = array_unique($subgraphIds);
        $activityIds = array_diff(array_unique($activityIds), $startIds, $endIds);

        $lines = [];

        if ($decisionIds !== []) {
            $lines[] = 'classDef flowDecision fill:#FFD966,stroke:#C9A227,stroke-width:2px,color:#000000;';
            $lines[] = 'class ' . implode(',', array_unique($decisionIds)) . ' flowDecision;';
        }
        if ($startIds !== []) {
            $lines[] = 'classDef flowStart fill:#FFFFFF,stroke:#548235,stroke-width:2px,color:#548235,font-weight:bold;';
            $lines[] = 'class ' . implode(',', array_unique($startIds)) . ' flowStart;';
        }
        if ($endIds !== []) {
            $lines[] = 'classDef flowEnd fill:#FF6B6B,stroke:#FF6B6B,stroke-width:2px,color:#FFFFFF,font-weight:bold;';
            $lines[] = 'class ' . implode(',', array_unique($endIds)) . ' flowEnd;';
        }
        if ($activityIds !== []) {
            $lines[] = 'classDef flowActivity fill:#FFFFFF,stroke:#5B9BD5,stroke-width:2px,color:#000000;';
            $lines[] = 'class ' . implode(',', $activityIds) . ' flowActivity;';
        }
        foreach ($subgraphIds as $subgraphId) {
            // Fondos de carril: blanco puro / gris muy claro, divisores sutiles — nunca el color que decida la IA.
            $lines[] = "style {$subgraphId} fill:#F7F9FC,stroke:#CCCCCC,stroke-width:1px;";
        }

        return self::INIT_DIRECTIVE . "\n" . $mermaid . "\n" . implode("\n", $lines) . "\n";
    }

    /**
     * Número de pasos de actividad detectados (para el conteo "(N pasos)" de la barra de título).
     */
    public function countActivitySteps(string $mermaid): int
    {
        $count = 0;

        foreach (explode("\n", $mermaid) as $line) {
            if (preg_match('/^\s*subgraph\s+/', $line)) {
                continue;
            }

            $count += preg_match_all('/\b[A-Za-z_]\w*\[[^\[\]{}]*\]/', $line);
        }

        return $count;
    }
}
