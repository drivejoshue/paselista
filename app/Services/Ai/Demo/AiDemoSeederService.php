<?php

namespace App\Services\Ai\Demo;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AiDemoSeederService
{
    private const CODE_PREFIX = 'IADEMO';

    private const FIRST_NAMES = [
        'Mateo', 'Sofía', 'Emiliano', 'Valentina', 'Santiago', 'Renata',
        'Leonardo', 'Camila', 'Sebastián', 'Regina', 'Diego', 'Victoria',
        'Nicolás', 'Luciana', 'Daniel', 'Mariana', 'Alejandro', 'Ximena',
        'Gael', 'Natalia', 'Rodrigo', 'Fernanda', 'Bruno', 'Elena',
    ];

    private const LAST_NAMES = [
        'Gómez Hernández', 'López García', 'Martínez Ruiz', 'Pérez Torres',
        'Hernández Díaz', 'García Morales', 'Sánchez Flores', 'Ramírez Cruz',
        'Torres Mendoza', 'Flores Ortega', 'Morales Castillo', 'Vargas Romero',
        'Reyes Navarro', 'Mendoza Silva', 'Ortega Ramos', 'Castillo Vega',
        'Romero Aguilar', 'Navarro Medina', 'Silva Rojas', 'Ramos Cabrera',
        'Vega Salazar', 'Aguilar Luna', 'Medina Soto', 'Rojas Campos',
    ];

    private const PROFILES = [
        'punctual',
        'occasional_late',
        'monday_late',
        'random_absence',
        'consecutive_absence',
        'improving',
        'worsening',
        'early_exit',
        'missing_exit',
        'manual_duplicates',
    ];

