<?php
declare(strict_types=1);

namespace App\Services\Academic;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicWorkflowService
{
    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'academic' => $message,
        ]);
    }

    private function cycle(int $schoolId): object
    {
        $cycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->where('status', 'active')
            ->first();

        if (!$cycle) {
            $this->fail('No existe un ciclo escolar activo.');
        }

        return $cycle;
    }

    public function catalog(int $schoolId): array
    {
        $cycle = $this->cycle($schoolId);

        return [
            'cycle' => [
                'id' => (int) $cycle->id,
                'name' => $cycle->name,
            ],

            'levels' => DB::table('academic_levels')
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get(['id', 'name']),

            'groups' => DB::table('school_groups')
                ->where('school_id', $schoolId)
                ->where('academic_cycle_id', $cycle->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'grade_label',
                    'academic_level_id',
                ]),

            'subjects' => DB::table('academic_subjects')
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),

            'teachers' => DB::table('academic_teacher_profiles as t')
                ->join('users as u', 'u.id', '=', 't.user_id')
                ->where('t.school_id', $schoolId)
                ->where('u.school_id', $schoolId)
                ->where('t.status', 'active')
                ->orderBy('u.name')
                ->get([
                    't.id',
                    'u.name',
                ]),

            'plans' => DB::table('academic_curriculum_plans as p')
                ->join(
                    'academic_levels as l',
                    'l.id',
                    '=',
                    'p.academic_level_id'
                )
                ->where('p.school_id', $schoolId)
                ->where('p.academic_cycle_id', $cycle->id)
                ->orderBy('l.sort_order')
                ->orderBy('p.name')
                ->get([
                    'p.id',
                    'p.name',
                    'p.version',
                    'p.status',
                    'p.academic_level_id',
                    'l.name as level_name',
                ]),

            'planSubjects' => DB::table(
                'academic_curriculum_plan_subjects as ps'
            )
                ->join(
                    'academic_curriculum_plans as p',
                    'p.id',
                    '=',
                    'ps.curriculum_plan_id'
                )
                ->join(
                    'academic_subjects as s',
                    's.id',
                    '=',
                    'ps.subject_id'
                )
                ->where('ps.school_id', $schoolId)
                ->where('p.school_id', $schoolId)
                ->where('p.academic_cycle_id', $cycle->id)
                ->orderBy('ps.sort_order')
                ->get([
                    'ps.id',
                    'ps.curriculum_plan_id',
                    'ps.grade_label',
                    'ps.affects_average',
                    's.name as subject_name',
                ]),

            'assignments' => DB::table(
                'academic_teaching_assignments as a'
            )
                ->join(
                    'school_groups as g',
                    'g.id',
                    '=',
                    'a.school_group_id'
                )
                ->join(
                    'academic_curriculum_plan_subjects as ps',
                    'ps.id',
                    '=',
                    'a.plan_subject_id'
                )
                ->join(
                    'academic_subjects as s',
                    's.id',
                    '=',
                    'ps.subject_id'
                )
                ->leftJoin(
                    'academic_teacher_profiles as t',
                    't.id',
                    '=',
                    'a.teacher_profile_id'
                )
                ->leftJoin(
                    'users as u',
                    'u.id',
                    '=',
                    't.user_id'
                )
                ->where('a.school_id', $schoolId)
                ->where('a.academic_cycle_id', $cycle->id)
                ->where('g.school_id', $schoolId)
                ->where('ps.school_id', $schoolId)
                ->orderBy('g.name')
                ->get([
                    'a.id',
                    'a.status',
                    'a.school_group_id',
                    'a.plan_subject_id',
                    'g.name as group_name',
                    's.name as subject_name',
                    'u.name as teacher_name',
                ]),

            'periods' => DB::table('academic_grading_periods')
                ->where('school_id', $schoolId)
                ->where('academic_cycle_id', $cycle->id)
                ->orderBy('sequence')
                ->get([
                    'id',
                    'name',
                    'sequence',
                    'academic_level_id',
                    'status',
                ]),

            'gradebooks' => DB::table('academic_gradebooks as gb')
                ->join(
                    'academic_teaching_assignments as a',
                    'a.id',
                    '=',
                    'gb.teaching_assignment_id'
                )
                ->join(
                    'academic_grading_periods as gp',
                    'gp.id',
                    '=',
                    'gb.grading_period_id'
                )
                ->where('gb.school_id', $schoolId)
                ->where('a.school_id', $schoolId)
                ->where('gp.school_id', $schoolId)
                ->where('a.academic_cycle_id', $cycle->id)
                ->where('gp.academic_cycle_id', $cycle->id)
                ->get([
                    'gb.id',
                    'gb.status',
                    'gb.teaching_assignment_id',
                    'gb.grading_period_id',
                    'gp.name as period_name',
                ]),
        ];
    }

    public function createPlan(
        int $schoolId,
        array $data
    ): int {
        $cycle = $this->cycle($schoolId);

        $level = DB::table('academic_levels')
            ->where('school_id', $schoolId)
            ->where('id', $data['academic_level_id'])
            ->where('status', 'active')
            ->exists();

        if (!$level) {
            $this->fail('El nivel no pertenece a la escuela.');
        }

        return DB::table('academic_curriculum_plans')
            ->insertGetId([
                'school_id' => $schoolId,
                'academic_cycle_id' => $cycle->id,
                'academic_level_id' => $data['academic_level_id'],
                'name' => $data['name'],
                'framework_code' => $data['framework_code'] ?? null,
                'version' => 1,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function addPlanSubject(
        int $schoolId,
        array $data
    ): int {
        $cycle = $this->cycle($schoolId);

        $plan = DB::table('academic_curriculum_plans')
            ->where('id', $data['curriculum_plan_id'])
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->where('status', 'draft')
            ->first();

        if (!$plan) {
            $this->fail('El plan no está disponible para edición.');
        }

        $subject = DB::table('academic_subjects')
            ->where('id', $data['subject_id'])
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if (!$subject) {
            $this->fail('La materia no pertenece a la escuela.');
        }

        $duplicate = DB::table('academic_curriculum_plan_subjects')
            ->where('school_id', $schoolId)
            ->where('curriculum_plan_id', $plan->id)
            ->where('subject_id', $subject->id)
            ->where('grade_label', $data['grade_label'])
            ->exists();

        if ($duplicate) {
            $this->fail('La materia ya está registrada para ese grado.');
        }

        return DB::table('academic_curriculum_plan_subjects')
            ->insertGetId([
                'school_id' => $schoolId,
                'curriculum_plan_id' => $plan->id,
                'subject_id' => $subject->id,
                'grade_label' => $data['grade_label'],
                'is_official' => $data['is_official'],
                'affects_average' => $data['affects_average'],
                'sort_order' => $data['sort_order'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function createAssignment(
        int $schoolId,
        array $data
    ): int {
        $cycle = $this->cycle($schoolId);

        $group = DB::table('school_groups')
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->where('status', 'active')
            ->where('id', $data['school_group_id'])
            ->first();

        if (!$group) {
            $this->fail('El grupo no pertenece al ciclo activo.');
        }

        $planSubject = DB::table(
            'academic_curriculum_plan_subjects as ps'
        )
            ->join(
                'academic_curriculum_plans as p',
                'p.id',
                '=',
                'ps.curriculum_plan_id'
            )
            ->where('ps.id', $data['plan_subject_id'])
            ->where('ps.school_id', $schoolId)
            ->where('p.school_id', $schoolId)
            ->where('p.academic_cycle_id', $cycle->id)
            ->where('p.academic_level_id', $group->academic_level_id)
            ->where('p.status', 'active')
            ->where(function ($query) use ($group) {
                $query->whereNull('ps.grade_label')
                    ->orWhere(
                        'ps.grade_label',
                        $group->grade_label
                    );
            })
            ->first(['ps.id']);

        if (!$planSubject) {
            $this->fail(
                'La materia no corresponde al nivel y grado del grupo o el plan no está activo.'
            );
        }

        if (!empty($data['teacher_profile_id'])) {
            $teacher = DB::table('academic_teacher_profiles')
                ->where('id', $data['teacher_profile_id'])
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->exists();

            if (!$teacher) {
                $this->fail('El docente no está disponible.');
            }
        }

        $duplicate = DB::table('academic_teaching_assignments')
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->where('school_group_id', $group->id)
            ->where('plan_subject_id', $planSubject->id)
            ->where('status', 'active')
            ->exists();

        if ($duplicate) {
            $this->fail('Ya existe una asignación activa para esa materia y grupo.');
        }

        return DB::table('academic_teaching_assignments')
            ->insertGetId([
                'school_id' => $schoolId,
                'academic_cycle_id' => $cycle->id,
                'school_group_id' => $group->id,
                'plan_subject_id' => $planSubject->id,
                'teacher_profile_id' => $data['teacher_profile_id'] ?? null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function createGradebook(
        int $schoolId,
        array $data
    ): int {
        $cycle = $this->cycle($schoolId);

        $assignment = DB::table('academic_teaching_assignments')
            ->where('id', $data['teaching_assignment_id'])
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->where('status', 'active')
            ->first();

        $period = DB::table('academic_grading_periods')
            ->where('id', $data['grading_period_id'])
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->first();

        if (!$assignment || !$period) {
            $this->fail('Asignación o período inválido.');
        }

        $group = DB::table('school_groups')
            ->where('id', $assignment->school_group_id)
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->first();

        if (
            !$group
            || (
                $period->academic_level_id !== null
                && (int) $period->academic_level_id
                    !== (int) $group->academic_level_id
            )
        ) {
            $this->fail('El período no corresponde al nivel del grupo.');
        }

        return DB::transaction(function () use (
            $schoolId,
            $assignment,
            $period
        ): int {
            DB::table('academic_gradebooks')->insertOrIgnore([
                'school_id' => $schoolId,
                'teaching_assignment_id' => $assignment->id,
                'grading_period_id' => $period->id,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (int) DB::table('academic_gradebooks')
                ->where('school_id', $schoolId)
                ->where('teaching_assignment_id', $assignment->id)
                ->where('grading_period_id', $period->id)
                ->value('id');
        });
    }


public function activatePlan(int $schoolId, int $planId): void
{
    $cycle = $this->cycle($schoolId);

    DB::transaction(function () use ($schoolId, $cycle, $planId): void {
        $plan = DB::table('academic_curriculum_plans')
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->where('id', $planId)
            ->lockForUpdate()
            ->first();

        if (!$plan || $plan->status !== 'draft') {
            $this->fail('El plan no está disponible para activación.');
        }

        $hasSubjects = DB::table('academic_curriculum_plan_subjects')
            ->where('school_id', $schoolId)
            ->where('curriculum_plan_id', $planId)
            ->exists();

        if (!$hasSubjects) {
            $this->fail('Agrega al menos una materia al plan.');
        }

        DB::table('academic_curriculum_plans')
            ->where('id', $planId)
            ->where('school_id', $schoolId)
            ->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);
    });
}

public function activatePeriod(int $schoolId, int $periodId): void
{
    $cycle = $this->cycle($schoolId);

    DB::table('academic_grading_periods')
        ->where('school_id', $schoolId)
        ->where('academic_cycle_id', $cycle->id)
        ->where('id', $periodId)
        ->where('status', 'draft')
        ->update([
            'status' => 'active',
            'updated_at' => now(),
        ]);
}

public function gradebookRoster(int $schoolId, int $gradebookId): array
{
    $cycle = $this->cycle($schoolId);

    $book = DB::table('academic_gradebooks as gb')
        ->join(
            'academic_teaching_assignments as a',
            'a.id',
            '=',
            'gb.teaching_assignment_id'
        )
        ->join(
            'academic_grading_periods as gp',
            'gp.id',
            '=',
            'gb.grading_period_id'
        )
        ->where('gb.id', $gradebookId)
        ->where('gb.school_id', $schoolId)
        ->where('a.school_id', $schoolId)
        ->where('gp.school_id', $schoolId)
        ->where('a.academic_cycle_id', $cycle->id)
        ->where('gp.academic_cycle_id', $cycle->id)
        ->first([
            'gb.id',
            'gb.status',
            'a.school_group_id',
            'a.academic_cycle_id',
            'gp.name as period_name',
            'gp.status as period_status',
            'gp.academic_level_id',
        ]);

    if (!$book) {
        $this->fail('El libro de calificaciones no existe.');
    }

    $group = DB::table('school_groups')
        ->where('id', $book->school_group_id)
        ->where('school_id', $schoolId)
        ->where('academic_cycle_id', $cycle->id)
        ->first(['id', 'academic_level_id']);

    if (
        !$group
        || (
            $book->academic_level_id !== null
            && (int) $book->academic_level_id
                !== (int) $group->academic_level_id
        )
    ) {
        $this->fail('El grupo no corresponde al período.');
    }

    $students = DB::table('student_enrollments as e')
        ->join('students as s', 's.id', '=', 'e.student_id')
        ->leftJoin('academic_student_grades as g', function ($join) use ($gradebookId) {
            $join->on('g.student_enrollment_id', '=', 'e.id')
                ->where('g.gradebook_id', '=', $gradebookId);
        })
        ->where('e.school_id', $schoolId)
        ->where('s.school_id', $schoolId)
        ->where('e.academic_cycle_id', $cycle->id)
        ->where('e.school_group_id', $book->school_group_id)
        ->where('e.status', 'active')
        ->where('s.status', 'active')
        ->orderBy('s.last_name')
        ->orderBy('s.first_name')
        ->get([
            'e.id as enrollment_id',
            's.student_code',
            's.first_name',
            's.last_name',
            'g.numeric_value',
            'g.text_value',
            'g.comment',
        ]);

    return [
        'book' => $book,
        'students' => $students,
        'editable' => $book->status === 'draft'
            && $book->period_status === 'active',
    ];
}

public function saveGrade(
    int $schoolId,
    int $gradebookId,
    int $enrollmentId,
    int $actorUserId,
    array $data
): void {
    DB::transaction(function () use (
        $schoolId,
        $gradebookId,
        $enrollmentId,
        $actorUserId,
        $data
    ): void {
        $cycle = $this->cycle($schoolId);

        $book = DB::table('academic_gradebooks as gb')
            ->join(
                'academic_teaching_assignments as a',
                'a.id',
                '=',
                'gb.teaching_assignment_id'
            )
            ->join(
                'academic_grading_periods as gp',
                'gp.id',
                '=',
                'gb.grading_period_id'
            )
            ->where('gb.id', $gradebookId)
            ->where('gb.school_id', $schoolId)
            ->where('a.school_id', $schoolId)
            ->where('gp.school_id', $schoolId)
            ->where('a.academic_cycle_id', $cycle->id)
            ->where('gp.academic_cycle_id', $cycle->id)
            ->where('a.status', 'active')
            ->select([
                'gb.id',
                'gb.status',
                'a.school_group_id',
                'gp.status as period_status',
                'gp.academic_level_id',
            ])
            ->lockForUpdate()
            ->first();

        if (
            !$book
            || $book->status !== 'draft'
            || $book->period_status !== 'active'
        ) {
            $this->fail('El libro no está abierto para captura.');
        }

        $group = DB::table('school_groups')
            ->where('id', $book->school_group_id)
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->where('status', 'active')
            ->first(['academic_level_id']);

        if (
            !$group
            || (
                $book->academic_level_id !== null
                && (int) $book->academic_level_id
                    !== (int) $group->academic_level_id
            )
        ) {
            $this->fail('El grupo no corresponde al período.');
        }

        $validEnrollment = DB::table('student_enrollments as e')
            ->join('students as s', 's.id', '=', 'e.student_id')
            ->where('e.id', $enrollmentId)
            ->where('e.school_id', $schoolId)
            ->where('s.school_id', $schoolId)
            ->where('e.academic_cycle_id', $cycle->id)
            ->where('e.school_group_id', $book->school_group_id)
            ->where('e.status', 'active')
            ->where('s.status', 'active')
            ->exists();

        if (!$validEnrollment) {
            $this->fail('La matrícula no corresponde al grupo y ciclo.');
        }

        $existing = DB::table('academic_student_grades')
            ->where('school_id', $schoolId)
            ->where('gradebook_id', $gradebookId)
            ->where('student_enrollment_id', $enrollmentId)
            ->lockForUpdate()
            ->first();

        $values = [
            'numeric_value' => $data['numeric_value'] ?? null,
            'text_value' => $data['text_value'] ?? null,
            'comment' => $data['comment'] ?? null,
            'graded_by_user_id' => $actorUserId,
            'graded_at' => now(),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('academic_student_grades')
                ->where('id', $existing->id)
                ->where('school_id', $schoolId)
                ->update($values);

            $gradeId = (int) $existing->id;
        } else {
            $gradeId = DB::table('academic_student_grades')
                ->insertGetId([
                    'school_id' => $schoolId,
                    'gradebook_id' => $gradebookId,
                    'student_enrollment_id' => $enrollmentId,
                    ...$values,
                    'created_at' => now(),
                ]);
        }

        DB::table('academic_grade_audit_logs')->insert([
            'school_id' => $schoolId,
            'student_grade_id' => $gradeId,
            'actor_user_id' => $actorUserId,
            'action' => $existing ? 'updated' : 'created',
            'before_json' => $existing
                ? json_encode([
                    'numeric_value' => $existing->numeric_value,
                    'text_value' => $existing->text_value,
                    'comment' => $existing->comment,
                ])
                : null,
            'after_json' => json_encode([
                'numeric_value' => $values['numeric_value'],
                'text_value' => $values['text_value'],
                'comment' => $values['comment'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });
}

}
