<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

final class TeacherAccessController extends Controller
{
    public function __construct(
        private readonly ModuleAccessService $modules
    ) {}

    /*
    |--------------------------------------------------------------------------
    | SEGURIDAD DEL PORTAL DOCENTE
    |--------------------------------------------------------------------------
    */

    private function validateTeacher(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user &&
            $user->role === 'teacher' &&
            $user->status === 'active' &&
            (int) $user->school_id > 0,
            403,
            'Acceso exclusivo para docentes activos.'
        );

        $schoolId = (int) $user->school_id;

        abort_unless(
            $this->modules->canAccess($schoolId, 'academic'),
            403,
            'Academic no habilitado.'
        );

        abort_unless(
            DB::table('academic_teacher_profiles')
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists(),
            403,
            'Perfil docente inactivo.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIO OBLIGATORIO DE CONTRASEÑA
    |--------------------------------------------------------------------------
    */

    public function changeForm(
        Request $request
    ): View|RedirectResponse {
        $this->validateTeacher($request);

        if (!$request->user()->must_change_password) {
            return redirect()->route('teacher.dashboard');
        }

        return view('teacher.change-password');
    }

    public function change(
        Request $request
    ): RedirectResponse {
        $this->validateTeacher($request);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'min:12',
                'max:128',
                'confirmed',
                'different:current_password',
            ],
        ]);

        $user = $request->user();

        if (!Hash::check(
            $data['current_password'],
            $user->password
        )) {
            return back()->withErrors([
                'current_password' =>
                    'Contraseña inicial incorrecta.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('teacher.dashboard')
            ->with(
                'status',
                'Contraseña actualizada.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNACIONES ACADÉMICAS COMPARTIDAS
    |--------------------------------------------------------------------------
    |
    | Una asignación representa:
    | escuela + ciclo + grupo + materia.
    |
    | Varios docentes pueden acceder a la misma asignación
    | mediante academic_assignment_teachers.
    |
    | Los libros de calificaciones mantienen su ID original.
    |
    */

    private function assignmentQuery(
        int $schoolId,
        int $userId
    ) {
        return DB::table('academic_teaching_assignments as a')

            ->join(
                'academic_assignment_teachers as at',
                function ($join) use ($schoolId): void {
                    $join->on(
                        'at.teaching_assignment_id',
                        '=',
                        'a.id'
                    )
                    ->where('at.school_id', '=', $schoolId)
                    ->where('at.status', '=', 'active')
                    ->whereNull('at.ended_at');
                }
            )

            ->join(
                'academic_teacher_profiles as p',
                function ($join) use ($schoolId): void {
                    $join->on(
                        'p.id',
                        '=',
                        'at.teacher_profile_id'
                    )
                    ->where('p.school_id', '=', $schoolId);
                }
            )

            ->join(
                'academic_curriculum_plan_subjects as ps',
                function ($join) use ($schoolId): void {
                    $join->on(
                        'ps.id',
                        '=',
                        'a.plan_subject_id'
                    )
                    ->where('ps.school_id', '=', $schoolId);
                }
            )

            ->join(
                'academic_curriculum_plans as cp',
                function ($join) use ($schoolId): void {
                    $join->on(
                        'cp.id',
                        '=',
                        'ps.curriculum_plan_id'
                    )
                    ->where('cp.school_id', '=', $schoolId);
                }
            )

            ->join(
                'academic_subjects as s',
                function ($join) use ($schoolId): void {
                    $join->on(
                        's.id',
                        '=',
                        'ps.subject_id'
                    )
                    ->where('s.school_id', '=', $schoolId);
                }
            )

            ->join(
                'school_groups as g',
                function ($join) use ($schoolId): void {
                    $join->on(
                        'g.id',
                        '=',
                        'a.school_group_id'
                    )
                    ->where('g.school_id', '=', $schoolId);
                }
            )

            ->join(
                'academic_cycles as c',
                function ($join) use ($schoolId): void {
                    $join->on(
                        'c.id',
                        '=',
                        'a.academic_cycle_id'
                    )
                    ->where('c.school_id', '=', $schoolId);
                }
            )

            ->join(
                'academic_levels as l',
                function ($join) use ($schoolId): void {
                    $join->on(
                        'l.id',
                        '=',
                        'g.academic_level_id'
                    )
                    ->where('l.school_id', '=', $schoolId);
                }
            )

            // Escuela y docente autenticado.
            ->where('a.school_id', $schoolId)
            ->where('p.user_id', $userId)

            // Solo vínculos vigentes.
            ->where('p.status', 'active')
            ->where('a.status', 'active')
            ->where('g.status', 'active')
            ->where('c.is_active', 1)

            // Compatibilidad de ciclo y plan.
            ->whereColumn(
                'g.academic_cycle_id',
                'a.academic_cycle_id'
            )
            ->whereColumn(
                'cp.academic_cycle_id',
                'g.academic_cycle_id'
            )
            ->whereColumn(
                'cp.academic_level_id',
                'g.academic_level_id'
            )

            // Materia correspondiente al grado.
            ->where(function ($query): void {
                $query
                    ->whereNull('ps.grade_label')
                    ->orWhereColumn(
                        'ps.grade_label',
                        'g.grade_label'
                    );
            });
    }

    /*
    |--------------------------------------------------------------------------
    | PREFERENCIAS DE CAPTURA
    |--------------------------------------------------------------------------
    */

    private function gradeInputDecimals(
        int $schoolId,
        int $userId
    ): bool {
        $raw = DB::table('school_settings')
            ->where('school_id', $schoolId)
            ->where(
                'key',
                "academic.teacher.{$userId}.grade_input"
            )
            ->value('value_json');

        if ($raw === null) {
            return true;
        }

        $setting = json_decode($raw, true);

        return (bool) (
            $setting['grade_input_decimals'] ?? true
        );
    }

    public function updateGradeInputPreference(
        Request $request
    ): JsonResponse {
        $this->validateTeacher($request);

        $user = $request->user();

        abort_if(
            (bool) $user->must_change_password,
            403,
            'Primero debes cambiar tu contraseña.'
        );

        $data = $request->validate([
            'grade_input_decimals' => [
                'required',
                'boolean',
            ],
        ]);

        $schoolId = (int) $user->school_id;
        $userId = (int) $user->id;

        $decimals = (bool) $data[
            'grade_input_decimals'
        ];

        DB::table('school_settings')->upsert(
            [[
                'school_id' => $schoolId,
                'key' =>
                    "academic.teacher.{$userId}.grade_input",
                'value_json' => json_encode([
                    'grade_input_decimals' => $decimals,
                ], JSON_THROW_ON_ERROR),
                'is_public' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            [
                'school_id',
                'key',
            ],
            [
                'value_json',
                'is_public',
                'updated_by',
                'updated_at',
            ]
        );

        return response()->json([
            'message' => 'Preferencia guardada.',
            'grade_input_decimals' => $decimals,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD DOCENTE REACT
    |--------------------------------------------------------------------------
    */

    public function dashboard(
        Request $request
    ): View|RedirectResponse {
        $this->validateTeacher($request);

        $user = $request->user();

        if ($user->must_change_password) {
            return redirect()->route(
                'teacher.password.change'
            );
        }

        $schoolId = (int) $user->school_id;

        // Todas las asignaciones donde el usuario
        // está vinculado como docente activo.
        $assignments = $this->assignmentQuery(
            $schoolId,
            (int) $user->id
        )
            ->orderBy('l.sort_order')
            ->orderBy('g.name')
            ->orderBy('s.name')
            ->get([
                'a.id',
                'a.academic_cycle_id',
                's.id as subject_id',
                'g.id as group_id',
                'g.name as group_name',
                'g.grade_label',
                'l.name as level_name',
                'c.name as cycle_name',
                's.name as subject_name',
            ]);

        $assignmentIds = $assignments
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $groupIds = $assignments
            ->pluck('group_id')
            ->unique()
            ->values()
            ->all();

        $cycleIds = $assignments
            ->pluck('academic_cycle_id')
            ->unique()
            ->values()
            ->all();

        /*
         * Alumnos únicos por matrícula activa.
         * No multiplicar el número de alumnos
         * por las materias ni por los docentes.
         */
        $students = $groupIds === []
            ? 0
            : DB::table('student_enrollments as e')
                ->join(
                    'students as s',
                    function ($join) use ($schoolId): void {
                        $join->on(
                            's.id',
                            '=',
                            'e.student_id'
                        )
                        ->where(
                            's.school_id',
                            '=',
                            $schoolId
                        );
                    }
                )
                ->where('e.school_id', $schoolId)
                ->whereIn(
                    'e.school_group_id',
                    $groupIds
                )
                ->whereIn(
                    'e.academic_cycle_id',
                    $cycleIds
                )
                ->where('e.status', 'active')
                ->where('s.status', 'active')
                ->distinct()
                ->count('e.id');

        /*
         * Un libro se contabiliza una sola vez,
         * aunque tenga varios docentes.
         */
        $statusCounts = $assignmentIds === []
            ? collect()
            : DB::table('academic_gradebooks')
                ->where('school_id', $schoolId)
                ->whereIn(
                    'teaching_assignment_id',
                    $assignmentIds
                )
                ->select(
                    'status',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('status')
                ->pluck('total', 'status');

        return view('teacher.dashboard', [
            'bootstrap' => [
                'page' => 'dashboard',

                'grade_input_decimals' =>
                    $this->gradeInputDecimals(
                        $schoolId,
                        (int) $user->id
                    ),

                'teacher' => [
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                ],

                'assignments' => $assignments,

                'metrics' => [
                    'groups' => count($groupIds),

                    'subjects' => $assignments
                        ->pluck('subject_id')
                        ->unique()
                        ->count(),

                    'students' => $students,

                    'drafts' => (int) (
                        $statusCounts['draft'] ?? 0
                    ),

                    'submitted' => (int) (
                        $statusCounts['submitted'] ?? 0
                    ),

                    'published' => (int) (
                        $statusCounts['published'] ?? 0
                    ),
                ],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ALUMNOS DE UNA ASIGNACIÓN
    |--------------------------------------------------------------------------
    */

    public function students(
        Request $request,
        int $assignment
    ): View|RedirectResponse {
        $this->validateTeacher($request);

        $user = $request->user();

        if ($user->must_change_password) {
            return redirect()->route(
                'teacher.password.change'
            );
        }

        $schoolId = (int) $user->school_id;

        // Verificar que este docente tenga
        // acceso a la asignación solicitada.
        $assigned = $this->assignmentQuery(
            $schoolId,
            (int) $user->id
        )
            ->where('a.id', $assignment)
            ->first([
                'a.id',
                'a.academic_cycle_id',
                'g.id as group_id',
                'g.name as group_name',
                'g.grade_label',
                'l.name as level_name',
                's.name as subject_name',
                'c.name as cycle_name',
            ]);

        abort_unless(
            $assigned,
            404,
            'Asignación no encontrada.'
        );

        /*
         * Los alumnos se obtienen de las matrículas
         * del grupo y ciclo correspondiente.
         */
        $students = DB::table('student_enrollments as e')
            ->join(
                'students as s',
                function ($join) use ($schoolId): void {
                    $join->on(
                        's.id',
                        '=',
                        'e.student_id'
                    )
                    ->where(
                        's.school_id',
                        '=',
                        $schoolId
                    );
                }
            )
            ->where('e.school_id', $schoolId)
            ->where(
                'e.academic_cycle_id',
                $assigned->academic_cycle_id
            )
            ->where(
                'e.school_group_id',
                $assigned->group_id
            )
            ->where('e.status', 'active')
            ->where('s.status', 'active')
            ->select(
                's.id',
                's.student_code',
                's.first_name',
                's.last_name'
            )
            ->distinct()
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        return view('teacher.dashboard', [
            'bootstrap' => [
                'page' => 'students',

                'teacher' => [
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                ],

                'assigned' => $assigned,
                'students' => $students,
            ],
        ]);
    }
}
