<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use App\Support\SchoolCore\AcademicPdfBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class AcademicGradeExportController extends Controller
{
    public function __construct(
        private readonly ModuleAccessService $modules
    ) {}

    private function schoolId(Request $request): int
    {
        $user = $request->user();

        abort_unless(
            $user &&
            $user->school_id &&
            in_array(
                (string) $user->role,
                ['director', 'school_admin'],
                true
            ),
            403
        );

        $schoolId = (int) $user->school_id;

        abort_unless(
            $this->modules->canAccess(
                $schoolId,
                'academic'
            ),
            403
        );

        return $schoolId;
    }

    /*
     * Capturas del ciclo activo, con identidad
     * de grupo, periodo y materia.
     */
    private function books(int $schoolId): Builder
    {
        return DB::table('academic_gradebooks as b')
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
                'academic_grading_periods as p',
                'p.id',
                '=',
                'b.grading_period_id'
            )
            ->join(
                'academic_cycles as c',
                'c.id',
                '=',
                'a.academic_cycle_id'
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
            ->where('b.school_id', $schoolId)
            ->where('a.school_id', $schoolId)
            ->where('g.school_id', $schoolId)
            ->where('p.school_id', $schoolId)
            ->where('c.school_id', $schoolId)
            ->where('ps.school_id', $schoolId)
            ->where('s.school_id', $schoolId)
            ->where('c.is_active', 1)
            ->whereColumn(
                'p.academic_cycle_id',
                'c.id'
            )
            ->whereColumn(
                'g.academic_cycle_id',
                'c.id'
            )
            ->select([
                'b.id',
                'b.status',
                'b.published_at',
                'a.id as assignment_id',
                'g.id as group_id',
                'g.name as group_name',
                'c.id as cycle_id',
                'c.name as cycle_name',
                'p.name as period_name',
                'p.sequence as period_sequence',
                's.name as subject_name',
            ]);
    }

    /*
     * Docentes activos sobre una misma asignación.
     * Evita duplicar libros por codocencia.
     */
    private function teacherNames(
        int $schoolId,
        array $assignmentIds
    ): array {
        if ($assignmentIds === []) {
            return [];
        }

        return DB::table(
            'academic_assignment_teachers as at'
        )
            ->join(
                'academic_teacher_profiles as tp',
                'tp.id',
                '=',
                'at.teacher_profile_id'
            )
            ->join(
                'users as u',
                'u.id',
                '=',
                'tp.user_id'
            )
            ->where('at.school_id', $schoolId)
            ->where('tp.school_id', $schoolId)
            ->where('u.school_id', $schoolId)
            ->where('at.status', 'active')
            ->whereNull('at.ended_at')
            ->whereIn(
                'at.teaching_assignment_id',
                $assignmentIds
            )
            ->orderBy('u.name')
            ->get([
                'at.teaching_assignment_id',
                'u.name',
            ])
            ->groupBy('teaching_assignment_id')
            ->map(
                fn ($rows) =>
                    $rows->pluck('name')
                        ->unique()
                        ->implode(', ')
            )
            ->all();
    }

    /*
     * PDF general: únicamente las capturas
     * visibles según los filtros de React.
     */
    public function summary(Request $request): Response
    {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'book_ids' => [
                'required',
                'array',
                'min:1',
                'max:500',
            ],
            'book_ids.*' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
        ]);

        $ids = array_map(
            'intval',
            $data['book_ids']
        );

        $books = $this->books($schoolId)
            ->whereIn('b.id', $ids)
            ->orderBy('g.name')
            ->orderBy('s.name')
            ->orderBy('p.sequence')
            ->get();

        abort_unless(
            $books->count() === count($ids),
            409,
            'La lista cambió; actualiza las capturas.'
        );

        $teacherNames = $this->teacherNames(
            $schoolId,
            $books->pluck('assignment_id')
                ->unique()
                ->all()
        );

        /*
         * Cantidad de alumnos activos
         * por grupo y ciclo.
         */
        $roster = DB::table('student_enrollments')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereIn(
                'school_group_id',
                $books->pluck('group_id')
                    ->unique()
                    ->all()
            )
            ->whereIn(
                'academic_cycle_id',
                $books->pluck('cycle_id')
                    ->unique()
                    ->all()
            )
            ->selectRaw(
                'academic_cycle_id, school_group_id, ' .
                'COUNT(*) AS total'
            )
            ->groupBy(
                'academic_cycle_id',
                'school_group_id'
            )
            ->get()
            ->keyBy(
                fn ($row) =>
                    $row->academic_cycle_id
                    . ':'
                    . $row->school_group_id
            );

        /*
         * Notas numéricas capturadas,
         * asociadas a la inscripción correcta.
         */
        $recorded = DB::table(
            'academic_student_grades as sg'
        )
            ->join(
                'student_enrollments as e',
                'e.id',
                '=',
                'sg.student_enrollment_id'
            )
            ->join(
                'academic_gradebooks as b',
                'b.id',
                '=',
                'sg.gradebook_id'
            )
            ->join(
                'academic_teaching_assignments as a',
                'a.id',
                '=',
                'b.teaching_assignment_id'
            )
            ->where('sg.school_id', $schoolId)
            ->where('e.school_id', $schoolId)
            ->where('b.school_id', $schoolId)
            ->where('a.school_id', $schoolId)
            ->where('e.status', 'active')
            ->whereColumn(
                'e.school_group_id',
                'a.school_group_id'
            )
            ->whereColumn(
                'e.academic_cycle_id',
                'a.academic_cycle_id'
            )
            ->whereIn('sg.gradebook_id', $ids)
            ->whereNotNull('sg.numeric_value')
            ->selectRaw(
                'sg.gradebook_id, COUNT(*) AS total'
            )
            ->groupBy('sg.gradebook_id')
            ->pluck('total', 'sg.gradebook_id');

        $rows = $books->map(
            function ($book) use (
                $roster,
                $recorded,
                $teacherNames
            ): array {
                $key =
                    $book->cycle_id .
                    ':' .
                    $book->group_id;

                $total = (int) (
                    $roster->get($key)->total ?? 0
                );

                $complete = (int) (
                    $recorded[$book->id] ?? 0
                );

                return [
                    'group' => $book->group_name,
                    'subject' => $book->subject_name,
                    'period' => $book->period_name,

                    'teacher' =>
                        $teacherNames[
                            $book->assignment_id
                        ] ?? 'Sin docente asignado',

                    'progress' =>
                        $complete . '/' . $total,

                    'status' => $book->status,
                ];
            }
        )->all();

        $branding = AcademicPdfBranding::forSchool(
            $schoolId
        );

        $response = Pdf::loadView(
            'academic.grade-export-pdf',
            [
                'branding' => $branding,
                'mode' => 'summary',
                'rows' => $rows,
                'cycle' => $books->first()->cycle_name,
                'book' => null,
                'students' => [],
                'teachers' => '',
            ]
        )
            ->setPaper('a4', 'landscape')
            ->download(
                'capturas-academic-' .
                now()->format('Ymd-His') .
                '.pdf'
            );

        $response->headers->set(
            'Cache-Control',
            'no-store, private'
        );

        return $response;
    }

    /*
     * PDF de una captura:
     * grupo + materia + periodo + alumnado.
     */
    public function book(
        Request $request,
        int $book
    ): Response {
        $schoolId = $this->schoolId($request);

        $item = $this->books($schoolId)
            ->where('b.id', $book)
            ->first();

        abort_unless($item, 404);

        $teachers = $this->teacherNames(
            $schoolId,
            [(int) $item->assignment_id]
        );

        $students = DB::table(
            'student_enrollments as e'
        )
            ->join(
                'students as s',
                's.id',
                '=',
                'e.student_id'
            )
            ->leftJoin(
                'academic_student_grades as sg',
                function ($join) use (
                    $book,
                    $schoolId
                ): void {
                    $join->on(
                        'sg.student_enrollment_id',
                        '=',
                        'e.id'
                    )
                        ->where(
                            'sg.gradebook_id',
                            $book
                        )
                        ->where(
                            'sg.school_id',
                            $schoolId
                        );
                }
            )
            ->where('e.school_id', $schoolId)
            ->where('s.school_id', $schoolId)
            ->where(
                'e.academic_cycle_id',
                $item->cycle_id
            )
            ->where(
                'e.school_group_id',
                $item->group_id
            )
            ->where('e.status', 'active')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get([
                's.student_code',
                's.first_name',
                's.last_name',
                'sg.numeric_value',
                'sg.text_value',
            ])
            ->map(
                static fn ($row): array => [
                    'code' =>
                        (string) $row->student_code,

                    'name' => trim(
                        $row->last_name .
                        ' ' .
                        $row->first_name
                    ),

                    'grade' =>
                        $row->numeric_value !== null
                            ? number_format(
                                (float) $row->numeric_value,
                                1,
                                '.',
                                ''
                            )
                            : (
                                trim(
                                    (string) $row->text_value
                                ) ?: '—'
                            ),
                ]
            )
            ->all();

        $branding = AcademicPdfBranding::forSchool(
            $schoolId
        );

        $response = Pdf::loadView(
            'academic.grade-export-pdf',
            [
                'branding' => $branding,
                'mode' => 'book',
                'rows' => [],
                'cycle' => $item->cycle_name,
                'book' => $item,
                'students' => $students,

                'teachers' =>
                    $teachers[
                        $item->assignment_id
                    ] ?? 'Sin docente asignado',
            ]
        )
            ->setPaper('a4', 'portrait')
            ->download(
                'captura-' . $item->id . '.pdf'
            );

        $response->headers->set(
            'Cache-Control',
            'no-store, private'
        );

        return $response;
    }
}
