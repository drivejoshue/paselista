<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AcademicCatalogController extends Controller
{
    public function __construct(private readonly ModuleAccessService $modules) {}

    private function school(Request $request): int
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId < 1, 403, 'Usuario sin escuela asociada.');
        abort_unless($this->modules->canAccess($schoolId, 'academic'), 403, 'Academic no contratado.');
        return $schoolId;
    }

    public function index(Request $request): JsonResponse
    {
        $school = $this->school($request);
        $cycles = DB::table('academic_cycles')->where('school_id', $school)
            ->orderByDesc('is_active')->orderByDesc('id')->get(['id','name','status','is_active']);
        $levels = DB::table('academic_levels')->where('school_id', $school)
            ->where('status', 'active')->orderBy('sort_order')->get(['id','name']);
        $plans = DB::table('academic_curriculum_plans as p')
            ->join('academic_cycles as c', function ($join) use ($school) {
                $join->on('c.id','=','p.academic_cycle_id')->where('c.school_id','=',$school);
            })
            ->join('academic_levels as l', function ($join) use ($school) {
                $join->on('l.id','=','p.academic_level_id')->where('l.school_id','=',$school);
            })
            ->where('p.school_id',$school)
            ->orderByDesc('p.id')
            ->get(['p.id','p.name','p.framework_code','p.version','p.status','p.academic_cycle_id','p.academic_level_id','c.name as cycle_name','l.name as level_name']);
        $subjects = DB::table('academic_subjects')->where('school_id',$school)
            ->orderBy('name')->get(['id','name','code','kind','status']);
        $links = DB::table('academic_curriculum_plan_subjects as ps')
            ->join('academic_curriculum_plans as p', function ($join) use ($school) {
                $join->on('p.id','=','ps.curriculum_plan_id')->where('p.school_id','=',$school);
            })
            ->join('academic_subjects as s', function ($join) use ($school) {
                $join->on('s.id','=','ps.subject_id')->where('s.school_id','=',$school);
            })
            ->where('ps.school_id',$school)->orderBy('ps.sort_order')->orderBy('ps.id')
            ->get(['ps.id','ps.curriculum_plan_id','ps.subject_id','ps.grade_label','ps.is_official','ps.affects_average','s.name as subject_name']);
        $teachers = DB::table('academic_teacher_profiles as t')
            ->join('users as u', function ($join) use ($school) {
                $join->on('u.id','=','t.user_id')->where('u.school_id','=',$school);
            })
            ->where('t.school_id',$school)->orderBy('u.name')
            ->get(['t.id','t.user_id','u.name','u.email','u.username','t.employee_code','t.status']);
        $summary = [
            'plans' => $plans->count(),
            'subjects' => $subjects->where('status', 'active')->count(),
            'teachers' => $teachers->where('status', 'active')->count(),
            'assignments' => DB::table('academic_teaching_assignments')
                ->where('school_id', $school)
                ->where('status', 'active')
                ->count(),
            'groups' => DB::table('school_groups')
                ->where('school_id', $school)
                ->where('status', 'active')
                ->count(),
        ];
        $canManageTeachers = in_array((string) $request->user()?->role, ['school_admin', 'director'], true);
        return response()->json(compact('cycles','levels','plans','subjects','links','teachers','summary','canManageTeachers'));
    }

    /**
     * Registra materia institucional y, opcionalmente, la agrega a un plan
     * de la misma escuela. Ambas escrituras son atómicas.
     */
    public function storeSubject(Request $request): JsonResponse
    {
        $school = $this->school($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'kind' => ['required', Rule::in(['custom', 'official'])],
            'curriculum_plan_id' => ['nullable', 'integer', 'min:1'],
            'grade_label' => ['nullable', 'string', 'max:100'],
            'is_official' => ['sometimes', 'boolean'],
            'affects_average' => ['sometimes', 'boolean'],
        ]);

        if (!empty($data['curriculum_plan_id'])) {
            $belongs = DB::table('academic_curriculum_plans')
                ->where('school_id', $school)
                ->where('id', $data['curriculum_plan_id'])
                ->exists();
            if (!$belongs) {
                throw ValidationException::withMessages([
                    'curriculum_plan_id' => 'El plan no pertenece a esta escuela.',
                ]);
            }
        }

        $result = DB::transaction(function () use ($school, $data): array {
            $code = strtolower(trim($data['code']));
            $existing = DB::table('academic_subjects')
                ->where('school_id', $school)
                ->where('code', $code)
                ->first(['id']);

            if ($existing) {
                throw ValidationException::withMessages([
                    'code' => 'Este código ya existe en el catálogo escolar. Selecciona la materia existente desde Vincular materia.',
                ]);
            }

            $subjectId = (int) DB::table('academic_subjects')->insertGetId([
                'school_id' => $school,
                'name' => trim($data['name']),
                'code' => $code,
                'kind' => $data['kind'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $linked = false;
            $planId = (int) ($data['curriculum_plan_id'] ?? 0);
            if ($planId > 0) {
                $sortOrder = (int) DB::table('academic_curriculum_plan_subjects')
                    ->where('school_id', $school)
                    ->where('curriculum_plan_id', $planId)
                    ->max('sort_order');

                DB::table('academic_curriculum_plan_subjects')->insert([
                    'school_id' => $school,
                    'curriculum_plan_id' => $planId,
                    'subject_id' => $subjectId,
                    'grade_label' => $data['grade_label'] ?? null,
                    'is_official' => (bool) ($data['is_official'] ?? false),
                    'affects_average' => (bool) ($data['affects_average'] ?? false),
                    'sort_order' => $sortOrder + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $linked = true;
            }

$status = DB::table('academic_curriculum_plans')
    ->where('school_id', $school)
    ->where('id', $data['curriculum_plan_id'])
    ->value('status');

abort_unless(
    $status === 'draft',
    409,
    'No puedes agregar materias directamente a un plan cerrado.'
);

            return ['id' => $subjectId, 'linked' => $linked];
        }, 3);

        return response()->json([
            ...$result,
            'message' => $result['linked']
                ? 'Materia registrada y vinculada al plan.'
                : 'Materia registrada en el catálogo de esta escuela.',
        ], 201);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $school = $this->school($request);
        $data = $request->validate([
            'name'=>['required','string','max:160'],
            'academic_cycle_id'=>['required','integer'],
            'academic_level_id'=>['required','integer'],
            'framework_code'=>['nullable','string','max:80'],
        ]);
        abort_unless(DB::table('academic_cycles')->where('school_id',$school)
            ->where('id',$data['academic_cycle_id'])->exists(),422,'Ciclo ajeno a esta escuela.');
        abort_unless(DB::table('academic_levels')->where('school_id',$school)
            ->where('id',$data['academic_level_id'])->exists(),422,'Nivel ajeno a esta escuela.');
        $id = DB::table('academic_curriculum_plans')->insertGetId([
            'school_id'=>$school,'academic_cycle_id'=>$data['academic_cycle_id'],
            'academic_level_id'=>$data['academic_level_id'],'name'=>trim($data['name']),
            'framework_code'=>$data['framework_code'] ?: null,
            'version'=>1,'status'=>'draft','created_at'=>now(),'updated_at'=>now(),
        ]);
        return response()->json(['id'=>$id,'message'=>'Plan creado en borrador.'],201);
    }

    public function attachSubject(Request $request, int $plan): JsonResponse
    {
        $school = $this->school($request);
        $data = $request->validate([
            'subject_id'=>['required','integer'],
            'grade_label'=>['nullable','string','max:100'],
            'is_official'=>['required','boolean'],
            'affects_average'=>['required','boolean'],
        ]);
        abort_unless(DB::table('academic_curriculum_plans')->where('school_id',$school)
            ->where('id',$plan)->exists(),404);
        abort_unless(DB::table('academic_subjects')->where('school_id',$school)
            ->where('id',$data['subject_id'])->where('status','active')->exists(),422,'Materia no disponible.');
        $exists = DB::table('academic_curriculum_plan_subjects')
            ->where('school_id',$school)->where('curriculum_plan_id',$plan)
            ->where('subject_id',$data['subject_id'])->where('grade_label',$data['grade_label'] ?? null)
            ->exists();
        if ($exists) throw ValidationException::withMessages(['subject_id'=>'La materia ya pertenece al plan y grado indicados.']);
        $sort = (int) DB::table('academic_curriculum_plan_subjects')->where('school_id',$school)
            ->where('curriculum_plan_id',$plan)->max('sort_order');
        $id = DB::table('academic_curriculum_plan_subjects')->insertGetId([
            'school_id'=>$school,'curriculum_plan_id'=>$plan,'subject_id'=>$data['subject_id'],
            'grade_label'=>$data['grade_label'] ?? null,'is_official'=>$data['is_official'],
            'affects_average'=>$data['affects_average'],'sort_order'=>$sort+1,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $status = DB::table('academic_curriculum_plans')
    ->where('school_id', $school)
    ->where('id', $plan)
    ->value('status');

abort_unless(
    $status === 'draft',
    409,
    'Este plan está cerrado. Crea una nueva versión para modificarlo.'
);
        return response()->json(['id'=>$id,'message'=>'Materia vinculada al plan.'],201);
    }

    /** Alta con credenciales entregadas por Dirección, sin correo obligatorio. */
    public function storeTeacher(Request $request): JsonResponse
    {
        $school = $this->school($request);
        $this->ensureDirector($request);
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'username' => ['required','string','min:4','max:60','regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users','username')],
            'email' => ['nullable','email','max:255',Rule::unique('users','email')],
            'employee_code' => ['nullable','string','max:60'],
            'password' => ['required','string','min:12','max:128','confirmed'],
        ]);
        $username = mb_strtolower(trim($data['username']));
        $email = !empty($data['email']) ? mb_strtolower(trim($data['email'])) : null;
        $code = trim($data['employee_code'] ?? '');
        if (DB::table('users')->whereRaw('LOWER(username) = ?', [$username])->exists()) {
            throw ValidationException::withMessages(['username'=>'Ese usuario ya está registrado.']);
        }
        if ($code !== '' && DB::table('academic_teacher_profiles')->where('school_id',$school)
            ->where('employee_code',$code)->exists()) {
            throw ValidationException::withMessages(['employee_code'=>'La clave de empleado ya existe.']);
        }
        $result = DB::transaction(function () use ($school,$data,$username,$email,$code): array {
            // Compatibilidad con users.email NOT NULL. Dominio .invalid: no enviable.
            $internalEmail = 'teacher-'.Str::lower((string) Str::uuid()).'@accounts.invalid';
            $userId = (int) DB::table('users')->insertGetId([
                'school_id'=>$school,
                'name'=>trim($data['name']),
                'username'=>$username,
                'email'=>$email ?? $internalEmail,
                'phone'=>null,
                'password'=>Hash::make($data['password']),
                'role'=>'teacher',
                'status'=>'active',
                'must_change_password'=>true,
                'remember_token'=>Str::random(60),
                'created_at'=>now(), 'updated_at'=>now(),
            ]);
            $profileId = (int) DB::table('academic_teacher_profiles')->insertGetId([
                'school_id'=>$school, 'user_id'=>$userId,
                'employee_code'=>$code !== '' ? $code : null,
                'status'=>'active', 'created_at'=>now(), 'updated_at'=>now(),
            ]);
            return ['user_id'=>$userId,'profile_id'=>$profileId];
        },3);
        return response()->json([...$result,'message'=>'Docente registrado. Entrega usuario y contraseña inicial de forma privada.'],201);
    }

    /** Reestablecer credenciales existentes sin duplicar perfil ni usuario. */
    public function resetTeacherPassword(Request $request, int $teacher): JsonResponse
    {
        $school = $this->school($request);
        $this->ensureDirector($request);
        $data = $request->validate([
            'username'=>['nullable','string','min:4','max:60','regex:/^[a-zA-Z0-9._-]+$/'],
            'password'=>['required','string','min:12','max:128','confirmed'],
        ]);
        $profile = DB::table('academic_teacher_profiles as t')
            ->join('users as u','u.id','=','t.user_id')
            ->where('t.id',$teacher)->where('t.school_id',$school)
            ->where('u.school_id',$school)->where('u.role','teacher')
            ->first(['t.id','u.id as user_id','u.username']);
        abort_unless($profile,404,'Docente no encontrado en esta escuela.');
        $username = !empty($data['username']) ? mb_strtolower(trim($data['username'])) : null;
        if (!$profile->username && !$username) {
            throw ValidationException::withMessages(['username'=>'Asigna un usuario de acceso al docente existente.']);
        }
        if ($username && DB::table('users')->where('id','!=',$profile->user_id)
            ->whereRaw('LOWER(username) = ?',[$username])->exists()) {
            throw ValidationException::withMessages(['username'=>'El usuario ya existe.']);
        }
        DB::transaction(function () use ($profile,$data,$username): void {
            DB::table('users')->where('id',$profile->user_id)->update([
                'username'=>$username ?: $profile->username,
                'password'=>Hash::make($data['password']),
                'must_change_password'=>true,
                'password_changed_at'=>null,
                'remember_token'=>Str::random(60),
                'updated_at'=>now(),
            ]);
            // Revoca tokens API emitidos a este usuario (Sanctum).
            DB::table('personal_access_tokens')
                ->where('tokenable_type', \App\Models\User::class)
                ->where('tokenable_id',$profile->user_id)->delete();
        },3);
        return response()->json(['message'=>'Credenciales actualizadas. El docente deberá cambiar su contraseña al ingresar.']);
    }

    private function ensureDirector(Request $request): void
    {
        abort_unless(in_array((string) $request->user()?->role,['school_admin','director'],true),403);
    }
}
