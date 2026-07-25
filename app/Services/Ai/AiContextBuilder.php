<?php

namespace App\Services\Ai;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiContextBuilder
{
    private const NO_CLASS_TYPES = [
        'holiday',
        'vacation',
        'suspension',
        'technical_council',
        'no_class',
    ];

    public function build(
        int $schoolId,
        string $scopeType,
        ?int $scopeId,
        Carbon $requestedFrom,
        Carbon $requestedTo,
        string $question
    ): array {
        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->firstOrFail();

        $cycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        if (! $cycle) {
            throw ValidationException::withMessages([
                'question' => 'No existe un ciclo escolar activo para analizar.',
            ]);
        }

        $timezone = $school->timezone ?: config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();
        $cycleStart = Carbon::parse($cycle->starts_on, $timezone)->startOfDay();
        $cycleEnd = Carbon::parse($cycle->ends_on, $timezone)->startOfDay();

        $originalRequestedFrom = $requestedFrom
            ->copy()
            ->timezone($timezone)
            ->startOfDay();

        $originalRequestedTo = $requestedTo
            ->copy()
            ->timezone($timezone)
            ->startOfDay();

        $from = $originalRequestedFrom
            ->copy()
            ->max($cycleStart);

        $to = $originalRequestedTo
            ->copy()
            ->min($cycleEnd)
            ->min($today);

        $periodAdjusted =
            ! $from->isSameDay(
                $originalRequestedFrom
            )
            || ! $to->isSameDay(
                $originalRequestedTo
            );

        $periodAdjustmentMessage = $periodAdjusted
            ? sprintf(
                'El periodo solicitado (%s a %s) se ajustó a los datos disponibles del ciclo activo (%s a %s).',
                $originalRequestedFrom->format('d/m/Y'),
                $originalRequestedTo->format('d/m/Y'),
                $from->format('d/m/Y'),
                $to->format('d/m/Y')
            )
            : null;

        if ($from->isAfter($to)) {
            throw ValidationException::withMessages([
                'period_from' => 'El periodo no coincide con el ciclo activo o está en el futuro.',
            ]);
        }

        $scope = $this->resolveScope(
            $schoolId,
            (int) $cycle->id,
            $scopeType,
            $scopeId
        );

        $enrollments = $this->enrollments(
            $schoolId,
            (int) $cycle->id,
            $scopeType,
            $scopeId,
            $from,
            $to
        );

        $studentIds = $enrollments
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $groupIds = $enrollments
            ->pluck('group_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $aliases = $this->aliases(
            $enrollments,
            $scopeType === 'student' ? $scopeId : null
        );

        $schedules = $this->schedules($schoolId, $groupIds);

        $noClassDates = DB::table('school_calendar_days')
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->whereBetween('date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->where('status', 'active')
            ->whereIn('type', self::NO_CLASS_TYPES)
            ->pluck('date')
            ->map(fn ($date): string => Carbon::parse($date)->toDateString())
            ->flip();

        $attendance = collect();

        if ($studentIds->isNotEmpty()) {
            $attendance = DB::table('daily_attendance')
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $studentIds)
                ->whereBetween('date', [
                    $from->toDateString(),
                    $to->toDateString(),
                ])
                ->get()
                ->groupBy(fn (object $row): string =>
                    $row->student_id.'|'.Carbon::parse($row->date)->toDateString()
                )
                ->map(fn (Collection $rows): object =>
                    $rows->sortByDesc('id')->first()
                );
        }

        $studentMetrics = $this->studentMetrics(
            $enrollments,
            $schedules,
            $noClassDates,
            $attendance,
            $aliases,
            $from,
            $to,
            $today,
            $timezone
        );

        $attention = $studentMetrics
            ->sortByDesc('attention_score')
            ->take((int) config('schoolpass_ai.limits.max_context_students', 25))
            ->values()
            ->map(fn (array $row): array => [
                'student_ref' => $row['student_ref'],
                'group' => $row['group'],
                'expected_days' => $row['expected_days'],
                'present_days' => $row['present_days'],
                'absent_days' => $row['absent_days'],
                'pending_days' => $row['pending_days'],
                'on_time_days' => $row['on_time_days'],
                'late_days' => $row['late_days'],
                'very_late_days' => $row['very_late_days'],
                'early_exits' => $row['early_exits'],
                'missing_exits' => $row['missing_exits'],
                'attendance_rate' => $row['attendance_rate'],
            ])
            ->all();

        return [
            'context' => [
                'schema_version' => 1,
                'scope' => [
                    'type' => $scopeType,
                    'reference' => $scope['reference'],
                    'label' => $scope['label'],
                ],
                'period' => [
                    'requested_from' =>
                        $originalRequestedFrom
                            ->toDateString(),

                    'requested_to' =>
                        $originalRequestedTo
                            ->toDateString(),

                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),

                    'days_in_calendar' =>
                        $from->diffInDays($to)
                        + 1,

                    'adjusted_to_available_data' =>
                        $periodAdjusted,

                    'adjustment_message' =>
                        $periodAdjustmentMessage,
                ],
                'cycle' => [
                    'name' => $cycle->name,
                    'starts_on' => $cycleStart->toDateString(),
                    'ends_on' => $cycleEnd->toDateString(),
                ],
                'summary' => $this->summary($studentMetrics),
                'access_activity' => $this->accessMetrics(
                    $schoolId,
                    $scopeType,
                    $scopeId,
                    $from,
                    $to
                ),
                'devices' => $this->deviceMetrics($schoolId, $timezone),
                'students_requiring_attention' => $attention,
                'data_notes' => array_values(
                    array_filter([
                        'Las ausencias se calculan solo en días con horario activo.',
                        'Los días sin clase del calendario escolar se excluyen.',
                        'Hoy no se cuenta como ausencia antes del cierre de la ventana de llegada.',
                        'No se enviaron nombres reales, contacto, fotos, QR ni nombres de tutores.',
                        $periodAdjustmentMessage,
                    ])
                ),
            ],
            'aliases' => $aliases,
            'redacted_question' => $this->redactQuestion(
                $question,
                $enrollments,
                $aliases
            ),
            'from' => $from,
            'to' => $to,

            'requested_from' =>
                $originalRequestedFrom,

            'requested_to' =>
                $originalRequestedTo,

            'period_adjusted' =>
                $periodAdjusted,

            'period_adjustment_message' =>
                $periodAdjustmentMessage,

            'scope' => $scope,
        ];
    }

    private function resolveScope(
        int $schoolId,
        int $cycleId,
        string $scopeType,
        ?int $scopeId
    ): array {
        if ($scopeType === 'school') {
            return [
                'reference' => 'SCHOOL',
                'label' => 'Toda la escuela',
            ];
        }

        if ($scopeType === 'group') {
            $group = DB::table('school_groups as sg')
                ->leftJoin('academic_levels as al', 'al.id', '=', 'sg.academic_level_id')
                ->leftJoin('campuses as c', 'c.id', '=', 'sg.campus_id')
                ->where('sg.school_id', $schoolId)
                ->where('sg.academic_cycle_id', $cycleId)
                ->where('sg.id', $scopeId)
                ->select([
                    'sg.id',
                    'sg.name',
                    'al.name as level_name',
                    'c.name as campus_name',
                ])
                ->first();

            if (! $group) {
                throw ValidationException::withMessages([
                    'scope_id' => 'El grupo no pertenece a la escuela o al ciclo activo.',
                ]);
            }

            return [
                'reference' => 'GROUP-'.$group->id,
                'label' => trim(
                    ($group->campus_name ?? '')
                    .' · '.($group->level_name ?? '')
                    .' · '.$group->name,
                    ' ·'
                ),
            ];
        }

        if ($scopeType === 'student') {
            $student = DB::table('student_enrollments as se')
                ->join('students as s', 's.id', '=', 'se.student_id')
                ->join('school_groups as sg', 'sg.id', '=', 'se.school_group_id')
                ->where('se.school_id', $schoolId)
                ->where('se.academic_cycle_id', $cycleId)
                ->where('se.status', 'active')
                ->where('s.status', 'active')
                ->where('s.id', $scopeId)
                ->select([
                    's.id',
                    's.first_name',
                    's.last_name',
                    'sg.name as group_name',
                ])
                ->first();

            if (! $student) {
                throw ValidationException::withMessages([
                    'scope_id' => 'El alumno no tiene una inscripción activa en este ciclo.',
                ]);
            }

            return [
                'reference' => 'ALUMNO-SELECCIONADO',
                'label' => trim($student->first_name.' '.$student->last_name),
            ];
        }

        throw ValidationException::withMessages([
            'scope_type' => 'El alcance seleccionado no es válido.',
        ]);
    }

    private function enrollments(
        int $schoolId,
        int $cycleId,
        string $scopeType,
        ?int $scopeId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return DB::table('student_enrollments as se')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->join('school_groups as sg', 'sg.id', '=', 'se.school_group_id')
            ->leftJoin('academic_levels as al', 'al.id', '=', 'sg.academic_level_id')
            ->leftJoin('campuses as c', 'c.id', '=', 'se.campus_id')
            ->where('se.school_id', $schoolId)
            ->where('se.academic_cycle_id', $cycleId)
            ->where('se.status', 'active')
            ->where('s.status', 'active')
            ->whereDate('se.enrolled_on', '<=', $to->toDateString())
            ->where(function ($query) use ($from): void {
                $query
                    ->whereNull('se.withdrawn_on')
                    ->orWhereDate('se.withdrawn_on', '>=', $from->toDateString());
            })
            ->when(
                $scopeType === 'group',
                fn ($query) => $query->where('se.school_group_id', $scopeId)
            )
            ->when(
                $scopeType === 'student',
                fn ($query) => $query->where('se.student_id', $scopeId)
            )
            ->select([
                'se.id as enrollment_id',
                'se.student_id',
                'se.school_group_id as group_id',
                'se.campus_id',
                'se.enrolled_on',
                'se.withdrawn_on',
                's.student_code',
                's.first_name',
                's.last_name',
                'sg.name as group_name',
                'al.name as level_name',
                'c.name as campus_name',
            ])
            ->orderBy('al.sort_order')
            ->orderBy('sg.name')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();
    }

    private function aliases(Collection $enrollments, ?int $selectedStudentId): array
    {
        $aliases = [];

        foreach ($enrollments as $row) {
            $alias = $selectedStudentId
                && (int) $row->student_id === $selectedStudentId
                    ? 'ALUMNO-SELECCIONADO'
                    : sprintf('ALU-%04d', (int) $row->student_id);

            $aliases[$alias] = trim($row->first_name.' '.$row->last_name);
        }

        return $aliases;
    }

    private function schedules(int $schoolId, Collection $groupIds): Collection
    {
        if ($groupIds->isEmpty()) {
            return collect();
        }

        return DB::table('group_access_schedules')
            ->where('school_id', $schoolId)
            ->whereIn('group_id', $groupIds)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (object $row): string => $row->group_id.'|'.$row->weekday)
            ->map(fn (Collection $rows): object => $rows->first());
    }

    private function studentMetrics(
        Collection $enrollments,
        Collection $schedules,
        Collection $noClassDates,
        Collection $attendance,
        array $aliases,
        Carbon $from,
        Carbon $to,
        Carbon $today,
        string $timezone
    ): Collection {
        $aliasByName = array_flip($aliases);

        return $enrollments->map(function (object $enrollment) use (
            $schedules,
            $noClassDates,
            $attendance,
            $aliasByName,
            $from,
            $to,
            $today,
            $timezone
        ): array {
            $fullName = trim($enrollment->first_name.' '.$enrollment->last_name);
            $alias = $aliasByName[$fullName]
                ?? sprintf('ALU-%04d', (int) $enrollment->student_id);

            $metrics = [
                'student_ref' => $alias,
                'group' => $enrollment->group_name,
                'expected_days' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'pending_days' => 0,
                'on_time_days' => 0,
                'late_days' => 0,
                'very_late_days' => 0,
                'early_exits' => 0,
                'missing_exits' => 0,
            ];

            $enrolledOn = $enrollment->enrolled_on
                ? Carbon::parse($enrollment->enrolled_on, $timezone)->startOfDay()
                : $from->copy();

            $withdrawnOn = $enrollment->withdrawn_on
                ? Carbon::parse($enrollment->withdrawn_on, $timezone)->startOfDay()
                : null;

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                if ($date->lt($enrolledOn)) {
                    continue;
                }

                if ($withdrawnOn && $date->gt($withdrawnOn)) {
                    continue;
                }

                $dateKey = $date->toDateString();

                if ($noClassDates->has($dateKey)) {
                    continue;
                }

                $schedule = $schedules->get(
                    $enrollment->group_id.'|'.$date->dayOfWeekIso
                );

                if (! $schedule) {
                    continue;
                }

                $metrics['expected_days']++;

                $row = $attendance->get(
                    $enrollment->student_id.'|'.$dateKey
                );

                if (! $row) {
                    if (
                        $date->isSameDay($today)
                        && Carbon::now($timezone)->format('H:i:s') <= $schedule->late_until
                    ) {
                        $metrics['pending_days']++;
                    } else {
                        $metrics['absent_days']++;
                    }

                    continue;
                }

                $metrics['present_days']++;
                $minutesLate = max(0, (int) ($row->minutes_late ?? 0));

                if ($minutesLate > 20) {
                    $metrics['very_late_days']++;
                } elseif ($minutesLate > 0 || $row->attendance_status === 'late') {
                    $metrics['late_days']++;
                } else {
                    $metrics['on_time_days']++;
                }

                if ($row->attendance_status === 'early_exit') {
                    $metrics['early_exits']++;
                }

                $exitShouldExist = $date->lt($today)
                    || (
                        $date->isSameDay($today)
                        && Carbon::now($timezone)->format('H:i:s') > $schedule->exit_time
                    );

                if ($exitShouldExist && $row->entry_at && ! $row->exit_at) {
                    $metrics['missing_exits']++;
                }
            }

            $closedExpected = max(
                0,
                $metrics['expected_days'] - $metrics['pending_days']
            );

            $metrics['attendance_rate'] = $closedExpected > 0
                ? round(($metrics['present_days'] / $closedExpected) * 100, 1)
                : 0.0;

            $metrics['attention_score'] =
                $metrics['absent_days'] * 5
                + $metrics['very_late_days'] * 3
                + $metrics['late_days'] * 2
                + $metrics['early_exits'] * 2
                + $metrics['missing_exits'] * 4;

            return $metrics;
        });
    }

    private function summary(Collection $metrics): array
    {
        $sum = fn (string $key): int => (int) $metrics->sum(
            fn (array $row): int => (int) $row[$key]
        );

        $expected = $sum('expected_days');
        $pending = $sum('pending_days');
        $closedExpected = max(0, $expected - $pending);
        $present = $sum('present_days');

        return [
            'students' => $metrics->count(),
            'expected_student_days' => $expected,
            'closed_expected_student_days' => $closedExpected,
            'present_student_days' => $present,
            'absent_student_days' => $sum('absent_days'),
            'pending_student_days' => $pending,
            'on_time_student_days' => $sum('on_time_days'),
            'late_student_days' => $sum('late_days'),
            'very_late_student_days' => $sum('very_late_days'),
            'early_exits' => $sum('early_exits'),
            'missing_exits' => $sum('missing_exits'),
            'attendance_rate' => $closedExpected > 0
                ? round(($present / $closedExpected) * 100, 1)
                : 0.0,
        ];
    }

    private function accessMetrics(
        int $schoolId,
        string $scopeType,
        ?int $scopeId,
        Carbon $from,
        Carbon $to
    ): array {
        $row = DB::table('access_logs')
            ->where('school_id', $schoolId)
            ->whereBetween('scanned_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->when(
                $scopeType === 'group',
                fn ($query) => $query->where('school_group_id', $scopeId)
            )
            ->when(
                $scopeType === 'student',
                fn ($query) => $query->where('student_id', $scopeId)
            )
            ->selectRaw(
                "COUNT(*) as total_events,
                 SUM(CASE WHEN decision = 'allowed' THEN 1 ELSE 0 END) as allowed_events,
                 SUM(CASE WHEN decision = 'denied' THEN 1 ELSE 0 END) as denied_events,
                 SUM(CASE WHEN event_status = 'duplicate' OR decision = 'duplicate' THEN 1 ELSE 0 END) as duplicate_events,
                 SUM(CASE WHEN source = 'manual' THEN 1 ELSE 0 END) as manual_events,
                 SUM(CASE WHEN event_status = 'guardian_required' THEN 1 ELSE 0 END) as guardian_required_denials,
                 SUM(CASE WHEN event_type = 'entry' AND decision = 'allowed' THEN 1 ELSE 0 END) as allowed_entries,
                 SUM(CASE WHEN event_type = 'exit' AND decision = 'allowed' THEN 1 ELSE 0 END) as allowed_exits"
            )
            ->first();

        return [
            'total_events' => (int) ($row->total_events ?? 0),
            'allowed_events' => (int) ($row->allowed_events ?? 0),
            'denied_events' => (int) ($row->denied_events ?? 0),
            'duplicate_events' => (int) ($row->duplicate_events ?? 0),
            'manual_events' => (int) ($row->manual_events ?? 0),
            'guardian_required_denials' => (int) ($row->guardian_required_denials ?? 0),
            'allowed_entries' => (int) ($row->allowed_entries ?? 0),
            'allowed_exits' => (int) ($row->allowed_exits ?? 0),
        ];
    }

    private function deviceMetrics(int $schoolId, string $timezone): array
    {
        $devices = DB::table('access_devices')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->get(['id', 'device_type', 'last_seen_at']);

        $offlineThreshold = Carbon::now($timezone)->subMinutes(10);

        $offline = $devices->filter(function (object $device) use (
            $offlineThreshold,
            $timezone
        ): bool {
            if (! $device->last_seen_at) {
                return true;
            }

            return Carbon::parse($device->last_seen_at, $timezone)
                ->lt($offlineThreshold);
        });

        return [
            'active_devices' => $devices->count(),
            'online_devices' => $devices->count() - $offline->count(),
            'offline_devices' => $offline->count(),
            'offline_device_refs' => $offline
                ->take(15)
                ->map(fn (object $device): string =>
                    'DEVICE-'.$device->id.' ('.$device->device_type.')'
                )
                ->values()
                ->all(),
        ];
    }

    private function redactQuestion(
        string $question,
        Collection $enrollments,
        array $aliases
    ): string {
        $redacted = preg_replace(
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu',
            '[CORREO-OCULTO]',
            $question
        );

        $redacted = preg_replace(
            '/(?:\+?52[\s\-]?)?(?:\d[\s\-]?){10,13}/',
            '[TELÉFONO-OCULTO]',
            (string) $redacted
        );

        $aliasByName = array_flip($aliases);

        foreach ($enrollments as $row) {
            $name = trim($row->first_name.' '.$row->last_name);
            $alias = $aliasByName[$name] ?? null;

            if (! $alias) {
                continue;
            }

            $redacted = str_ireplace($name, $alias, (string) $redacted);

            if (trim((string) $row->student_code) !== '') {
                $redacted = str_ireplace(
                    (string) $row->student_code,
                    $alias,
                    (string) $redacted
                );
            }
        }

        return trim((string) $redacted);
    }
}
