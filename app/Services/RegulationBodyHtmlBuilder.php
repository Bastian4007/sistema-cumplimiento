<?php

namespace App\Services;

/**
 * Cuerpo del documento con el formato EXACTO (colores, tablas, tipografías) del documento de
 * referencia `resources/ai-reference/documento_ejemplo.docx` — la misma idea que
 * `RegulationDocxHeaderBuilder` aplica al encabezado, pero para el resto del documento.
 *
 * Antes, la IA generaba libremente el HTML completo del cuerpo (`documento_html`), decidiendo
 * ella misma colores/estructura en cada generación — sin garantía de que dos generaciones (o
 * incluso la primera) coincidieran con el formato ya aprobado por el cliente. Ahora la IA solo
 * redacta TEXTO (ver `AiProcedureGenerationService::schema()`, campos `details` y `documento`) y
 * este builder arma el HTML final de forma 100% determinista, sección por sección, con el mismo
 * estilo cada vez — igual que ya pasa con el encabezado.
 *
 * El HTML que produce se usa dos veces con la misma fuente de verdad (para que "Ver" y
 * "Descargar" luzcan idénticos): se inyecta tal cual en la vista previa del navegador, y se pasa
 * a `PhpOffice\PhpWord\Shared\Html::addHtml()` para compilar el .docx.
 */
class RegulationBodyHtmlBuilder
{
    private const TITLE_COLOR = '1A5276';
    private const BOX_BG = 'F2F3F4';
    private const TABLE_HEADER_BG = '002060';
    private const STEP_TITLE_COLOR = '2E74B5';
    private const RESPONSIBLE_COLOR = '595959';
    private const FOOTER_COLOR = '666666';

    /**
     * Ancho de contenido en puntos (Carta 8.5in = 612pt, menos 1in de margen a cada lado —
     * mismos márgenes que usa RegulationVersionController al armar la sección del .docx). El
     * PhpWord\Shared\Html::addHtml() de PhpWord IGNORA por completo cualquier "width"/"max-width"
     * puesto en el style="" de un <td> si no viene expresado así — y si un <td> no trae "width"
     * en absoluto, PhpWord no reparte el ancho de la tabla entre columnas ni las ajusta al
     * contenido: aplica un ancho mínimo casi fijo a cada una, sin importar cuánto texto tengan
     * ("width: 100%" en el <table> no alcanza, hay que ponerlo también en cada <td>). Por eso
     * dataTable() siempre debe recibir anchos de columna explícitos en pt.
     */
    private const CONTENT_WIDTH_PT = 468;

    /** Debe coincidir con AiProcedureGenerationService::DIAGRAM_MARKER (mismo literal, servicios independientes). */
    public const DIAGRAM_MARKER = '{{DIAGRAMA_FLUJO}}';

    /**
     * @param  array<string, string>  $details  Los 20 campos de texto plano ya afinados (DETAIL_FIELDS).
     * @param  array<string, mixed>  $documento  Contenido estructurado adicional (ver schema en AiProcedureGenerationService).
     */
    public function build(array $details, array $documento, string $companyName): string
    {
        $html = '';
        $html .= $this->section('Objetivo', $this->box($details['resultado_esperado'] ?? ''));
        $html .= $this->section('Alcance', $this->alcanceBox($details));
        $html .= $this->section('Tópicos', $this->plainList($documento['topicos'] ?? []));
        $html .= $this->section('Indicadores', $this->indicatorsTable($details, $documento));
        $html .= $this->section('Definiciones y Abreviaturas', $this->definitionsTable($documento['definiciones'] ?? []));
        $html .= $this->section('Diagrama de Flujo del Proceso', '<p style="text-align: center;">' . self::DIAGRAM_MARKER . '</p>');
        $html .= $this->section('Descripción del Proceso / Actividades', $this->activitiesBody($details, $documento));
        $html .= $this->section('Riesgos conocidos y errores frecuentes', $this->plainList($documento['riesgos'] ?? []));
        $html .= $this->section('Requerimientos normativos y legales', $this->plainList($documento['requerimientos'] ?? []));
        $html .= $this->section('Anexos', $this->annexesList($documento['anexos'] ?? []));
        $html .= $this->footer($companyName);

        return $html;
    }

