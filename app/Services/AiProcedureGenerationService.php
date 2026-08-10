<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\OutputConfig;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;

class AiProcedureGenerationService
{
    /** Word largo con las condiciones/instrucciones de redacción. Dentro de su texto remite al documento de ejemplo. */
    private const CONDITIONS_DOCX = 'documento_condiciones.docx';

    /** Documento de ejemplo que muestra cómo debe quedar el resultado final (formato/estilo de referencia). */
    private const EXAMPLE_DOCX = 'documento_ejemplo.docx';

    /** Texto de validación/instrucciones adicional (system prompt). */
    private const VALIDATION_TEXT = 'texto_validacion.md';

    /** Imagen de ejemplo del diagrama de flujo por carriles — se manda como referencia visual (Claude ve imágenes). */
    private const DIAGRAM_EXAMPLE_IMAGE = 'diagrama_ejemplo.png';

    /** Marcador que la IA debe dejar en documento_html donde va el diagrama — se reemplaza por la imagen ya renderizada. */
    private const DIAGRAM_MARKER = '{{DIAGRAMA_FLUJO}}';

    /**
     * Factor de escala (Puppeteer deviceScaleFactor) con el que mermaid-cli captura el diagrama.
     * El diagrama siempre se muestra al mismo tamaño físico en el documento (imageDimensionAttrs()
     * lo topa a 624px), así que subir este factor no lo agranda — solo aumenta la densidad de
     * píxeles, evitando que se vea borroso/pixelado al imprimir o hacer zoom en el PDF.
     */
    private const DIAGRAM_RENDER_SCALE = 3;

    public const DETAIL_FIELDS = [
        'problema_resuelve', 'resultado_esperado', 'areas_aplica', 'fuera_alcance',
        'indicador_proceso', 'indicador_resultado', 'meta_valor', 'frecuencia_medicion',
        'que_detona', 'lista_actividades', 'areas_ejecutan', 'decisiones_control',
        'documentos_usados', 'resultado_entregable', 'areas_roles_mapa',
        'procedimientos_relacionados', 'proveedores_clientes', 'terminos_abreviaturas',
        'riesgos_errores', 'requerimientos_normativos',
    ];

    public function __construct(
        private readonly RegulationBodyHtmlBuilder $bodyBuilder,
        private readonly MermaidDiagramStyler $diagramStyler,
        private readonly DiagramTitleBarComposer $titleBarComposer,
    ) {}

