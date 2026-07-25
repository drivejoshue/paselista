<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiMessage;
use App\Models\AiRun;
use App\Services\Ai\AiRunEventLogger;
use App\Services\Ai\Charts\AiChartDataBuilder;
use App\Services\Ai\Charts\AiChartPlanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChartController extends Controller
{
    public function store(
        Request $request,
        int $run,
        AiChartDataBuilder $dataBuilder,
        AiChartPlanner $planner,
        AiRunEventLogger $eventLogger
    ): JsonResponse {
        $user = $request->user();

        $schoolId = (int) (
            $user?->school_id
            ?? 0
        );

        abort_unless(
            $schoolId > 0,
            403
        );

        $aiRun = AiRun::query()
            ->where('id', $run)
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'status',
                'success'
            )
            ->firstOrFail();

        $result = is_array(
            $aiRun->response_json
        )
            ? $aiRun->response_json
            : [];

        $existingCharts = is_array(
            $result['charts']
            ?? null
        )
            ? $result['charts']
            : [];

        if ($existingCharts !== []) {
            return response()->json([
                'ok' => true,
                'generated' => false,
                'charts' => $existingCharts,
                'message' =>
                    'La respuesta ya contiene una gráfica.',
            ]);
        }

        $eventLogger->start(
            runId: $aiRun->id,
            stageKey: 'manual_chart',
            label: 'Generando gráfica',
            detail:
                'Calculando una visualización con los datos del análisis.',
            sortOrder: 60
        );

        $datasets = $dataBuilder->build(
            schoolId: $schoolId,
            scopeType:
                $aiRun->scope_type,
            scopeId:
                $aiRun->scope_id,
            from:
                $aiRun->period_from,
            to:
                $aiRun->period_to
        );

        $charts = $planner->plan(
            question:
                $aiRun->question,
            scopeType:
                $aiRun->scope_type,
            datasets:
                $datasets,
            force: true
        );

        if ($charts === []) {
            $eventLogger->fail(
                runId: $aiRun->id,
                stageKey: 'manual_chart',
                label:
                    'No se pudo generar la gráfica',
                detail:
                    'El periodo no contiene datos suficientes para visualizar.',
                sortOrder: 60
            );

            return response()->json([
                'ok' => false,
                'generated' => false,
                'charts' => [],
                'message' =>
                    'No hay datos suficientes para generar una gráfica.',
            ], 422);
        }

        $result['charts'] = $charts;

        $aiRun->forceFill([
            'response_json' => $result,
            'updated_at' => now(),
        ])->save();

        $message = AiMessage::query()
            ->where(
                'ai_run_id',
                $aiRun->id
            )
            ->where(
                'conversation_id',
                $aiRun->conversation_id
            )
            ->where(
                'role',
                'assistant'
            )
            ->orderByDesc('id')
            ->first();

        if ($message) {
            $messageResult = is_array(
                $message->structured_json
            )
                ? $message->structured_json
                : [];

            $messageResult['charts'] =
                $charts;

            $message->forceFill([
                'structured_json' =>
                    $messageResult,
                'updated_at' => now(),
            ])->save();
        }

        $eventLogger->complete(
            runId: $aiRun->id,
            stageKey: 'manual_chart',
            label: 'Gráfica generada',
            detail:
                'La visualización quedó disponible en el chat y en el PDF.',
            sortOrder: 60,
            metadata: [
                'chart_count' =>
                    count($charts),
            ]
        );

        return response()->json([
            'ok' => true,
            'generated' => true,
            'charts' => $charts,
            'message' =>
                'Gráfica generada correctamente.',
        ]);
    }
}