    public function seed(
        int $schoolId,
        int $studentsPerGroup,
        int $days
    ): array {
        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->where('status', 'active')
            ->first();

        if (! $school) {
            throw new RuntimeException('La escuela no existe o no está activa.');
        }

        $cycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        if (! $cycle) {
            throw new RuntimeException('La escuela no tiene un ciclo activo.');
        }

        $timezone = $school->timezone ?: config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();
        $cycleStart = Carbon::parse($cycle->starts_on, $timezone)->startOfDay();
        $cycleEnd = Carbon::parse($cycle->ends_on, $timezone)->startOfDay();
        $periodEnd = $today->copy()->min($cycleEnd);
        $periodStart = $periodEnd->copy()->subDays($days - 1)->max($cycleStart);

        if ($periodStart->isAfter($periodEnd)) {
            throw new RuntimeException(
                'El ciclo activo todavía no inicia o no contiene fechas utilizables.'
            );
        }

        $groups = DB::table('school_groups as sg')
            ->where('sg.school_id', $schoolId)
            ->where('sg.academic_cycle_id', $cycle->id)
            ->where('sg.status', 'active')
            ->whereExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('group_access_schedules as gas')
                    ->whereColumn('gas.group_id', 'sg.id')
                    ->whereColumn('gas.school_id', 'sg.school_id')
                    ->where('gas.status', 'active');
            })
            ->get([
                'sg.id',
                'sg.campus_id',
                'sg.name',
            ]);

        if ($groups->isEmpty()) {
            throw new RuntimeException(
                'No hay grupos activos con horarios configurados.'
            );
        }

        $schedules = DB::table('group_access_schedules')
            ->where('school_id', $schoolId)
            ->whereIn('group_id', $groups->pluck('id'))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (object $row): string => $row->group_id.'|'.$row->weekday)
            ->map(fn (Collection $rows): object => $rows->first());

        $noClassDates = DB::table('school_calendar_days')
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->whereBetween('date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->where('status', 'active')
            ->whereIn('type', [
                'holiday',
                'vacation',
                'suspension',
                'technical_council',
                'no_class',
            ])
            ->pluck('date')
            ->map(fn ($date): string => Carbon::parse($date)->toDateString())
            ->flip();

        $creatorId = DB::table('users')
            ->where('school_id', $schoolId)
            ->whereIn('role', ['superadmin', 'school_admin'])
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id');

        $deviceByCampus = DB::table('access_devices')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->orderByDesc('last_seen_at')
            ->get(['id', 'campus_id'])
            ->groupBy('campus_id')
            ->map(fn (Collection $rows) => $rows->first()->id);

        $createdStudents = 0;
        $attendanceRows = 0;
        $accessRows = 0;

        foreach ($groups as $groupIndex => $group) {
            $classDates = $this->classDates(
                groupId: (int) $group->id,
                schedules: $schedules,
                noClassDates: $noClassDates,
                from: $periodStart,
                to: $periodEnd
            );

            if ($classDates->isEmpty()) {
                continue;
            }

            for ($studentIndex = 0; $studentIndex < $studentsPerGroup; $studentIndex++) {
                $code = sprintf(
                    '%s-%d-%d-%03d',
                    self::CODE_PREFIX,
                    $schoolId,
                    $group->id,
                    $studentIndex + 1
                );

                if (DB::table('students')
                    ->where('school_id', $schoolId)
                    ->where('student_code', $code)
                    ->exists()
                ) {
                    continue;
                }

                $nameIndex = ($groupIndex * $studentsPerGroup) + $studentIndex;
                $firstName = self::FIRST_NAMES[$nameIndex % count(self::FIRST_NAMES)];
                $lastName = self::LAST_NAMES[$nameIndex % count(self::LAST_NAMES)];
                $profile = self::PROFILES[$studentIndex % count(self::PROFILES)];

                $studentId = DB::table('students')->insertGetId([
                    'school_id' => $schoolId,
                    'campus_id' => $group->campus_id,
                    'current_group_id' => $group->id,
                    'user_id' => null,
                    'student_code' => $code,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'photo_url' => null,
                    'status' => 'active',
                    'notes' => 'Alumno ficticio para pruebas de SchoolPass IA. Perfil: '.$profile,
                    'requires_guardian_scan_override' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $enrollmentId = DB::table('student_enrollments')->insertGetId([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'academic_cycle_id' => $cycle->id,
                    'school_group_id' => $group->id,
                    'campus_id' => $group->campus_id,
                    'previous_enrollment_id' => null,
                    'status' => 'active',
                    'enrollment_type' => 'new',
                    'enrolled_on' => $periodStart->toDateString(),
                    'completed_on' => null,
                    'withdrawn_on' => null,
                    'withdrawal_reason' => null,
                    'notes' => 'Inscripción ficticia generada para pruebas de SchoolPass IA.',
                    'created_by_user_id' => $creatorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $records = $this->recordsForStudent(
                    schoolId: $schoolId,
                    cycleId: (int) $cycle->id,
                    campusId: (int) $group->campus_id,
                    groupId: (int) $group->id,
                    studentId: (int) $studentId,
                    enrollmentId: (int) $enrollmentId,
                    deviceId: $deviceByCampus->get($group->campus_id),
                    profile: $profile,
                    classDates: $classDates,
                    schedules: $schedules,
                    timezone: $timezone
                );

                foreach (array_chunk($records['logs'], 500) as $chunk) {
                    DB::table('access_logs')->insert($chunk);
                }

                $logMap = DB::table('access_logs')
                    ->where('school_id', $schoolId)
                    ->where('student_id', $studentId)
                    ->where('notes', 'like', 'AI_DEMO:'.$studentId.':%')
                    ->pluck('id', 'notes');

                $attendance = collect($records['attendance'])
                    ->map(function (array $row) use ($logMap): array {
                        $row['entry_log_id'] = $logMap->get($row['_entry_marker']);
                        $row['exit_log_id'] = $row['_exit_marker']
                            ? $logMap->get($row['_exit_marker'])
                            : null;

                        unset($row['_entry_marker'], $row['_exit_marker']);

                        return $row;
                    })
                    ->all();

                foreach (array_chunk($attendance, 500) as $chunk) {
                    DB::table('daily_attendance')->insert($chunk);
                }

                $createdStudents++;
                $attendanceRows += count($attendance);
                $accessRows += count($records['logs']);
            }
        }

        return [
            'students' => $createdStudents,
            'attendance_rows' => $attendanceRows,
            'access_rows' => $accessRows,
            'groups' => $groups->count(),
            'period_from' => $periodStart->toDateString(),
            'period_to' => $periodEnd->toDateString(),
        ];
    }

    public function clear(int $schoolId): array
    {
        $studentIds = DB::table('students')
            ->where('school_id', $schoolId)
            ->where('student_code', 'like', self::CODE_PREFIX.'-'.$schoolId.'-%')
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return [
                'students' => 0,
                'attendance_rows' => 0,
                'access_rows' => 0,
            ];
        }

        $attendanceRows = DB::table('daily_attendance')
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->count();

        $accessRows = DB::table('access_logs')
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->count();

        DB::transaction(function () use ($schoolId, $studentIds): void {
            DB::table('daily_attendance')
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $studentIds)
                ->delete();

            DB::table('access_logs')
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $studentIds)
                ->delete();

            DB::table('student_guardians')
                ->whereIn('student_id', $studentIds)
                ->delete();

            DB::table('student_credentials')
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $studentIds)
                ->delete();

            DB::table('student_enrollments')
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $studentIds)
                ->delete();

            DB::table('students')
                ->where('school_id', $schoolId)
                ->whereIn('id', $studentIds)
                ->delete();
        });

        return [
            'students' => $studentIds->count(),
            'attendance_rows' => $attendanceRows,
            'access_rows' => $accessRows,
        ];
    }

    private function classDates(
        int $groupId,
        Collection $schedules,
        Collection $noClassDates,
        Carbon $from,
        Carbon $to
    ): Collection {
        $dates = collect();

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $dateKey = $date->toDateString();

            if ($noClassDates->has($dateKey)) {
                continue;
            }

            if (! $schedules->has($groupId.'|'.$date->dayOfWeekIso)) {
                continue;
            }

            $dates->push($date->copy());
        }

        return $dates;
    }

    private function recordsForStudent(
        int $schoolId,
        int $cycleId,
        int $campusId,
        int $groupId,
        int $studentId,
        int $enrollmentId,
        ?int $deviceId,
        string $profile,
        Collection $classDates,
        Collection $schedules,
        string $timezone
    ): array {
        $logs = [];
        $attendance = [];
        $middle = (int) floor($classDates->count() / 2);
        $consecutiveStart = max(1, $middle - 1);

        foreach ($classDates as $index => $date) {
            $schedule = $schedules->get($groupId.'|'.$date->dayOfWeekIso);
            $seed = abs(crc32($studentId.'|'.$date->toDateString())) % 100;

            if ($this->isAbsent(
                profile: $profile,
                index: $index,
                middle: $middle,
                consecutiveStart: $consecutiveStart,
                seed: $seed
            )) {
                continue;
            }

            $minutesLate = $this->minutesLate(
                profile: $profile,
                date: $date,
                index: $index,
                middle: $middle,
                seed: $seed
            );

            $entryTime = Carbon::parse(
                $date->toDateString().' '.substr((string) $schedule->entry_time, 0, 8),
                $timezone
            )->addMinutes($minutesLate);

            $exitTime = Carbon::parse(
                $date->toDateString().' '.substr((string) $schedule->exit_time, 0, 8),
                $timezone
            )->addMinutes(3 + ($seed % 13));

            $earlyExit = $profile === 'early_exit' && $seed < 34;

            if ($earlyExit) {
                $exitTime->subMinutes(60 + ($seed % 30));
            }

            $missingExit = $profile === 'missing_exit' && $seed < 28;
            $manual = $profile === 'manual_duplicates' && $seed < 48;

            $entryStatus = match (true) {
                $minutesLate > 20 => 'very_late',
                $minutesLate > 0 => 'late',
                default => 'on_time',
            };

            $attendanceStatus = $earlyExit ? 'early_exit' : $entryStatus;
            $entryMarker = sprintf(
                'AI_DEMO:%d:%s:entry',
                $studentId,
                $date->toDateString()
            );
            $exitMarker = $missingExit
                ? null
                : sprintf(
                    'AI_DEMO:%d:%s:exit',
                    $studentId,
                    $date->toDateString()
                );

            $logs[] = $this->logRow(
                $schoolId,
                $cycleId,
                $campusId,
                $groupId,
                $studentId,
                $enrollmentId,
                $deviceId,
                'entry',
                $entryStatus,
                'allowed',
                $entryTime,
                $manual ? 'manual' : 'qr',
                $manual ? 'manual' : 'camera_qr',
                $minutesLate,
                $entryMarker
            );

            if (! $missingExit) {
                $logs[] = $this->logRow(
                    $schoolId,
                    $cycleId,
                    $campusId,
                    $groupId,
                    $studentId,
                    $enrollmentId,
                    $deviceId,
                    'exit',
                    $earlyExit ? 'early_exit' : 'normal_exit',
                    'allowed',
                    $exitTime,
                    $manual ? 'manual' : 'qr',
                    $manual ? 'manual' : 'camera_qr',
                    null,
                    $exitMarker
                );
            }

            if ($profile === 'manual_duplicates' && $seed < 35) {
                $logs[] = $this->logRow(
                    $schoolId,
                    $cycleId,
                    $campusId,
                    $groupId,
                    $studentId,
                    $enrollmentId,
                    $deviceId,
                    'entry',
                    'duplicate',
                    'duplicate',
                    $entryTime->copy()->addSeconds(25),
                    'qr',
                    'camera_qr',
                    $minutesLate,
                    sprintf('AI_DEMO:%d:%s:duplicate', $studentId, $date->toDateString())
                );
            }

            if ($profile === 'manual_duplicates' && $seed % 17 === 0) {
                $logs[] = $this->logRow(
                    $schoolId,
                    $cycleId,
                    $campusId,
                    $groupId,
                    $studentId,
                    $enrollmentId,
                    $deviceId,
                    'exit',
                    'guardian_required',
                    'denied',
                    $exitTime->copy()->subMinutes(10),
                    'guardian_qr',
                    'camera_qr',
                    null,
                    sprintf('AI_DEMO:%d:%s:denied', $studentId, $date->toDateString())
                );
            }

            $attendance[] = [
                'school_id' => $schoolId,
                'campus_id' => $campusId,
                'student_id' => $studentId,
                'group_id' => $groupId,
                'date' => $date->toDateString(),
                'entry_log_id' => null,
                'exit_log_id' => null,
                'attendance_status' => $attendanceStatus,
                'entry_at' => $entryTime->format('Y-m-d H:i:s'),
                'exit_at' => $missingExit ? null : $exitTime->format('Y-m-d H:i:s'),
                'minutes_late' => $minutesLate,
                'created_at' => now(),
                'updated_at' => now(),
                '_entry_marker' => $entryMarker,
                '_exit_marker' => $exitMarker,
            ];
        }

        return [
            'logs' => $logs,
            'attendance' => $attendance,
        ];
    }

    private function isAbsent(
        string $profile,
        int $index,
        int $middle,
        int $consecutiveStart,
        int $seed
    ): bool {
        return match ($profile) {
            'random_absence' => $seed < 18,
            'consecutive_absence' =>
                $index >= $consecutiveStart
                && $index < $consecutiveStart + 3,
            'improving' => $index < $middle && $seed < 14,
            'worsening' => $index >= $middle && $seed < 18,
            default => false,
        };
    }

    private function minutesLate(
        string $profile,
        Carbon $date,
        int $index,
        int $middle,
        int $seed
    ): int {
        return match ($profile) {
            'occasional_late' => $seed < 32 ? 5 + ($seed % 13) : 0,
            'monday_late' => $date->dayOfWeekIso === 1
                ? 12 + ($seed % 16)
                : ($seed < 8 ? 5 + ($seed % 8) : 0),
            'improving' => $index < $middle
                ? ($seed < 55 ? 8 + ($seed % 24) : 0)
                : ($seed < 10 ? 4 + ($seed % 7) : 0),
            'worsening' => $index < $middle
                ? ($seed < 10 ? 4 + ($seed % 7) : 0)
                : ($seed < 58 ? 9 + ($seed % 27) : 0),
            default => $seed < 5 ? 3 + ($seed % 4) : 0,
        };
    }

    private function logRow(
        int $schoolId,
        int $cycleId,
        int $campusId,
        int $groupId,
        int $studentId,
        int $enrollmentId,
        ?int $deviceId,
        string $eventType,
        string $eventStatus,
        string $decision,
        Carbon $scannedAt,
        string $source,
        string $readerType,
        ?int $minutesLate,
        string $notes
    ): array {
        return [
            'school_id' => $schoolId,
            'campus_id' => $campusId,
            'area_id' => null,
            'access_device_id' => $deviceId,
            'student_id' => $studentId,
            'guardian_id' => null,
            'academic_cycle_id' => $cycleId,
            'student_enrollment_id' => $enrollmentId,
            'school_group_id' => $groupId,
            'credential_id' => null,
            'guardian_credential_id' => null,
            'user_id' => null,
            'event_type' => $eventType,
            'event_status' => $eventStatus,
            'decision' => $decision,
            'action' => 'none',
            'scanned_at' => $scannedAt->format('Y-m-d H:i:s'),
            'source' => $source,
            'reader_type' => $readerType,
            'performed_for' => 'self',
            'minutes_late' => $minutesLate,
            'reason' => null,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