    /**
     * @param  array<string, string>  $wizardData  Campos capturados por el wizard (el esqueleto).
     * @param  array{details: array<string, string>, documento: array<string, mixed>, documento_html: string}|null  $previousResult  Resultado anterior, si esta es una revisión.
     * @param  string|null  $feedback  Cambios solicitados por el usuario sobre $previousResult.
     * @param  string  $companyName  Nombre de la empresa, para el aviso de control al pie del documento.
     * @param  string  $documentName  Nombre del procedimiento, para la barra de título del diagrama de flujo.
     * @param  string  $documentCode  Código del procedimiento, para la barra de título del diagrama de flujo.
     * @return array{details: array<string, string>, documento: array<string, mixed>, documento_html: string, diagrama_flujo_mermaid: ?string}
     */
    public function generate(
        array $wizardData,
        ?array $previousResult = null,
        ?string $feedback = null,
        string $companyName = '',
        string $documentName = '',
        string $documentCode = '',
    ): array {
        $client = new Client(apiKey: config('services.anthropic.key'));
        $model = config('services.anthropic.model');
        $startedAt = microtime(true);

        $message = $client->messages->create(
            model: $model,
            maxTokens: 24000,
            system: $this->validationText(),
            messages: [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => 'image/png',
                            'data' => base64_encode($this->diagramExampleImage()),
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $this->buildPrompt($wizardData, $previousResult, $feedback),
                    ],
                ],
            ]],
            outputConfig: OutputConfig::with(format: JSONOutputFormat::with(schema: $this->schema())),
        );

        Log::info('AiProcedureGenerationService: generación completada', [
            'model' => $model,
            'revision' => $previousResult !== null,
            'seconds' => round(microtime(true) - $startedAt, 2),
            'input_tokens' => $message->usage->inputTokens ?? null,
            'output_tokens' => $message->usage->outputTokens ?? null,
            'stop_reason' => $message->stopReason ?? null,
        ]);

        // Con adaptive thinking, el primer bloque puede ser "thinking" en vez de "text" — buscamos el bloque de texto explícitamente.
        $textBlock = null;
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $textBlock = $block;
                break;
            }
        }

        $raw = $textBlock->text ?? null;

        if ($raw === null) {
            throw new RuntimeException('La IA no devolvió contenido para el procedimiento.');
        }

        $data = json_decode($raw, true);

        if (! is_array($data) || ! isset($data['details'], $data['documento'])) {
            throw new RuntimeException('La IA devolvió una respuesta en un formato inesperado (stop_reason: ' . ($message->stopReason ?? 'desconocido') . ').');
        }

        // El formato (colores, tablas, tipografía) NUNCA lo decide la IA — solo redactó texto en
        // "details"/"documento". El HTML final lo arma este builder de forma 100% determinista,
        // igual que RegulationDocxHeaderBuilder ya hace con el encabezado.
        $data['documento_html'] = $this->bodyBuilder->build($data['details'], $data['documento'], $companyName);
        $data['documento_html'] = $this->insertFlowDiagram(
            $data['documento_html'],
            $data['diagrama_flujo_mermaid'] ?? null,
            $documentName,
            $documentCode
        );
        $data['documento_html'] = $this->sanitizeHtmlForWord($data['documento_html']);

        return $data;
    }

    /**
     * Reemplaza el marcador {{DIAGRAMA_FLUJO}} por el diagrama ya renderizado como imagen
     * (Mermaid → mermaid-cli → PNG, con el estilo fijo de MermaidDiagramStyler y la barra de
     * título de DiagramTitleBarComposer encima — igual que el documento_ejemplo.docx). Si no
     * hay mermaid, la renderización falla, o el marcador no aparece (la IA no lo respetó), se
     * deja una nota simple en vez de bloquear la generación — el documento completo nunca debe
     * fallar solo por el diagrama.
     */
    private function insertFlowDiagram(string $html, ?string $mermaidSource, string $documentName = '', string $documentCode = ''): string
    {
        if (! str_contains($html, self::DIAGRAM_MARKER)) {
            return $html;
        }

        // El prompt le pide a la IA que escriba el marcador como <p>{{DIAGRAMA_FLUJO}}</p>.
        // El reemplazo (fallback o <img>) ya trae su propio wrapper de bloque, así que hay
        // que quitar ese <p> envolvente aquí — si no, queda <p><p>...</p></p> (o <p><img/></p>,
        // inofensivo, pero inconsistente), y PhpWord revienta con "Cannot add TextRun in
        // TextRun" al convertir a Word porque no tolera un <p> anidado dentro de otro <p>.
        $html = preg_replace(
            '/<p\b[^>]*>\s*' . preg_quote(self::DIAGRAM_MARKER, '/') . '\s*<\/p>/i',
            self::DIAGRAM_MARKER,
            $html
        );

        $fallback = '<p><em>(No se pudo generar el diagrama de flujo automáticamente.)</em></p>';

        if (empty($mermaidSource)) {
            return str_replace(self::DIAGRAM_MARKER, $fallback, $html);
        }

        // El color/forma de cada nodo y el fondo de los carriles NUNCA los decide la IA ni el
        // tema por defecto de Mermaid — se fuerzan aquí, igual que el resto del formato del documento.
        $styledMermaid = $this->diagramStyler->style($mermaidSource);
        $png = $this->renderMermaidDiagram($styledMermaid);

        if ($png !== null) {
            $steps = $this->diagramStyler->countActivitySteps($mermaidSource);
            $label = trim("{$documentCode} {$documentName}");
            $title = 'Diagrama de flujo' . ($label !== '' ? " — {$label}" : '') . " ({$steps} pasos)";
            $png = $this->titleBarComposer->addTitleBar($png, $title, self::DIAGRAM_RENDER_SCALE);
        }

        $replacement = $png !== null
            ? '<img src="data:image/png;base64,' . base64_encode($png) . '"' . $this->imageDimensionAttrs($png) . ' />'
            : $fallback;

        return str_replace(self::DIAGRAM_MARKER, $replacement, $html);
    }

    /**
     * Devuelve los atributos width/height (en px) que hay que ponerle al <img> del diagrama para
     * que quepa en el ancho de contenido de la página — PhpWord\Shared\Html::addHtml() SOLO lee
     * las dimensiones de una imagen de los atributos width/height del <img>, nunca de su style=""
     * (por eso el "max-width:100%" que se usaba antes no hacía nada: el diagrama se insertaba a su
     * ancho nativo en píxeles, casi siempre mucho más ancho que la página, y Word lo recortaba en
     * vez de encogerlo). El diagrama de mermaid-cli suele salir bastante más ancho que alto (varios
     * carriles en fila), así que casi siempre hay que reducir el ancho y mantener la proporción.
     */
    private function imageDimensionAttrs(string $png): string
    {
        $info = @getimagesizefromstring($png);

        if (! $info || empty($info[0]) || empty($info[1])) {
            return '';
        }

        [$width, $height] = $info;

        // 624px = 6.5in de contenido en Carta (mismo ancho que RegulationBodyHtmlBuilder::CONTENT_WIDTH_PT
        // representa en puntos: 468pt / 72pt-por-in = 6.5in), convertido a la equivalencia de 96px/in
        // que PhpWord asume para los atributos width/height del <img> (Converter::INCH_TO_PIXEL).
        $maxWidth = 624;

        if ($width > $maxWidth) {
            $height = (int) round($height * ($maxWidth / $width));
            $width = $maxWidth;
        }

        return sprintf(' width="%d" height="%d"', $width, $height);
    }

    /**
     * Convierte una definición de diagrama Mermaid a PNG con mermaid-cli (@mermaid-js/mermaid-cli,
     * instalado como dependencia del proyecto en package.json — no globalmente, para no depender
     * del PATH del usuario/servicio que corra PHP). Devuelve null en vez de lanzar una excepción
     * si falla, para no bloquear todo el documento.
     *
     * Antes esto se hacía vía Kroki.io (servicio público gratuito, sin SLA), que renderiza Mermaid
     * lanzando un Chromium headless en SU servidor compartido — y ese lanzamiento fallaba
     * intermitentemente por falta de recursos ahí (confirmado en pruebas: ~40% de fallas bajo
     * ráfagas de peticiones). Renderizar localmente con mermaid-cli (que también usa Puppeteer/
     * Chromium, pero corriendo en nuestro propio servidor) elimina esa dependencia externa.
     */
    private function renderMermaidDiagram(string $mermaidSource): ?string
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $input = tempnam(sys_get_temp_dir(), 'mmd_in_') . '.mmd';
            $output = tempnam(sys_get_temp_dir(), 'mmd_out_') . '.png';
            file_put_contents($input, $mermaidSource);

            try {
                [$exitCode, $stdout, $stderr] = $this->runMermaidCli($input, $output);

                if ($exitCode === 0 && is_file($output)) {
                    return file_get_contents($output);
                }

                Log::warning('AiProcedureGenerationService: mermaid-cli no pudo renderizar el diagrama', [
                    'attempt' => $attempt,
                    'exit_code' => $exitCode,
                    'output' => $stderr ?: $stdout,
                    'mermaid' => $mermaidSource,
                ]);
            } catch (\Throwable $e) {
                Log::warning('AiProcedureGenerationService: fallo al renderizar el diagrama de flujo', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                @unlink($input);
                @unlink($output);
            }

            if ($attempt < $maxAttempts) {
                usleep(500_000);
            }
        }

        return null;
    }

    /**
     * Se invoca con proc_open() nativo de PHP en vez de Illuminate\Support\Facades\Process a
     * propósito: en Windows (confirmado en producción y en local, con Node 22 LTS y con Node 24,
     * y sin importar si se llama al binario node_modules/.bin/mmdc.cmd o a "node" + el script
     * directamente), Symfony Process hace que Node truene al arrancar con "Assertion failed:
     * ncrypto::CSPRNG" — Node ni siquiera llega a ejecutar una línea del script. La MISMA llamada
     * vía proc_open()/shell_exec() nativo, en el mismo proceso PHP, funciona sin problema. No se
     * investigó más a fondo por qué Symfony Process dispara esto (aparenta ser una interacción
     * rara con cómo arma el comando en Windows); proc_open es la vía que sí funciona.
     */
    private function runMermaidCli(string $input, string $output): array
    {
        $cliJs = base_path('node_modules/@mermaid-js/mermaid-cli/src/cli.js');
        $cmd = sprintf(
            'node %s -i %s -o %s -b white -s %d',
            escapeshellarg($cliJs),
            escapeshellarg($input),
            escapeshellarg($output),
            self::DIAGRAM_RENDER_SCALE
        );

        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (! is_resource($proc)) {
            return [1, '', 'No se pudo iniciar el proceso de mermaid-cli.'];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = microtime(true);
        $timeoutSeconds = 30;

        do {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            $status = proc_get_status($proc);

            if ($status['running'] && (microtime(true) - $start) > $timeoutSeconds) {
                proc_terminate($proc);
                $stderr .= "\n[mermaid-cli: tiempo de espera agotado tras {$timeoutSeconds}s]";
                break;
            }

            if ($status['running']) {
                usleep(100_000);
            }
        } while ($status['running']);

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        return [$exitCode, $stdout, $stderr];
    }

    /**
     * Prueba real de renderizado, reusando exactamente el mismo camino (proc_open) que usa
     * insertFlowDiagram() en producción — a diferencia de un chequeo con exec()/Process, esto
     * detecta con certeza si el render de verdad funciona en este servidor, no solo si el
     * binario existe. Pensada para diagnóstico (processes:check-requirements --deep).
     *
     * @return array{ok: bool, exit_code: int, stderr: string, stdout: string}
     */
    public function testMermaidCli(): array
    {
        $input = tempnam(sys_get_temp_dir(), 'mmd_check_') . '.mmd';
        $output = tempnam(sys_get_temp_dir(), 'mmd_check_out_') . '.png';
        file_put_contents($input, "flowchart LR\n  a([Inicio]) --> b[Paso] --> c([Fin])\n");

        try {
            [$exitCode, $stdout, $stderr] = $this->runMermaidCli($input, $output);
            $ok = $exitCode === 0 && is_file($output) && filesize($output) > 0;

            return ['ok' => $ok, 'exit_code' => $exitCode, 'stderr' => trim($stderr), 'stdout' => trim($stdout)];
        } finally {
            @unlink($input);
            @unlink($output);
        }
    }

    private function diagramExampleImage(): string
    {
        $path = resource_path('ai-reference/' . self::DIAGRAM_EXAMPLE_IMAGE);

        if (! file_exists($path)) {
            throw new RuntimeException("Falta la imagen de ejemplo del diagrama en {$path}. Colócala antes de generar procedimientos con IA.");
        }

        return file_get_contents($path);
    }

    /**
     * PhpWord parsea el HTML como XML estricto, mucho menos tolerante que un navegador:
     * - Etiquetas vacías como <br> o <hr> deben autocerrarse (<br/>).
     * - Un mismo atributo (típicamente "style") no puede repetirse en la misma etiqueta
     *   — el modelo lo hace sobre todo al revisar (ej. agrega un segundo style="..." en
     *   vez de fusionarlo con el existente), y eso rompe DOMDocument::loadXML().
     * - Un "<" o "&" sueltos en el texto (ej. "100% en < 24 h", común en tablas de
     *   indicadores) rompen el .docx de forma más sutil: PhpWord 1.1.0 tiene un bug real
     *   donde, aunque el HTML de entrada venga bien escapado ("&lt;"), internamente lo
     *   decodifica al caracter literal y al ESCRIBIR el .docx no lo vuelve a escapar —
     *   el archivo queda con XML inválido y ni "Ver" ni "Editar" pueden volver a abrirlo
     *   (confirmado con pruebas aisladas contra el pipeline real de PhpWord). Se
     *   reemplazan por el caracter Unicode de ancho completo equivalente (se ve idéntico,
     *   no tiene significado especial en XML) en vez de intentar escaparlos mejor.
     * También se quita cualquier <script>, ya que este HTML se muestra en el navegador
     * durante la vista previa antes de convertirse a Word.
     */
    public function sanitizeHtmlForWord(string $html): string
    {
        $html = $this->stripPageWrapper($html);
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);
        // El editor TipTap (extension-table) agrega <colgroup><col ...></colgroup> a cada tabla
        // para recordar el ancho de columnas — PhpWord no lo necesita (las tablas del generador de
        // IA nunca lo llevan) y su <col> sin autocerrar rompe DOMDocument::loadXML() con "Opening
        // and ending tag mismatch: col ... colgroup" (confirmado guardando una edición real con
        // una tabla). Se quita entero en vez de intentar arreglarlo: la tabla queda igual, solo
        // sin el ancho de columna fijo en px, que Word calcula solo de todos modos.
        $html = preg_replace('/<colgroup\b[^>]*>.*?<\/colgroup>/si', '', $html);
        $html = $this->escapeStrayCharsForPhpWord($html);
        $html = $this->dedupeTagAttributes($html);

        return preg_replace_callback('/<(br|hr|img|col)\b([^>]*)>/i', function (array $m) {
            $attrs = rtrim($m[2]);

            return str_ends_with($attrs, '/')
                ? "<{$m[1]}{$attrs}>"
                : "<{$m[1]}{$attrs} />";
        }, $html);
    }

    /**
     * El schema/prompt prohíben explícitamente <!DOCTYPE>, <html>, <head> y <body> —
     * el modelo casi siempre lo respeta, pero de vez en cuando (más probable cuanto más
     * contenido lleva el prompt, ej. al agregar la imagen de referencia del diagrama)
     * los incluye de todos modos. Un <body> real confunde el parser de PhpWord y produce
     * "Cannot add TextRun in TextRun" al convertir a Word. Se quitan aquí en vez de
     * confiar únicamente en la instrucción del prompt.
     */
    private function stripPageWrapper(string $html): string
    {
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<head\b[^>]*>.*?<\/head>/si', '', $html);
        $html = preg_replace('/<\/?html\b[^>]*>/i', '', $html);

        return preg_replace('/<\/?body\b[^>]*>/i', '', $html);
    }

    /**
     * Reemplaza "<" y "&" sueltos (no parte de una etiqueta o entidad real) — y las
     * entidades "&lt;"/"&amp;" ya escapadas, que de todos modos se decodifican al
     * caracter literal antes de llegar al bug de escritura de PhpWord — por su
     * equivalente Unicode de ancho completo. No toca tags reales ("<table>", "</p>")
     * ni otras entidades (acentos, "&nbsp;", etc.), que no disparan el bug.
     */
    private function escapeStrayCharsForPhpWord(string $html): string
    {
        $html = str_replace(['&lt;', '&amp;'], ['＜', '＆'], $html);
        $html = preg_replace('/&(?!(?:[a-zA-Z][a-zA-Z0-9]*|#[0-9]+|#x[0-9a-fA-F]+);)/', '＆', $html);

        return preg_replace('/<(?![a-zA-Z\/!?])/', '＜', $html);
    }

    /**
     * Si una etiqueta repite un atributo (ej. dos "style"), lo fusiona en uno solo
     * (concatenando declaraciones para "style"; el resto se queda con el último valor).
     */
    private function dedupeTagAttributes(string $html): string
    {
        return preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)((?:\s+[^<>]*)?)>/', function (array $m) {
            $tag = $m[1];
            $rest = $m[2];

            $selfClosing = (bool) preg_match('/\/\s*$/', $rest);
            $rest = preg_replace('/\/\s*$/', '', $rest);

            preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*"([^"]*)"/', $rest, $attrMatches, PREG_SET_ORDER);

            if (count($attrMatches) === 0) {
                return $m[0];
            }

            $merged = [];
            foreach ($attrMatches as $attrMatch) {
                $name = strtolower($attrMatch[1]);
                $value = $attrMatch[2];

                $merged[$name] = isset($merged[$name]) && $name === 'style'
                    ? rtrim($merged[$name], '; ') . '; ' . $value
                    : $value;
            }

            $out = '<' . $tag;
            foreach ($merged as $name => $value) {
                $out .= ' ' . $name . '="' . $value . '"';
            }

            return $out . ($selfClosing ? ' />' : '>');
        }, $html);
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'details' => [
                    'type' => 'object',
                    'properties' => array_fill_keys(self::DETAIL_FIELDS, [
                        'type' => 'string',
                        'description' => 'Contenido afinado de este campo del procedimiento, en español, listo para publicarse.',
                    ]),
                    'required' => self::DETAIL_FIELDS,
                    'additionalProperties' => false,
                ],
                'documento' => [
                    'type' => 'object',
                    'description' => 'Contenido adicional del cuerpo del documento, SOLO texto plano (nada de HTML, colores ni '
                        . 'estilos — el sistema arma el documento final con un formato fijo idéntico siempre, tú solo escribes el contenido).',
                    'properties' => [
                        'topicos' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Entre 4 y 7 líneas cortas que resumen, a modo de índice de temas, lo que cubre el '
                                . 'documento (qué prestaciones/actividades/indicadores/riesgos toca) — cada elemento es una línea, texto plano.',
                        ],
                        'indicador_proceso_formula' => [
                            'type' => 'string',
                            'description' => 'Fórmula de cálculo del indicador de proceso (details.indicador_proceso), en texto plano. Ej: "(Núm. de trámites en tiempo / Total de trámites del periodo) × 100".',
                        ],
                        'indicador_resultado_formula' => [
                            'type' => 'string',
                            'description' => 'Fórmula de cálculo del indicador de resultado (details.indicador_resultado), en texto plano.',
                        ],
                        'indicadores_responsable' => [
                            'type' => 'string',
                            'description' => 'Puesto responsable de dar seguimiento a ambos indicadores (ej. "Gerente de Compras"). Tómalo de details.areas_ejecutan o details.areas_aplica.',
                        ],
                        'definiciones' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'termino' => ['type' => 'string', 'description' => 'Término o abreviatura, tal como se usa en el documento.'],
                                    'definicion' => ['type' => 'string', 'description' => 'Definición completa del término.'],
                                ],
                                'required' => ['termino', 'definicion'],
                                'additionalProperties' => false,
                            ],
                            'description' => 'Expande details.terminos_abreviaturas en una fila por término/abreviatura, incluyendo también los que uses en los pasos (campo "pasos") aunque no estén en ese texto.',
                        ],
                        'pasos' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'titulo' => ['type' => 'string', 'description' => 'Título corto del paso, SIN el número (el sistema numera automáticamente, ej. "Recepción y verificación del expediente").'],
                                    'responsable' => ['type' => 'string', 'description' => 'Puesto/área responsable de ejecutar este paso.'],
                                    'quien' => ['type' => 'string', 'description' => 'Quién ejecuta la actividad.'],
                                    'que' => ['type' => 'string', 'description' => 'Qué se hace en este paso.'],
                                    'como' => ['type' => 'string', 'description' => 'Cómo se hace (herramienta/sistema/formato usado).'],
                                    'cuando' => ['type' => 'string', 'description' => 'Cuándo se hace (plazo/momento).'],
                                    'donde' => ['type' => 'string', 'description' => 'Dónde se hace o dónde queda archivado.'],
                                    'excepcion' => ['type' => 'string', 'description' => 'Qué hacer si algo falla, no aplica o hay una excepción en este paso. Cadena vacía si no aplica.'],
                                    'evidencia' => ['type' => 'string', 'description' => 'Evidencia/documento que debe quedar archivado tras completar este paso.'],
                                ],
                                'required' => ['titulo', 'responsable', 'quien', 'que', 'como', 'cuando', 'donde', 'excepcion', 'evidencia'],
                                'additionalProperties' => false,
                            ],
                            'description' => 'Expande details.lista_actividades en un paso por actividad (mismo orden), usando details.areas_ejecutan para responsable/quién, details.decisiones_control para excepciones, y details.documentos_usados para la evidencia de cada paso.',
                        ],
                        'riesgos' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Expande details.riesgos_errores en una lista de riesgos/errores puntuales, uno por elemento.',
                        ],
                        'requerimientos' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Expande details.requerimientos_normativos en una lista de requerimientos puntuales, uno por elemento.',
                        ],
                        'anexos' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'codigo' => ['type' => 'string', 'description' => 'Código del formato/documento (ej. "F-COM-001"). Cadena vacía si no tiene código.'],
                                    'nombre' => ['type' => 'string', 'description' => 'Nombre del documento, formato o procedimiento relacionado.'],
                                ],
                                'required' => ['codigo', 'nombre'],
                                'additionalProperties' => false,
                            ],
                            'description' => 'A partir de details.documentos_usados y details.procedimientos_relacionados: cada documento/formato/procedimiento mencionado en el cuerpo, con su código si tiene uno.',
                        ],
                    ],
                    'required' => [
                        'topicos', 'indicador_proceso_formula', 'indicador_resultado_formula', 'indicadores_responsable',
                        'definiciones', 'pasos', 'riesgos', 'requerimientos', 'anexos',
                    ],
                    'additionalProperties' => false,
                ],
                'diagrama_flujo_mermaid' => [
                    'type' => 'string',
                    'description' => 'El diagrama de flujo de ESTE procedimiento en sintaxis Mermaid, imitando el estilo visual '
                        . 'de la imagen de referencia adjunta (carriles por puesto/responsable, óvalos de inicio/fin, pasos '
                        . 'numerados, rombos de decisión con ramas Sí/No). Debe empezar con "flowchart LR" y usar un '
                        . '"subgraph NOMBRE_CORTO[\"Nombre del puesto\"]" con "direction TB" adentro por cada puesto '
                        . 'involucrado, en el orden en que participan. No uses acentos ni caracteres especiales en los '
                        . 'IDs de nodos/subgraphs (sí puedes usarlos dentro de las etiquetas de texto entre corchetes/comillas).',
                ],
            ],
            'required' => ['details', 'documento', 'diagrama_flujo_mermaid'],
            'additionalProperties' => false,
        ];
    }

    private function buildPrompt(array $wizardData, ?array $previousResult = null, ?string $feedback = null): string
    {
        $parts = [
            'La imagen adjunta es un EJEMPLO de cómo debe verse el diagrama de flujo del procedimiento — carriles por '
                . 'puesto/responsable colocados lado a lado como columnas, óvalos de inicio y fin, pasos numerados dentro '
                . 'de cada carril, rombos de decisión con ramas Sí/No, flechas que cruzan de un carril a otro cuando cambia '
                . 'el responsable. Genera el diagrama de ESTE procedimiento (campo diagrama_flujo_mermaid) siguiendo ese '
                . 'mismo estilo visual, con los pasos y responsables reales de este procedimiento — no copies el contenido '
                . 'del ejemplo, solo su forma.',

            'El usuario capturó el siguiente esqueleto de procedimiento en un wizard. Es el punto de partida, en formato JSON:',
            json_encode($wizardData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),

            'Documento con las condiciones e instrucciones de redacción que debes seguir para el CONTENIDO '
                . '(no para el formato — el formato visual del documento final NUNCA lo decides tú, ver más abajo). '
                . 'Dentro de su propio texto, este documento hace referencia al "documento de ejemplo" '
                . '(que se incluye después):',
            $this->docxToPlainText(self::CONDITIONS_DOCX, 'documento de condiciones'),

            'Documento de ejemplo: referencia de tono, nivel de detalle y calidad de redacción esperados '
                . '(NO de formato visual — el sistema ya reproduce el formato exacto del documento de ejemplo '
                . 'de forma automática, por eso aquí solo debes fijarte en cómo está redactado el contenido, no en cómo se ve):',
            $this->docxToPlainText(self::EXAMPLE_DOCX, 'documento de ejemplo'),
        ];

        if ($previousResult !== null && $feedback !== null) {
            $parts[] = 'Ya redactaste una versión de este procedimiento con las fuentes anteriores. Este fue el resultado '
                . '(details + documento), en el mismo formato que debes devolver ahora:';
            $parts[] = json_encode([
                'details' => $previousResult['details'] ?? null,
                'documento' => $previousResult['documento'] ?? null,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $parts[] = 'El usuario revisó ese resultado y pidió estos cambios puntuales: "' . $feedback . '". '
                . 'Aplica ÚNICAMENTE los cambios solicitados sobre el resultado anterior. Todo lo que no se pidió cambiar '
                . 'debe quedar exactamente igual (mismo contenido, redacción y estructura). Devuelve los campos '
                . 'completos y actualizados (details + documento), no solo la parte modificada.';
        } else {
            $parts[] = 'Con las tres fuentes anteriores (esqueleto del wizard, condiciones, ejemplo), afina cada campo del '
                . 'esqueleto en "details" y redacta el contenido estructurado en "documento" (pasos, definiciones, '
                . 'indicadores, riesgos, requerimientos, anexos, tópicos), siguiendo también las instrucciones del system prompt.';
        }

        $parts[] = 'Recuerda: "details" y "documento" son SOLO texto plano — nunca escribas HTML, colores, ni decidas '
            . 'ningún aspecto de formato/diseño. El sistema arma el documento final (encabezado, tablas, colores, '
            . 'tipografía) siempre con el mismo formato fijo, idéntico al documento de ejemplo, a partir de este texto. '
            . 'Tu única responsabilidad es que el CONTENIDO sea correcto, completo y esté bien redactado.';

        return implode("\n\n", $parts);
    }

    private function docxToPlainText(string $filename, string $label): string
    {
        $path = resource_path('ai-reference/' . $filename);

        if (! file_exists($path)) {
            throw new RuntimeException("Falta el {$label} en {$path}. Colócalo antes de generar procedimientos con IA.");
        }

        $phpWord = IOFactory::load($path);
        $writer = IOFactory::createWriter($phpWord, 'HTML');
        $tmp = tempnam(sys_get_temp_dir(), 'phpword_ref_') . '.html';
        $writer->save($tmp);
        $html = file_get_contents($tmp);
        @unlink($tmp);

        $body = preg_match('/<body[^>]*>(.*?)<\/body>/si', $html, $m) ? $m[1] : $html;

        return trim(html_entity_decode(strip_tags($body)));
    }

    private function validationText(): string
    {
        $path = resource_path('ai-reference/' . self::VALIDATION_TEXT);

        if (! file_exists($path)) {
            throw new RuntimeException("Falta el texto de validación en {$path}. Colócalo antes de generar procedimientos con IA.");
        }

        return file_get_contents($path);
    }
}
