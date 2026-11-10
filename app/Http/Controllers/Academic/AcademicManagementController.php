<?php
declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AcademicManagementController extends Controller
{
    private function schoolId(Request $request): int
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);

        abort_unless($schoolId > 0, 403);

        return $schoolId;
    }

    private function activeCycle(int $schoolId): ?object
    {
        return DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->first(['id', 'name']);
    }

    public function data(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);
        $cycle = $this->activeCycle($schoolId);

        $teachers = DB::table('academic_teacher_profiles as t')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('t.school_id', $schoolId)
            ->where('u.school_id', $schoolId)
            ->orderBy('u.name')
            ->select([
                't.id',
                't.user_id',
                'u.name',
                't.employee_code',
                't.status',
            ])
            ->get();

        $subjects = DB::table('academic_subjects')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'kind',
                'status',
            ]);

        $periods = $cycle
            ? DB::table('academic_grading_periods')
                ->where('school_id', $schoolId)
                ->where('academic_cycle_id', $cycle->id)
                ->orderBy('sequence')
                ->orderBy('id')
                ->get([
                    'id',
                    'name',
                    'sequence',
                    'academic_level_id',
                    'starts_on',
                    'ends_on',
                    'status',
                ])
            : collect();

        return response()->json([
            'cycle' => $cycle,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'periods' => $periods,
        ]);
    }

    public function storeTeacher(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('school_id', $schoolId),
            ],
            'employee_code' => [
                'nullable',
                'string',
                'max:60',
            ],
        ]);

        $user = DB::table('users')
            ->where('id', $data['user_id'])
            ->where('school_id', $schoolId)
            ->first(['id', 'role']);

        abort_unless(
            $user && $user->role === 'teacher',
            422,
            'El usuario debe tener el rol docente.'
        );

        $exists = DB::table('academic_teacher_profiles')
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'El docente ya tiene un perfil académico.',
            ], 409);
        }

        $id = DB::table('academic_teacher_profiles')
            ->insertGetId([
                'school_id' => $schoolId,
                'user_id' => $user->id,
                'employee_code' => $data['employee_code'] ?? null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Docente registrado.',
            'id' => $id,
        ], 201);
    }

    public function updateTeacher(
        Request $request,
        int $teacher
    ): JsonResponse {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'employee_code' => [
                'nullable',
                'string',
                'max:60',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ]);

        $updated = DB::table('academic_teacher_profiles')
            ->where('id', $teacher)
            ->where('school_id', $schoolId)
            ->update([
                'employee_code' => $data['employee_code'] ?? null,
                'status' => $data['status'],
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            $exists = DB::table('academic_teacher_profiles')
                ->where('id', $teacher)
                ->where('school_id', $schoolId)
                ->exists();

            abort_unless($exists, 404);
        }

        return response()->json([
            'message' => 'Docente actualizado.',
        ]);
    }

    public function storeSubject(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('academic_subjects', 'code')
                    ->where('school_id', $schoolId),
            ],
            'kind' => [
                'required',
                Rule::in(['official', 'custom']),
            ],
        ]);

        $id = DB::table('academic_subjects')
            ->insertGetId([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'code' => $data['code'],
                'kind' => $data['kind'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Materia registrada.',
            'id' => $id,
        ], 201);
    }

    public function updateSubject(
        Request $request,
        int $subject
    ): JsonResponse {
        $schoolId = $this->schoolId($request);

        $current = DB::table('academic_subjects')
            ->where('id', $subject)
            ->where('school_id', $schoolId)
            ->first();

        abort_unless($current, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('academic_subjects', 'code')
                    ->where('school_id', $schoolId)
                    ->ignore($subject),
            ],
            'kind' => [
                'required',
                Rule::in(['official', 'custom']),
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ]);

        DB::table('academic_subjects')
            ->where('id', $subject)
            ->where('school_id', $schoolId)
            ->update([
                ...$data,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Materia actualizada.',
        ]);
    }

    public function storePeriod(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);
        $cycle = $this->activeCycle($schoolId);

        abort_unless(
            $cycle,
            422,
            'Debes activar un ciclo escolar.'
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => [
                'nullable',
                'date',
                'after_or_equal:starts_on',
            ],
        ]);

        $exists = DB::table('academic_grading_periods')
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycle->id)
            ->whereNull('academic_level_id')
            ->where('sequence', $data['sequence'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un período con ese orden.',
            ], 409);
        }

        $id = DB::table('academic_grading_periods')
            ->insertGetId([
                'school_id' => $schoolId,
                'academic_cycle_id' => $cycle->id,
                'academic_level_id' => null,
                'name' => $data['name'],
                'sequence' => $data['sequence'],
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Período creado.',
            'id' => $id,
        ], 201);
    }

    public function updatePeriod(
        Request $request,
        int $period
    ): JsonResponse {
        $schoolId = $this->schoolId($request);

        $current = DB::table('academic_grading_periods')
            ->where('school_id', $schoolId)
            ->where('id', $period)
            ->first();

        abort_unless($current, 404);

        abort_unless(
            $current->status === 'draft',
            409,
            'Solamente se pueden editar períodos en borrador.'
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => [
                'nullable',
                'date',
                'after_or_equal:starts_on',
            ],
        ]);

        DB::table('academic_grading_periods')
            ->where('id', $period)
            ->where('school_id', $schoolId)
            ->where('status', 'draft')
            ->update([
                ...$data,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Período actualizado.',
        ]);
    }
}
