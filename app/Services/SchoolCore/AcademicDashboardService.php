<?php
declare(strict_types=1);

namespace App\Services\SchoolCore;

use Illuminate\Support\Facades\DB;

class AcademicDashboardService
{
    public function get(int $schoolId): array
    {
        $cycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->first(['id', 'name']);

        $cycleId = $cycle?->id;

        return [
            'cycle' => $cycle ? [
                'id' => (int) $cycle->id,
                'name' => $cycle->name,
            ] : null,

            'stats' => [
                'teachers' => DB::table('academic_teacher_profiles')
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->count(),

                'subjects' => DB::table('academic_subjects')
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->count(),

                'assignments' => $cycleId
                    ? DB::table('academic_teaching_assignments')
                        ->where('school_id', $schoolId)
                        ->where('academic_cycle_id', $cycleId)
                        ->where('status', 'active')
                        ->count()
                    : 0,

                'periods' => $cycleId
                    ? DB::table('academic_grading_periods')
                        ->where('school_id', $schoolId)
                        ->where('academic_cycle_id', $cycleId)
                        ->count()
                    : 0,
            ],

            'subjects' => DB::table('academic_subjects')
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'code', 'kind'])
                ->toArray(),
        ];
    }
}