    private function section(string $title, string $innerHtml): string
    {
        return '<p style="text-align: justify; margin-top: 10pt; margin-bottom: 4pt;">'
            . '<span style="color: #' . self::TITLE_COLOR . '; font-weight: bold; text-transform: uppercase;">'
            . $this->esc($title) . '</span></p>' . $innerHtml;
    }

    /** Caja con fondo gris-azulado, igual a Objetivo/Alcance/Detonante/Resultado en el documento de ejemplo. */
    private function box(string $text): string
    {
        $paragraphs = $this->paragraphsFrom($text, '#000000');

        return '<table style="width: 100%; border-collapse: collapse;"><tr>'
            . '<td bgcolor="#' . self::BOX_BG . '" style="border: 1px solid #000000; padding: 6px;">'
            . ($paragraphs !== '' ? $paragraphs : $this->paragraph('—', '#000000'))
            . '</td></tr></table>';
    }

    private function alcanceBox(array $details): string
    {
        $inner = '';

        if (! empty($details['areas_aplica'])) {
            $inner .= '<p style="text-align: justify; margin-bottom: 3pt;">'
                . '<span style="color: #000000; font-weight: bold;">Este procedimiento aplica a: </span>'
                . '<span style="color: #000000;">' . $this->esc($details['areas_aplica']) . '</span></p>';
        }

        if (! empty($details['fuera_alcance'])) {
            $inner .= '<p style="text-align: justify; margin-bottom: 4pt;">'
                . '<span style="color: #000000; font-weight: bold;">Queda fuera del alcance: </span>'
                . '<span style="color: #000000;">' . $this->esc($details['fuera_alcance']) . '</span></p>';
        }

        if ($inner === '') {
            $inner = $this->paragraph('—', '#000000');
        }

        return '<table style="width: 100%; border-collapse: collapse;"><tr>'
            . '<td bgcolor="#' . self::BOX_BG . '" style="border: 1px solid #000000; padding: 6px;">' . $inner . '</td>'
            . '</tr></table>';
    }

    /** @param  array<int, string>  $items */
    private function plainList(array $items): string
    {
        $items = array_values(array_filter(array_map('trim', $items), fn ($i) => $i !== ''));

        if ($items === []) {
            return $this->paragraph('—', '#000000');
        }

        return implode('', array_map(fn ($item) => $this->paragraph($item, '#000000'), $items));
    }

    private function indicatorsTable(array $details, array $documento): string
    {
        $header = ['TIPO', 'INDICADOR', 'FÓRMULA DE CÁLCULO', 'META / FRECUENCIA'];

        $rows = [
            [
                'Proceso',
                $details['indicador_proceso'] ?? '—',
                $documento['indicador_proceso_formula'] ?? '—',
                $this->metaFrecuenciaLines($details, $documento),
            ],
            [
                'Resultado',
                $details['indicador_resultado'] ?? '—',
                $documento['indicador_resultado_formula'] ?? '—',
                $this->metaFrecuenciaLines($details, $documento),
            ],
        ];

        // TIPO/INDICADOR son etiquetas cortas; FÓRMULA y META/FRECUENCIA suelen traer texto largo
        // (fórmulas, varias líneas) — sin este reparto quedan las 4 casi iguales y el texto largo
        // se ve aplastado en una columna angosta (una palabra por línea).
        return $this->dataTable($header, $rows, firstColBold: true, colWidthRatios: [0.12, 0.20, 0.34, 0.34]);
    }

