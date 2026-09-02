<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\OutputConfig;
use Illuminate\Support\Facades\Log;

/**
 * A partir de la justificación de cambio que el usuario captura al editar un documento
 * (texto libre, casi siempre una lista de instrucciones puntuales), genera con IA una tabla
 * que separa cada instrucción y cita el fragmento del documento nuevo donde quedó atendida —
 * para que quien aprueba pueda verificar de un vistazo que cada punto solicitado sí se aplicó.
 *
 * Complementa a RegulationChangeDiffService (que compara secciones completas, sin IA): aquél
 * muestra "qué texto cambió", este servicio muestra "qué se pidió y dónde quedó atendido".
 *
 * Usa el modelo barato/rápido (config('services.anthropic.change_model')) porque es un mapeo
 * de texto corto, no la redacción completa de un procedimiento como AiProcedureGenerationService.
 * Nunca lanza: si falla, quien llama debe tratar el resultado null como "no se pudo generar" y
 * seguir guardando la versión sin bloquear al usuario por esto.
 */
class RegulationChangeTableService
{
    public function __construct(
        private readonly RegulationChangeDiffService $diffService,
    ) {}

    /**
     * @return array{rows: array<int, array{modificacion: string, texto_incorporado: string}>}|null
     */
    public function generate(
        ?string $changeJustification,
        ?string $changeDescription,
        ?string $oldHtml,
        string $newHtml,
    ): ?array {
        $changeJustification = trim((string) $changeJustification);

        if ($changeJustification === '') {
            return null;
        }

        try {
            $client = new Client(apiKey: config('services.anthropic.key'));
            $model = config('services.anthropic.change_model');

            $message = $client->messages->create(
                model: $model,
                maxTokens: 4000,
                system: 'Comparas la justificación de un cambio a un documento contra el documento ya '
                    . 'actualizado, y devuelves una tabla que ayuda a quien aprueba el documento a '
                    . 'verificar que cada punto solicitado sí quedó reflejado.',
                messages: [[
                    'role' => 'user',
                    'content' => $this->buildPrompt($changeJustification, $changeDescription, $oldHtml, $newHtml),
                ]],
                outputConfig: OutputConfig::with(format: JSONOutputFormat::with(schema: $this->schema())),
            );

            $textBlock = null;
            foreach ($message->content as $block) {
                if (($block->type ?? null) === 'text') {
                    $textBlock = $block;
                    break;
                }
            }

            $raw = $textBlock->text ?? null;
            $data = $raw !== null ? json_decode($raw, true) : null;

            if (! is_array($data) || ! isset($data['rows']) || ! is_array($data['rows']) || count($data['rows']) === 0) {
                Log::warning('RegulationChangeTableService: la IA no devolvió filas válidas', [
                    'stop_reason' => $message->stopReason ?? null,
                ]);

                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            Log::warning('RegulationChangeTableService: no se pudo generar la tabla de modificaciones', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildPrompt(string $changeJustification, ?string $changeDescription, ?string $oldHtml, string $newHtml): string
    {
        $parts = [
            'Justificación del cambio que capturó el usuario al guardar esta versión (casi siempre '
                . 'una lista de instrucciones puntuales, aunque puede venir sin numerar):',
            $changeJustification,
        ];

        if (! empty($changeDescription)) {
            $parts[] = 'Descripción corta del cambio que también capturó el usuario:';
            $parts[] = $changeDescription;
        }

        if (! empty($oldHtml)) {
            $parts[] = 'Texto plano del documento ANTES de este cambio:';
            $parts[] = $this->diffService->toPlainText($oldHtml);
        }

        $parts[] = 'Texto plano del documento DESPUÉS de este cambio (ya con las modificaciones aplicadas):';
        $parts[] = $this->diffService->toPlainText($newHtml);

        $parts[] = 'Separa la justificación en instrucciones/modificaciones individuales y discretas '
            . '(si ya viene numerada o en viñetas, respeta esa división; si es una sola oración o '
            . 'párrafo sin partes claramente separables, es una sola fila). Para cada una, busca en el '
            . 'documento DESPUÉS el fragmento de texto que la atiende y cítalo TEXTUALMENTE — nunca '
            . 'inventes ni parafrasees texto que no esté realmente en el documento. Si no encuentras un '
            . 'fragmento que atienda una instrucción, dilo explícitamente en vez de inventar uno '
            . '(ej. "No se identificó un cambio correspondiente en el documento").';

        return implode("\n\n", $parts);
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'rows' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'modificacion' => [
                                'type' => 'string',
                                'description' => 'Una instrucción/modificación puntual, tal como se solicitó (puede resumirse, sin cambiar su sentido).',
                            ],
                            'texto_incorporado' => [
                                'type' => 'string',
                                'description' => 'Cita textual del fragmento del documento nuevo donde se atendió esa instrucción, o una nota indicando que no se encontró un cambio correspondiente.',
                            ],
                        ],
                        'required' => ['modificacion', 'texto_incorporado'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['rows'],
            'additionalProperties' => false,
        ];
    }
}
