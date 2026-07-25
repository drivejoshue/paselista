<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class AiModelRouter
{
    public function resolve(
        string $question,
        string $scopeType,
        int $periodDays,
        object $settings,
        array $context
    ): string {
        if (! (bool) $settings->allow_pro) {
            return 'fast';
        }

        $text = Str::lower(
            Str::ascii($question)
        );

        $score = 0;

        foreach ([
            'compara',
            'comparacion',
            'tendencia',
            'evolucion',
            'periodo anterior',
            'mes anterior',
            'cambio',
            'correlacion',
            'informe ejecutivo',
            'analisis institucional',
            'todos los grupos',
            'entre grupos',
        ] as $term) {
            if (str_contains($text, $term)) {
                $score++;
            }
        }

        if ($scopeType === 'school') {
            $score++;
        }

        if ($periodDays > 45) {
            $score++;
        }

        if ($periodDays > 90) {
            $score++;
        }

        if (
            (int) data_get(
                $context,
                'summary.students',
                0
            ) > 250
        ) {
            $score++;
        }

        if (mb_strlen($question) > 220) {
            $score++;
        }

        return $score >= 3
            ? 'deep'
            : 'fast';
    }
}