    /** @return array<int, string> líneas apiladas dentro de la celda meta/frecuencia/responsable */
    private function metaFrecuenciaLines(array $details, array $documento): array
    {
        return array_values(array_filter([
            $details['meta_valor'] ?? null,
            $details['frecuencia_medicion'] ?? null,
            $documento['indicadores_responsable'] ?? null,
        ], fn ($v) => ! empty($v)));
    }

    /** @param  array<int, array{termino?: string, definicion?: string}>  $definiciones */
    private function definitionsTable(array $definiciones): string
    {
        $rows = array_map(fn ($d) => [$d['termino'] ?? '', $d['definicion'] ?? ''], $definiciones);

        if ($rows === []) {
            return $this->paragraph('—', '#000000');
        }

        return $this->dataTable(['TÉRMINO / ABREVIATURA', 'DEFINICIÓN'], $rows, firstColBold: true, colWidthRatios: [0.25, 0.75]);
    }

    private function activitiesBody(array $details, array $documento): string
    {
        $html = '';

        if (! empty($details['que_detona'])) {
            $html .= '<p style="text-align: justify; margin-bottom: 4pt;"><span style="color: #000000; font-weight: bold;">Detonante del proceso:</span></p>';
            $html .= $this->box($details['que_detona']);
        }

        $pasos = $documento['pasos'] ?? [];
        foreach ($pasos as $i => $paso) {
            $html .= $this->stepHtml($i + 1, $paso);
        }

        if (! empty($details['resultado_entregable'])) {
            $html .= '<p>&nbsp;</p>' . $this->box($details['resultado_entregable']);
        }

        return $html;
    }

    private function stepHtml(int $numero, array $paso): string
    {
        $titulo = trim(($paso['titulo'] ?? '') !== '' ? $paso['titulo'] : 'Paso');
        $html = '<p style="text-align: justify; margin-top: 10pt; margin-bottom: 3pt;">'
            . '<span style="color: #' . self::STEP_TITLE_COLOR . '; font-weight: bold;">Paso ' . $numero . '. ' . $this->esc($titulo) . '</span></p>';

        if (! empty($paso['responsable'])) {
            $html .= '<p style="text-align: justify; margin-bottom: 4pt;">'
                . '<span style="font-size: 10pt; color: #' . self::RESPONSIBLE_COLOR . '; font-style: italic;">Responsable: '
                . $this->esc($paso['responsable']) . '</span></p>';
        }

        foreach ([
            'quien' => 'Quién',
            'que' => 'Qué',
            'como' => 'Cómo',
            'cuando' => 'Cuándo',
            'donde' => 'Dónde',
            'excepcion' => 'Excepción',
        ] as $key => $label) {
            if (! empty($paso[$key])) {
                $html .= '<p style="text-align: justify; margin-bottom: 4pt;">'
                    . '<span style="color: #000000; font-weight: bold;">' . $label . ': </span>'
                    . '<span style="color: #000000;">' . $this->esc($paso[$key]) . '</span></p>';
            }
        }

        if (! empty($paso['evidencia'])) {
            $html .= '<p style="text-align: justify; margin-top: 4pt; margin-bottom: 5pt;">'
                . '<span style="font-size: 10pt; color: #' . self::STEP_TITLE_COLOR . '; font-weight: bold;">Evidencia requerida: </span>'
                . '<span style="font-size: 10pt; color: #000000;">' . $this->esc($paso['evidencia']) . '</span></p>';
        }

        return $html . '<p>&nbsp;</p>';
    }

    /** @param  array<int, array{codigo?: string, nombre?: string}>  $anexos */
    private function annexesList(array $anexos): string
    {
        $lines = array_map(function ($a) {
            $codigo = trim($a['codigo'] ?? '');
            $nombre = trim($a['nombre'] ?? '');

            return $codigo !== '' && $nombre !== '' ? "{$codigo} — {$nombre}" : ($codigo ?: $nombre);
        }, $anexos);

        return $this->plainList($lines);
    }

