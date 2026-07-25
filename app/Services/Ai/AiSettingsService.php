<?php

namespace App\Services\Ai;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiSettingsService
{
    public function forSchool(
        int $schoolId
    ): object {
        $row = DB::table('ai_settings')
            ->where(
                'school_id',
                $schoolId
            )
            ->first();

        if ($row) {
            return $row;
        }

        return (object) [
            'id' => null,
            'school_id' => $schoolId,

            'enabled' => (bool) config(
                'schoolpass_ai.defaults.school_enabled',
                true
            ),

            'default_model' => (string) config(
                'schoolpass_ai.defaults.default_model',
                'fast'
            ),

            'allow_pro' => (bool) config(
                'schoolpass_ai.defaults.allow_pro',
                false
            ),

            'monthly_query_limit' => (int) config(
                'schoolpass_ai.defaults.monthly_query_limit',
                300
            ),

            'max_range_days' => (int) config(
                'schoolpass_ai.defaults.max_range_days',
                120
            ),

            'allow_school_analysis' => (bool) config(
                'schoolpass_ai.defaults.allow_school_analysis',
                true
            ),

            'allow_group_analysis' => (bool) config(
                'schoolpass_ai.defaults.allow_group_analysis',
                true
            ),

            'allow_student_analysis' => (bool) config(
                'schoolpass_ai.defaults.allow_student_analysis',
                true
            ),

            'usage_reset_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    /*
     * Compatibilidad: este método ahora devuelve créditos consumidos.
     * Fast = 1 crédito. Pro = 6 créditos.
     */
    public function successfulRunsThisMonth(
        int $schoolId
    ): int {
        return $this->creditsUsedThisMonth(
            $schoolId
        );
    }

    public function creditsUsedThisMonth(
        int $schoolId
    ): int {
        return (int) DB::table('ai_runs')
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'status',
                'success'
            )
            ->whereBetween(
                'created_at',
                [
                    $this->usageWindowStart(
                        $schoolId
                    ),
                    now()->endOfMonth(),
                ]
            )
            ->sum(
                'quota_units'
            );
    }

    public function creditsCommittedThisMonth(
        int $schoolId
    ): int {
        return (int) DB::table('ai_runs')
            ->where(
                'school_id',
                $schoolId
            )
            ->whereIn(
                'status',
                [
                    'queued',
                    'processing',
                    'success',
                ]
            )
            ->whereBetween(
                'created_at',
                [
                    $this->usageWindowStart(
                        $schoolId
                    ),
                    now()->endOfMonth(),
                ]
            )
            ->sum(
                'quota_units'
            );
    }

    public function remainingCredits(
        int $schoolId,
        int $limit
    ): int {
        return max(
            0,
            $limit
            - $this->creditsCommittedThisMonth(
                $schoolId
            )
        );
    }

    public function unitsForTier(
        string $tier
    ): int {
        return $tier === 'pro'
            ? max(
                1,
                (int) config(
                    'schoolpass_ai.quota.pro_units',
                    6
                )
            )
            : max(
                1,
                (int) config(
                    'schoolpass_ai.quota.fast_units',
                    1
                )
            );
    }

    public function usageThisMonth(
        int $schoolId
    ): object {
        $windowStart = $this
            ->usageWindowStart(
                $schoolId
            );

        $success = DB::table('ai_runs')
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'status',
                'success'
            )
            ->whereBetween(
                'created_at',
                [
                    $windowStart,
                    now()->endOfMonth(),
                ]
            )
            ->selectRaw(
                'COUNT(*) as successful_runs,
                 COALESCE(SUM(quota_units), 0) as credits,
                 COALESCE(SUM(input_tokens), 0) as input_tokens,
                 COALESCE(SUM(output_tokens), 0) as output_tokens,
                 COALESCE(SUM(total_tokens), 0) as total_tokens,
                 COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd,
                 COALESCE(AVG(duration_ms), 0) as average_duration_ms'
            )
            ->first();

        $errors = DB::table('ai_runs')
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'status',
                'error'
            )
            ->whereBetween(
                'created_at',
                [
                    $windowStart,
                    now()->endOfMonth(),
                ]
            )
            ->count();

        $queued = DB::table('ai_runs')
            ->where(
                'school_id',
                $schoolId
            )
            ->whereIn(
                'status',
                [
                    'queued',
                    'processing',
                ]
            )
            ->whereBetween(
                'created_at',
                [
                    $windowStart,
                    now()->endOfMonth(),
                ]
            )
            ->selectRaw(
                'COUNT(*) as pending_runs,
                 COALESCE(SUM(quota_units), 0) as pending_credits'
            )
            ->first();

