<?php

namespace App\Services\Attendance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FamilyAttendanceCalendarService
{
    /**
     * Tipos del calendario que cancelan clases.
     */
    private const NO_CLASS_TYPES = [
        'holiday',
        'vacation',
        'suspension',
        'technical_council',
    ];

    /**
     * Tipos que obligatoriamente se consideran día escolar,
     * aunque caigan en sábado o domingo.
     */
    private const FORCE_CLASS_TYPES = [
        'class_day',
        'exam',
    ];

    public function __construct(
        private readonly AttendancePeriodService $attendancePeriod
    ) {
    }

    /**
     * Construye el calendario mensual completo para Family.
     *
     * El cliente Android no calcula faltas ni días escolares.
     * Solamente representa los estados recibidos.
     */
    public function build(
        int $schoolId,
        int $studentId,
        ?string $requestedMonth = null
    ): array {
        $timezone = (string) config(
            'app.timezone',
            'America/Mexico_City'
        );

        $requestedMonthStart = $this->resolveMonth(
            requestedMonth: $requestedMonth,
            timezone: $timezone
        );

        $activeWindow = $this->attendancePeriod
            ->attendanceWindow($schoolId);

        /*
         * Primero validamos que el alumno exista dentro
         * de la institución.
         */
        $basicStudent = $this->studentContext(
            schoolId: $schoolId,
            studentId: $studentId,
            cycleId: null
        );

        abort_unless($basicStudent, 404);

        /*
         * Sin ciclo activo no se infieren ausencias.
         *
         * Devolvemos un calendario estructurado para que
         * Android pueda mostrar un estado informativo.
         */
        if ($activeWindow === null) {
            return $this->withoutActiveCycle(
                student: $basicStudent,
                monthStart: $requestedMonthStart,
                timezone: $timezone
            );
        }

        $cycle = $activeWindow['cycle'];

        $cycleStart = Carbon::parse(
            $cycle->starts_on,
            $timezone
        )->startOfDay();

        $cycleEnd = Carbon::parse(
            $cycle->ends_on,
            $timezone
        )->endOfDay();

        /*
         * Para asistencia permitimos navegar desde el inicio
         * del ciclo hasta el mes actual.
         *
         * Si el ciclo activo todavía no comienza, se muestra
         * su primer mes sin fabricar faltas.
         */
        $monthResolution = $this->resolveAllowedMonth(
            requestedMonthStart: $requestedMonthStart,
            cycleStart: $cycleStart,
            cycleEnd: $cycleEnd,
            timezone: $timezone
        );

        $monthStart = $monthResolution['month_start'];
        $monthEnd = $monthStart->copy()->endOfMonth();

        $student = $this->studentContext(
            schoolId: $schoolId,
            studentId: $studentId,
            cycleId: (int) $cycle->id
        );

        abort_unless($student, 404);

        $hasEnrollment = $student->enrollment_id !== null;

        $enrollmentStart = null;
        $enrollmentEnd = null;

        if ($hasEnrollment) {
            $enrollmentStart = $student->enrolled_on
                ? Carbon::parse(
                    $student->enrolled_on,
                    $timezone
                )->startOfDay()
                : $cycleStart->copy();

            if ($student->withdrawn_on) {
                $enrollmentEnd = Carbon::parse(
                    $student->withdrawn_on,
                    $timezone
                )->endOfDay();
            } elseif ($student->completed_on) {
                $enrollmentEnd = Carbon::parse(
                    $student->completed_on,
                    $timezone
                )->endOfDay();
            } else {
                $enrollmentEnd = $cycleEnd->copy();
            }
        }

        $groupId = $student->group_id !== null
            ? (int) $student->group_id
            : null;

        $schedules = $this->groupSchedules(
            schoolId: $schoolId,
            groupId: $groupId
        );

        $calendarDays = $this->calendarDays(
            schoolId: $schoolId,
            cycleId: (int) $cycle->id,
            monthStart: $monthStart,
            monthEnd: $monthEnd
        );

        $attendance = $this->attendanceRecords(
            schoolId: $schoolId,
            studentId: $studentId,
            monthStart: $monthStart,
            monthEnd: $monthEnd
        );

        $today = Carbon::now($timezone)->startOfDay();
        $now = Carbon::now($timezone);

        $days = collect();
        $cursor = $monthStart->copy();

        while ($cursor->lte($monthEnd)) {
            $dateKey = $cursor->toDateString();

            $calendarDay = $calendarDays->get($dateKey);
            $schedule = $schedules->get(
                $cursor->isoWeekday()
            );
            $record = $attendance->get($dateKey);

            $insideCycle = $cursor->betweenIncluded(
                $cycleStart,
                $cycleEnd
            );

            $insideEnrollment = $hasEnrollment
                && $enrollmentStart !== null
                && $enrollmentEnd !== null
                && $cursor->betweenIncluded(
                    $enrollmentStart,
                    $enrollmentEnd
                );

            $isClassDay = $insideCycle
                && $insideEnrollment
                && $this->isClassDay(
                    calendarDay: $calendarDay,
                    schedule: $schedule
                );

            $dayStatus = $this->resolveDayStatus(
                date: $cursor,
                today: $today,
                now: $now,
                insideCycle: $insideCycle,
                insideEnrollment: $insideEnrollment,
                isClassDay: $isClassDay,
                schedule: $schedule,
                record: $record,
                timezone: $timezone
            );

            $days->push([
                'date' => $dateKey,
                'day' => $cursor->day,
                'weekday' => $cursor->isoWeekday(),

                'weekday_label' => ucfirst(
                    $cursor
                        ->copy()
                        ->locale('es')
                        ->dayName
                ),

                'is_today' => $cursor->isSameDay($today),
                'is_future' => $cursor->gt($today),
                'is_inside_cycle' => $insideCycle,
                'is_enrolled' => $insideEnrollment,
                'is_class_day' => $isClassDay,
                'has_record' => $record !== null,

                /*
                 * Estado utilizado por Android para el color.
                 */
                'visual_status' => $dayStatus['status'],

                /*
                 * Se mantiene también como status para
                 * compatibilidad temporal con el DTO anterior.
                 */
                'status' => $dayStatus['status'],

                'status_label' => $this->statusLabel(
                    $dayStatus['status']
                ),

                'attendance_id' => $record?->id,

                'attendance_status' =>
                    $record?->attendance_status,

                'entry_event_status' =>
                    $record?->entry_event_status,

                'exit_event_status' =>
                    $record?->exit_event_status,

                'entry_at' => $this->formatTime(
                    $record?->entry_at,
                    $timezone
                ),

                'exit_at' => $this->formatTime(
                    $record?->exit_at,
                    $timezone
                ),

                'minutes_late' => (int) (
                    $record?->minutes_late
                    ?? 0
                ),

                'incident' => $dayStatus['incident'],

                'calendar' => $calendarDay
                    ? [
                        'type' => $calendarDay->type,
                        'title' => $calendarDay->title,
                        'notes' => $calendarDay->notes,
                    ]
                    : null,
            ]);

            $cursor->addDay();
        }

        $summary = $this->summary($days);

        $navigation = $this->navigation(
            monthStart: $monthStart,
            cycleStart: $cycleStart,
            cycleEnd: $cycleEnd,
            timezone: $timezone
        );

        /*
         * Estructura compatible con el historial anterior.
         * Después de terminar Android podremos retirar items.
         */
        $legacyItems = $days
            ->map(function (array $day) use ($student): array {
                return [
                    'id' => $day['attendance_id'] ?? 0,
                    'date' => $day['date'],
                    'group' => $student->group_name,
                    'status' => $day['visual_status'],
                    'status_label' => $day['status_label'],
                    'entry_at' => $day['entry_at'],
                    'exit_at' => $day['exit_at'],
                    'minutes_late' => $day['minutes_late'],
                ];
            })
            ->values()
            ->all();

        return [
            'message' => null,

            'has_active_cycle' => true,
            'has_enrollment' => $hasEnrollment,

            'student' => [
                'id' => (int) $student->id,

                'name' => trim(
                    $student->first_name
                    .' '
                    .$student->last_name
                ),

                'student_code' =>
                    $student->student_code,

                'group_id' => $student->group_id
                    ? (int) $student->group_id
                    : null,

                'group' => $student->group_name,

                'enrollment_status' =>
                    $student->enrollment_status,
            ],

            'cycle' => [
                'id' => (int) $cycle->id,
                'name' => $cycle->name,
                'starts_on' => $cycleStart->toDateString(),
                'ends_on' => $cycleEnd->toDateString(),
            ],

            'month' => [
                'requested_value' =>
                    $requestedMonthStart->format('Y-m'),

                'value' => $monthStart->format('Y-m'),

                'label' => ucfirst(
                    $monthStart
                        ->copy()
                        ->locale('es')
                        ->translatedFormat('F Y')
                ),

                'starts_on' => $monthStart->toDateString(),
                'ends_on' => $monthEnd->toDateString(),

                'was_clamped' =>
                    $monthResolution['was_clamped'],
            ],

            'navigation' => $navigation,
            'summary' => $summary,

            /*
             * Respuesta nueva.
             */
            'days' => $days->values()->all(),

            /*
             * Compatibilidad con la pantalla anterior.
             */
            'student_id' => $studentId,
            'range' => 'month',
            'count' => $days->count(),
            'items' => $legacyItems,
        ];
    }

