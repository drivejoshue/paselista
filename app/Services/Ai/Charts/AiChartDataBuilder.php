<?php

namespace App\Services\Ai\Charts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiChartDataBuilder
{
    private const NO_CLASS_TYPES = [
        'holiday',
        'vacation',
        'suspension',
        'technical_council',
        'no_class',
    ];

    private const WEEKDAY_LABELS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public function build(
        int $schoolId,
        string $scopeType,
        ?int $scopeId,
        Carbon $from,
        Carbon $to
    ): array {
        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->firstOrFail();

        $timezone = $school->timezone
            ?: config('app.timezone');

        $today = Carbon::now($timezone)
            ->startOfDay();

        $nowTime = Carbon::now($timezone)
            ->format('H:i:s');

        $cycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        if (! $cycle) {
            return $this->emptyData();
        }

        $enrollments = $this->enrollments(
            schoolId: $schoolId,
            cycleId: (int) $cycle->id,
            scopeType: $scopeType,
            scopeId: $scopeId,
            from: $from,
            to: $to
        );

        if ($enrollments->isEmpty()) {
            return $this->emptyData();
        }

        $studentIds = $enrollments
            ->pluck('student_id')
            ->map(
                fn ($id): int => (int) $id
            )
            ->unique()
            ->values();

        $groupIds = $enrollments
            ->pluck('group_id')
            ->map(
                fn ($id): int => (int) $id
            )
            ->unique()
            ->values();

        $schedules = DB::table(
            'group_access_schedules'
        )
            ->where('school_id', $schoolId)
            ->whereIn('group_id', $groupIds)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->get()
            ->groupBy(
                fn (object $row): string =>
                    $row->group_id
                    .'|'
                    .$row->weekday
            )
            ->map(
                fn (Collection $rows): object =>
                    $rows->first()
            );

        $noClassDates = DB::table(
            'school_calendar_days'
        )
            ->where('school_id', $schoolId)
            ->where(
                'academic_cycle_id',
                $cycle->id
            )
            ->whereBetween('date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->where('status', 'active')
            ->whereIn(
                'type',
                self::NO_CLASS_TYPES
            )
            ->pluck('date')
            ->map(
                fn ($date): string =>
                    Carbon::parse($date)
                        ->toDateString()
            )
            ->flip();

        $attendance = DB::table(
            'daily_attendance'
        )
            ->where('school_id', $schoolId)
            ->whereIn(
                'student_id',
                $studentIds
            )
            ->whereBetween('date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->get()
            ->groupBy(
                fn (object $row): string =>
                    $row->student_id
                    .'|'
                    .Carbon::parse(
                        $row->date
                    )->toDateString()
            )
            ->map(
                fn (Collection $rows): object =>
                    $rows
                        ->sortByDesc('id')
                        ->first()
            );

        $daily = [];
        $groups = [];
        $weekdays = [];

        foreach ($enrollments as $enrollment) {
            $enrolledOn = $enrollment->enrolled_on
                ? Carbon::parse(
                    $enrollment->enrolled_on,
                    $timezone
                )->startOfDay()
                : $from->copy();

            $withdrawnOn = $enrollment->withdrawn_on
                ? Carbon::parse(
                    $enrollment->withdrawn_on,
                    $timezone
                )->startOfDay()
                : null;

            for (
                $date = $from->copy();
                $date->lte($to);
                $date->addDay()
            ) {
                if ($date->lt($enrolledOn)) {
                    continue;
                }

                if (
                    $withdrawnOn
                    && $date->gt($withdrawnOn)
                ) {
                    continue;
                }

                $dateKey = $date->toDateString();

                if ($noClassDates->has($dateKey)) {
                    continue;
                }

                $schedule = $schedules->get(
                    $enrollment->group_id
                    .'|'
                    .$date->dayOfWeekIso
                );

                if (! $schedule) {
                    continue;
                }

                $groupKey = (string)
                    $enrollment->group_id;

                $weekdayKey = (int)
                    $date->dayOfWeekIso;

                $daily[$dateKey] ??=
                    $this->metricRow();

                $groups[$groupKey] ??=
                    array_merge(
                        $this->metricRow(),
                        [
                            'label' =>
                                $enrollment
                                    ->group_name,
                        ]
                    );

                $weekdays[$weekdayKey] ??=
                    array_merge(
                        $this->metricRow(),
                        [
                            'label' =>
                                self::WEEKDAY_LABELS[
                                    $weekdayKey
                                ] ?? 'Día',
                        ]
                    );

                foreach (
                    [
                        &$daily[$dateKey],
                        &$groups[$groupKey],
                        &$weekdays[$weekdayKey],
                    ]
                    as &$metric
                ) {
                    $metric['expected']++;
                }

                unset($metric);

                $attendanceRow = $attendance->get(
                    $enrollment->student_id
                    .'|'
                    .$dateKey
                );

                if (! $attendanceRow) {
                    $pending = $date
                        ->isSameDay($today)
                        && $nowTime
                            <= $schedule->late_until;

                    foreach (
                        [
                            &$daily[$dateKey],
                            &$groups[$groupKey],
                            &$weekdays[$weekdayKey],
                        ]
                        as &$metric
                    ) {
                        $metric[
                            $pending
                                ? 'pending'
                                : 'absent'
                        ]++;
                    }

                    unset($metric);

                    continue;
                }

                $minutesLate = max(
                    0,
                    (int) (
                        $attendanceRow->minutes_late
                        ?? 0
                    )
                );

                $punctualityKey = match (true) {
                    $minutesLate > 20 =>
                        'very_late',

                    $minutesLate > 0
                    || $attendanceRow
                        ->attendance_status
                        === 'late' =>
                            'late',

                    default => 'on_time',
                };

                $exitShouldExist =
                    $date->lt($today)
                    || (
                        $date->isSameDay($today)
                        && $nowTime
                            > $schedule->exit_time
                    );

                foreach (
                    [
                        &$daily[$dateKey],
                        &$groups[$groupKey],
                        &$weekdays[$weekdayKey],
                    ]
                    as &$metric
                ) {
                    $metric['present']++;
                    $metric[$punctualityKey]++;

                    if (
                        $attendanceRow
                            ->attendance_status
                            === 'early_exit'
                    ) {
                        $metric['early_exit']++;
                    }

                    if (
                        $exitShouldExist
                        && $attendanceRow->entry_at
                        && ! $attendanceRow->exit_at
                    ) {
                        $metric['missing_exit']++;
                    }
                }

                unset($metric);
            }
        }

        ksort($daily);
        ksort($weekdays);

        $weekly = $this->weekly(
            $daily,
            $timezone
        );

        $groupRows = collect($groups)
            ->map(
                fn (array $row): array =>
                    $this->withRates($row)
            )
            ->sortBy('attendance_rate')
            ->take(12)
            ->values()
            ->all();

        $weekdayRows = collect($weekdays)
            ->map(
                fn (array $row): array =>
                    $this->withRates($row)
            )
            ->values()
            ->all();

        $totals = $this->sumMetrics(
            collect($daily)
        );

        return [
            'weekly_trend' => $weekly,

            'group_comparison' =>
                $groupRows,

            'weekday_distribution' =>
                $weekdayRows,

            'attendance_mix' => [
                [
                    'label' => 'Presentes',
                    'value' => $totals['present'],
                ],
                [
                    'label' => 'Ausentes',
                    'value' => $totals['absent'],
                ],
                [
                    'label' => 'Pendientes',
                    'value' => $totals['pending'],
                ],
            ],

            'access_summary' =>
                $this->accessSummary(
                    schoolId: $schoolId,
                    scopeType: $scopeType,
                    scopeId: $scopeId,
                    from: $from,
                    to: $to
                ),
        ];
    }

    private function enrollments(
        int $schoolId,
        int $cycleId,
        string $scopeType,
        ?int $scopeId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return DB::table(
            'student_enrollments as se'
        )
            ->join(
                'students as s',
                's.id',
                '=',
                'se.student_id'
            )
            ->join(
                'school_groups as sg',
                'sg.id',
                '=',
                'se.school_group_id'
            )
            ->where(
                'se.school_id',
                $schoolId
            )
            ->where(
                'se.academic_cycle_id',
                $cycleId
            )
            ->where(
                'se.status',
                'active'
            )
            ->where(
                's.status',
                'active'
            )
            ->whereDate(
                'se.enrolled_on',
                '<=',
                $to->toDateString()
            )
            ->where(
                function ($query) use (
                    $from
                ): void {
                    $query
                        ->whereNull(
                            'se.withdrawn_on'
                        )
                        ->orWhereDate(
                            'se.withdrawn_on',
                            '>=',
                            $from->toDateString()
                        );
                }
            )
            ->when(
                $scopeType === 'group',
                fn ($query) =>
                    $query->where(
                        'se.school_group_id',
                        $scopeId
                    )
            )
            ->when(
                $scopeType === 'student',
                fn ($query) =>
                    $query->where(
                        'se.student_id',
                        $scopeId
                    )
            )
            ->select([
                'se.student_id',
                'se.school_group_id as group_id',
                'se.enrolled_on',
                'se.withdrawn_on',
                'sg.name as group_name',
            ])
            ->get();
    }

    private function accessSummary(
        int $schoolId,
        string $scopeType,
        ?int $scopeId,
        Carbon $from,
        Carbon $to
    ): array {
        $row = DB::table('access_logs')
            ->where(
                'school_id',
                $schoolId
            )
            ->whereBetween('scanned_at', [
                $from
                    ->copy()
                    ->startOfDay(),

                $to
                    ->copy()
                    ->endOfDay(),
            ])
            ->when(
                $scopeType === 'group',
                fn ($query) =>
                    $query->where(
                        'school_group_id',
                        $scopeId
                    )
            )
            ->when(
                $scopeType === 'student',
                fn ($query) =>
                    $query->where(
                        'student_id',
                        $scopeId
                    )
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN decision = 'allowed'
                        THEN 1 ELSE 0
                    END
                ) as allowed_count,

                SUM(
                    CASE
                        WHEN decision = 'denied'
                        THEN 1 ELSE 0
                    END
                ) as denied_count,

                SUM(
                    CASE
                        WHEN event_status = 'duplicate'
                        OR decision = 'duplicate'
                        THEN 1 ELSE 0
                    END
                ) as duplicate_count,

                SUM(
                    CASE
                        WHEN source = 'manual'
                        THEN 1 ELSE 0
                    END
                ) as manual_count"
            )
            ->first();

        return [
            [
                'label' => 'Permitidos',
                'value' => (int) (
                    $row->allowed_count
                    ?? 0
                ),
            ],
            [
                'label' => 'Denegados',
                'value' => (int) (
                    $row->denied_count
                    ?? 0
                ),
            ],
            [
                'label' => 'Duplicados',
                'value' => (int) (
                    $row->duplicate_count
                    ?? 0
                ),
            ],
            [
                'label' => 'Manuales',
                'value' => (int) (
                    $row->manual_count
                    ?? 0
                ),
            ],
        ];
    }

    private function weekly(
        array $daily,
        string $timezone
    ): array {
        $weeks = [];

        foreach ($daily as $date => $metrics) {
            $day = Carbon::parse(
                $date,
                $timezone
            );

            $weekStart = $day
                ->copy()
                ->startOfWeek();

            $weekEnd = $day
                ->copy()
                ->endOfWeek();

            $key = $weekStart
                ->toDateString();

            $weeks[$key] ??=
                array_merge(
                    $this->metricRow(),
                    [
                        'label' => sprintf(
                            '%s–%s',
                            $weekStart->format('d/m'),
                            $weekEnd->format('d/m')
                        ),
                    ]
                );

            foreach (
                array_keys(
                    $this->metricRow()
                )
                as $field
            ) {
                $weeks[$key][$field] +=
                    (int) (
                        $metrics[$field]
                        ?? 0
                    );
            }
        }

        ksort($weeks);

        return collect($weeks)
            ->map(
                fn (array $row): array =>
                    $this->withRates($row)
            )
            ->values()
            ->all();
    }

    private function withRates(
        array $row
    ): array {
        $closedExpected = max(
            0,
            (int) $row['expected']
            - (int) $row['pending']
        );

        $present = (int)
            $row['present'];

        return [
            ...$row,

            'attendance_rate' =>
                $closedExpected > 0
                    ? round(
                        (
                            $present
                            / $closedExpected
                        ) * 100,
                        1
                    )
                    : 0.0,

            'punctuality_rate' =>
                $present > 0
                    ? round(
                        (
                            (int) $row['on_time']
                            / $present
                        ) * 100,
                        1
                    )
                    : 0.0,
        ];
    }

    private function sumMetrics(
        Collection $rows
    ): array {
        $total = $this->metricRow();

        foreach ($rows as $row) {
            foreach (
                array_keys($total)
                as $field
            ) {
                $total[$field] +=
                    (int) (
                        $row[$field]
                        ?? 0
                    );
            }
        }

        return $total;
    }

    private function metricRow(): array
    {
        return [
            'expected' => 0,
            'present' => 0,
            'absent' => 0,
            'pending' => 0,
            'on_time' => 0,
            'late' => 0,
            'very_late' => 0,
            'early_exit' => 0,
            'missing_exit' => 0,
        ];
    }

    private function emptyData(): array
    {
        return [
            'weekly_trend' => [],
            'group_comparison' => [],
            'weekday_distribution' => [],
            'attendance_mix' => [],
            'access_summary' => [],
        ];
    }
}
