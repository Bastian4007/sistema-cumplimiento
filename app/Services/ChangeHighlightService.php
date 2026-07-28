<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\OutputConfig;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Compara la versión anterior de un documento contra la nueva y, con un modelo
 * pequeño (Haiku 4.5 por defecto — este análisis corre en cada guardado, así que
 * conviene que sea rápido y barato), resalta con <mark> lo que cambió y redacta
 * una descripción corta del cambio para el historial de versiones.
 *
 * No modifica nada si la IA falla o si el resultado no se puede confiar (ver isSafe) —
 * el guardado normal del documento nunca debe bloquearse por esto.
 */
class ChangeHighlightService
{
    /**
     * @return array{highlighted_html: string, change_summary: string}|null  null si no hay nada seguro que devolver
     */
    public function analyze(string $oldHtml, string $newHtml): ?array
    {
        if ($this->plainText($oldHtml) === $this->plainText($newHtml)) {
            return null;
        }

        // El diagrama de flujo (una imagen en base64) no tiene nada que ver con este análisis de
        // texto, pero puede pesar decenas de KB — confirmado con un documento real: 45 KB de un
        // documento de 70 KB era solo la imagen. Mandarla tal cual al modelo, pidiéndole que la
        // reproduzca íntegra y literal en la respuesta, es lento (la llamada real tardó >90s y
        // llegó a tronar por el límite de ejecución de PHP) e inseguro (un solo carácter distinto
        // en ese base64 rompe la imagen). Se reemplaza por un marcador corto antes de enviar, y se
        // restaura la imagen real al resultado después — la IA nunca la ve ni la reescribe.
        [$oldForPrompt] = $this->extractImages($oldHtml);
        [$newForPrompt, $newImages] = $this->extractImages($newHtml);

        try {
            $client = new Client(apiKey: config('services.anthropic.key'));
            $model = config('services.anthropic.change_model');

            $startedAt = microtime(true);

            $message = $client->messages->create(
                model: $model,
                // highlighted_html debe devolver el documento COMPLETO (no solo el cambio), así que el
                // límite tiene que escalar con el tamaño del documento, no ser un valor fijo chico —
                // con 8000 se truncaba (stop_reason=max_tokens) en documentos reales de varias páginas.
                maxTokens: 24000,
                system: $this->systemPrompt(),
                messages: [[
                    'role' => 'user',
                    'content' => $this->buildPrompt($oldForPrompt, $newForPrompt),
                ]],
                outputConfig: OutputConfig::with(format: JSONOutputFormat::with(schema: $this->schema())),
            );

            Log::info('ChangeHighlightService: análisis completado', [
                'model' => $model,
                'seconds' => round(microtime(true) - $startedAt, 2),
                'input_tokens' => $message->usage->inputTokens ?? null,
                'output_tokens' => $message->usage->outputTokens ?? null,
                'stop_reason' => $message->stopReason ?? null,
            ]);

            $textBlock = null;
            foreach ($message->content as $block) {
                if (($block->type ?? null) === 'text') {
                    $textBlock = $block;
                    break;
                }
            }

            $raw = $textBlock->text ?? null;

            if ($raw === null) {
                Log::warning('ChangeHighlightService: la IA no devolvió un bloque de texto.', ['stop_reason' => $message->stopReason ?? null]);

                return null;
            }

            $data = json_decode($raw, true);

            if (! is_array($data) || ! isset($data['highlighted_html'], $data['change_summary'])) {
                Log::warning('ChangeHighlightService: respuesta con formato inesperado, probablemente truncada.', [
                    'stop_reason' => $message->stopReason ?? null,
                    'output_tokens' => $message->usage->outputTokens ?? null,
                ]);

                return null;
            }

            if (! $this->isSafe($data['highlighted_html'], $newForPrompt)) {
                Log::warning('ChangeHighlightService: el HTML resaltado no coincide con el documento nuevo, se descarta el resaltado automático.');

                return null;
            }

            $data['highlighted_html'] = $this->restoreImages($data['highlighted_html'], $newImages);

            return $data;
        } catch (Throwable $e) {
            Log::warning('ChangeHighlightService: no se pudo generar el resaltado automático', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * El único cambio permitido entre $highlighted y el $original es la presencia de
     * etiquetas <mark>...</mark> — si quitándolas el texto no coincide exactamente,
     * la IA alteró contenido real y no es seguro usarlo.
     */
    private function isSafe(string $highlighted, string $original): bool
    {
        $stripped = preg_replace('/<mark\b[^>]*>(.*?)<\/mark>/si', '$1', $highlighted);

        return $this->normalizeWhitespace($stripped) === $this->normalizeWhitespace($original);
    }

    private function normalizeWhitespace(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', $html));
    }

    private function plainText(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))));
    }

    /**
     * @return array{0: string, 1: array<string, string>}  [html con marcadores, mapa marcador => <img> original]
     */
    private function extractImages(string $html): array
    {
        $images = [];

        $replaced = preg_replace_callback('/<img\b[^>]*>/i', function (array $m) use (&$images) {
            $token = '[[IMG_' . count($images) . ']]';
            $images[$token] = $m[0];

            return $token;
        }, $html);

        return [$replaced, $images];
    }

    private function restoreImages(string $html, array $images): string
    {
        return strtr($html, $images);
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'highlighted_html' => [
                    'type' => 'string',
                    'description' => 'Exactamente el "documento nuevo" recibido, sin alterar ninguna etiqueta, atributo ni texto '
                        . 'existente — la ÚNICA modificación permitida es envolver con <mark>...</mark> los fragmentos de texto '
                        . 'que fueron agregados o modificados respecto al documento anterior. No agregues, quites, resumas ni '
                        . 'reescribas ningún otro contenido: cópialo literal.',
                ],
                'change_summary' => [
                    'type' => 'string',
                    'description' => 'Resumen breve en español (una oración, máximo 25 palabras) de qué cambió. Si puedes '
                        . 'identificar la sección afectada (ej. Objetivo, Alcance, Actividades), menciónala.',
                ],
            ],
            'required' => ['highlighted_html', 'change_summary'],
            'additionalProperties' => false,
        ];
    }

    private function systemPrompt(): string
    {
        return 'Eres un asistente que compara dos versiones de un documento HTML y resalta lo que cambió. '
            . 'Nunca alteras, resumes ni corriges el contenido real del documento nuevo — únicamente agregas etiquetas '
            . '<mark> alrededor del texto que fue agregado o modificado, y describes el cambio en una oración corta.';
    }

    private function buildPrompt(string $oldHtml, string $newHtml): string
    {
        return "Documento ANTERIOR (versión vieja):\n{$oldHtml}\n\n"
            . "Documento NUEVO (versión actual, la que se va a guardar):\n{$newHtml}\n\n"
            . 'Compara ambos documentos. Devuelve el documento NUEVO exactamente igual —mismas etiquetas, mismos '
            . 'atributos, mismo texto— pero con <mark>...</mark> alrededor de cada fragmento de texto que fue agregado '
            . 'o modificado respecto al documento anterior. No toques nada que no haya cambiado. No reescribas, '
            . 'resumas ni corrijas el texto: cópialo literal. También describe el cambio brevemente.';
    }
}
