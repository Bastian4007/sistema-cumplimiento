<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Section;

/**
 * Encabezado fijo (tabla de 4 columnas x 2 filas, con el formato exacto ya aprobado por el
 * cliente) que debe aparecer idéntico en TODOS los documentos de Procesos, en todas las
 * páginas — nombre, código, versión, elaboró, aprobó, fecha de efectividad y número de página
 * automático.
 *
 * A propósito NO se le pide esto a la IA en cada generación: por más detalladas que sean las
 * instrucciones, no hay garantía de que el modelo replique exactamente el mismo diseño dos
 * veces (colores, bordes, estructura de tabla), y cualquier variación rompe el formato ya
 * establecido con el cliente. Construirlo aquí, con los datos reales del documento, lo deja
 * 100% consistente siempre — y como se agrega vía `Section::addHeader()`, Word lo repite
 * nativamente en cada página sin que el HTML del cuerpo tenga que mencionarlo.
 */
class RegulationDocxHeaderBuilder
{
    // Colores fijados según la especificación exacta documentada en
    // resources/ai-reference/texto_validacion.md y documento_condiciones.docx: fila 1 azul
    // marino con texto blanco, fila 2 gris claro con etiquetas en azul marino, bordes #1A3A5C.
    private const NAVY_BG = '002060';
    private const GRAY_BG = 'F2F3F4';
    private const NAVY_TEXT = '002060';
    private const BORDER = '1A3A5C';

    /**
     * @param  array{nombre: string, codigo: ?string, version: int|string, quien_elabora: ?string, quien_aprueba: ?string, fecha_vigencia: ?string}  $meta
     */
    public function apply(Section $section, array $meta): void
    {
        $header = $section->addHeader();

        $table = $header->addTable([
            'borderColor' => self::BORDER,
            'borderSize'  => 4,
            'unit'        => 'pct',
            'width'       => 100 * 50,
            'cellMargin'  => 80,
        ]);

        // Fila 1: logo / nombre del procedimiento / código / versión — fondo azul marino, texto blanco
        $table->addRow();
        $this->logoCell($table);
        $this->cell($table, self::NAVY_BG, 'FFFFFF', 'FFFFFF', 'PROCEDIMIENTO', $meta['nombre'] ?? '', italicValue: true);
        $this->cell($table, self::NAVY_BG, 'FFFFFF', 'FFFFFF', 'CÓDIGO', $meta['codigo'] ?? '—');
        $this->cell($table, self::NAVY_BG, 'FFFFFF', 'FFFFFF', 'VERSIÓN', (string) ($meta['version'] ?? '01'));

        // Fila 2: elaboró / aprobó / vigencia / página — fondo gris claro, etiquetas azul marino, valores negros
        $table->addRow();
        $this->cell($table, self::GRAY_BG, self::NAVY_TEXT, '000000', 'ELABORADO POR:', $meta['quien_elabora'] ?? '—');
        $this->cell($table, self::GRAY_BG, self::NAVY_TEXT, '000000', 'APROBADO POR:', $meta['quien_aprueba'] ?? '—');
        $this->cell($table, self::GRAY_BG, self::NAVY_TEXT, '000000', 'Fecha de elaboración:', $this->formatFecha($meta['fecha_vigencia'] ?? null));
        $this->pageNumberCell($table);
    }

    private function logoCell($table): void
    {
        $cell = $table->addCell(2500, ['bgColor' => self::NAVY_BG, 'valign' => 'center']);
        $cell->addText('LOGO EMPRESA', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => 'center']);
        $cell->addText('(insertar logotipo)', ['italic' => true, 'size' => 8, 'color' => 'FFFFFF'], ['alignment' => 'center']);
    }

    private function cell($table, string $bgColor, string $labelColor, string $valueColor, string $label, string $value, bool $italicValue = false): void
    {
        $cell = $table->addCell(2500, ['bgColor' => $bgColor, 'valign' => 'center']);
        $cell->addText($label, ['bold' => true, 'size' => 9, 'color' => $labelColor], ['alignment' => 'center']);
        $cell->addText($value, ['italic' => $italicValue, 'size' => 8, 'color' => $valueColor], ['alignment' => 'center']);
    }

    private function pageNumberCell($table): void
    {
        $cell = $table->addCell(2500, ['bgColor' => self::GRAY_BG, 'valign' => 'center']);
        $cell->addText('Página:', ['bold' => true, 'size' => 9, 'color' => self::NAVY_TEXT], ['alignment' => 'center']);

        $run = $cell->addTextRun(['alignment' => 'center']);
        $run->addField('PAGE', [], [], null);
        $run->addText(' de ', ['size' => 8, 'color' => '000000']);
        $run->addField('NUMPAGES', [], [], null);
    }

    private function formatFecha(?string $fecha): string
    {
        if (empty($fecha)) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable) {
            return $fecha;
        }
    }
}