    private function studentContext(
        int $schoolId,
        int $studentId,
        ?int $cycleId
    ): ?object {
        return DB::table('students as s')
            ->leftJoin(
                'student_enrollments as se',
                function ($join) use (
                    $schoolId,
                    $cycleId
                ): void {
                    $join
                        ->on(
                            'se.student_id',
                            '=',
                            's.id'
                        )
                        ->where(
                            'se.school_id',
                            '=',
                            $schoolId
                        );

                    if ($cycleId !== null) {
                        $join->where(
                            'se.academic_cycle_id',
                            '=',
                            $cycleId
                        );
                    } else {
                        $join->whereRaw('1 = 0');
                    }
                }
            )
            ->leftJoin(
                'school_groups as enrollment_group',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'enrollment_group.id',
                            '=',
                            'se.school_group_id'
                        )
                        ->where(
                            'enrollment_group.school_id',
                            '=',
                            $schoolId
                        );
                }
            )
            ->leftJoin(
                'school_groups as current_group',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'current_group.id',
                            '=',
                            's.current_group_id'
                        )
                        ->where(
                            'current_group.school_id',
                            '=',
                            $schoolId
                        );
                }
            )
            ->where('s.school_id', $schoolId)
            ->where('s.id', $studentId)
            ->where('s.status', 'active')
            ->select([
                's.id',
                's.student_code',
                's.first_name',
                's.last_name',
                's.photo_url',
                's.current_group_id',

                'se.id as enrollment_id',
                'se.status as enrollment_status',
                'se.enrolled_on',
                'se.completed_on',
                'se.withdrawn_on',

                DB::raw(
                    'COALESCE(
                        enrollment_group.id,
                        current_group.id
                    ) as group_id'
                ),

                DB::raw(
                    'COALESCE(
                        enrollment_group.name,
                        current_group.name
                    ) as group_name'
                ),
            ])
            ->first();
    }

    private function groupSchedules(
        int $schoolId,
        ?int $groupId
    ): Collection {
        if ($groupId === null) {
            return collect();
        }

        return DB::table('group_access_schedules')
            ->where('school_id', $schoolId)
            ->where('group_id', $groupId)
            ->where('status', 'active')
            ->get([
                'weekday',
                'entry_time',
                'grace_until',
                'late_until',
                'exit_time',
            ])
            ->keyBy(
                fn (object $row): int =>
                    (int) $row->weekday
            );
    }

    private function calendarDays(
        int $schoolId,
        int $cycleId,
        Carbon $monthStart,
        Carbon $monthEnd
    ): Collection {
        return DB::table('school_calendar_days')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereBetween('date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->where(function ($query) use ($cycleId): void {
                $query
                    ->whereNull('academic_cycle_id')
                    ->orWhere(
                        'academic_cycle_id',
                        $cycleId
                    );
            })
            ->get([
                'id',
                'academic_cycle_id',
                'date',
                'type',
                'title',
                'notes',
            ])
            ->keyBy(
                fn (object $row): string =>
                    Carbon::parse($row->date)
                        ->toDateString()
            );
    }

    private function attendanceRecords(
        int $schoolId,
        int $studentId,
        Carbon $monthStart,
        Carbon $monthEnd
    ): Collection {
        return DB::table('daily_attendance as da')
            ->leftJoin(
                'access_logs as entry_log',
                'entry_log.id',
                '=',
                'da.entry_log_id'
            )
            ->leftJoin(
                'access_logs as exit_log',
                'exit_log.id',
                '=',
                'da.exit_log_id'
            )
            ->where('da.school_id', $schoolId)
            ->where('da.student_id', $studentId)
            ->whereBetween('da.date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->select([
                'da.id',
                'da.date',
                'da.attendance_status',
                'da.entry_at',
                'da.exit_at',
                'da.minutes_late',

                'entry_log.event_status as entry_event_status',
                'entry_log.decision as entry_decision',

                'exit_log.event_status as exit_event_status',
                'exit_log.decision as exit_decision',
            ])
            ->orderBy('da.date')
            ->get()
            ->keyBy(
                fn (object $row): string =>
                    Carbon::parse($row->date)
                        ->toDateString()
            );
    }

    private function isClassDay(
        ?object $calendarDay,
        ?object $schedule
    ): bool {
        $calendarType = strtolower(
            trim(
                (string) (
                    $calendarDay?->type
                    ?? ''
                )
            )
        );

        if (
            in_array(
                $calendarType,
                self::NO_CLASS_TYPES,
                true
            )
        ) {
            return false;
        }

        if (
            in_array(
                $calendarType,
                self::FORCE_CLASS_TYPES,
                true
            )
        ) {
            return true;
        }

        /*
         * Un evento escolar no crea por sí solo una obligación
         * de asistencia. Conserva la regla semanal del grupo.
         */
        return $schedule !== null;
    }

    private function resolveDayStatus(
        Carbon $date,
        Carbon $today,
        Carbon $now,
        bool $insideCycle,
        bool $insideEnrollment,
        bool $isClassDay,
        ?object $schedule,
        ?object $record,
        string $timezone
    ): array {
        if (! $insideCycle) {
            return [
                'status' => 'outside_cycle',
                'incident' => null,
            ];
        }

        if (! $insideEnrollment) {
            return [
                'status' => 'not_enrolled',
                'incident' => null,
            ];
        }

        if (! $isClassDay) {
            return [
                'status' => 'no_class',
                'incident' =>
                    $this->recordIncident($record),
            ];
        }

        if ($record !== null) {
            return [
                'status' =>
                    $this->recordVisualStatus(
                        record: $record,
                        schedule: $schedule,
                        timezone: $timezone
                    ),

                'incident' =>
                    $this->recordIncident($record),
            ];
        }

        if ($date->gt($today)) {
            return [
                'status' => 'future',
                'incident' => null,
            ];
        }

        if ($date->isSameDay($today)) {
            $cutoff = $schedule?->exit_time
                ? Carbon::parse(
                    $date->toDateString()
                    .' '
                    .$schedule->exit_time,
                    $timezone
                )
                : $date->copy()->endOfDay();

            if ($now->lt($cutoff)) {
                return [
                    'status' => 'pending',
                    'incident' => null,
                ];
            }
        }

        /*
         * Solo se infiere ausencia cuando:
         *
         * - está dentro del ciclo;
         * - existe inscripción;
         * - corresponde clase;
         * - el periodo evaluable ya terminó;
         * - no existe daily_attendance.
         */
        return [
            'status' => 'absent',
            'incident' => null,
        ];
    }

    private function recordVisualStatus(
        object $record,
        ?object $schedule,
        string $timezone
    ): string {
        $attendanceStatus = strtolower(
            trim(
                (string) (
                    $record->attendance_status
                    ?? ''
                )
            )
        );

        $entryEventStatus = strtolower(
            trim(
                (string) (
                    $record->entry_event_status
                    ?? ''
                )
            )
        );

        if (
            in_array(
                $entryEventStatus,
                [
                    'on_time',
                    'late',
                    'very_late',
                ],
                true
            )
        ) {
            return $entryEventStatus;
        }

        if (
            in_array(
                $attendanceStatus,
                [
                    'absent',
                    'justified',
                    'pending',
                    'partial',
                    'very_late',
                ],
                true
            )
        ) {
            return $attendanceStatus;
        }

        if ($attendanceStatus === 'late') {
            if (
                $this->isVeryLate(
                    entryAt: $record->entry_at,
                    schedule: $schedule,
                    timezone: $timezone
                )
            ) {
                return 'very_late';
            }

            return 'late';
        }

        if (
            in_array(
                $attendanceStatus,
                [
                    'present',
                    'on_time',
                ],
                true
            )
        ) {
            return 'on_time';
        }

        /*
         * early_exit describe el incidente de salida.
         * El color principal sigue representando la entrada.
         */
        if ($attendanceStatus === 'early_exit') {
            return (int) ($record->minutes_late ?? 0) > 0
                ? 'late'
                : 'on_time';
        }

        if (! empty($record->entry_at)) {
            return (int) ($record->minutes_late ?? 0) > 0
                ? 'late'
                : 'on_time';
        }

        return 'partial';
    }

    private function isVeryLate(
        ?string $entryAt,
        ?object $schedule,
        string $timezone
    ): bool {
        if (
            empty($entryAt)
            || $schedule === null
            || empty($schedule->late_until)
        ) {
            return false;
        }

        $entry = Carbon::parse(
            $entryAt,
            $timezone
        );

        $lateUntil = Carbon::parse(
            $entry->toDateString()
            .' '
            .$schedule->late_until,
            $timezone
        );

        return $entry->gt($lateUntil);
    }

    private function recordIncident(
        ?object $record
    ): ?string {
        if ($record === null) {
            return null;
        }

        $attendanceStatus = strtolower(
            trim(
                (string) (
                    $record->attendance_status
                    ?? ''
                )
            )
        );

        $exitEventStatus = strtolower(
            trim(
                (string) (
                    $record->exit_event_status
                    ?? ''
                )
            )
        );

        if (
            $attendanceStatus === 'early_exit'
            || $exitEventStatus === 'early_exit'
        ) {
            return 'early_exit';
        }

        return null;
    }

    private function summary(
        Collection $days
    ): array {
        $summary = $this->emptySummary();

        foreach ($days as $day) {
            $status = (string) $day['visual_status'];

            if (
                $day['is_inside_cycle']
                && $day['is_enrolled']
                && $day['is_class_day']
            ) {
                $summary['scheduled_class_days']++;
            }

            if (
                array_key_exists(
                    $status,
                    $summary
                )
                && is_int($summary[$status])
            ) {
                $summary[$status]++;
            }

            if ($day['incident'] === 'early_exit') {
                $summary['early_exits']++;
            }
        }

        $summary['attendance_days'] =
            $summary['on_time']
            + $summary['late']
            + $summary['very_late'];

        $summary['evaluated_days'] =
            $summary['attendance_days']
            + $summary['absent']
            + $summary['justified']
            + $summary['partial'];

        $attendanceBase =
            $summary['attendance_days']
            + $summary['absent']
            + $summary['partial'];

        $summary['attendance_rate'] =
            $attendanceBase > 0
                ? round(
                    (
                        $summary['attendance_days']
                        / $attendanceBase
                    ) * 100,
                    1
                )
                : 0.0;

        $summary['punctuality_rate'] =
            $summary['attendance_days'] > 0
                ? round(
                    (
                        $summary['on_time']
                        / $summary['attendance_days']
                    ) * 100,
                    1
                )
                : 0.0;

        $summary['incidents'] =
            $summary['late']
            + $summary['very_late']
            + $summary['absent']
            + $summary['partial']
            + $summary['early_exits'];

        return $summary;
    }

    private function emptySummary(): array
    {
        return [
            'scheduled_class_days' => 0,
            'evaluated_days' => 0,
            'attendance_days' => 0,

            'on_time' => 0,
            'late' => 0,
            'very_late' => 0,
            'absent' => 0,
            'justified' => 0,
            'partial' => 0,
            'pending' => 0,
            'future' => 0,
            'no_class' => 0,
            'outside_cycle' => 0,
            'not_enrolled' => 0,

            'early_exits' => 0,
            'incidents' => 0,

            'attendance_rate' => 0.0,
            'punctuality_rate' => 0.0,
        ];
    }

    private function navigation(
        Carbon $monthStart,
        Carbon $cycleStart,
        Carbon $cycleEnd,
        string $timezone
    ): array {
        $cycleStartMonth = $cycleStart
            ->copy()
            ->startOfMonth();

        $cycleEndMonth = $cycleEnd
            ->copy()
            ->startOfMonth();

        $currentMonth = Carbon::now($timezone)
            ->startOfMonth();

        $lastAllowedMonth = $cycleEndMonth->lt(
            $currentMonth
        )
            ? $cycleEndMonth
            : $currentMonth;

        if ($cycleStartMonth->gt($lastAllowedMonth)) {
            $lastAllowedMonth = $cycleStartMonth->copy();
        }

        $previous = $monthStart
            ->copy()
            ->subMonthNoOverflow()
            ->startOfMonth();

        $next = $monthStart
            ->copy()
            ->addMonthNoOverflow()
            ->startOfMonth();

        $canPrevious = $previous->gte(
            $cycleStartMonth
        );

        $canNext = $next->lte(
            $lastAllowedMonth
        );

        return [
            'previous_month' => $canPrevious
                ? $previous->format('Y-m')
                : null,

            'next_month' => $canNext
                ? $next->format('Y-m')
                : null,

            'can_go_previous' => $canPrevious,
            'can_go_next' => $canNext,

            'first_month' =>
                $cycleStartMonth->format('Y-m'),

            'last_month' =>
                $lastAllowedMonth->format('Y-m'),
        ];
    }

    private function resolveAllowedMonth(
        Carbon $requestedMonthStart,
        Carbon $cycleStart,
        Carbon $cycleEnd,
        string $timezone
    ): array {
        $cycleStartMonth = $cycleStart
            ->copy()
            ->startOfMonth();

        $cycleEndMonth = $cycleEnd
            ->copy()
            ->startOfMonth();

        $currentMonth = Carbon::now($timezone)
            ->startOfMonth();

        $lastAllowedMonth = $cycleEndMonth->lt(
            $currentMonth
        )
            ? $cycleEndMonth
            : $currentMonth;

        /*
         * Ciclo activo que comienza en el futuro.
         */
        if ($cycleStartMonth->gt($lastAllowedMonth)) {
            $lastAllowedMonth = $cycleStartMonth->copy();
        }

        $monthStart = $requestedMonthStart
            ->copy()
            ->startOfMonth();

        if ($monthStart->lt($cycleStartMonth)) {
            $monthStart = $cycleStartMonth->copy();
        }

        if ($monthStart->gt($lastAllowedMonth)) {
            $monthStart = $lastAllowedMonth->copy();
        }

        return [
            'month_start' => $monthStart,

            'was_clamped' =>
                ! $monthStart->isSameMonth(
                    $requestedMonthStart
                ),
        ];
    }

    private function resolveMonth(
        ?string $requestedMonth,
        string $timezone
    ): Carbon {
        $value = trim(
            (string) $requestedMonth
        );

        if ($value === '') {
            return Carbon::now($timezone)
                ->startOfMonth();
        }

        return Carbon::createFromFormat(
            'Y-m-d',
            $value.'-01',
            $timezone
        )->startOfMonth();
    }

    private function withoutActiveCycle(
        object $student,
        Carbon $monthStart,
        string $timezone
    ): array {
        $monthEnd = $monthStart
            ->copy()
            ->endOfMonth();

        $days = collect();
        $cursor = $monthStart->copy();
        $today = Carbon::now($timezone)->startOfDay();

        while ($cursor->lte($monthEnd)) {
            $days->push([
                'date' => $cursor->toDateString(),
                'day' => $cursor->day,
                'weekday' => $cursor->isoWeekday(),

                'weekday_label' => ucfirst(
                    $cursor
                        ->copy()
                        ->locale('es')
                        ->dayName
                ),

                'is_today' => $cursor->isSameDay($today),
                'is_future' => $cursor->gt($today),
                'is_inside_cycle' => false,
                'is_enrolled' => false,
                'is_class_day' => false,
                'has_record' => false,

                'visual_status' => 'outside_cycle',
                'status' => 'outside_cycle',
                'status_label' => 'Sin ciclo activo',

                'attendance_id' => null,
                'attendance_status' => null,
                'entry_event_status' => null,
                'exit_event_status' => null,
                'entry_at' => null,
                'exit_at' => null,
                'minutes_late' => 0,
                'incident' => null,
                'calendar' => null,
            ]);

            $cursor->addDay();
        }

        $summary = $this->emptySummary();
        $summary['outside_cycle'] = $days->count();

        return [
            'message' =>
                'La institución no tiene un ciclo escolar activo.',

            'has_active_cycle' => false,
            'has_enrollment' => false,

            'student' => [
                'id' => (int) $student->id,

                'name' => trim(
                    $student->first_name
                    .' '
                    .$student->last_name
                ),

                'student_code' =>
                    $student->student_code,

                'group_id' => $student->group_id
                    ? (int) $student->group_id
                    : null,

                'group' => $student->group_name,

                'enrollment_status' => null,
            ],

            'cycle' => null,

            'month' => [
                'requested_value' =>
                    $monthStart->format('Y-m'),

                'value' =>
                    $monthStart->format('Y-m'),

                'label' => ucfirst(
                    $monthStart
                        ->copy()
                        ->locale('es')
                        ->translatedFormat('F Y')
                ),

                'starts_on' =>
                    $monthStart->toDateString(),

                'ends_on' =>
                    $monthEnd->toDateString(),

                'was_clamped' => false,
            ],

            'navigation' => [
                'previous_month' => null,
                'next_month' => null,
                'can_go_previous' => false,
                'can_go_next' => false,
                'first_month' => null,
                'last_month' => null,
            ],

            'summary' => $summary,
            'days' => $days->values()->all(),

            'student_id' => (int) $student->id,
            'range' => 'month',
            'count' => $days->count(),

            'items' => $days
                ->map(function (array $day) use ($student): array {
                    return [
                        'id' => 0,
                        'date' => $day['date'],
                        'group' => $student->group_name,
                        'status' => 'outside_cycle',
                        'status_label' => 'Sin ciclo activo',
                        'entry_at' => null,
                        'exit_at' => null,
                        'minutes_late' => 0,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function statusLabel(
        string $status
    ): string {
        return match ($status) {
            'on_time' => 'Puntual',
            'late' => 'Retardo',
            'very_late' => 'Retardo mayor',
            'absent' => 'Falta',
            'justified' => 'Falta justificada',
            'partial' => 'Registro incompleto',
            'pending' => 'Pendiente',
            'future' => 'Próximo',
            'no_class' => 'Sin clases',
            'outside_cycle' => 'Fuera del ciclo',
            'not_enrolled' => 'Sin inscripción',
            default => 'Sin información',
        };
    }

    private function formatTime(
        ?string $value,
        string $timezone
    ): ?string {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse(
            $value,
            $timezone
        )->format('H:i');
    }
}