        $credits = (int) (
            $success->credits
            ?? 0
        );

        return (object) [
            /*
             * "runs" se conserva para no romper vistas anteriores.
             * Su valor ahora equivale a créditos utilizados.
             */
            'runs' => $credits,
            'credits' => $credits,

            'successful_runs' => (int) (
                $success->successful_runs
                ?? 0
            ),

            'pending_runs' => (int) (
                $queued->pending_runs
                ?? 0
            ),

            'pending_credits' => (int) (
                $queued->pending_credits
                ?? 0
            ),

            'input_tokens' => (int) (
                $success->input_tokens
                ?? 0
            ),

            'output_tokens' => (int) (
                $success->output_tokens
                ?? 0
            ),

            'total_tokens' => (int) (
                $success->total_tokens
                ?? 0
            ),

            'estimated_cost_usd' => (float) (
                $success->estimated_cost_usd
                ?? 0
            ),

            'average_duration_ms' => (float) (
                $success->average_duration_ms
                ?? 0
            ),

            'errors' => (int) $errors,
            'window_start' => $windowStart,
            'window_end' => now()->endOfMonth(),
        ];
    }

    public function usageWindowStart(
        int $schoolId
    ): Carbon {
        $monthStart = now()
            ->startOfMonth();

        $resetAt = DB::table(
            'ai_settings'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->value(
                'usage_reset_at'
            );

        if (! $resetAt) {
            return $monthStart;
        }

        $reset = Carbon::parse(
            $resetAt
        );

        return $reset->gt($monthStart)
            ? $reset
            : $monthStart;
    }

    public function saveForSchool(
        int $schoolId,
        array $values,
        int $actorId
    ): object {
        $current = $this->forSchool(
            $schoolId
        );

        $now = now();

        $payload = [
            'enabled' => (bool) (
                $values['enabled']
                ?? $current->enabled
            ),

            'default_model' => (string) (
                $values['default_model']
                ?? $current->default_model
            ),

            'allow_pro' => (bool) (
                $values['allow_pro']
                ?? $current->allow_pro
            ),

            'monthly_query_limit' => (int) (
                $values['monthly_query_limit']
                ?? $current->monthly_query_limit
            ),

            'max_range_days' => (int) (
                $values['max_range_days']
                ?? $current->max_range_days
            ),

            'allow_school_analysis' => (bool) (
                $values['allow_school_analysis']
                ?? $current->allow_school_analysis
            ),

            'allow_group_analysis' => (bool) (
                $values['allow_group_analysis']
                ?? $current->allow_group_analysis
            ),

            'allow_student_analysis' => (bool) (
                $values['allow_student_analysis']
                ?? $current->allow_student_analysis
            ),

            'usage_reset_at' =>
                $current->usage_reset_at,

            'updated_by' => $actorId,
            'updated_at' => $now,
        ];

        if ($current->id === null) {
            $payload['school_id'] =
                $schoolId;

            $payload['created_by'] =
                $actorId;

            $payload['created_at'] =
                $now;

            DB::table('ai_settings')
                ->insert($payload);
        } else {
            DB::table('ai_settings')
                ->where(
                    'school_id',
                    $schoolId
                )
                ->update($payload);
        }

        return $this->forSchool(
            $schoolId
        );
    }

    public function resetUsage(
        int $schoolId,
        int $actorId
    ): object {
        $current = $this->forSchool(
            $schoolId
        );

        $now = now();

        $payload = [
            'enabled' =>
                (bool) $current->enabled,

            'default_model' =>
                (string) $current->default_model,

            'allow_pro' =>
                (bool) $current->allow_pro,

            'monthly_query_limit' =>
                (int) $current->monthly_query_limit,

            'max_range_days' =>
                (int) $current->max_range_days,

            'allow_school_analysis' =>
                (bool) $current
                    ->allow_school_analysis,

            'allow_group_analysis' =>
                (bool) $current
                    ->allow_group_analysis,

            'allow_student_analysis' =>
                (bool) $current
                    ->allow_student_analysis,

            'usage_reset_at' => $now,
            'updated_by' => $actorId,
            'updated_at' => $now,
        ];

        if ($current->id === null) {
            $payload['school_id'] =
                $schoolId;

            $payload['created_by'] =
                $actorId;

            $payload['created_at'] =
                $now;

            DB::table('ai_settings')
                ->insert($payload);
        } else {
            DB::table('ai_settings')
                ->where(
                    'school_id',
                    $schoolId
                )
                ->update($payload);
        }

        return $this->forSchool(
            $schoolId
        );
    }
}