    private function footer(string $companyName): string
    {
        $companyName = $companyName !== '' ? $companyName : 'la empresa';

        return '<p style="text-align: justify; margin-top: 10pt; margin-bottom: 4pt;">'
            . '<span style="font-size: 9pt; color: #' . self::FOOTER_COLOR . '; font-style: italic;">AVISO DE CONTROL: Este documento es propiedad de '
            . $this->esc($companyName) . '. Su contenido es confidencial y de uso interno. Cualquier copia impresa se considera NO CONTROLADA. '
            . 'Para la versión vigente, consultar el sistema de control de documentos.</span></p>'
            . '<p style="text-align: center; margin-bottom: 4pt;">'
            . '<span style="font-size: 9pt; color: #' . self::FOOTER_COLOR . '; font-style: italic;">Documento controlado — Prohibida su reproducción parcial o total sin autorización '
            . '| Versión impresa no controlada. Verifique vigencia en el sistema.</span></p>';
    }

    /**
     * Tabla de datos con encabezado azul marino/texto blanco 9pt y filas de datos negras 10pt —
     * mismo estilo que Indicadores/Definiciones/Historial en el documento de ejemplo.
     *
     * @param  array<int, string>  $headerCols
     * @param  array<int, array<int, string|array<int, string>>>  $rows  Cada celda puede ser un string o un array de líneas apiladas.
     * @param  array<int, float>  $colWidthRatios  Proporción de cada columna (no necesitan sumar 1) —
     *                                             ver el comentario de CONTENT_WIDTH_PT: sin esto, todas
     *                                             las columnas terminan casi igual de angostas sin
     *                                             importar cuánto texto tengan.
     */
    private function dataTable(array $headerCols, array $rows, bool $firstColBold = false, array $colWidthRatios = []): string
    {
        $colCount = count($headerCols);
        $ratios = $colWidthRatios !== [] ? $colWidthRatios : array_fill(0, $colCount, 1);
        $ratioSum = array_sum($ratios) ?: 1;
        $colWidthsPt = array_map(
            fn ($ratio) => round(self::CONTENT_WIDTH_PT * $ratio / $ratioSum),
            $ratios
        );

        $html = '<table style="width: 100%; border-collapse: collapse;"><tr>';

        foreach ($headerCols as $i => $col) {
            $html .= '<td bgcolor="#' . self::TABLE_HEADER_BG . '" style="border: 1px solid #000000; padding: 4px; width: ' . $colWidthsPt[$i] . 'pt;">'
                . '<p style="text-align: center; margin: 0;"><span style="font-size: 9pt; color: #FFFFFF; font-weight: bold;">'
                . $this->esc($col) . '</span></p></td>';
        }
        $html .= '</tr>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (array_values($row) as $i => $cell) {
                $lines = is_array($cell) ? $cell : [$cell];
                $bold = $firstColBold && $i === 0;
                $align = $i === 0 ? 'center' : 'justify';

                $cellHtml = implode('', array_map(
                    fn ($line) => '<p style="text-align: ' . $align . '; margin: 0;">'
                        . '<span style="font-size: 10pt; color: #000000;' . ($bold ? ' font-weight: bold;' : '') . '">'
                        . $this->esc((string) $line) . '</span></p>',
                    $lines !== [] ? $lines : ['—']
                ));

                $html .= '<td bgcolor="#FFFFFF" style="border: 1px solid #000000; padding: 4px; width: ' . ($colWidthsPt[$i] ?? round(self::CONTENT_WIDTH_PT / $colCount)) . 'pt;">' . $cellHtml . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</table>';
    }

    private function paragraphsFrom(string $text, string $color): string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== ''));

        return implode('', array_map(fn ($l) => $this->paragraph($l, $color), $lines));
    }

    private function paragraph(string $text, string $color): string
    {
        return '<p style="text-align: justify; margin-top: 0; margin-bottom: 0;">'
            . '<span style="color: ' . $color . ';">' . $this->esc($text) . '</span></p>';
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
