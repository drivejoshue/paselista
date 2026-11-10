<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AcademicGroupsController extends Controller
{
    public function __construct(
        private readonly ModuleAccessService $access
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CONSULTA DE GRUPOS, PLANES Y DOCENTES
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        $cycles = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get(['id', 'name', 'is_active']);

        /*
         * Obtener cada grupo y su plan aplicado.
         * No usamos $group porque aquí todavía no existe.
         */
        $groups = DB::table('school_groups as g')
            ->join('academic_levels as l', function ($join) use ($schoolId): void {
                $join->on('l.id', '=', 'g.academic_level_id')
                    ->where('l.school_id', '=', $schoolId);
            })
            ->join('academic_cycles as c', function ($join) use ($schoolId): void {
                $join->on('c.id', '=', 'g.academic_cycle_id')
                    ->where('c.school_id', '=', $schoolId);
            })
            ->leftJoin('academic_group_curriculum_plans as gp', function ($join) use ($schoolId): void {
                $join->on('gp.school_group_id', '=', 'g.id')
                    ->where('gp.school_id', '=', $schoolId)
                    ->on('gp.academic_cycle_id', '=', 'g.academic_cycle_id');
            })
            ->where('g.school_id', $schoolId)
            ->where('g.status', 'active')
            ->where('c.is_active', 1)
            ->select([
                'g.id',
                'g.name',
                'g.grade_label',
                'g.academic_cycle_id',
                'g.academic_level_id',
                'l.name as level_name',
                'c.name as cycle_name',
                'gp.curriculum_plan_id as applied_plan_id',
            ])
            ->selectSub(function ($query): void {
                $query->from('student_enrollments as e')
                    ->selectRaw('COUNT(DISTINCT e.student_id)')
                    ->whereColumn('e.school_group_id', 'g.id')
                    ->whereColumn('e.school_id', 'g.school_id')
                    ->whereColumn('e.academic_cycle_id', 'g.academic_cycle_id')
                    ->where('e.status', 'active');
            }, 'active_students')
            ->orderBy('l.sort_order')
            ->orderBy('g.grade_label')
            ->orderBy('g.name')
            ->get();

        /*
         * Planes disponibles para la escuela.
         */
        $plans = DB::table('academic_curriculum_plans as p')
            ->where('p.school_id', $schoolId)
            ->whereIn('p.status', ['draft', 'active'])
            ->orderByDesc('p.version')
            ->orderByDesc('p.id')
            ->get([
                'p.id',
                'p.name',
                'p.academic_cycle_id',
                'p.academic_level_id',
                'p.version',
                'p.status',
            ]);

        $planIds = $plans->pluck('id')->all();

        /*
         * Materias vinculadas a los planes.
         */
        $links = $planIds === []
            ? collect()
            : DB::table('academic_curriculum_plan_subjects as ps')
                ->join('academic_subjects as s', function ($join) use ($schoolId): void {
                    $join->on('s.id', '=', 'ps.subject_id')
                        ->where('s.school_id', '=', $schoolId);
                })
                ->where('ps.school_id', $schoolId)
                ->whereIn('ps.curriculum_plan_id', $planIds)
                ->where('s.status', 'active')
                ->orderBy('ps.sort_order')
                ->orderBy('ps.id')
                ->get([
                    'ps.id',
                    'ps.curriculum_plan_id',
                    'ps.grade_label',
                    'ps.subject_id',
                    's.name as subject_name',
                    'ps.is_official',
                ]);

        /*
         * Una asignación académica por materia,
         * grupo y ciclo escolar.
         *
         * teacher_profile_id permanece por compatibilidad,
         * pero ya no determina los docentes autorizados.
         */
        $assignments = DB::table('academic_teaching_assignments as a')
            ->leftJoin('academic_teacher_profiles as t', function ($join) use ($schoolId): void {
                $join->on('t.id', '=', 'a.teacher_profile_id')
                    ->where('t.school_id', '=', $schoolId);
            })
            ->leftJoin('users as u', function ($join) use ($schoolId): void {
                $join->on('u.id', '=', 't.user_id')
                    ->where('u.school_id', '=', $schoolId);
            })
            ->where('a.school_id', $schoolId)
            ->where('a.status', 'active')
            ->get([
                'a.id',
                'a.school_group_id',
                'a.plan_subject_id',
                't.id as teacher_profile_id',
                'u.name as teacher_name',
            ]);

        /*
         * Docentes actualmente vinculados.
         * Esta es la relación real de autorización.
         */
        $assignmentTeachers = DB::table('academic_assignment_teachers as at')
            ->join('academic_teaching_assignments as a', function ($join) use ($schoolId): void {
                $join->on('a.id', '=', 'at.teaching_assignment_id')
                    ->where('a.school_id', '=', $schoolId);
            })
            ->join('academic_teacher_profiles as tp', function ($join) use ($schoolId): void {
                $join->on('tp.id', '=', 'at.teacher_profile_id')
                    ->where('tp.school_id', '=', $schoolId);
            })
            ->join('users as u', function ($join) use ($schoolId): void {
                $join->on('u.id', '=', 'tp.user_id')
                    ->where('u.school_id', '=', $schoolId);
            })
            ->where('at.school_id', $schoolId)
            ->where('at.status', 'active')
            ->whereNull('at.ended_at')
            ->where('a.status', 'active')
            ->where('tp.status', 'active')
            ->where('u.status', 'active')
            ->where('u.role', 'teacher')
            ->orderBy('u.name')
            ->get([
                'at.teaching_assignment_id',
                'at.teacher_profile_id',
                'u.name as teacher_name',
            ]);

        /*
         * Catálogo de docentes disponibles.
         */
        $teachers = DB::table('academic_teacher_profiles as tp')
            ->join('users as u', function ($join) use ($schoolId): void {
                $join->on('u.id', '=', 'tp.user_id')
                    ->where('u.school_id', '=', $schoolId);
            })
            ->where('tp.school_id', $schoolId)
            ->where('tp.status', 'active')
            ->where('u.status', 'active')
            ->where('u.role', 'teacher')
            ->orderBy('u.name')
            ->get([
                'tp.id',
                'u.name',
                'tp.employee_code',
            ]);

        return response()->json([
            'cycles' => $cycles,
            'groups' => $groups,
            'plans' => $plans,
            'links' => $links,
            'assignments' => $assignments,
            'assignment_teachers' => $assignmentTeachers,
            'teachers' => $teachers,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRAR DOCENTES
    |--------------------------------------------------------------------------
    |
    | add     : Agregar un docente.
    | remove  : Desasignar un docente.
    | replace : Reemplazar un docente.
    |
    | Los libros y calificaciones permanecen intactos.
    |
    */

    public function assign(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        abort_unless(
            in_array(
                (string) $request->user()?->role,
                ['director', 'school_admin'],
                true
            ),
            403,
            'Solo Dirección puede administrar asignaciones docentes.'
        );

        $data = $request->validate([
            'action' => [
                'sometimes',
                'in:add,remove,replace',
            ],
            'school_group_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'plan_subject_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'teacher_profile_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'replacement_teacher_profile_id' => [
                'required_if:action,replace',
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $action = (string) ($data['action'] ?? 'add');
        $teacherId = (int) $data['teacher_profile_id'];
        $replacementId = (int) (
            $data['replacement_teacher_profile_id'] ?? 0
        );

        abort_if(
            $action === 'replace' &&
            $replacementId === $teacherId,
            422,
            'Debes seleccionar otro docente para reemplazarlo.'
        );

        $message = DB::transaction(function () use (
            $schoolId,
            $data,
            $action,
            $teacherId,
            $replacementId
        ): string {
            /*
             * Primero obtenemos el grupo.
             * A partir de aquí sí existe $group.
             */
            $group = DB::table('school_groups')
                ->where('school_id', $schoolId)
                ->where('id', $data['school_group_id'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            abort_unless(
                $group,
                422,
                'El grupo no está activo en esta escuela.'
            );

            /*
             * Comprobar ciclo escolar activo.
             */
            $cycleActive = DB::table('academic_cycles')
                ->where('school_id', $schoolId)
                ->where('id', $group->academic_cycle_id)
                ->where('is_active', 1)
                ->exists();

            abort_unless(
                $cycleActive,
                422,
                'El grupo no pertenece al ciclo activo.'
            );

            /*
             * Validar materia y plan curricular.
             */
            $link = DB::table('academic_curriculum_plan_subjects as ps')
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
                ->where('ps.id', $data['plan_subject_id'])
                ->where('p.school_id', $schoolId)
                ->where('s.school_id', $schoolId)
                ->where('s.status', 'active')
                ->whereIn('p.status', ['draft', 'active'])
                ->first([
                    'ps.id',
                    'ps.curriculum_plan_id',
                    'ps.grade_label',
                    'p.academic_cycle_id',
                    'p.academic_level_id',
                ]);

            abort_unless(
                $link,
                422,
                'Materia no disponible para esta escuela.'
            );

            abort_unless(
                (int) $link->academic_cycle_id ===
                    (int) $group->academic_cycle_id &&
                (int) $link->academic_level_id ===
                    (int) $group->academic_level_id &&
                (
                    $link->grade_label === null ||
                    trim((string) $link->grade_label) ===
                        trim((string) $group->grade_label)
                ),
                422,
                'La materia no corresponde al ciclo, nivel o grado del grupo.'
            );

            /*
             * Validar el plan aplicado.
             *
             * Esta comprobación está dentro de assign(),
             * nunca dentro de index().
             */
            $appliedPlanId = DB::table(
                'academic_group_curriculum_plans'
            )
                ->where('school_id', $schoolId)
                ->where(
                    'academic_cycle_id',
                    $group->academic_cycle_id
                )
                ->where('school_group_id', $group->id)
                ->value('curriculum_plan_id');

            abort_if(
                $appliedPlanId !== null &&
                (int) $appliedPlanId !==
                    (int) $link->curriculum_plan_id,
                422,
                'Esta materia pertenece a otro plan. Abre el plan aplicado al grupo.'
            );

            /*
             * Validar docente que se agrega.
             */
            $incomingTeacher = $action === 'replace'
                ? $replacementId
                : $teacherId;

            if ($action !== 'remove') {
                $validTeacher = DB::table(
                    'academic_teacher_profiles as tp'
                )
                    ->join(
                        'users as u',
                        'u.id',
                        '=',
                        'tp.user_id'
                    )
                    ->where('tp.id', $incomingTeacher)
                    ->where('tp.school_id', $schoolId)
                    ->where('tp.status', 'active')
                    ->where('u.school_id', $schoolId)
                    ->where('u.role', 'teacher')
                    ->where('u.status', 'active')
                    ->exists();

                abort_unless(
                    $validTeacher,
                    422,
                    'El nuevo docente debe estar activo.'
                );
            }

            /*
             * Buscar la asignación académica existente.
             */
            $current = DB::table(
                'academic_teaching_assignments'
            )
                ->where('school_id', $schoolId)
                ->where(
                    'academic_cycle_id',
                    $group->academic_cycle_id
                )
                ->where('school_group_id', $group->id)
                ->where('plan_subject_id', $link->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            abort_if(
                $current->count() > 1,
                409,
                'Existen asignaciones duplicadas; requiere revisión administrativa.'
            );

            $assignment = $current->first();

            if (!$assignment) {
                abort_unless(
                    $action === 'add',
                    422,
                    'No existe asignación que modificar.'
                );

                /*
                 * Evitar la creación de un segundo libro
                 * si existen evaluaciones históricas.
                 */
                $historyIds = DB::table(
                    'academic_teaching_assignments'
                )
                    ->where('school_id', $schoolId)
                    ->where(
                        'academic_cycle_id',
                        $group->academic_cycle_id
                    )
                    ->where('school_group_id', $group->id)
                    ->where('plan_subject_id', $link->id)
                    ->pluck('id');

                $hasGrades = $historyIds->isNotEmpty() &&
                    DB::table('academic_gradebooks')
                        ->where('school_id', $schoolId)
                        ->whereIn(
                            'teaching_assignment_id',
                            $historyIds
                        )
                        ->exists();

                abort_if(
                    $hasGrades,
                    409,
                    'Existen libros históricos; revisa antes de reactivar.'
                );

                /*
                 * Crear la asignación académica.
                 * El campo docente original se conserva
                 * por compatibilidad.
                 */
                $assignmentId = (int) DB::table(
                    'academic_teaching_assignments'
                )->insertGetId([
                    'school_id' => $schoolId,
                    'academic_cycle_id' =>
                        $group->academic_cycle_id,
                    'school_group_id' => $group->id,
                    'plan_subject_id' => $link->id,
                    'teacher_profile_id' => $teacherId,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $assignmentId = (int) $assignment->id;
            }

            /*
             * Relación docente/asignación.
             */
            $relation = static function (int $id) use (
                $schoolId,
                $assignmentId
            ) {
                return DB::table('academic_assignment_teachers')
                    ->where('school_id', $schoolId)
                    ->where(
                        'teaching_assignment_id',
                        $assignmentId
                    )
                    ->where('teacher_profile_id', $id);
            };

            /*
             * Verificar docente saliente.
             */
            if (
                $action === 'remove' ||
                $action === 'replace'
            ) {
                $oldActive = $relation($teacherId)
                    ->where('status', 'active')
                    ->whereNull('ended_at')
                    ->exists();

                abort_unless(
                    $oldActive,
                    422,
                    'Ese docente ya no está asignado.'
                );
            }

            /*
             * Agregar o reactivar docente.
             */
            if (
                $action === 'add' ||
                $action === 'replace'
            ) {
                $targetId = $action === 'replace'
                    ? $replacementId
                    : $teacherId;

                $target = $relation($targetId)->first();

                if (
                    $target &&
                    $target->status === 'active' &&
                    $target->ended_at === null
                ) {
                    abort_if(
                        $action === 'replace',
                        422,
                        'El reemplazo ya está asignado.'
                    );

                    return 'El docente ya estaba asignado.';
                }

                if ($target) {
                    $relation($targetId)->update([
                        'status' => 'active',
                        'ended_at' => null,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table(
                        'academic_assignment_teachers'
                    )->insert([
                        'school_id' => $schoolId,
                        'teaching_assignment_id' =>
                            $assignmentId,
                        'teacher_profile_id' => $targetId,
                        'status' => 'active',
                        'ended_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            /*
             * Desactivar docente anterior.
             * No borrar ningún libro ni calificación.
             */
            if (
                $action === 'remove' ||
                $action === 'replace'
            ) {
                $relation($teacherId)->update([
                    'status' => 'inactive',
                    'ended_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return match ($action) {
                'add' =>
                    'Docente agregado a la materia y grupo.',
                'remove' =>
                    'Docente desasignado; las calificaciones se conservaron.',
                'replace' =>
                    'Docente reemplazado; las calificaciones se conservaron.',
            };
        }, 3);

        return response()->json([
            'message' => $message,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN DE ESCUELA Y MÓDULO
    |--------------------------------------------------------------------------
    */

    private function schoolId(Request $request): int
    {
        $schoolId = (int) (
            $request->user()?->school_id ?? 0
        );

        abort_if(
            $schoolId < 1,
            403,
            'Usuario sin escuela asociada.'
        );

        abort_unless(
            $this->access->canAccess($schoolId, 'academic'),
            403,
            'Academic no contratado.'
        );

        return $schoolId;
    }
}
