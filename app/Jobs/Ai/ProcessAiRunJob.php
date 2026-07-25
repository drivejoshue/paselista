<?php

namespace App\Jobs\Ai;

use App\Models\AiRun;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\AiConversationService;
use App\Services\Ai\AiModelRouter;
use App\Services\Ai\AiPrivacyRedactor;
use App\Services\Ai\AiRunEventLogger;
use App\Services\Ai\AiSettingsService;
use App\Services\Ai\Charts\AiChartDataBuilder;
use App\Services\Ai\Charts\AiChartPlanner;
use App\Services\Ai\SchoolPassAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ProcessAiRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;
    public bool $failOnTimeout = true;

    public function __construct(
        public readonly int $runId
    ) {
    }

    public function handle(
        AiSettingsService $settingsService,
        AiContextBuilder $contextBuilder,
        AiPrivacyRedactor $privacyRedactor,
        AiModelRouter $modelRouter,
        SchoolPassAiService $aiService,
        AiChartDataBuilder $chartDataBuilder,
        AiChartPlanner $chartPlanner,
        AiConversationService $conversationService,
        AiRunEventLogger $eventLogger
    ): void {
        $run = AiRun::query()
            ->with([
                'conversation',
                'user',
            ])
            ->find($this->runId);

        if (! $run) {
            return;
        }

        if (! in_array(
            $run->status,
            ['queued', 'processing'],
            true
        )) {
            return;
        }

        $conversation = $run->conversation;

        if (! $conversation) {
            $this->markError(
                $run,
                'La conversación asociada no existe.',
                $conversationService,
                $eventLogger
            );

            return;
        }

        $startedAt = hrtime(true);

        try {
            $run->forceFill([
                'status' => 'processing',
                'updated_at' => now(),
            ])->save();

            $eventLogger->complete(
                runId: $run->id,
                stageKey: 'queued',
                label: 'Procesamiento iniciado',
                detail: 'PaseLista comenzó a preparar la consulta.',
                sortOrder: 15
            );

            $eventLogger->start(
                runId: $run->id,
                stageKey: 'building_context',
                label: 'Preparando datos escolares',
                detail: 'Revisando ciclo, inscripciones, horarios y calendario.',
                sortOrder: 20
            );

            $bundle = $contextBuilder->build(
                schoolId: (int) $run->school_id,
                scopeType: $run->scope_type,
                scopeId: $run->scope_id,
                requestedFrom: $run->period_from,
                requestedTo: $run->period_to,
                question: $run->question
            );

            $history = $privacyRedactor
                ->conversationHistory(
                    schoolId: (int) $run->school_id,
                    conversationId: (int) $conversation->id,
                    currentRunId: (int) $run->id
                );

            $bundle['aliases'] = array_merge(
                $history['aliases'],
                $bundle['aliases']
            );

            $contextJson = json_encode(
                $bundle['context'],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );

            $run->forceFill([
                'period_from' => $bundle['from']
                    ->toDateString(),
                'period_to' => $bundle['to']
                    ->toDateString(),
                'redacted_question' =>
                    $bundle['redacted_question'],
                'context_hash' => hash(
                    'sha256',
                    $contextJson
                ),
                'context_generated_at' => now(),
                'updated_at' => now(),
            ])->save();

            $eventLogger->complete(
                runId: $run->id,
                stageKey: 'building_context',
                label: 'Datos escolares preparados',
                detail: $bundle['period_adjusted']
                    ? $bundle['period_adjustment_message']
                    : 'Se calcularon métricas y se protegieron los datos personales.',
                sortOrder: 20,
                metadata: [
                    'scope_type' => $run->scope_type,
                    'period_from' => $bundle['from']
                        ->toDateString(),
                    'period_to' => $bundle['to']
                        ->toDateString(),

                    'requested_period_from' =>
                        $bundle['requested_from']
                            ->toDateString(),

                    'requested_period_to' =>
                        $bundle['requested_to']
                            ->toDateString(),

                    'period_adjusted' =>
                        $bundle['period_adjusted'],
                ]
            );

            $eventLogger->start(
                runId: $run->id,
                stageKey: 'choosing_strategy',
                label: 'Seleccionando estrategia',
                detail: 'Ajustando el nivel de análisis a la pregunta y al volumen de datos.',
                sortOrder: 25
            );

            $settings = $settingsService->forSchool(
                (int) $run->school_id
            );

            $periodDays = $bundle['from']
                ->diffInDays($bundle['to'])
                + 1;

            $requestedModelTier = in_array(
                (string) $run->requested_model_tier,
                [
                    'fast',
                    'pro',
                ],
                true
            )
                ? (string) $run->requested_model_tier
                : null;

            if ($requestedModelTier === 'pro') {
                if (! (bool) $settings->allow_pro) {
                    throw new RuntimeException(
                        'El análisis avanzado dejó de estar autorizado para esta escuela.'
                    );
                }

                $mode = 'pro';
            } elseif ($requestedModelTier === 'fast') {
                $mode = 'fast';
            } else {
                /*
                 * Compatibilidad con ejecuciones creadas antes de almacenar
                 * el nivel solicitado.
                 */
                $mode = $modelRouter->resolve(
                    question: $run->question,
                    scopeType: $run->scope_type,
                    periodDays: $periodDays,
                    settings: $settings,
                    context: $bundle['context']
                );

                $requestedModelTier =
                    $mode === 'pro'
                        ? 'pro'
                        : 'fast';
            }

            $quotaUnits = (int) (
                $run->quota_units
                ?: $settingsService
                    ->unitsForTier(
                        $requestedModelTier
                    )
            );

            $run->forceFill([
                'requested_model_tier' =>
                    $requestedModelTier,
                'quota_units' =>
                    $quotaUnits,
                'updated_at' => now(),
            ])->save();

            $eventLogger->complete(
                runId: $run->id,
                stageKey: 'choosing_strategy',
                label: 'Estrategia preparada',
                detail: $requestedModelTier === 'pro'
                    ? sprintf(
                        'Se utilizará análisis avanzado. Consumo reservado: %d créditos.',
                        $quotaUnits
                    )
                    : sprintf(
                        'Se utilizará análisis rápido. Consumo reservado: %d crédito.',
                        $quotaUnits
                    ),
                sortOrder: 25,
                metadata: [
                    'requested_model_tier' =>
                        $requestedModelTier,
                    'quota_units' =>
                        $quotaUnits,
                ]
            );

            $eventLogger->start(
                runId: $run->id,
                stageKey: 'provider_request',
                label: 'Analizando patrones',
                detail: 'Interpretando asistencia, puntualidad y accesos.',
                sortOrder: 30
            );

            $analysis = $aiService->analyze(
                bundle: $bundle,
                mode: $mode,
                history: $history['messages']
            );

            $eventLogger->complete(
                runId: $run->id,
                stageKey: 'provider_request',
                label: 'Patrones analizados',
                detail: 'La respuesta estructurada fue recibida.',
                sortOrder: 30
            );

            $analysis['result']['charts'] = [];
            $analysis['result']['model_tier'] =
                $requestedModelTier;
            $analysis['result']['quota_units'] =
                $quotaUnits;
            $analysis['result']['provider_model'] =
                $analysis['model'];

            if (
                $chartPlanner->shouldGenerate(
                    $run->question
                )
            ) {
                $eventLogger->start(
                    runId: $run->id,
                    stageKey: 'building_charts',
                    label: 'Preparando visualización',
                    detail: 'Calculando una gráfica con datos verificados de PaseLista.',
                    sortOrder: 35
                );

                $chartDatasets =
                    $chartDataBuilder->build(
                        schoolId: (int)
                            $run->school_id,
                        scopeType:
                            $run->scope_type,
                        scopeId:
                            $run->scope_id,
                        from:
                            $bundle['from'],
                        to:
                            $bundle['to']
                    );

                $analysis['result']['charts'] =
                    $chartPlanner->plan(
                        question:
                            $run->question,
                        scopeType:
                            $run->scope_type,
                        datasets:
                            $chartDatasets,
                        force: true
                    );

                $eventLogger->complete(
                    runId: $run->id,
                    stageKey: 'building_charts',
                    label: 'Visualización preparada',
                    detail: count(
                        $analysis['result']['charts']
                    ) > 0
                        ? 'La gráfica fue generada con cifras calculadas por PaseLista.'
                        : 'No hubo datos suficientes para construir la gráfica.',
                    sortOrder: 35
                );
            }

            $eventLogger->start(
                runId: $run->id,
                stageKey: 'validating_response',
                label: 'Validando respuesta',
                detail: 'Comprobando estructura, evidencia y campos permitidos.',
                sortOrder: 40
            );

            $run->forceFill([
                'model' => $analysis['model'],
                'thinking_enabled' =>
                    $analysis['thinking_enabled'],
                'response_json' =>
                    $analysis['result'],
                'input_tokens' =>
                    $analysis['usage']['input_tokens'],
                'cached_input_tokens' =>
                    $analysis['usage']['cached_input_tokens'],
                'output_tokens' =>
                    $analysis['usage']['output_tokens'],
                'total_tokens' =>
                    $analysis['usage']['total_tokens'],
                'estimated_cost_usd' =>
                    $analysis['estimated_cost_usd'],
                'duration_ms' =>
                    $this->durationMs($startedAt),
                'status' => 'success',
                'completed_at' => now(),
                'updated_at' => now(),
            ])->save();

            $conversationService->addMessage(
                conversation: $conversation,
                role: 'assistant',
                content: (string) (
                    $analysis['result']['answer']
                    ?? 'Análisis completado.'
                ),
                aiRunId: $run->id,
                structured: $analysis['result'],
                status: 'completed'
            );

            $eventLogger->complete(
                runId: $run->id,
                stageKey: 'validating_response',
                label: 'Respuesta validada',
                detail: 'La respuesta quedó guardada en la conversación.',
                sortOrder: 40
            );

            $eventLogger->complete(
                runId: $run->id,
                stageKey: 'completed',
                label: 'Análisis terminado',
                detail: 'La respuesta está lista para revisión administrativa.',
                sortOrder: 50
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->markError(
                $run,
                $exception->getMessage(),
                $conversationService,
                $eventLogger,
                $startedAt
            );
        }
    }

    private function markError(
        AiRun $run,
        string $message,
        AiConversationService $conversationService,
        AiRunEventLogger $eventLogger,
        ?int $startedAt = null
    ): void {
        $run->forceFill([
            'status' => 'error',
            'duration_ms' => $startedAt
                ? $this->durationMs($startedAt)
                : $run->duration_ms,
            'error_message' => mb_substr(
                $message,
                0,
                4000
            ),
            'completed_at' => now(),
            'updated_at' => now(),
        ])->save();

        $eventLogger->fail(
            runId: $run->id,
            stageKey: 'failed',
            label: 'No se pudo completar el análisis',
            detail: 'PaseLista registró el error para revisión técnica.',
            sortOrder: 999
        );

        if ($run->conversation) {
            $conversationService->addMessage(
                conversation: $run->conversation,
                role: 'system_notice',
                content: 'El análisis no pudo completarse. Intenta nuevamente o solicita revisión técnica.',
                aiRunId: $run->id,
                status: 'error'
            );
        }
    }

    private function durationMs(
        int $startedAt
    ): int {
        return max(
            0,
            (int) round(
                (
                    hrtime(true)
                    - $startedAt
                ) / 1_000_000
            )
        );
    }
}
