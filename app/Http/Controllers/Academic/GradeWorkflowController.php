<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GradeWorkflowController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad y autorización
    |--------------------------------------------------------------------------
    */

    private function actor(Request $request, array $roles): object
    {
        $user = $request->user();

        abort_unless(
            $user && in_array((string) $user->role, $roles, true),
            403
        );

        abort_unless($user->school_id !== null, 403);

        if ((string) $user->role === 'teacher') {
            abort_if(
                (bool) $user->must_change_password,
                403,
                'Primero debes cambiar tu contraseña.'
            );
        }

        return $user;
    }

    /**
     * Obtiene una asignación académica.
     *
     * La asignación representa:
     * escuela + ciclo + grupo + materia.
     *
     * Los docentes se autorizan mediante
     * academic_assignment_teachers.
     *
     * No se modifica el ID de la asignación existente
     * porque academic_gradebooks depende de él.
     */
    private function assignment(
        int $id,
        int $schoolId,
        ?int $userId = null
    ): object {
        $query = DB::table('academic_teaching_assignments as a')
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
            ->where('a.id', $id)
            ->where('a.school_id', $schoolId)
            ->where('g.school_id', $schoolId)
            ->where('ps.school_id', $schoolId)
            ->where('p.school_id', $schoolId)
            ->where('s.school_id', $schoolId)
            ->where('a.status', 'active')
            ->whereColumn(
                'g.academic_cycle_id',
                'a.academic_cycle_id'
            )
            ->whereColumn(
                'p.academic_cycle_id',
                'a.academic_cycle_id'
            )
            ->whereColumn(
                'p.academic_level_id',
                'g.academic_level_id'
            )
            ->where(function ($query): void {
                $query
                    ->whereNull('ps.grade_label')
                    ->orWhereColumn(
                        'ps.grade_label',
                        'g.grade_label'
                    );
            })
            ->select(
                'a.*',
                'g.name as group_name',
                'g.academic_level_id',
                's.name as subject_name'
            );

        if ($userId !== null) {
            $query->whereExists(
                function ($query) use (
                    $schoolId,
                    $userId
                ): void {
                    $query
                        ->selectRaw('1')
                        ->from(
                            'academic_assignment_teachers as at'
                        )
                        ->join(
                            'academic_teacher_profiles as tp',
                            function ($join) use ($schoolId): void {
                                $join
                                    ->on(
                                        'tp.id',
                                        '=',
                                        'at.teacher_profile_id'
                                    )
                                    ->where(
                                        'tp.school_id',
                                        '=',
                                        $schoolId
                                    );
                            }
                        )
                        ->join(
                            'users as u',
                            function ($join) use ($schoolId): void {
                                $join
                                    ->on(
                                        'u.id',
                                        '=',
                                        'tp.user_id'
                                    )
                                    ->where(
                                        'u.school_id',
                                        '=',
                                        $schoolId
                                    );
                            }
                        )
                        ->whereColumn(
                            'at.teaching_assignment_id',
                            'a.id'
                        )
                        ->where('at.school_id', $schoolId)
                        ->where('at.status', 'active')
                        ->whereNull('at.ended_at')
                        ->where('tp.user_id', $userId)
                        ->where('tp.status', 'active')
                        ->where('u.role', 'teacher')
                        ->where('u.status', 'active');
                }
            );
        }

        // Compatibilidad con las vistas existentes.
        $query->selectRaw(
            '? as user_id',
            [$userId]
        );

        return $query->first()
            ?? abort(
                404,
                'Asignación no disponible para este usuario.'
            );
    }

    private function period(
        int $periodId,
        object $assignment,
        int $schoolId
    ): object {
        return DB::table('academic_grading_periods')
            ->where('id', $periodId)
            ->where('school_id', $schoolId)
            ->where(
                'academic_cycle_id',
                $assignment->academic_cycle_id
            )
            ->where(function ($query) use ($assignment): void {
                $query
                    ->whereNull('academic_level_id')
                    ->orWhere(
                        'academic_level_id',
                        $assignment->academic_level_id
                    );
            })
            ->where('status', 'active')
            ->first() ?? abort(404);
    }

    private function roster(
        object $assignment,
        int $schoolId
    ) {
        return DB::table('student_enrollments as e')
            ->join(
                'students as s',
                's.id',
                '=',
                'e.student_id'
            )
            ->where('e.school_id', $schoolId)
            ->where('s.school_id', $schoolId)
            ->where(
                'e.academic_cycle_id',
                $assignment->academic_cycle_id
            )
            ->where(
                'e.school_group_id',
                $assignment->school_group_id
            )
            ->where('e.status', 'active')
            ->where('s.status', 'active')
            ->select(
                'e.id as enrollment_id',
                's.student_code',
                's.first_name',
                's.last_name'
            )
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Libros y auditoría
    |--------------------------------------------------------------------------
    */

    private function book(
        int $assignmentId,
        int $periodId,
        int $schoolId,
        bool $create = false
    ): ?object {
        $query = DB::table('academic_gradebooks')
            ->where('school_id', $schoolId)
            ->where(
                'teaching_assignment_id',
                $assignmentId
            )
            ->where(
                'grading_period_id',
                $periodId
            );

        $book = $query->first();

        if ($book || !$create) {
            return $book;
        }

        $id = DB::table('academic_gradebooks')
            ->insertGetId([
                'school_id' => $schoolId,
                'teaching_assignment_id' => $assignmentId,
                'grading_period_id' => $periodId,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('academic_gradebooks')
            ->where('id', $id)
            ->where('school_id', $schoolId)
            ->first();
    }

    private function audit(
        int $schoolId,
        ?int $gradeId,
        int $actorId,
        string $action,
        mixed $before,
        mixed $after,
        ?string $reason = null
    ): void {
        DB::table('academic_grade_audit_logs')
            ->insert([
                'school_id' => $schoolId,
                'student_grade_id' => $gradeId,
                'actor_user_id' => $actorId,
                'action' => $action,
                'before_json' => $before === null
                    ? null
                    : json_encode(
                        $before,
                        JSON_THROW_ON_ERROR
                    ),
                'after_json' => $after === null
                    ? null
                    : json_encode(
                        $after,
                        JSON_THROW_ON_ERROR
                    ),
                'reason' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Avance real de calificaciones contra matrícula activa.
     *
     * Una nota pendiente no equivale a cero.
     * Cero es una calificación válida.
     */
    private function gradeProgress(
        object $assignment,
        int $bookId,
        int $schoolId
    ): array {
        $roster = $this->roster(
            $assignment,
            $schoolId
        );

        $ids = $roster
            ->pluck('enrollment_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $grades = DB::table('academic_student_grades')
            ->where('school_id', $schoolId)
            ->where('gradebook_id', $bookId)
            ->whereIn(
                'student_enrollment_id',
                $ids
            )
            ->get([
                'student_enrollment_id',
                'numeric_value',
            ])
            ->keyBy('student_enrollment_id');

        $total = count($ids);
        $recorded = 0;
        $pending = 0;
        $invalid = 0;
        $low = 0;

        foreach ($ids as $enrollmentId) {
            $grade = $grades->get($enrollmentId);

            if (
                !$grade ||
                $grade->numeric_value === null
            ) {
                $pending++;
                continue;
            }

            $recorded++;

            $value = (float) $grade->numeric_value;

            if (
                !is_finite($value) ||
                $value < 0 ||
                $value > 10 ||
                abs(
                    $value * 10 -
                    round($value * 10)
                ) > 0.00001
            ) {
                $invalid++;
                continue;
            }

            if ($value < 6) {
                $low++;
            }
        }

        return [
            'total' => $total,
            'recorded' => $recorded,
            'pending' => $pending,
            'invalid' => $invalid,
            'low' => $low,
            'complete' => (
                $total > 0 &&
                $pending === 0 &&
                $invalid === 0
            ),
        ];
    }

    private function requireComplete(
        object $assignment,
        int $bookId,
        int $schoolId
    ): array {
        $progress = $this->gradeProgress(
            $assignment,
            $bookId,
            $schoolId
        );

        if (!$progress['complete']) {
            throw ValidationException::withMessages([
                'grades' => sprintf(
                    'La captura #%d no puede avanzar: %d pendientes y %d inválidas.',
                    $bookId,
                    $progress['pending'],
                    $progress['invalid']
                ),
            ]);
        }

        return $progress;
    }

    /**
     * Respuesta compatible con Blade y React.
     */
    private function actionResponse(
        Request $request,
        string $message,
        string $redirect
    ) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return redirect($redirect)
            ->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Portal docente: listado
    |--------------------------------------------------------------------------
    */

    public function teacherIndex(Request $request)
    {
        $actor = $this->actor(
            $request,
            ['teacher']
        );

        $schoolId = (int) $actor->school_id;

        $assignments = DB::table(
            'academic_teaching_assignments as a'
        )
            ->join(
                'academic_assignment_teachers as at',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'at.teaching_assignment_id',
                            '=',
                            'a.id'
                        )
                        ->where(
                            'at.school_id',
                            '=',
                            $schoolId
                        )
                        ->where(
                            'at.status',
                            '=',
                            'active'
                        )
                        ->whereNull('at.ended_at');
                }
            )
            ->join(
                'academic_teacher_profiles as tp',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'tp.id',
                            '=',
                            'at.teacher_profile_id'
                        )
                        ->where(
                            'tp.school_id',
                            '=',
                            $schoolId
                        );
                }
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
                'academic_curriculum_plans as cp',
                'cp.id',
                '=',
                'ps.curriculum_plan_id'
            )
            ->join(
                'academic_subjects as s',
                's.id',
                '=',
                'ps.subject_id'
            )
            ->join(
                'academic_cycles as c',
                'c.id',
                '=',
                'a.academic_cycle_id'
            )
            ->where('tp.user_id', $actor->id)
            ->where('tp.status', 'active')
            ->where('a.school_id', $schoolId)
            ->where('g.school_id', $schoolId)
            ->where('g.status', 'active')
            ->where('ps.school_id', $schoolId)
            ->where('cp.school_id', $schoolId)
            ->where('s.school_id', $schoolId)
            ->where('a.status', 'active')
            ->where('c.is_active', 1)
            ->where('c.school_id', $schoolId)
            ->whereColumn(
                'g.academic_cycle_id',
                'a.academic_cycle_id'
            )
            ->whereColumn(
                'cp.academic_cycle_id',
                'a.academic_cycle_id'
            )
            ->whereColumn(
                'cp.academic_level_id',
                'g.academic_level_id'
            )
            ->where(function ($query): void {
                $query
                    ->whereNull('ps.grade_label')
                    ->orWhereColumn(
                        'ps.grade_label',
                        'g.grade_label'
                    );
            })
            ->select(
                'a.id',
                'g.name as group_name',
                'g.academic_level_id',
                's.name as subject_name',
                'a.academic_cycle_id'
            )
            ->orderBy('g.name')
            ->orderBy('s.name')
            ->get();

        $periods = DB::table('academic_grading_periods')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->orderBy('sequence')
            ->get();

        $books = DB::table('academic_gradebooks')
            ->where('school_id', $schoolId)
            ->whereIn(
                'teaching_assignment_id',
                $assignments->pluck('id')
            )
            ->get([
                'teaching_assignment_id',
                'grading_period_id',
                'status',
            ]);

        return view('teacher.dashboard', [
            'bootstrap' => [
                'page' => 'grades',
                'teacher' => [
                    'name' => (string) $actor->name,
                    'email' => (string) $actor->email,
                ],
                'assignments' => $assignments,
                'periods' => $periods,
                'books' => $books,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Portal docente: captura
    |--------------------------------------------------------------------------
    */

    public function teacher(
        Request $request,
        int $assignment,
        int $period
    ) {
        $actor = $this->actor(
            $request,
            ['teacher']
        );

        $schoolId = (int) $actor->school_id;

        $a = $this->assignment(
            $assignment,
            $schoolId,
            (int) $actor->id
        );

        $p = $this->period(
            $period,
            $a,
            $schoolId
        );

        $book = $this->book(
            $assignment,
            $period,
            $schoolId
        );

        $roster = $this->roster(
            $a,
            $schoolId
        );

        $grades = $book
            ? DB::table('academic_student_grades')
                ->where(
                    'gradebook_id',
                    $book->id
                )
                ->where(
                    'school_id',
                    $schoolId
                )
                ->pluck(
                    'numeric_value',
                    'student_enrollment_id'
                )
            : collect();

        $students = $roster->map(
            fn ($student) => [
                'enrollment_id' =>
                    (int) $student->enrollment_id,
                'student_code' =>
                    $student->student_code,
                'first_name' =>
                    $student->first_name,
                'last_name' =>
                    $student->last_name,
                'numeric_value' => $grades->get(
                    $student->enrollment_id
                ),
            ]
        );

        $setting = DB::table('school_settings')
            ->where('school_id', $schoolId)
            ->where(
                'key',
                'academic.teacher.' .
                    $actor->id .
                    '.grade_input'
            )
            ->value('value_json');

        $settingData = $setting === null
            ? []
            : (
                json_decode($setting, true) ?: []
            );

        $gradeInputDecimals = (bool) (
            $settingData['grade_input_decimals'] ??
            true
        );

        return view('teacher.dashboard', [
            'bootstrap' => [
                'grade_input_decimals' =>
                    $gradeInputDecimals,
                'page' => 'capture',
                'teacher' => [
                    'name' => (string) $actor->name,
                    'email' => (string) $actor->email,
                ],
                'a' => $a,
                'p' => $p,
                'book' => $book,
                'students' => $students,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Portal docente: PDF
    |--------------------------------------------------------------------------
    */

    public function teacherPdf(
        Request $request,
        int $assignment,
        int $period
    ) {
        $actor = $this->actor(
            $request,
            ['teacher']
        );

        $schoolId = (int) $actor->school_id;

        $a = $this->assignment(
            $assignment,
            $schoolId,
            (int) $actor->id
        );

        $p = $this->period(
            $period,
            $a,
            $schoolId
        );

        $book = $this->book(
            $assignment,
            $period,
            $schoolId
        );

        abort_unless(
            $book,
            404,
            'Todavía no hay una captura guardada.'
        );

        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->first() ?? abort(404);

        $cycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where(
                'id',
                $a->academic_cycle_id
            )
            ->first() ?? abort(404);

        $roster = $this->roster(
            $a,
            $schoolId
        );

        $grades = DB::table('academic_student_grades')
            ->where('school_id', $schoolId)
            ->where('gradebook_id', $book->id)
            ->pluck(
                'numeric_value',
                'student_enrollment_id'
            );

        $rows = $roster->map(
            function ($student) use ($grades) {
                return (object) [
                    'student_code' =>
                        $student->student_code,
                    'name' => trim(
                        $student->last_name .
                        ' ' .
                        $student->first_name
                    ),
                    'numeric_value' => $grades->get(
                        $student->enrollment_id
                    ),
                ];
            }
        );

        $total = $rows->count();

        $recorded = $rows
            ->filter(
                fn ($row) =>
                    $row->numeric_value !== null
            )
            ->count();

        $low = $rows
            ->filter(
                fn ($row) =>
                    $row->numeric_value !== null &&
                    (float) $row->numeric_value < 6
            )
            ->count();

        $generatedAt = now(
            ($school->timezone ?? null)
                ?: 'America/Mexico_City'
        )->format('d/m/Y H:i');

        $pdf = Pdf::loadView(
            'academic.grades.teacher-report-pdf',
            [
                'school' => $school,
                'cycle' => $cycle,
                'assignment' => $a,
                'period' => $p,
                'book' => $book,
                'teacherName' => $actor->name,
                'rows' => $rows,
                'total' => $total,
                'recorded' => $recorded,
                'pending' =>
                    $total - $recorded,
                'low' => $low,
                'generatedAt' => $generatedAt,
            ]
        )->setPaper(
            'a4',
            'portrait'
        );

        $filename = sprintf(
            'calificaciones_%d_%d_%d.pdf',
            $assignment,
            $period,
            $book->id
        );

        return $pdf->download($filename);
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar borrador docente
    |--------------------------------------------------------------------------
    */


/**
 * Guarda únicamente las calificaciones modificadas.
 * Detecta conflictos cuando otro docente cambió una nota
 * después de que el usuario abrió la pantalla.
 */
public function teacherSave(
    Request $request,
    int $assignment,
    int $period
) {
    $actor = $this->actor($request, ['teacher']);
    $schoolId = (int) $actor->school_id;

    $data = $request->validate([
        'grades' => ['required', 'array', 'min:1'],
        'grades.*' => [
            'nullable', 'numeric', 'between:0,10', 'decimal:0,1',
        ],
        'original_grades' => ['required', 'array'],
        'original_grades.*' => [
            'nullable', 'numeric', 'between:0,10', 'decimal:0,3',
        ],
    ]);

    // Validar autorización y periodo.
    $a = $this->assignment(
        $assignment,
        $schoolId,
        (int) $actor->id
    );
    $this->period($period, $a, $schoolId);

    $saved = DB::transaction(function () use (
        $actor,
        $schoolId,
        $assignment,
        $period,
        $data
    ): int {
        // Serializa la creación y escritura del libro compartido.
        $lockedAssignment = DB::table(
            'academic_teaching_assignments'
        )
            ->where('id', $assignment)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        abort_unless(
            $lockedAssignment,
            409,
            'La asignación académica ya no está activa.'
        );

        // Revalidar autorización dentro de la transacción.
        $a = $this->assignment(
            $assignment,
            $schoolId,
            (int) $actor->id
        );
        $this->period($period, $a, $schoolId);

        $book = $this->book(
            $assignment,
            $period,
            $schoolId,
            true
        );

        $book = DB::table('academic_gradebooks')
            ->where('id', $book->id)
            ->where('school_id', $schoolId)
            ->lockForUpdate()
            ->first();

        abort_unless(
            $book && $book->status === 'draft',
            409,
            'Esta captura ya fue enviada o publicada. Recarga la pantalla.'
        );

        $roster = $this->roster($a, $schoolId)
            ->keyBy('enrollment_id');

        $changedIds = [];

        foreach ($data['grades'] as $enrollmentId => $value) {
            $key = (string) $enrollmentId;

            if (
                !ctype_digit($key) ||
                !isset($roster[(int) $enrollmentId])
            ) {
                abort(
                    422,
                    'Se recibió una matrícula que no pertenece al grupo.'
                );
            }

            if (
                !array_key_exists(
                    $enrollmentId,
                    $data['original_grades']
                )
            ) {
                abort(
                    422,
                    'Falta la calificación original de una matrícula. Actualiza la página.'
                );
            }

            $changedIds[] = (int) $enrollmentId;
        }

        // Consultar los valores actuales de las filas modificadas.
        $previous = DB::table('academic_student_grades')
            ->where('school_id', $schoolId)
            ->where('gradebook_id', $book->id)
            ->whereIn('student_enrollment_id', $changedIds)
            ->get()
            ->keyBy('student_enrollment_id');

        // La base almacena DECIMAL(7,3).
        $asDecimal = static fn ($value): ?string =>
            $value === null || $value === ''
                ? null
                : number_format((float) $value, 3, '.', '');

        /*
         * PRIMERA PASADA:
         * Comparar todos los valores originales.
         * Todavía no modificar ninguna calificación.
         */
        foreach ($changedIds as $enrollmentId) {
            $dbValue = $previous
                ->get($enrollmentId)?->numeric_value;

            $expected = $data['original_grades'][$enrollmentId];

            if (
                $asDecimal($dbValue) !==
                $asDecimal($expected)
            ) {
                $code = (string) $roster[
                    $enrollmentId
                ]->student_code;

                abort(
                    409,
                    "Otro docente modificó la calificación de la matrícula {$code}. No se guardó ningún cambio. Actualiza la captura."
                );
            }
        }

        /*
         * SEGUNDA PASADA:
         * Ya verificamos todas las filas.
         * Ahora sí aplicar los cambios.
         */
        $written = 0;

        foreach ($changedIds as $enrollmentId) {
            $prior = $previous->get($enrollmentId);

            $numeric = $data['grades'][$enrollmentId] === null
                ? null
                : (float) $data['grades'][$enrollmentId];

            // Evitar escrituras y auditorías innecesarias.
            if (
                $asDecimal($prior?->numeric_value) ===
                $asDecimal($numeric)
            ) {
                continue;
            }

            if ($prior) {
                DB::table('academic_student_grades')
                    ->where('id', $prior->id)
                    ->where('school_id', $schoolId)
                    ->update([
                        'numeric_value' => $numeric,
                        'graded_by_user_id' => $actor->id,
                        'graded_at' => now(),
                        'updated_at' => now(),
                    ]);

                $gradeId = (int) $prior->id;
            } else {
                $gradeId = (int) DB::table(
                    'academic_student_grades'
                )->insertGetId([
                    'school_id' => $schoolId,
                    'gradebook_id' => $book->id,
                    'student_enrollment_id' => $enrollmentId,
                    'numeric_value' => $numeric,
                    'graded_by_user_id' => $actor->id,
                    'graded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->audit(
                $schoolId,
                $gradeId,
                (int) $actor->id,
                'teacher_grade_saved',
                $prior
                    ? ['numeric_value' => $prior->numeric_value]
                    : null,
                ['numeric_value' => $numeric]
            );

            $written++;
        }

        return $written;
    }, 3);

    $message = "Borrador actualizado: {$saved} calificación(es).";

    return $this->actionResponse(
        $request,
        $message,
        url()->previous()
    );
}


    /*
    |--------------------------------------------------------------------------
    | Enviar captura a Dirección
    |--------------------------------------------------------------------------
    */

    public function submit(
        Request $request,
        int $assignment,
        int $period
    ) {
        $actor = $this->actor(
            $request,
            ['teacher']
        );

        $request->validate([
            'confirm_submission' => [
                'required',
                'accepted',
            ],
        ]);

        $schoolId = (int) $actor->school_id;

        $a = $this->assignment(
            $assignment,
            $schoolId,
            (int) $actor->id
        );

        $this->period(
            $period,
            $a,
            $schoolId
        );

        DB::transaction(
            function () use (
                $actor,
                $a,
                $assignment,
                $period,
                $schoolId
            ): void {
                $book = $this->book(
                    $assignment,
                    $period,
                    $schoolId
                );

                abort_unless(
                    $book,
                    422,
                    'Primero debes capturar y guardar las calificaciones.'
                );

                $book = DB::table(
                    'academic_gradebooks'
                )
                    ->where(
                        'id',
                        $book->id
                    )
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->lockForUpdate()
                    ->first();

                abort_unless(
                    $book &&
                    $book->status === 'draft',
                    409,
                    'Esta captura ya fue enviada o publicada.'
                );

                $this->requireComplete(
                    $a,
                    (int) $book->id,
                    $schoolId
                );

                DB::table('academic_gradebooks')
                    ->where('id', $book->id)
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->where(
                        'status',
                        'draft'
                    )
                    ->update([
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'updated_at' => now(),
                    ]);

                $this->audit(
                    $schoolId,
                    null,
                    (int) $actor->id,
                    'gradebook_submitted',
                    [
                        'gradebook_id' =>
                            $book->id,
                        'status' => 'draft',
                    ],
                    [
                        'gradebook_id' =>
                            $book->id,
                        'status' => 'submitted',
                    ]
                );
            },
            3
        );

        return back()->with(
            'success',
            'Captura enviada a Dirección. Las calificaciones quedaron bloqueadas para edición.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección: datos del listado React
    |--------------------------------------------------------------------------
    */

    public function directorData(
        Request $request
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $schoolId = (int) $actor->school_id;

        $cycle = DB::table('academic_cycles')
            ->where(
                'school_id',
                $schoolId
            )
            ->where('is_active', 1)
            ->first([
                'id',
                'name',
            ]);

        $levels = DB::table('academic_levels')
            ->where(
                'school_id',
                $schoolId
            )
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get([
                'id',
                'name',
            ]);

        $periods = DB::table(
            'academic_grading_periods'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->when(
                $cycle,
                fn ($query) =>
                    $query->where(
                        'academic_cycle_id',
                        $cycle->id
                    )
            )
            ->orderBy('sequence')
            ->get([
                'id',
                'name',
                'academic_level_id',
                'sequence',
                'status',
            ]);

        /*
         * Consultamos libros una sola vez.
         * No hacemos JOIN directo a la tabla
         * de varios docentes porque duplicaría
         * las filas del listado.
         */
        $query = DB::table(
            'academic_gradebooks as b'
        )
            ->join(
                'academic_teaching_assignments as a',
                'a.id',
                '=',
                'b.teaching_assignment_id'
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
            ->join(
                'academic_grading_periods as p',
                'p.id',
                '=',
                'b.grading_period_id'
            )
            ->leftJoin(
                'academic_teacher_profiles as tp',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'tp.id',
                            '=',
                            'a.teacher_profile_id'
                        )
                        ->where(
                            'tp.school_id',
                            '=',
                            $schoolId
                        );
                }
            )
            ->leftJoin(
                'users as u',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'u.id',
                            '=',
                            'tp.user_id'
                        )
                        ->where(
                            'u.school_id',
                            '=',
                            $schoolId
                        );
                }
            )
            ->where(
                'b.school_id',
                $schoolId
            )
            ->where(
                'a.school_id',
                $schoolId
            )
            ->where(
                'g.school_id',
                $schoolId
            )
            ->where(
                'ps.school_id',
                $schoolId
            )
            ->where(
                's.school_id',
                $schoolId
            )
            ->where(
                'p.school_id',
                $schoolId
            )
            ->whereColumn(
                'p.academic_cycle_id',
                'a.academic_cycle_id'
            );

        if ($cycle) {
            $query->where(
                'a.academic_cycle_id',
                $cycle->id
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        $books = $query
            ->select([
                'b.id',
                'b.status',
                'b.teaching_assignment_id',
                'b.grading_period_id',
                'b.submitted_at',
                'b.published_at',
                'a.school_group_id',
                'a.academic_cycle_id',
                'g.name as group_name',
                's.name as subject_name',
                'p.name as period_name',
                'u.name as teacher_name',
            ])
            ->orderByRaw(
                "CASE b.status
                    WHEN 'submitted' THEN 0
                    WHEN 'draft' THEN 1
                    ELSE 2 END"
            )
            ->orderBy('g.name')
            ->orderBy('s.name')
            ->get();

        /*
         * Agrupar nombres de docentes
         * por asignación, no por libro.
         */
        $teacherNames = DB::table(
            'academic_assignment_teachers as at'
        )
            ->join(
                'academic_teacher_profiles as tp',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'tp.id',
                            '=',
                            'at.teacher_profile_id'
                        )
                        ->where(
                            'tp.school_id',
                            '=',
                            $schoolId
                        );
                }
            )
            ->join(
                'users as u',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'u.id',
                            '=',
                            'tp.user_id'
                        )
                        ->where(
                            'u.school_id',
                            '=',
                            $schoolId
                        );
                }
            )
            ->where('at.school_id', $schoolId)
            ->where('at.status', 'active')
            ->whereNull('at.ended_at')
            ->whereIn(
                'at.teaching_assignment_id',
                $books->pluck(
                    'teaching_assignment_id'
                )
            )
            ->orderBy('u.name')
            ->get([
                'at.teaching_assignment_id',
                'u.name',
            ])
            ->groupBy(
                'teaching_assignment_id'
            )
            ->map(
                fn ($rows) => $rows
                    ->pluck('name')
                    ->unique()
                    ->implode(', ')
            );

        $items = $books->map(
            function ($book) use (
                $schoolId,
                $teacherNames
            ) {
                return array_merge(
                    (array) $book,
                    [
                        'teacher_name' =>
                            $teacherNames->get(
                                (int) $book->teaching_assignment_id
                            ) ?: $book->teacher_name,
                    ],
                    $this->gradeProgress(
                        $book,
                        (int) $book->id,
                        $schoolId
                    )
                );
            }
        );

        $submitted = $items->where(
            'status',
            'submitted'
        );

        $eligible = $submitted
            ->filter(
                fn ($book) =>
                    $book['complete']
            )
            ->count();

        return response()->json([
            'cycle' => $cycle,
            'levels' => $levels,
            'periods' => $periods,
            'books' => $items->values(),
            'bulk' => [
                'submitted' =>
                    $submitted->count(),
                'eligible' => $eligible,
                'can_publish' => (
                    $submitted->count() > 0 &&
                    $eligible === $submitted->count()
                ),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección: vistas Blade heredadas
    |--------------------------------------------------------------------------
    */

    public function director(
        Request $request
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $books = DB::table(
            'academic_gradebooks as b'
        )
            ->join(
                'academic_teaching_assignments as a',
                'a.id',
                '=',
                'b.teaching_assignment_id'
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
            ->join(
                'academic_grading_periods as p',
                'p.id',
                '=',
                'b.grading_period_id'
            )
            ->where(
                'b.school_id',
                $actor->school_id
            )
            ->where(
                'a.school_id',
                $actor->school_id
            )
            ->where(
                'g.school_id',
                $actor->school_id
            )
            ->where(
                'ps.school_id',
                $actor->school_id
            )
            ->where(
                's.school_id',
                $actor->school_id
            )
            ->where(
                'p.school_id',
                $actor->school_id
            )
            ->select(
                'b.id',
                'b.status',
                'b.teaching_assignment_id',
                'b.grading_period_id',
                'g.name as group_name',
                's.name as subject_name',
                'p.name as period_name'
            )
            ->orderByRaw(
                "CASE b.status
                    WHEN 'submitted' THEN 0
                    WHEN 'published' THEN 1
                    ELSE 2 END"
            )
            ->get();

        $cycle = DB::table(
            'academic_cycles'
        )
            ->where(
                'school_id',
                $actor->school_id
            )
            ->where(
                'is_active',
                1
            )
            ->first();

        $levels = DB::table(
            'academic_levels'
        )
            ->where(
                'school_id',
                $actor->school_id
            )
            ->where(
                'status',
                'active'
            )
            ->orderBy('sort_order')
            ->get();

        $periods = DB::table(
            'academic_grading_periods'
        )
            ->where(
                'school_id',
                $actor->school_id
            )
            ->orderBy('sequence')
            ->get();

        return view(
            'academic.grades.director',
            compact(
                'books',
                'cycle',
                'levels',
                'periods'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección: revisión React
    |--------------------------------------------------------------------------
    */

    public function reviewData(
        Request $request,
        int $book
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $schoolId = (int) $actor->school_id;

        $b = DB::table('academic_gradebooks')
            ->where('id', $book)
            ->where('school_id', $schoolId)
            ->first() ?? abort(404);

        $a = $this->assignment(
            (int) $b->teaching_assignment_id,
            $schoolId
        );

        $period = $this->period(
            (int) $b->grading_period_id,
            $a,
            $schoolId
        );

        $roster = $this->roster(
            $a,
            $schoolId
        );

        $grades = DB::table(
            'academic_student_grades'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'gradebook_id',
                $book
            )
            ->get([
                'id',
                'student_enrollment_id',
                'numeric_value',
            ])
            ->keyBy(
                'student_enrollment_id'
            );

        $students = $roster->map(
            function ($student) use ($grades) {
                $grade = $grades->get(
                    $student->enrollment_id
                );

                return [
                    'enrollment_id' =>
                        (int) $student->enrollment_id,
                    'student_code' =>
                        $student->student_code,
                    'student_name' => trim(
                        $student->last_name .
                        ' ' .
                        $student->first_name
                    ),
                    'grade_id' =>
                        $grade?->id,
                    'numeric_value' =>
                        $grade?->numeric_value,
                ];
            }
        );

        $gradeIds = $grades
            ->pluck('id')
            ->all();

        $history = DB::table(
            'academic_grade_audit_logs as l'
        )
            ->leftJoin(
                'users as u',
                function ($join) use ($schoolId): void {
                    $join
                        ->on(
                            'u.id',
                            '=',
                            'l.actor_user_id'
                        )
                        ->where(
                            'u.school_id',
                            '=',
                            $schoolId
                        );
                }
            )
            ->where(
                'l.school_id',
                $schoolId
            )
            ->where(
                'l.action',
                'director_grade_corrected'
            )
            ->whereIn(
                'l.student_grade_id',
                $gradeIds
            )
            ->orderByDesc('l.id')
            ->limit(100)
            ->get([
                'l.id',
                'l.student_grade_id',
                'l.actor_user_id',
                'u.name as actor_name',
                'l.before_json',
                'l.after_json',
                'l.reason',
                'l.created_at',
            ])
            ->map(
                fn ($row) => [
                    'id' => $row->id,
                    'student_grade_id' =>
                        $row->student_grade_id,
                    'actor_name' =>
                        $row->actor_name
                            ?? 'Usuario no disponible',
                    'before' => json_decode(
                        $row->before_json ?? 'null',
                        true
                    ),
                    'after' => json_decode(
                        $row->after_json ?? 'null',
                        true
                    ),
                    'reason' => $row->reason,
                    'created_at' =>
                        $row->created_at,
                ]
            );

        return response()->json([
            'book' => [
                'id' => (int) $b->id,
                'status' => $b->status,
                'subject_name' =>
                    $a->subject_name,
                'group_name' =>
                    $a->group_name,
                'period_name' =>
                    $period->name,
                'submitted_at' =>
                    $b->submitted_at,
                'published_at' =>
                    $b->published_at,
            ],
            'progress' => $this->gradeProgress(
                $a,
                $book,
                $schoolId
            ),
            'students' => $students,
            'history' => $history,
        ]);
    }

    public function review(
        Request $request,
        int $book
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $schoolId = (int) $actor->school_id;

        $b = DB::table('academic_gradebooks')
            ->where('id', $book)
            ->where(
                'school_id',
                $schoolId
            )
            ->first() ?? abort(404);

        $a = $this->assignment(
            (int) $b->teaching_assignment_id,
            $schoolId
        );

        $this->period(
            (int) $b->grading_period_id,
            $a,
            $schoolId
        );

        $roster = $this->roster(
            $a,
            $schoolId
        );

        $grades = DB::table(
            'academic_student_grades'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'gradebook_id',
                $book
            )
            ->get()
            ->keyBy(
                'student_enrollment_id'
            );

        return view(
            'academic.grades.review',
            compact(
                'b',
                'a',
                'roster',
                'grades'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección: publicar una captura
    |--------------------------------------------------------------------------
    */

    public function publish(
        Request $request,
        int $book
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $schoolId = (int) $actor->school_id;

        DB::transaction(
            function () use (
                $actor,
                $schoolId,
                $book
            ): void {
                $b = DB::table(
                    'academic_gradebooks'
                )
                    ->where('id', $book)
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->lockForUpdate()
                    ->first() ?? abort(404);

                abort_unless(
                    $b->status === 'submitted',
                    409,
                    'Solo puede publicarse una captura enviada.'
                );

                $a = $this->assignment(
                    (int) $b->teaching_assignment_id,
                    $schoolId
                );

                $this->period(
                    (int) $b->grading_period_id,
                    $a,
                    $schoolId
                );

                $this->requireComplete(
                    $a,
                    (int) $b->id,
                    $schoolId
                );

                DB::table(
                    'academic_gradebooks'
                )
                    ->where('id', $book)
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->where(
                        'status',
                        'submitted'
                    )
                    ->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'published_by_user_id' =>
                            $actor->id,
                        'updated_at' => now(),
                    ]);

                $this->audit(
                    $schoolId,
                    null,
                    (int) $actor->id,
                    'gradebook_published',
                    [
                        'gradebook_id' =>
                            $book,
                        'status' => 'submitted',
                    ],
                    [
                        'gradebook_id' =>
                            $book,
                        'status' => 'published',
                    ]
                );
            },
            3
        );

        return $this->actionResponse(
            $request,
            'Calificaciones validadas y publicadas.',
            route('academic.grades.director')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección: publicar todas las enviadas
    |--------------------------------------------------------------------------
    */

    public function publishAll(
        Request $request
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $schoolId = (int) $actor->school_id;

        $cycleId = DB::table(
            'academic_cycles'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->where('is_active', 1)
            ->value('id');

        abort_unless(
            $cycleId,
            422,
            'No existe un ciclo escolar activo.'
        );

        $published = DB::transaction(
            function () use (
                $actor,
                $schoolId,
                $cycleId
            ): int {
                $assignmentIds = DB::table(
                    'academic_teaching_assignments'
                )
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->where(
                        'academic_cycle_id',
                        $cycleId
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->select('id');

                $books = DB::table(
                    'academic_gradebooks'
                )
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->where(
                        'status',
                        'submitted'
                    )
                    ->whereIn(
                        'teaching_assignment_id',
                        $assignmentIds
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($books->isEmpty()) {
                    throw ValidationException::withMessages([
                        'books' =>
                            'No hay capturas enviadas pendientes de validación.',
                    ]);
                }

                /*
                 * Validación integral:
                 * si un libro está incompleto,
                 * no se publica ninguno.
                 */
                foreach ($books as $book) {
                    $a = $this->assignment(
                        (int) $book->teaching_assignment_id,
                        $schoolId
                    );

                    $this->period(
                        (int) $book->grading_period_id,
                        $a,
                        $schoolId
                    );

                    $this->requireComplete(
                        $a,
                        (int) $book->id,
                        $schoolId
                    );
                }

                foreach ($books as $book) {
                    DB::table(
                        'academic_gradebooks'
                    )
                        ->where(
                            'id',
                            $book->id
                        )
                        ->where(
                            'school_id',
                            $schoolId
                        )
                        ->where(
                            'status',
                            'submitted'
                        )
                        ->update([
                            'status' => 'published',
                            'published_at' => now(),
                            'published_by_user_id' =>
                                $actor->id,
                            'updated_at' => now(),
                        ]);

                    $this->audit(
                        $schoolId,
                        null,
                        (int) $actor->id,
                        'gradebook_published_bulk',
                        [
                            'gradebook_id' =>
                                $book->id,
                            'status' => 'submitted',
                        ],
                        [
                            'gradebook_id' =>
                                $book->id,
                            'status' => 'published',
                        ]
                    );
                }

                return $books->count();
            },
            3
        );

        return $this->actionResponse(
            $request,
            $published .
                ' capturas validadas y publicadas.',
            route('academic.grades.director')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección: corrección auditada
    |--------------------------------------------------------------------------
    */

    public function correct(
        Request $request,
        int $book,
        int $grade
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $schoolId = (int) $actor->school_id;

        $data = $request->validate([
            'numeric_value' => [
                'required',
                'numeric',
                'between:0,10',
                'decimal:0,1',
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        DB::transaction(
            function () use (
                $actor,
                $schoolId,
                $book,
                $grade,
                $data
            ): void {
                $b = DB::table(
                    'academic_gradebooks'
                )
                    ->where(
                        'id',
                        $book
                    )
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->lockForUpdate()
                    ->first() ?? abort(404);

                abort_unless(
                    $b->status === 'published',
                    409,
                    'Solo se pueden corregir notas publicadas.'
                );

                $a = $this->assignment(
                    (int) $b->teaching_assignment_id,
                    $schoolId
                );

                $allowed = $this->roster(
                    $a,
                    $schoolId
                )
                    ->pluck('enrollment_id')
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->all();

                $g = DB::table(
                    'academic_student_grades'
                )
                    ->where('id', $grade)
                    ->where(
                        'gradebook_id',
                        $book
                    )
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->lockForUpdate()
                    ->first() ?? abort(404);

                abort_unless(
                    in_array(
                        (int) $g->student_enrollment_id,
                        $allowed,
                        true
                    ),
                    403
                );

                abort_if(
                    (float) $g->numeric_value ===
                    (float) $data['numeric_value'],
                    422,
                    'La nueva calificación debe ser distinta de la actual.'
                );

                DB::table(
                    'academic_student_grades'
                )
                    ->where(
                        'id',
                        $grade
                    )
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->update([
                        'numeric_value' =>
                            $data['numeric_value'],
                        'graded_by_user_id' =>
                            $actor->id,
                        'graded_at' => now(),
                        'updated_at' => now(),
                    ]);

                $this->audit(
                    $schoolId,
                    $grade,
                    (int) $actor->id,
                    'director_grade_corrected',
                    [
                        'numeric_value' =>
                            $g->numeric_value,
                    ],
                    [
                        'numeric_value' =>
                            (float) $data['numeric_value'],
                    ],
                    trim($data['reason'])
                );
            },
            3
        );

        return $this->actionResponse(
            $request,
            'Calificación corregida con auditoría interna.',
            route('academic.grades.director')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección: crear periodo
    |--------------------------------------------------------------------------
    */

    public function createPeriod(
        Request $request
    ) {
        $actor = $this->actor(
            $request,
            ['director', 'school_admin']
        );

        $schoolId = (int) $actor->school_id;

        $data = $request->validate([
            'academic_cycle_id' => [
                'required',
                'integer',
            ],
            'academic_level_id' => [
                'required',
                'integer',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],
        ]);

        $cycle = DB::table(
            'academic_cycles'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'id',
                $data['academic_cycle_id']
            )
            ->where(
                'is_active',
                1
            )
            ->first();

        $level = DB::table(
            'academic_levels'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'id',
                $data['academic_level_id']
            )
            ->first();

        abort_unless(
            $cycle && $level,
            422,
            'Ciclo o nivel inválido.'
        );

        $exists = DB::table(
            'academic_grading_periods'
        )
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'academic_cycle_id',
                $cycle->id
            )
            ->where(
                'academic_level_id',
                $level->id
            )
            ->where(
                'sequence',
                $data['sequence']
            )
            ->exists();

        abort_if(
            $exists,
            422,
            'Ya existe ese periodo para el nivel.'
        );

        DB::table(
            'academic_grading_periods'
        )
            ->insert([
                'school_id' =>
                    $schoolId,
                'academic_cycle_id' =>
                    $cycle->id,
                'academic_level_id' =>
                    $level->id,
                'name' => $data['name'],
                'sequence' =>
                    $data['sequence'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->actionResponse(
            $request,
            'Periodo creado.',
            route('academic.grades.director')
        );
    }
}
