<?php

namespace App\Services\SchoolCore;

use Illuminate\Support\Facades\DB;

class DashboardService
{

public function __construct(
    private readonly ModuleAccessService $moduleAccess,
) {
}

    public function get(int $schoolId): array
    {
        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->first(['id', 'name']);

        abort_unless($school, 404);

        $cycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->first(['id', 'name']);

        $cycleId = $cycle?->id;

        $students = $cycleId
            ? DB::table('student_enrollments as se')
                ->join('students as s', function ($join) use ($schoolId) {
                    $join->on('s.id', '=', 'se.student_id')
                        ->where('s.school_id', $schoolId)
                        ->where('s.status', 'active');
                })
                ->where('se.school_id', $schoolId)
                ->where('se.academic_cycle_id', $cycleId)
                ->where('se.status', 'active')
                ->whereDate('se.enrolled_on', '<=', today())
                ->where(function ($query) {
                    $query->whereNull('se.withdrawn_on')
                        ->orWhereDate('se.withdrawn_on', '>=', today());
                })
                ->distinct()
                ->count('se.student_id')
            : 0;

        $groups = $cycleId
            ? DB::table('school_groups')
                ->where('school_id', $schoolId)
                ->where('academic_cycle_id', $cycleId)
                ->where('status', 'active')
                ->count()
            : 0;

        $teachers = DB::table('academic_teacher_profiles')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();

      $modules = collect(
    $this->moduleAccess->catalog($schoolId)
);
        return [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
            ],
            'cycle' => $cycle ? [
                'id' => $cycle->id,
                'name' => $cycle->name,
            ] : null,
            'stats' => [
    'students' => $students,
    'teachers' => $teachers,
    'groups' => $groups,
    'modules' => $modules
        ->where('active', true)
        ->count(),
],
            'modules' => $modules->all(),
        ];
    }
}
