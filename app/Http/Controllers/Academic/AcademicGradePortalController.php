<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\SchoolCore\AcademicPdfBranding;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

final class AcademicGradePortalController extends Controller
{
    public function __construct(private readonly ModuleAccessService $modules) {}

    private function school(string $slug): object
    {
        $school = DB::table('schools')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first(['id', 'name', 'slug']);

        abort_unless($school, 404);
        abort_unless($this->modules->canAccess((int) $school->id, 'academic'), 404);
        return $school;
    }

    private function enabled(int $schoolId): bool
    {
        return (bool) DB::table('academic_grade_portal_settings')
            ->where('school_id', $schoolId)->value('enabled');
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        return $response->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function show(string $school): View
    {
        $schoolRow = $this->school($school);
        abort_unless($this->enabled((int) $schoolRow->id), 404);

        return view('academic.public-grades', [
            'schoolName' => $schoolRow->name,
            'schoolSlug' => $schoolRow->slug,
        ]);
    }


public function unlock(Request $request, string $school): JsonResponse
{
    $schoolRow = $this->school($school);
    $schoolId = (int) $schoolRow->id;

    abort_unless($this->enabled($schoolId), 404);

    $data = $request->validate([
        'curp' => [
            'required',
            'string',
            'size:18',
            'regex:/^[A-Za-z0-9]{18}$/',
        ],
        'student_code' => [
            'required',
            'string',
            'max:50',
        ],
    ]);

    $curp = strtoupper(trim($data['curp']));
    $studentCode = strtoupper(trim($data['student_code']));

    $secret = (string) config('app.key');

    $keyIp = 'grades:ip:' . hash_hmac(
        'sha256',
        $schoolId . '|' . $request->ip(),
        $secret
    );

    $keyStudent = 'grades:student:' . hash_hmac(
        'sha256',
        $schoolId . '|' . $curp,
        $secret
    );

    if (
        RateLimiter::tooManyAttempts($keyIp, 20) ||
        RateLimiter::tooManyAttempts($keyStudent, 5)
    ) {
        return $this->noStore(response()->json([
            'message' => 'Demasiados intentos. Intenta más tarde.',
        ], 429));
    }

    $student = DB::table('students')
        ->where('school_id', $schoolId)
        ->where('curp', $curp)
        ->where('status', 'active')
        ->first(['id', 'student_code']);

    $valid = $student !== null
        && hash_equals(
            strtoupper(trim((string) $student->student_code)),
            $studentCode
        );

    if ($valid) {
        $valid = DB::table('student_enrollments as e')
            ->join(
                'academic_cycles as c',
                'c.id',
                '=',
                'e.academic_cycle_id'
            )
            ->where('e.school_id', $schoolId)
            ->where('e.student_id', $student->id)
            ->where('e.status', 'active')
            ->where('c.school_id', $schoolId)
            ->where('c.is_active', 1)
            ->exists();
    }

    if (!$valid) {
        RateLimiter::hit($keyIp, 900);
        RateLimiter::hit($keyStudent, 900);

        return $this->noStore(response()->json([
            'message' => 'No fue posible validar los datos proporcionados.',
        ], 422));
    }

    RateLimiter::clear($keyStudent);

    $request->session()->regenerate();

    $request->session()->put('academic_grade_portal', [
        'school_id' => $schoolId,
        'student_id' => (int) $student->id,
        'expires' => now()->addMinutes(15)->timestamp,
    ]);

    return $this->noStore(response()->json([
        'message' => 'Consulta autorizada.',
        'csrf_token' => csrf_token(),
    ]));
}



private function currentStudent(
    Request $request,
    int $schoolId
): int {
    abort_unless($this->enabled($schoolId), 403);

    $session = $request->session()
        ->get('academic_grade_portal');

    abort_unless(is_array($session), 401);

    abort_unless(
        (int) ($session['school_id'] ?? 0) === $schoolId,
        401
    );

    abort_unless(
        (int) ($session['expires'] ?? 0) >= now()->timestamp,
        401
    );

    $studentId = (int) ($session['student_id'] ?? 0);

    $valid = DB::table('students as s')
        ->join(
            'student_enrollments as e',
            'e.student_id',
            '=',
            's.id'
        )
        ->join(
            'academic_cycles as c',
            'c.id',
            '=',
            'e.academic_cycle_id'
        )
        ->where('s.id', $studentId)
        ->where('s.school_id', $schoolId)
        ->where('s.status', 'active')
        ->where('e.school_id', $schoolId)
        ->where('e.status', 'active')
        ->where('c.school_id', $schoolId)
        ->where('c.is_active', 1)
        ->exists();

    abort_unless($valid, 401);

    return $studentId;
}


    private function report(int $schoolId, int $studentId, string $schoolName): array
    {
        $student = DB::table('students')
            ->where('school_id', $schoolId)->where('id', $studentId)
            ->first(['id', 'first_name', 'last_name', 'student_code']);
        abort_unless($student, 401);

        $rows = DB::table('academic_student_grades as grade')
            ->join('student_enrollments as e', 'e.id', '=', 'grade.student_enrollment_id')
            ->join('academic_cycles as cycle', 'cycle.id', '=', 'e.academic_cycle_id')
            ->join('academic_gradebooks as book', 'book.id', '=', 'grade.gradebook_id')
            ->join('academic_teaching_assignments as a', 'a.id', '=', 'book.teaching_assignment_id')
            ->join('academic_curriculum_plan_subjects as ps', 'ps.id', '=', 'a.plan_subject_id')
            ->join('academic_subjects as subject', 'subject.id', '=', 'ps.subject_id')
            ->join('academic_grading_periods as period', 'period.id', '=', 'book.grading_period_id')
            ->where('grade.school_id', $schoolId)
            ->where('e.school_id', $schoolId)
            ->where('e.student_id', $studentId)
            ->where('e.status', 'active')
            ->where('cycle.school_id', $schoolId)->where('cycle.is_active', 1)
            ->where('book.school_id', $schoolId)->where('book.status', 'published')
            ->whereNotNull('book.published_at')
            ->where('a.school_id', $schoolId)
            ->whereColumn('a.academic_cycle_id', 'e.academic_cycle_id')
            ->where('ps.school_id', $schoolId)
            ->where('subject.school_id', $schoolId)
            ->where('period.school_id', $schoolId)
            ->whereColumn('period.academic_cycle_id', 'e.academic_cycle_id')
            ->whereNotNull('grade.numeric_value')
            ->orderBy('period.sequence')
            ->orderBy('subject.name')
            ->get([
                'cycle.name as cycle', 'period.name as period', 'period.sequence as period_sequence',
                'subject.name as subject', 'grade.numeric_value',
                'ps.affects_average', 'book.published_at',
            ]);

        $periods = $rows->groupBy('period_sequence')->map(function ($items) {
            $graded = $items->filter(fn ($row) => (bool) $row->affects_average);
            return [
                'name' => (string) $items->first()->period,
                'grades' => $items->map(static fn ($row): array => [
                    'subject' => (string) $row->subject,
                    'value' => number_format((float) $row->numeric_value, 1, '.', ''),
                    'affects_average' => (bool) $row->affects_average,
                    'published_at' => $row->published_at,
                ])->values(),
                'average' => $graded->isNotEmpty()
                    ? number_format($graded->avg(fn ($r) => (float) $r->numeric_value), 1, '.', '')
                    : null,
            ];
        })->values();

        return [
            'school' => $schoolName,
            'student' => trim($student->first_name . ' ' . $student->last_name),
            'student_code' => $student->student_code,
            'cycle' => $rows->first()?->cycle,
            'periods' => $periods,
        ];
    }

    public function results(Request $request, string $school): JsonResponse
    {
        $schoolRow = $this->school($school);
        $schoolId = (int) $schoolRow->id;
        $studentId = $this->currentStudent($request, $schoolId);
        return $this->noStore(response()->json($this->report($schoolId, $studentId, $schoolRow->name)));
    }


public function pdf(Request $request, string $school)
{
    $schoolRow = $this->school($school);

    $schoolId = (int) $schoolRow->id;

    // Verifica sesión autorizada y consulta habilitada.
    $studentId = $this->currentStudent(
        $request,
        $schoolId
    );

    // Recupera exclusivamente libros publicados.
    $data = $this->report(
        $schoolId,
        $studentId,
        $schoolRow->name
    );

    /*
     * Grupo y ciclo desde la inscripción activa,
     * no desde students.current_group_id.
     */
    $enrollment = DB::table(
        'student_enrollments as e'
    )
        ->join(
            'academic_cycles as c',
            'c.id',
            '=',
            'e.academic_cycle_id'
        )
        ->leftJoin(
            'school_groups as g',
            function ($join) use ($schoolId): void {
                $join->on(
                    'g.id',
                    '=',
                    'e.school_group_id'
                )->where(
                    'g.school_id',
                    '=',
                    $schoolId
                );
            }
        )
        ->where('e.school_id', $schoolId)
        ->where('e.student_id', $studentId)
        ->where('e.status', 'active')
        ->where('c.school_id', $schoolId)
        ->where('c.is_active', 1)
        ->orderByDesc('e.id')
        ->first([
            'g.name as group_name',
            'c.name as cycle_name',
        ]);

    $data['group'] = $enrollment?->group_name;

    $data['cycle'] =
        $data['cycle'] ??
        $enrollment?->cycle_name;

    $data['branding'] =
        AcademicPdfBranding::forSchool($schoolId);

    $code = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '',
        (string) $data['student_code']
    );

    $response = Pdf::loadView(
        'academic.public-grades-pdf',
        $data
    )
        ->setPaper('a4', 'portrait')
        ->download(
            'calificaciones-' . $code . '.pdf'
        );

    $response->headers->set(
        'Cache-Control',
        'no-store, private'
    );

    return $response;
}


    public function lock(Request $request, string $school): JsonResponse
    {
        $this->school($school);
        $request->session()->forget('academic_grade_portal');
        $request->session()->regenerateToken();

        return $this->noStore(response()->json([
            'message' => 'Consulta cerrada.',
            'csrf_token' => csrf_token(),
        ]));
    }
}
