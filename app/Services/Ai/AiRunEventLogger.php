<?php

namespace App\Services\Ai;

use App\Models\AiRunEvent;
use Illuminate\Support\Collection;

class AiRunEventLogger
{
    public function start(
        int $runId,
        string $stageKey,
        string $label,
        ?string $detail = null,
        int $sortOrder = 0,
        ?array $metadata = null
    ): AiRunEvent {
        return AiRunEvent::query()->updateOrCreate(
            [
                'ai_run_id' => $runId,
                'stage_key' => $stageKey,
            ],
            [
                'label' => $label,
                'status' => 'running',
                'public_detail' => $detail,
                'metadata_json' => $metadata,
                'sort_order' => $sortOrder,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );
    }

    public function complete(
        int $runId,
        string $stageKey,
        string $label,
        ?string $detail = null,
        int $sortOrder = 0,
        ?array $metadata = null
    ): AiRunEvent {
        $event = AiRunEvent::query()->firstOrNew([
            'ai_run_id' => $runId,
            'stage_key' => $stageKey,
        ]);

        $event->fill([
            'label' => $label,
            'status' => 'completed',
            'public_detail' => $detail,
            'metadata_json' => $metadata,
            'sort_order' => $sortOrder,
            'started_at' => $event->started_at
                ?: now(),
            'completed_at' => now(),
        ])->save();

        return $event;
    }

    public function fail(
        int $runId,
        string $stageKey,
        string $label,
        ?string $detail = null,
        int $sortOrder = 999,
        ?array $metadata = null
    ): AiRunEvent {
        $event = AiRunEvent::query()->firstOrNew([
            'ai_run_id' => $runId,
            'stage_key' => $stageKey,
        ]);

        $event->fill([
            'label' => $label,
            'status' => 'failed',
            'public_detail' => $detail,
            'metadata_json' => $metadata,
            'sort_order' => $sortOrder,
            'started_at' => $event->started_at
                ?: now(),
            'completed_at' => now(),
        ])->save();

        return $event;
    }

    public function timeline(
        int $runId
    ): Collection {
        return AiRunEvent::query()
            ->where('ai_run_id', $runId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
