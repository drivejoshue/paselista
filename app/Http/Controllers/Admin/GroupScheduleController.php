<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $this->schoolId($request);
        $activeCycle = $this->activeCycle($schoolId);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],

            'campus_id' => [
                'nullable',
                'integer',
                Rule::exists('campuses', 'id')
                    ->where('school_id', $schoolId),
            ],

            'level_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_levels', 'id')
                    ->where('school_id', $schoolId),
            ],

            'configuration' => [
                'nullable',
                Rule::in([
                    'complete',
                    'warning',
                    'without_schedule',
                    'inactive',
                ]),
            ],
        ]);

        $filters = [
            'search' => trim(
                (string) ($validated['search'] ?? '')
            ),

            'campus_id' => ! empty($validated['campus_id'])
                ? (int) $validated['campus_id']
                : null,

            'level_id' => ! empty($validated['level_id'])
                ? (int) $validated['level_id']
                : null,

            'configuration' =>
                $validated['configuration'] ?? null,
        ];

        $groups = collect();
        $campuses = collect();
        $levels = collect();

        if ($activeCycle) {
            $groups = DB::table('school_groups as sg')
                ->leftJoin(
                    'academic_levels as al',
                    'al.id',
                    '=',
                    'sg.academic_level_id'
                )
                ->leftJoin(
                    'campuses as c',
                    'c.id',
                    '=',
                    'sg.campus_id'
                )
                ->where('sg.school_id', $schoolId)
                ->where(
                    'sg.academic_cycle_id',
                    $activeCycle->id
                )
                ->when(
                    $filters['search'] !== '',
                    function ($query) use ($filters): void {
                        $search = $filters['search'];

                        $query->where(
                            function ($inner) use ($search): void {
                                $inner
                                    ->where(
                                        'sg.name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'sg.grade_label',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'al.name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'c.name',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $filters['campus_id'],
                    fn ($query, $campusId) =>
                        $query->where(
                            'sg.campus_id',
                            $campusId
                        )
                )
                ->when(
                    $filters['level_id'],
                    fn ($query, $levelId) =>
                        $query->where(
                            'sg.academic_level_id',
                            $levelId
                        )
                )
                ->select([
                    'sg.id',
                    'sg.name',
                    'sg.grade_label',
                    'sg.status',
                    'sg.requires_guardian_scan',
                    'sg.auto_transition_minutes',
                    'sg.campus_id',
                    'sg.academic_level_id',

                    'al.name as level_name',
                    'al.sort_order as level_sort_order',
                    'c.name as campus_name',

                    DB::raw(
                        "(
                            SELECT COUNT(*)
                            FROM student_enrollments se
                            WHERE se.school_id = sg.school_id
                              AND se.academic_cycle_id = sg.academic_cycle_id
                              AND se.school_group_id = sg.id
                              AND se.status = 'active'
                        ) as students_count"
                    ),
                ])
                ->orderBy('c.name')
                ->orderBy('al.sort_order')
                ->orderBy('sg.name')
                ->get();

            $scheduleRows = DB::table(
                'group_access_schedules'
            )
                ->where('school_id', $schoolId)
                ->whereIn(
                    'group_id',
                    $groups->pluck('id')
                )
                ->orderBy('group_id')
                ->orderBy('weekday')
                ->orderByDesc('id')
                ->get()
                ->groupBy('group_id');

            $groups = $groups
                ->map(function ($group) use ($scheduleRows): object {
                    $rows = $scheduleRows->get(
                        $group->id,
                        collect()
                    );

                    $diagnostics = $this->diagnostics(
                        rows: $rows,
                        autoTransitionMinutes:
                            (int) ($group->auto_transition_minutes ?? 30)
                    );

                    foreach ($diagnostics as $key => $value) {
                        $group->{$key} = $value;
                    }

                    return $group;
                })
                ->when(
                    $filters['configuration'],
                    function (
                        Collection $collection,
                        string $configuration
                    ): Collection {
                        return $collection
                            ->filter(
                                fn (object $group): bool =>
                                    $group->configuration_status
                                    === $configuration
                            )
                            ->values();
                    }
                );

            $campuses = DB::table('campuses as c')
                ->join(
                    'school_groups as sg',
                    'sg.campus_id',
                    '=',
                    'c.id'
                )
                ->where('c.school_id', $schoolId)
                ->where('c.status', 'active')
                ->where('sg.school_id', $schoolId)
                ->where(
                    'sg.academic_cycle_id',
                    $activeCycle->id
                )
                ->select([
                    'c.id',
                    'c.name',
                ])
                ->distinct()
                ->orderBy('c.name')
                ->get();

            $levels = DB::table('academic_levels as al')
                ->join(
                    'school_groups as sg',
                    'sg.academic_level_id',
                    '=',
                    'al.id'
                )
                ->where('al.school_id', $schoolId)
                ->where('al.status', 'active')
                ->where('sg.school_id', $schoolId)
                ->where(
                    'sg.academic_cycle_id',
                    $activeCycle->id
                )
                ->select([
                    'al.id',
                    'al.name',
                    'al.sort_order',
                ])
                ->distinct()
                ->orderBy('al.sort_order')
                ->orderBy('al.name')
                ->get();
        }

        $summary = [
            'total' => $groups->count(),

            'complete' => $groups
                ->where(
                    'configuration_status',
                    'complete'
                )
                ->count(),

            'warning' => $groups
                ->where(
                    'configuration_status',
                    'warning'
                )
                ->count(),

            'without_schedule' => $groups
                ->where(
                    'configuration_status',
                    'without_schedule'
                )
                ->count(),

            'guardian_required' => $groups
                ->where('requires_guardian_scan', 1)
                ->count(),
        ];

        return view('admin.groups.index', [
            'groups' => $groups,
            'activeCycle' => $activeCycle,
            'campuses' => $campuses,
            'levels' => $levels,
            'filters' => $filters,
            'summary' => $summary,
            'weekdays' => $this->weekdays(),
        ]);
    }

    public function edit(
        Request $request,
        int $group
    ): View {
        $schoolId = $this->schoolId($request);
        $activeCycle = $this->activeCycle($schoolId);

        abort_unless(
            $activeCycle,
            404,
            'No existe un ciclo activo.'
        );

        $groupRow = DB::table('school_groups as sg')
            ->leftJoin(
                'academic_levels as al',
                'al.id',
                '=',
                'sg.academic_level_id'
            )
            ->leftJoin(
                'campuses as c',
                'c.id',
                '=',
                'sg.campus_id'
            )
            ->where('sg.school_id', $schoolId)
            ->where(
                'sg.academic_cycle_id',
                $activeCycle->id
            )
            ->where('sg.id', $group)
            ->select([
                'sg.*',
                'al.name as level_name',
                'c.name as campus_name',
            ])
            ->firstOrFail();

        $allScheduleRows = DB::table(
            'group_access_schedules'
        )
            ->where('school_id', $schoolId)
            ->where('group_id', $group)
            ->orderBy('weekday')
            ->orderByDesc('id')
            ->get();

        /*
         * Si existieran filas históricas duplicadas por día,
         * la más reciente es la que se muestra. Al guardar, el
         * controlador normaliza y elimina las copias sobrantes.
         */
        $schedules = $allScheduleRows
            ->groupBy('weekday')
            ->map(
                fn (Collection $rows) => $rows->first()
            );

        $diagnostics = $this->diagnostics(
            rows: $allScheduleRows,
            autoTransitionMinutes:
                (int) ($groupRow->auto_transition_minutes ?? 30)
        );

        $studentsCount = DB::table(
            'student_enrollments'
        )
            ->where('school_id', $schoolId)
            ->where(
                'academic_cycle_id',
                $activeCycle->id
            )
            ->where('school_group_id', $group)
            ->where('status', 'active')
            ->count();

        return view('admin.groups.schedules', [
            'groupRow' => $groupRow,
            'schedules' => $schedules,
            'weekdays' => $this->weekdays(),
            'activeCycle' => $activeCycle,
            'diagnostics' => $diagnostics,
            'studentsCount' => $studentsCount,
        ]);
    }

    public function update(
        Request $request,
        int $group
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);
        $activeCycle = $this->activeCycle($schoolId);

        if (! $activeCycle) {
            return back()->withErrors([
                'schedule' =>
                    'No existe un ciclo escolar activo.',
            ]);
        }

        $groupRow = DB::table('school_groups')
            ->where('school_id', $schoolId)
            ->where(
                'academic_cycle_id',
                $activeCycle->id
            )
            ->where('id', $group)
            ->firstOrFail();

        $data = $request->validate([
            'requires_guardian_scan' => [
                'nullable',
                'boolean',
            ],

            'auto_transition_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:120',
            ],

            'active_weekdays' => [
                'nullable',
                'array',
            ],

            'active_weekdays.*' => [
                'integer',
                'between:1,7',
            ],

            'entry_time' => [
                'nullable',
                'array',
            ],

            'entry_time.*' => [
                'nullable',
                'date_format:H:i',
            ],

            'grace_until' => [
                'nullable',
                'array',
            ],

            'grace_until.*' => [
                'nullable',
                'date_format:H:i',
            ],

            'late_until' => [
                'nullable',
                'array',
            ],

            'late_until.*' => [
                'nullable',
                'date_format:H:i',
            ],

            'exit_time' => [
                'nullable',
                'array',
            ],

            'exit_time.*' => [
                'nullable',
                'date_format:H:i',
            ],
        ]);

        $activeWeekdays = collect(
            $data['active_weekdays'] ?? []
        )
            ->map(fn ($day): int => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $autoTransitionMinutes = (int)
            $data['auto_transition_minutes'];

        $normalizedSchedules = [];
        $validationErrors = [];

        foreach (range(1, 7) as $weekday) {
            $isActive = in_array(
                $weekday,
                $activeWeekdays,
                true
            );

            $entryTime = $this->timeValue(
                $data,
                'entry_time',
                $weekday
            );

            $graceUntil = $this->timeValue(
                $data,
                'grace_until',
                $weekday
            );

            $lateUntil = $this->timeValue(
                $data,
                'late_until',
                $weekday
            );

            $exitTime = $this->timeValue(
                $data,
                'exit_time',
                $weekday
            );

            if ($isActive) {
                if (
                    ! $entryTime
                    || ! $graceUntil
                    || ! $lateUntil
                    || ! $exitTime
                ) {
                    $validationErrors[] = sprintf(
                        '%s: completa entrada, tolerancia, límite de retardo y salida.',
                        $this->weekdays()[$weekday]
                    );

                    continue;
                }

                if (! (
                    $entryTime <= $graceUntil
                    && $graceUntil <= $lateUntil
                    && $lateUntil < $exitTime
                )) {
                    $validationErrors[] = sprintf(
                        '%s: el orden debe ser entrada ≤ tolerancia ≤ límite de retardo < salida.',
                        $this->weekdays()[$weekday]
                    );
                }

                $transitionStart = Carbon::createFromFormat(
                    'H:i',
                    $exitTime
                )
                    ->subMinutes($autoTransitionMinutes)
                    ->format('H:i');

                if ($transitionStart < $lateUntil) {
                    $validationErrors[] = sprintf(
                        '%s: la transición automática iniciaría a las %s, antes de terminar la ventana de llegada (%s). Reduce la anticipación o ajusta el horario.',
                        $this->weekdays()[$weekday],
                        $transitionStart,
                        $lateUntil
                    );
                }
            }

            $normalizedSchedules[$weekday] = [
                'entry_time' => $entryTime
                    ?: '07:00',

                'grace_until' => $graceUntil
                    ?: '07:10',

                'late_until' => $lateUntil
                    ?: '07:30',

                'exit_time' => $exitTime
                    ?: '13:00',

                'status' => $isActive
                    ? 'active'
                    : 'inactive',
            ];
        }

        if ($validationErrors !== []) {
            return back()
                ->withInput()
                ->withErrors([
                    'schedule' => implode(
                        ' ',
                        $validationErrors
                    ),
                ]);
        }

        DB::transaction(
            function () use (
                $schoolId,
                $groupRow,
                $request,
                $autoTransitionMinutes,
                $normalizedSchedules
            ): void {
                DB::table('school_groups')
                    ->where('school_id', $schoolId)
                    ->where('id', $groupRow->id)
                    ->update([
                        'requires_guardian_scan' =>
                            $request->boolean(
                                'requires_guardian_scan'
                            ),

                        'auto_transition_minutes' =>
                            $autoTransitionMinutes,

                        'updated_at' => now(),
                    ]);

                $existingRows = DB::table(
                    'group_access_schedules'
                )
                    ->where('school_id', $schoolId)
                    ->where('group_id', $groupRow->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get()
                    ->groupBy('weekday');

                foreach (
                    $normalizedSchedules
                    as $weekday => $schedule
                ) {
                    $rowsForDay = $existingRows->get(
                        $weekday,
                        collect()
                    );

                    $primary = $rowsForDay->first();

                    if ($primary) {
                        DB::table(
                            'group_access_schedules'
                        )
                            ->where('id', $primary->id)
                            ->where('school_id', $schoolId)
                            ->update([
                                ...$schedule,
                                'updated_at' => now(),
                            ]);

                        $duplicateIds = $rowsForDay
                            ->skip(1)
                            ->pluck('id');

                        if ($duplicateIds->isNotEmpty()) {
                            DB::table(
                                'group_access_schedules'
                            )
                                ->where('school_id', $schoolId)
                                ->whereIn('id', $duplicateIds)
                                ->delete();
                        }
                    } else {
                        DB::table(
                            'group_access_schedules'
                        )->insert([
                            'school_id' => $schoolId,
                            'group_id' => $groupRow->id,
                            'weekday' => $weekday,
                            ...$schedule,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            },
            3
        );

        return redirect()
            ->route(
                'admin.groups.schedules.edit',
                $groupRow->id
            )
            ->with(
                'success',
                sprintf(
                    'Configuración actualizada: %d día(s) activo(s).',
                    count($activeWeekdays)
                )
            );
    }

    private function diagnostics(
        Collection $rows,
        int $autoTransitionMinutes
    ): array {
        $grouped = $rows->groupBy('weekday');

        $activeDays = [];
        $invalidDays = [];
        $duplicateDays = [];

        foreach (range(1, 7) as $weekday) {
            $rowsForDay = $grouped->get(
                $weekday,
                collect()
            );

            if ($rowsForDay->count() > 1) {
                $duplicateDays[] = $weekday;
            }

            $schedule = $rowsForDay
                ->sortByDesc('id')
                ->first();

            if (! $schedule || $schedule->status !== 'active') {
                continue;
            }

            $activeDays[] = $weekday;

            $entry = substr(
                (string) $schedule->entry_time,
                0,
                5
            );

            $grace = substr(
                (string) $schedule->grace_until,
                0,
                5
            );

            $late = substr(
                (string) $schedule->late_until,
                0,
                5
            );

            $exit = substr(
                (string) $schedule->exit_time,
                0,
                5
            );

            $sequenceIsValid = (
                $entry <= $grace
                && $grace <= $late
                && $late < $exit
            );

            $transitionStart = Carbon::createFromFormat(
                'H:i',
                $exit
            )
                ->subMinutes($autoTransitionMinutes)
                ->format('H:i');

            $transitionIsValid =
                $transitionStart >= $late;

            if (
                ! $sequenceIsValid
                || ! $transitionIsValid
            ) {
                $invalidDays[] = $weekday;
            }
        }

        if ($activeDays === []) {
            $configurationStatus =
                'without_schedule';
        } elseif (
            $invalidDays !== []
            || $duplicateDays !== []
        ) {
            $configurationStatus = 'warning';
        } else {
            $configurationStatus = 'complete';
        }

        return [
            'active_schedules_count' =>
                count($activeDays),

            'active_weekdays' =>
                $activeDays,

            'invalid_weekdays' =>
                array_values(
                    array_unique($invalidDays)
                ),

            'duplicate_weekdays' =>
                array_values(
                    array_unique($duplicateDays)
                ),

            'configuration_status' =>
                $configurationStatus,
        ];
    }

    private function timeValue(
        array $data,
        string $field,
        int $weekday
    ): ?string {
        $value = $data[$field][$weekday]
            ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    private function activeCycle(
        int $schoolId
    ): ?object {
        return DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();
    }

    private function schoolId(
        Request $request
    ): int {
        $user = $request->user();

        abort_unless(
            $user && $user->school_id,
            403
        );

        return (int) $user->school_id;
    }

    private function weekdays(): array
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    }
}
