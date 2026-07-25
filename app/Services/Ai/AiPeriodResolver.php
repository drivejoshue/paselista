<?php

namespace App\Services\Ai;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiPeriodResolver
{
    private const NUMBER_WORDS = [
        'un' => 1,
        'una' => 1,
        'uno' => 1,
        'dos' => 2,
        'tres' => 3,
        'cuatro' => 4,
        'cinco' => 5,
        'seis' => 6,
        'siete' => 7,
        'ocho' => 8,
        'nueve' => 9,
        'diez' => 10,
        'once' => 11,
        'doce' => 12,
    ];

    public function resolve(
        int $schoolId,
        string $question,
        Carbon $requestedFrom,
        Carbon $requestedTo,
        int $maxRangeDays
    ): array {
        $timezone = DB::table('schools')
            ->where('id', $schoolId)
            ->value('timezone')
            ?: config('app.timezone');

        $today = Carbon::now($timezone)
            ->startOfDay();

        $manualFrom = $requestedFrom
            ->copy()
            ->timezone($timezone)
            ->startOfDay();

        $manualTo = $requestedTo
            ->copy()
            ->timezone($timezone)
            ->startOfDay()
            ->min($today);

        $normalized = $this->normalize(
            $question
        );

        $resolved = $this->detect(
            normalizedQuestion: $normalized,
            today: $today
        );

        if (! $resolved) {
            return [
                'from' => $manualFrom,
                'to' => $manualTo,
                'detected' => false,
                'source' => 'calendar',
                'label' => 'Periodo seleccionado en el calendario',
                'requested_days' =>
                    $manualFrom->diffInDays(
                        $manualTo
                    ) + 1,
                'limited' => false,
            ];
        }

        $from = $resolved['from']
            ->copy()
            ->startOfDay();

        $to = $resolved['to']
            ->copy()
            ->startOfDay()
            ->min($today);

        $requestedDays = $from
            ->diffInDays($to)
            + 1;

        $limited = false;

        if (
            $requestedDays
            > $maxRangeDays
        ) {
            $from = $to
                ->copy()
                ->subDays(
                    $maxRangeDays - 1
                );

            $limited = true;
        }

        return [
            'from' => $from,
            'to' => $to,
            'detected' => true,
            'source' => 'question',
            'label' => $resolved['label'],
            'requested_days' =>
                $requestedDays,
            'limited' => $limited,
        ];
    }

    private function detect(
        string $normalizedQuestion,
        Carbon $today
    ): ?array {
        /*
         * Comparaciones como:
         * "compara los últimos 30 días con los 30 anteriores"
         * necesitan 60 días de contexto.
         */
        if (
            preg_match(
                '/ultim(?:o|os|a|as)\s+(\d{1,3})\s+dias?.{0,80}(?:anteriores|previos|periodo anterior)/',
                $normalizedQuestion,
                $matches
            )
        ) {
            $days = max(
                1,
                (int) $matches[1]
            );

            $totalDays = $days * 2;

            return [
                'from' => $today
                    ->copy()
                    ->subDays(
                        $totalDays - 1
                    ),
                'to' => $today->copy(),
                'label' => sprintf(
                    'Últimos %d días y los %d anteriores',
                    $days,
                    $days
                ),
            ];
        }

        if (
            preg_match(
                '/ultim(?:o|os|a|as)\s+(\d{1,3})\s+dias?/',
                $normalizedQuestion,
                $matches
            )
        ) {
            $days = max(
                1,
                (int) $matches[1]
            );

            return [
                'from' => $today
                    ->copy()
                    ->subDays(
                        $days - 1
                    ),
                'to' => $today->copy(),
                'label' => sprintf(
                    'Últimos %d días',
                    $days
                ),
            ];
        }

        if (
            preg_match(
                '/ultim(?:o|os|a|as)\s+(\d{1,2})\s+semanas?/',
                $normalizedQuestion,
                $matches
            )
        ) {
            $weeks = max(
                1,
                (int) $matches[1]
            );

            $days = $weeks * 7;

            return [
                'from' => $today
                    ->copy()
                    ->subDays(
                        $days - 1
                    ),
                'to' => $today->copy(),
                'label' => sprintf(
                    'Últimas %d semanas',
                    $weeks
                ),
            ];
        }

        if (
            preg_match(
                '/ultim(?:o|os|a|as)\s+(\d{1,2})\s+meses?/',
                $normalizedQuestion,
                $matches
            )
        ) {
            $months = max(
                1,
                (int) $matches[1]
            );

            return [
                'from' => $today
                    ->copy()
                    ->subMonthsNoOverflow(
                        $months
                    )
                    ->addDay(),
                'to' => $today->copy(),
                'label' => sprintf(
                    'Últimos %d meses',
                    $months
                ),
            ];
        }

        if (
            str_contains(
                $normalizedQuestion,
                'semana pasada'
            )
            || str_contains(
                $normalizedQuestion,
                'semana anterior'
            )
        ) {
            return [
                'from' => $today
                    ->copy()
                    ->subWeek()
                    ->startOfWeek(),
                'to' => $today
                    ->copy()
                    ->subWeek()
                    ->endOfWeek(),
                'label' => 'Semana anterior',
            ];
        }

        if (
            str_contains(
                $normalizedQuestion,
                'esta semana'
            )
            || str_contains(
                $normalizedQuestion,
                'semana actual'
            )
        ) {
            return [
                'from' => $today
                    ->copy()
                    ->startOfWeek(),
                'to' => $today->copy(),
                'label' => 'Semana actual',
            ];
        }

        if (
            str_contains(
                $normalizedQuestion,
                'mes pasado'
            )
            || str_contains(
                $normalizedQuestion,
                'mes anterior'
            )
        ) {
            return [
                'from' => $today
                    ->copy()
                    ->subMonthNoOverflow()
                    ->startOfMonth(),
                'to' => $today
                    ->copy()
                    ->subMonthNoOverflow()
                    ->endOfMonth(),
                'label' => 'Mes anterior',
            ];
        }

        if (
            str_contains(
                $normalizedQuestion,
                'este mes'
            )
            || str_contains(
                $normalizedQuestion,
                'mes actual'
            )
        ) {
            return [
                'from' => $today
                    ->copy()
                    ->startOfMonth(),
                'to' => $today->copy(),
                'label' => 'Mes actual',
            ];
        }

        if (
            preg_match(
                '/\bayer\b/',
                $normalizedQuestion
            )
        ) {
            return [
                'from' => $today
                    ->copy()
                    ->subDay(),
                'to' => $today
                    ->copy()
                    ->subDay(),
                'label' => 'Ayer',
            ];
        }

        if (
            preg_match(
                '/\bhoy\b/',
                $normalizedQuestion
            )
        ) {
            return [
                'from' => $today->copy(),
                'to' => $today->copy(),
                'label' => 'Hoy',
            ];
        }

        return null;
    }

    private function normalize(
        string $question
    ): string {
        $normalized = Str::ascii(
            mb_strtolower(
                trim($question)
            )
        );

        foreach (
            self::NUMBER_WORDS
            as $word => $number
        ) {
            $normalized = preg_replace(
                '/\b'.preg_quote(
                    $word,
                    '/'
                ).'\b/',
                (string) $number,
                $normalized
            );
        }

        return preg_replace(
            '/\s+/',
            ' ',
            (string) $normalized
        );
    }
}
