<?php

namespace App\Services\Ai\Charts;

use Illuminate\Support\Str;

class AiChartPlanner
{
    public function shouldGenerate(
        string $question
    ): bool {
        $query = Str::ascii(
            mb_strtolower($question)
        );

        return $this->contains(
            $query,
            [
                'grafica',
                'grafico',
                'graficar',
                'graficamente',
                'chart',
                'visualiza',
                'visualizacion',
            ]
        );
    }

    public function plan(
        string $question,
        string $scopeType,
        array $datasets,
        bool $force = false
    ): array {
        $query = Str::ascii(
            mb_strtolower($question)
        );

        if (
            ! $force
            && ! $this->shouldGenerate(
                $question
            )
        ) {
            return [];
        }

        $charts = [];

        $explicitChart = true;

        $accessQuestion = $this->contains(
            $query,
            [
                'acceso',
                'entrada',
                'salida',
                'denegad',
                'duplicad',
                'manual',
                'dispositivo',
                'tutor',
            ]
        );

        $groupQuestion = $this->contains(
            $query,
            [
                'grupo',
                'grupos',
                'nivel',
                'plantel',
                'ranking',
                'ordena',
                'compara',
            ]
        );

        $punctualityQuestion = $this->contains(
            $query,
            [
                'puntual',
                'retardo',
                'tarde',
                'impuntual',
                'dia de la semana',
            ]
        );

        $trendQuestion = $this->contains(
            $query,
            [
                'semana',
                'mes',
                'periodo anterior',
                'cambio',
                'mejor',
                'empeor',
                'tendencia',
                'evolucion',
                'ultimos',
                'ultimas',
            ]
        );

        if (
            $accessQuestion
            && ! empty(
                $datasets['access_summary']
            )
        ) {
            $charts[] =
                $this->accessChart(
                    $datasets[
                        'access_summary'
                    ]
                );
        }

        if (
            $scopeType === 'school'
            && $groupQuestion
            && ! empty(
                $datasets[
                    'group_comparison'
                ]
            )
        ) {
            $charts[] =
                $this->groupChart(
                    $datasets[
                        'group_comparison'
                    ]
                );
        }

        if (
            $punctualityQuestion
            && ! empty(
                $datasets[
                    'weekday_distribution'
                ]
            )
        ) {
            $charts[] =
                $this->weekdayChart(
                    $datasets[
                        'weekday_distribution'
                    ]
                );
        }

        if (
            (
                $trendQuestion
                || $explicitChart
                || in_array(
                    $scopeType,
                    [
                        'student',
                        'group',
                    ],
                    true
                )
            )
            && count(
                $datasets[
                    'weekly_trend'
                ] ?? []
            ) >= 2
        ) {
            $charts[] =
                $this->weeklyChart(
                    $datasets[
                        'weekly_trend'
                    ]
                );
        }

        if (
            empty($charts)
            && ! empty(
                $datasets[
                    'attendance_mix'
                ]
            )
        ) {
            $charts[] =
                $this->attendanceChart(
                    $datasets[
                        'attendance_mix'
                    ]
                );
        }

        return collect($charts)
            ->unique('id')
            ->take(1)
            ->values()
            ->all();
    }

    private function weeklyChart(
        array $data
    ): array {
        return [
            'id' => 'weekly-attendance',
            'type' => 'line',

            'title' =>
                'Tendencia semanal',

            'description' =>
                'Evolución de asistencia y puntualidad.',

            'x_key' => 'label',

            'series' => [
                [
                    'key' =>
                        'attendance_rate',

                    'label' =>
                        'Asistencia',

                    'suffix' => '%',
                ],
                [
                    'key' =>
                        'punctuality_rate',

                    'label' =>
                        'Puntualidad',

                    'suffix' => '%',
                ],
            ],

            'data' => $data,
        ];
    }

    private function groupChart(
        array $data
    ): array {
        return [
            'id' =>
                'group-comparison',

            'type' => 'bar',

            'horizontal' => true,

            'title' =>
                'Comparación por grupo',

            'description' =>
                'Grupos con menor asistencia en el periodo.',

            'x_key' => 'label',

            'series' => [
                [
                    'key' =>
                        'attendance_rate',

                    'label' =>
                        'Asistencia',

                    'suffix' => '%',
                ],
                [
                    'key' =>
                        'punctuality_rate',

                    'label' =>
                        'Puntualidad',

                    'suffix' => '%',
                ],
            ],

            'data' => $data,
        ];
    }

    private function weekdayChart(
        array $data
    ): array {
        return [
            'id' =>
                'weekday-punctuality',

            'type' => 'bar',

            'title' =>
                'Retardos por día',

            'description' =>
                'Distribución de llegadas tarde y muy tarde.',

            'x_key' => 'label',

            'series' => [
                [
                    'key' => 'late',
                    'label' => 'Retardos',
                    'suffix' => '',
                ],
                [
                    'key' => 'very_late',
                    'label' => 'Muy tarde',
                    'suffix' => '',
                ],
            ],

            'data' => $data,
        ];
    }

    private function accessChart(
        array $data
    ): array {
        return [
            'id' =>
                'access-summary',

            'type' => 'bar',

            'title' =>
                'Actividad de accesos',

            'description' =>
                'Eventos permitidos, denegados, duplicados y manuales.',

            'x_key' => 'label',

            'series' => [
                [
                    'key' => 'value',
                    'label' => 'Eventos',
                    'suffix' => '',
                ],
            ],

            'data' => $data,
        ];
    }

    private function attendanceChart(
        array $data
    ): array {
        return [
            'id' =>
                'attendance-mix',

            'type' => 'bar',

            'title' =>
                'Composición de asistencia',

            'description' =>
                'Jornadas-alumno presentes, ausentes y pendientes.',

            'x_key' => 'label',

            'series' => [
                [
                    'key' => 'value',
                    'label' => 'Jornadas',
                    'suffix' => '',
                ],
            ],

            'data' => $data,
        ];
    }

    private function contains(
        string $query,
        array $needles
    ): bool {
        foreach ($needles as $needle) {
            if (str_contains(
                $query,
                $needle
            )) {
                return true;
            }
        }

        return false;
    }
}
