<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Family\FamilySponsorshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class GuardianController extends Controller
{
    public function __construct(
        private readonly FamilySponsorshipService $familySponsorshipService,
    ) {
    }

    public function index(Request $request): View
    {
        $schoolId = $this->schoolId($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => trim((string) $request->query('status', '')),
            'account' => trim((string) $request->query('account', '')),
            'qr' => trim((string) $request->query('qr', '')),
        ];

        $familyTrialActive = DB::table('school_licenses')
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '>=', now()->toDateString())
            ->exists();

        $guardians = DB::table('guardians as g')
            ->leftJoin('users as u', 'u.id', '=', 'g.user_id')
            ->leftJoin('guardian_memberships as gm', function ($join) use ($schoolId): void {
                $join->on('gm.guardian_id', '=', 'g.id')
                    ->where('gm.school_id', '=', $schoolId);
            })
            ->where('g.school_id', $schoolId)
            ->whereExists(function ($query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('student_guardians as sg_primary_filter')
                    ->whereColumn('sg_primary_filter.guardian_id', 'g.id')
                    ->where('sg_primary_filter.status', 'active')
                    ->where('sg_primary_filter.is_primary', true);
            })
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('g.first_name', 'like', "%{$search}%")
                        ->orWhere('g.last_name', 'like', "%{$search}%")
                        ->orWhere('g.email', 'like', "%{$search}%")
                        ->orWhere('g.phone', 'like', "%{$search}%")
                        ->orWhere('u.email', 'like', "%{$search}%")
                        ->orWhereExists(function ($studentQuery) use ($search): void {
                            $studentQuery
                                ->select(DB::raw(1))
                                ->from('student_guardians as sg_search')
                                ->join('students as s_search', 's_search.id', '=', 'sg_search.student_id')
                                ->whereColumn('sg_search.guardian_id', 'g.id')
                                ->where('sg_search.status', 'active')
                                ->where('sg_search.is_primary', true)
                                ->where(function ($nameQuery) use ($search): void {
                                    $nameQuery
                                        ->where('s_search.first_name', 'like', "%{$search}%")
                                        ->orWhere('s_search.last_name', 'like', "%{$search}%")
                                        ->orWhere('s_search.student_code', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when(
                in_array($filters['status'], ['active', 'inactive', 'blocked'], true),
                fn ($query) => $query->where('g.status', $filters['status'])
            )
            ->when($filters['account'] === 'with', fn ($query) => $query->whereNotNull('g.user_id'))
            ->when($filters['account'] === 'without', fn ($query) => $query->whereNull('g.user_id'))
            ->when($filters['qr'] === 'active', function ($query): void {
                $query->whereExists(function ($subquery): void {
                    $subquery
                        ->select(DB::raw(1))
                        ->from('guardian_credentials as gc_filter')
                        ->whereColumn('gc_filter.guardian_id', 'g.id')
                        ->where('gc_filter.status', 'active')
                        ->where(function ($expiration): void {
                            $expiration
                                ->whereNull('gc_filter.expires_at')
                                ->orWhere('gc_filter.expires_at', '>', now());
                        });
                });
            })
            ->when($filters['qr'] === 'without', function ($query): void {
                $query->whereNotExists(function ($subquery): void {
                    $subquery
                        ->select(DB::raw(1))
                        ->from('guardian_credentials as gc_filter')
                        ->whereColumn('gc_filter.guardian_id', 'g.id')
                        ->where('gc_filter.status', 'active')
                        ->where(function ($expiration): void {
                            $expiration
                                ->whereNull('gc_filter.expires_at')
                                ->orWhere('gc_filter.expires_at', '>', now());
                        });
                });
            })
            ->select([
                'g.*',
                'u.email as access_username',
                'u.status as user_status',
                'gm.status as membership_status',
                'gm.source as membership_source',
                'gm.starts_at as membership_starts_at',
                'gm.expires_at as membership_expires_at',
                DB::raw('(
                    SELECT COUNT(*)
                    FROM student_guardians sg_primary
                    WHERE sg_primary.guardian_id = g.id
                      AND sg_primary.status = "active"
                      AND sg_primary.is_primary = 1
                ) as primary_students_count'),
                DB::raw('(
                    SELECT COUNT(DISTINCT sg_member.guardian_id)
                    FROM student_guardians sg_owner
                    INNER JOIN student_guardians sg_member
                        ON sg_member.student_id = sg_owner.student_id
                       AND sg_member.status = "active"
                       AND sg_member.guardian_id <> g.id
                    WHERE sg_owner.guardian_id = g.id
                      AND sg_owner.status = "active"
                      AND sg_owner.is_primary = 1
                ) as family_members_count'),
                DB::raw('(
                    SELECT GROUP_CONCAT(
                        DISTINCT CONCAT(s.first_name, " ", s.last_name)
                        ORDER BY s.last_name, s.first_name
                        SEPARATOR " · "
                    )
                    FROM student_guardians sg_names
                    INNER JOIN students s ON s.id = sg_names.student_id
                    WHERE sg_names.guardian_id = g.id
                      AND sg_names.status = "active"
                      AND sg_names.is_primary = 1
                ) as primary_students_names'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM guardian_credentials gc
                    WHERE gc.guardian_id = g.id
                      AND gc.status = "active"
                      AND (gc.expires_at IS NULL OR gc.expires_at > NOW())
                ) as active_credentials_count'),
                DB::raw('(
                    SELECT MAX(al.scanned_at)
                    FROM access_logs al
                    WHERE al.guardian_id = g.id
                ) as last_access_at'),
            ])
            ->orderBy('g.last_name')
            ->orderBy('g.first_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.guardians.index', [
            'guardians' => $guardians,
            'filters' => $filters,
            'search' => $filters['search'],
            'familyTrialActive' => $familyTrialActive,
        ]);
    }

    public function create(Request $request): View
    {
        $schoolId = $this->schoolId($request);
        $activeCycle = $this->activeCycle($schoolId);

        $availableStudents = DB::table('students as s')
            ->leftJoin('student_enrollments as se', function ($join) use ($schoolId, $activeCycle): void {
                $join->on('se.student_id', '=', 's.id')
                    ->where('se.school_id', '=', $schoolId);

                if ($activeCycle) {
                    $join->where('se.academic_cycle_id', '=', $activeCycle->id);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('school_groups as gr', 'gr.id', '=', 'se.school_group_id')
            ->where('s.school_id', $schoolId)
            ->where('s.status', 'active')
            ->whereNotExists(function ($query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('student_guardians as primary_sg')
                    ->whereColumn('primary_sg.student_id', 's.id')
                    ->where('primary_sg.status', 'active')
                    ->where('primary_sg.is_primary', true);
            })
            ->select([
                's.id',
                's.first_name',
                's.last_name',
                's.student_code',
                'gr.name as group_name',
            ])
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        $availableGuardianCandidates = DB::table('guardians as candidate')
            ->leftJoin('users as candidate_user', 'candidate_user.id', '=', 'candidate.user_id')
            ->where('candidate.school_id', $schoolId)
            ->where('candidate.status', 'active')
            ->whereNotExists(function ($query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('student_guardians as candidate_primary')
                    ->whereColumn(
                        'candidate_primary.guardian_id',
                        'candidate.id'
                    )
                    ->where('candidate_primary.status', 'active')
                    ->where('candidate_primary.is_primary', true);
            })
            ->select([
                'candidate.id',
                'candidate.first_name',
                'candidate.last_name',
                'candidate.phone',
                'candidate.email',
                'candidate.user_id',
                'candidate_user.email as access_username',
            ])
            ->orderBy('candidate.last_name')
            ->orderBy('candidate.first_name')
            ->get();

        return view('admin.guardians.create', compact(
            'availableStudents',
            'availableGuardianCandidates',
            'activeCycle'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'principal_mode' => [
                'required',
                Rule::in(['new', 'existing']),
            ],
            'existing_guardian_id' => [
                'nullable',
                'required_if:principal_mode,existing',
                'integer',
                Rule::exists('guardians', 'id')->where(
                    fn ($query) => $query
                        ->where('school_id', $schoolId)
                        ->where('status', 'active')
                ),
            ],
            'first_name' => [
                'nullable',
                'required_if:principal_mode,new',
                'string',
                'max:100',
            ],
            'last_name' => [
                'nullable',
                'required_if:principal_mode,new',
                'string',
                'max:150',
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('guardians', 'email')->where(
                    'school_id',
                    $schoolId
                ),
            ],
            'status' => [
                'nullable',
                'required_if:principal_mode,new',
                Rule::in(['active', 'inactive', 'blocked']),
            ],
            'create_user' => ['nullable', 'boolean'],
            'relationship' => [
                'required',
                Rule::in(['madre', 'padre', 'tutor']),
            ],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('students', 'id')->where(
                    fn ($query) => $query
                        ->where('school_id', $schoolId)
                        ->where('status', 'active')
                ),
            ],
        ], [
            'student_ids.required' => (
                'Selecciona al menos un alumno para formar el núcleo familiar.'
            ),
            'student_ids.min' => (
                'Selecciona al menos un alumno para formar el núcleo familiar.'
            ),
            'existing_guardian_id.required_if' => (
                'Selecciona la persona existente que será responsable principal.'
            ),
        ]);

        $createUser = $request->boolean('create_user');
        $studentIds = collect($data['student_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        try {
            [$guardianId, $credentials] = DB::transaction(
                function () use (
                    $data,
                    $schoolId,
                    $createUser,
                    $studentIds
                ): array {
                    $students = DB::table('students')
                        ->where('school_id', $schoolId)
                        ->where('status', 'active')
                        ->whereIn('id', $studentIds)
                        ->lockForUpdate()
                        ->get(['id', 'first_name', 'last_name']);

                    if ($students->count() !== $studentIds->count()) {
                        throw ValidationException::withMessages([
                            'student_ids' => (
                                'Uno de los alumnos seleccionados ya no está disponible.'
                            ),
                        ]);
                    }

                    DB::table('student_guardians')
                        ->whereIn('student_id', $studentIds)
                        ->lockForUpdate()
                        ->get();

                    $existingPrimary = DB::table('student_guardians as sg')
                        ->join('guardians as g', 'g.id', '=', 'sg.guardian_id')
                        ->whereIn('sg.student_id', $studentIds)
                        ->where('sg.status', 'active')
                        ->where('sg.is_primary', true)
                        ->select([
                            'sg.student_id',
                            'g.first_name',
                            'g.last_name',
                        ])
                        ->first();

                    if ($existingPrimary) {
                        $student = $students->firstWhere(
                            'id',
                            $existingPrimary->student_id
                        );

                        throw ValidationException::withMessages([
                            'student_ids' => sprintf(
                                '%s %s ya tiene como responsable principal a '
                                .'%s %s. Debes transferirlo de forma explícita '
                                .'desde su núcleo actual.',
                                $student?->first_name ?? 'El alumno',
                                $student?->last_name ?? '',
                                $existingPrimary->first_name,
                                $existingPrimary->last_name
                            ),
                        ]);
                    }

                    if ($data['principal_mode'] === 'existing') {
                        $guardianRow = DB::table('guardians')
                            ->where('school_id', $schoolId)
                            ->where('id', $data['existing_guardian_id'])
                            ->where('status', 'active')
                            ->lockForUpdate()
                            ->firstOrFail();

                        $alreadyPrincipal = DB::table('student_guardians')
                            ->where('guardian_id', $guardianRow->id)
                            ->where('status', 'active')
                            ->where('is_primary', true)
                            ->exists();

                        if ($alreadyPrincipal) {
                            throw ValidationException::withMessages([
                                'existing_guardian_id' => (
                                    'La persona seleccionada ya tiene un núcleo. '
                                    .'Agrega los alumnos desde su expediente actual.'
                                ),
                            ]);
                        }

                        $guardianId = (int) $guardianRow->id;
                    } else {
                        $guardianId = DB::table('guardians')->insertGetId([
                            'school_id' => $schoolId,
                            'user_id' => null,
                            'first_name' => trim($data['first_name']),
                            'last_name' => trim($data['last_name']),
                            'phone' => $this->nullableTrim($data['phone'] ?? null),
                            'email' => $this->nullableTrim($data['email'] ?? null),
                            'photo_url' => null,
                            'status' => $data['status'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $guardianRow = DB::table('guardians')
                            ->where('id', $guardianId)
                            ->firstOrFail();
                    }

                    foreach ($studentIds as $studentId) {
                        $existingRelation = DB::table('student_guardians')
                            ->where('student_id', $studentId)
                            ->where('guardian_id', $guardianId)
                            ->first();

                        $values = [
                            'relationship' => $data['relationship'],
                            'is_primary' => true,
                            'can_receive_notifications' => true,
                            'can_view_attendance' => true,
                            'can_authorize_exit' => true,
                            'can_drop_off' => true,
                            'can_pick_up' => true,
                            'valid_from' => null,
                            'valid_until' => null,
                            'status' => 'active',
                            'updated_at' => now(),
                        ];

                        if ($existingRelation) {
                            DB::table('student_guardians')
                                ->where('id', $existingRelation->id)
                                ->update($values);
                        } else {
                            DB::table('student_guardians')->insert([
                                ...$values,
                                'student_id' => $studentId,
                                'guardian_id' => $guardianId,
                                'created_at' => now(),
                            ]);
                        }
                    }

                    $credentials = null;

                    if ($createUser && empty($guardianRow->user_id)) {
                        $credentials = $this->createGuardianUser(
                            schoolId: $schoolId,
                            guardianId: $guardianId,
                            firstName: $guardianRow->first_name,
                            lastName: $guardianRow->last_name,
                            phone: $guardianRow->phone,
                            guardianStatus: $guardianRow->status
                        );
                    }

                    return [$guardianId, $credentials];
                },
                3
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'guardian' => app()->environment('local')
                        ? $exception->getMessage()
                        : 'No se pudo crear el núcleo familiar.',
                ]);
        }

        $redirect = redirect()
            ->route('admin.guardians.show', $guardianId)
            ->with('success', 'Núcleo familiar creado correctamente.');

        if ($credentials) {
            $redirect->with('generated_credentials', $credentials);
        }

        return $redirect;
    }

    public function show(Request $request, int $guardian): View
    {
        $schoolId = $this->schoolId($request);
        $activeCycle = $this->activeCycle($schoolId);

        $guardianRow = $this->guardianForSchool($schoolId, $guardian);

        $linkedStudents = DB::table('student_guardians as sg')
            ->join('students as s', function ($join) use ($schoolId): void {
                $join->on('s.id', '=', 'sg.student_id')
                    ->where('s.school_id', '=', $schoolId);
            })
            ->leftJoin('student_enrollments as se', function ($join) use ($schoolId, $activeCycle): void {
                $join->on('se.student_id', '=', 's.id')
                    ->where('se.school_id', '=', $schoolId);

                if ($activeCycle) {
                    $join->where('se.academic_cycle_id', '=', $activeCycle->id);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('school_groups as gr', 'gr.id', '=', 'se.school_group_id')
            ->leftJoin('academic_levels as al', 'al.id', '=', 'gr.academic_level_id')
            ->where('sg.guardian_id', $guardian)
            ->where('sg.status', 'active')
            ->select([
                'sg.*',
                's.first_name',
                's.last_name',
                's.student_code',
                's.photo_url as student_photo_url',
                's.status as student_status',
                'se.id as enrollment_id',
                'se.status as enrollment_status',
                'gr.name as group_name',
                'al.name as level_name',
                DB::raw('(
                    SELECT primary_link.guardian_id
                    FROM student_guardians primary_link
                    WHERE primary_link.student_id = sg.student_id
                      AND primary_link.status = "active"
                      AND primary_link.is_primary = 1
                    LIMIT 1
                ) as primary_guardian_id'),
                DB::raw('(
                    SELECT CONCAT(
                        primary_guardian.first_name,
                        " ",
                        primary_guardian.last_name
                    )
                    FROM student_guardians primary_link
                    INNER JOIN guardians primary_guardian
                        ON primary_guardian.id = primary_link.guardian_id
                    WHERE primary_link.student_id = sg.student_id
                      AND primary_link.status = "active"
                      AND primary_link.is_primary = 1
                    LIMIT 1
                ) as primary_guardian_name'),
            ])
            ->orderByDesc('sg.is_primary')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        $availableStudents = DB::table('students as s')
            ->leftJoin('student_enrollments as se', function ($join) use ($schoolId, $activeCycle): void {
                $join->on('se.student_id', '=', 's.id')
                    ->where('se.school_id', '=', $schoolId);

                if ($activeCycle) {
                    $join->where('se.academic_cycle_id', '=', $activeCycle->id);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('school_groups as gr', 'gr.id', '=', 'se.school_group_id')
            ->where('s.school_id', $schoolId)
            ->where('s.status', 'active')
            ->whereNotExists(function ($query) use ($guardian): void {
                $query->select(DB::raw(1))
                    ->from('student_guardians as existing_sg')
                    ->whereColumn('existing_sg.student_id', 's.id')
                    ->where('existing_sg.guardian_id', $guardian)
                    ->where('existing_sg.status', 'active');
            })
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('student_guardians as primary_sg')
                    ->whereColumn('primary_sg.student_id', 's.id')
                    ->where('primary_sg.status', 'active')
                    ->where('primary_sg.is_primary', 1);
            })
            ->select([
                's.id',
                's.first_name',
                's.last_name',
                's.student_code',
                'se.status as enrollment_status',
                'gr.name as group_name',
                DB::raw('(
                    SELECT COUNT(*)
                    FROM student_guardians all_sg
                    WHERE all_sg.student_id = s.id
                      AND all_sg.status = "active"
                ) as guardians_count'),
            ])
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        $credentials = DB::table('guardian_credentials as gc')
            ->leftJoin('users as creator', 'creator.id', '=', 'gc.created_by')
            ->where('gc.school_id', $schoolId)
            ->where('gc.guardian_id', $guardian)
            ->select([
                'gc.*',
                'creator.name as created_by_name',
            ])
            ->orderByRaw("
                CASE gc.status
                    WHEN 'active' THEN 1
                    WHEN 'revoked' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('gc.id')
            ->get();

        $activeCredential = $credentials
            ->first(function ($credential): bool {
                if ($credential->status !== 'active') {
                    return false;
                }

                return $credential->expires_at === null
                    || now()->lt($credential->expires_at);
            });

        $accessLogs = DB::table('access_logs as log')
            ->leftJoin(
                'students as s',
                's.id',
                '=',
                'log.student_id'
            )
            ->leftJoin(
                'school_groups as gr',
                'gr.id',
                '=',
                'log.school_group_id'
            )
            ->leftJoin(
                'access_devices as d',
                'd.id',
                '=',
                'log.access_device_id'
            )
            ->leftJoin(
                'areas as a',
                'a.id',
                '=',
                'log.area_id'
            )
            ->leftJoin(
                'users as u',
                'u.id',
                '=',
                'log.user_id'
            )
            ->leftJoin(
                'guardian_credentials as gc',
                'gc.id',
                '=',
                'log.guardian_credential_id'
            )
            ->where('log.school_id', $schoolId)
            ->where('log.guardian_id', $guardian)
            ->select([
                'log.*',
                's.first_name as student_first_name',
                's.last_name as student_last_name',
                's.student_code',
                'gr.name as group_name',
                'd.name as device_name',
                'a.name as area_name',
                'u.name as performed_by_name',
                'u.role as performed_by_role',
                'gc.public_code as guardian_public_code',
            ])
            ->orderByDesc('log.scanned_at')
            ->orderByDesc('log.id')
            ->paginate(
                perPage: 15,
                columns: ['*'],
                pageName: 'movements_page'
            )
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | PaseLista Family
        |--------------------------------------------------------------------------
        |
        | La cuenta del tutor y la membresía Family son conceptos distintos.
        | El trial institucional concede acceso sin crear una membresía.
        |
        */

        $familySponsorshipSummary = $this
            ->familySponsorshipService
            ->summary($schoolId);

        $familyMembership = DB::table(
            'guardian_memberships'
        )
            ->where('school_id', $schoolId)
            ->where('guardian_id', $guardian)
            ->first();

        $familySponsorshipAssignment = null;

        if ($activeCycle !== null) {
            $familySponsorshipAssignment = DB::table(
                'school_family_sponsorship_assignments as assignment'
            )
                ->leftJoin(
                    'users as assigned_by',
                    'assigned_by.id',
                    '=',
                    'assignment.assigned_by_user_id'
                )
                ->leftJoin(
                    'users as released_by',
                    'released_by.id',
                    '=',
                    'assignment.released_by_user_id'
                )
                ->where(
                    'assignment.school_id',
                    $schoolId
                )
                ->where(
                    'assignment.academic_cycle_id',
                    $activeCycle->id
                )
                ->where(
                    'assignment.guardian_id',
                    $guardian
                )
                ->select([
                    'assignment.*',
                    'assigned_by.name as assigned_by_name',
                    'released_by.name as released_by_name',
                ])
                ->latest('assignment.id')
                ->first();
        }

        $familyPrimaryStudents = $linkedStudents
            ->filter(
                fn (object $student): bool => (
                    (bool) $student->is_primary
                )
            )
            ->values();


        $familyPrimaryStudentIds = $familyPrimaryStudents
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $familyMemberRows = collect();

        if ($familyPrimaryStudentIds->isNotEmpty()) {
            $familyMemberRows = DB::table('student_guardians as sg')
                ->join('guardians as member', function ($join) use ($schoolId): void {
                    $join->on('member.id', '=', 'sg.guardian_id')
                        ->where('member.school_id', '=', $schoolId);
                })
                ->leftJoin('users as member_user', 'member_user.id', '=', 'member.user_id')
                ->join('students as s', 's.id', '=', 'sg.student_id')
                ->whereIn('sg.student_id', $familyPrimaryStudentIds)
                ->where('sg.guardian_id', '!=', $guardian)
                ->where('sg.status', 'active')
                ->select([
                    'member.id as guardian_id',
                    'member.first_name',
                    'member.last_name',
                    'member.phone',
                    'member.email',
                    'member.photo_url',
                    'member.status as guardian_status',
                    'member.user_id',
                    'member_user.email as access_username',
                    'member_user.status as user_status',
                    'sg.student_id',
                    'sg.relationship',
                    'sg.can_receive_notifications',
                    'sg.can_view_attendance',
                    'sg.can_drop_off',
                    'sg.can_pick_up',
                    'sg.can_authorize_exit',
                    'sg.valid_from',
                    'sg.valid_until',
                    's.first_name as student_first_name',
                    's.last_name as student_last_name',
                    's.student_code',
                ])
                ->orderBy('member.last_name')
                ->orderBy('member.first_name')
                ->orderBy('s.last_name')
                ->get();
        }

        $familyMembers = $familyMemberRows
            ->groupBy('guardian_id')
            ->map(function ($rows): object {
                $first = $rows->first();

                return (object) [
                    'guardian_id' => (int) $first->guardian_id,
                    'first_name' => $first->first_name,
                    'last_name' => $first->last_name,
                    'phone' => $first->phone,
                    'email' => $first->email,
                    'photo_url' => $first->photo_url,
                    'guardian_status' => $first->guardian_status,
                    'user_id' => $first->user_id,
                    'access_username' => $first->access_username,
                    'user_status' => $first->user_status,
                    'students' => $rows->values(),
                ];
            })
            ->values();

        $availableFamilyGuardians = DB::table('guardians as candidate')
            ->leftJoin('users as candidate_user', 'candidate_user.id', '=', 'candidate.user_id')
            ->where('candidate.school_id', $schoolId)
            ->where('candidate.id', '!=', $guardian)
            ->where('candidate.status', 'active')
            ->select([
                'candidate.id',
                'candidate.first_name',
                'candidate.last_name',
                'candidate.phone',
                'candidate.email',
                'candidate_user.email as access_username',
            ])
            ->orderBy('candidate.last_name')
            ->orderBy('candidate.first_name')
            ->get();

        $availablePrincipalGuardians = DB::table('guardians as candidate')
            ->where('candidate.school_id', $schoolId)
            ->where('candidate.id', '!=', $guardian)
            ->where('candidate.status', 'active')
            ->whereExists(function ($query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('student_guardians as candidate_primary')
                    ->whereColumn(
                        'candidate_primary.guardian_id',
                        'candidate.id'
                    )
                    ->where('candidate_primary.status', 'active')
                    ->where('candidate_primary.is_primary', true);
            })
            ->select([
                'candidate.id',
                'candidate.first_name',
                'candidate.last_name',
                DB::raw('(
                    SELECT COUNT(*)
                    FROM student_guardians candidate_primary_count
                    WHERE candidate_primary_count.guardian_id = candidate.id
                      AND candidate_primary_count.status = "active"
                      AND candidate_primary_count.is_primary = 1
                ) as primary_students_count'),
            ])
            ->orderBy('candidate.last_name')
            ->orderBy('candidate.first_name')
            ->get();

        $familyIsPrimaryGuardian = (
            $familyPrimaryStudents->isNotEmpty()
        );

        $familyTrialActive = (bool) data_get(
            $familySponsorshipSummary,
            'license.trial_active',
            false
        );

        $familyMembershipUsable = false;

        if (
            $familyMembership !== null
            && $familyMembership->status === 'active'
            && $familyMembership->revoked_at === null
        ) {
            $today = now()->startOfDay();

            $membershipStarted = (
                $familyMembership->starts_at === null
                || \Illuminate\Support\Carbon::parse(
                    $familyMembership->starts_at
                )
                    ->startOfDay()
                    ->lte($today)
            );

            $membershipNotExpired = (
                $familyMembership->expires_at === null
                || \Illuminate\Support\Carbon::parse(
                    $familyMembership->expires_at
                )
                    ->endOfDay()
                    ->gte(now())
            );

            $familyMembershipUsable = (
                $membershipStarted
                && $membershipNotExpired
            );
        }

        $familyAssignmentBlocksSponsorship = (
            $familySponsorshipAssignment !== null
            && in_array(
                $familySponsorshipAssignment->status,
                [
                    'assigned',
                    'released',
                ],
                true
            )
        );

        $familyCanSponsor = (
            ! $familyTrialActive
            && $guardianRow->status === 'active'
            && $familyIsPrimaryGuardian
            && ! $familyMembershipUsable
            && ! $familyAssignmentBlocksSponsorship
            && (bool) data_get(
                $familySponsorshipSummary,
                'can_assign',
                false
            )
        );

        $familyStatus = [
            'key' => 'inactive',
            'label' => 'Sin membresía',
            'badge_class' => 'bg-secondary-lt',
            'description' => (
                'La familia todavía no tiene habilitadas '
                .'las funciones digitales.'
            ),
        ];

        if ($familyTrialActive) {
            $familyStatus = [
                'key' => 'school_trial',
                'label' => 'Prueba institucional',
                'badge_class' => 'bg-azure-lt',
                'description' => (
                    'Family tiene acceso completo mientras '
                    .'la prueba de la institución permanezca vigente.'
                ),
            ];
        } elseif ($familyMembershipUsable) {
            $familyStatus = match (
                $familyMembership->source
            ) {
                'school_sponsorship' => [
                    'key' => 'school_sponsorship',
                    'label' => 'Patrocinio escolar',
                    'badge_class' => 'bg-success-lt',
                    'description' => (
                        'La institución asignó uno de sus '
                        .'patrocinios del ciclo escolar.'
                    ),
                ],

                'google_play' => [
                    'key' => 'google_play',
                    'label' => 'Membresía pagada',
                    'badge_class' => 'bg-green-lt',
                    'description' => (
                        'La familia cuenta con una membresía '
                        .'anual adquirida mediante Google Play.'
                    ),
                ],

                'sysadmin_courtesy' => [
                    'key' => 'sysadmin_courtesy',
                    'label' => 'Cortesía Sysadmin',
                    'badge_class' => 'bg-purple-lt',
                    'description' => (
                        'La membresía fue habilitada '
                        .'administrativamente.'
                    ),
                ],

                default => [
                    'key' => 'active',
                    'label' => 'Membresía activa',
                    'badge_class' => 'bg-success-lt',
                    'description' => (
                        'La familia tiene acceso a las '
                        .'funciones digitales.'
                    ),
                ],
            };
        } elseif (
            $familyMembership !== null
            && $familyMembership->status === 'revoked'
        ) {
            $familyStatus = [
                'key' => 'revoked',
                'label' => 'Membresía revocada',
                'badge_class' => 'bg-danger-lt',
                'description' => (
                    'La membresía fue revocada y requiere '
                    .'revisión administrativa.'
                ),
            ];
        } elseif (
            $familyMembership !== null
            && $familyMembership->expires_at !== null
            && \Illuminate\Support\Carbon::parse(
                $familyMembership->expires_at
            )
                ->endOfDay()
                ->lt(now())
        ) {
            $familyStatus = [
                'key' => 'expired',
                'label' => 'Membresía vencida',
                'badge_class' => 'bg-warning-lt',
                'description' => (
                    'La vigencia de la membresía terminó.'
                ),
            ];
        } elseif (! $familyIsPrimaryGuardian) {
            $familyStatus = [
                'key' => 'secondary_guardian',
                'label' => 'Tutor secundario',
                'badge_class' => 'bg-blue-lt',
                'description' => (
                    'Este tutor no compra ni consume patrocinio. '
                    .'Su acceso depende del tutor principal '
                    .'de cada alumno.'
                ),
            ];
        } elseif (
            $familySponsorshipAssignment?->status === 'released'
        ) {
            $familyStatus = [
                'key' => 'requires_review',
                'label' => 'Requiere revisión',
                'badge_class' => 'bg-warning-lt',
                'description' => (
                    'El patrocinio fue liberado por Sysadmin. '
                    .'Debe revisarse antes de asignarlo nuevamente.'
                ),
            ];
        } elseif ($familyIsPrimaryGuardian) {
            $familyStatus = [
                'key' => 'payment_required',
                'label' => 'Pendiente de activación',
                'badge_class' => 'bg-warning-lt',
                'description' => (
                    'La familia puede adquirir la membresía '
                    .'anual o recibir un patrocinio escolar.'
                ),
            ];
        }

        $familyMembershipContext = [
            'trial_active' => $familyTrialActive,
            'is_primary_guardian' => $familyIsPrimaryGuardian,
            'primary_students' => $familyPrimaryStudents,
            'membership' => $familyMembership,
            'membership_usable' => $familyMembershipUsable,
            'assignment' => $familySponsorshipAssignment,
            'summary' => $familySponsorshipSummary,
            'can_sponsor' => $familyCanSponsor,
            'status' => $familyStatus,
        ];

        $summary = [
            'students' => $linkedStudents->count(),
            'primary_students' => $linkedStudents->where('is_primary', 1)->count(),
            'can_drop_off' => $linkedStudents->where('can_drop_off', 1)->count(),
            'can_pick_up' => $linkedStudents->where('can_pick_up', 1)->count(),
            'movements' => $accessLogs->total(),
        ];

        return view('admin.guardians.show', compact(
            'guardianRow',
            'linkedStudents',
            'availableStudents',
            'activeCycle',
            'credentials',
            'activeCredential',
            'accessLogs',
            'summary',
            'familyMembershipContext',
            'familyMembers',
            'availableFamilyGuardians',
            'availablePrincipalGuardians'
        ));
    }

    public function edit(Request $request, int $guardian): View
    {
        $schoolId = $this->schoolId($request);

        $guardianRow = $this->guardianForSchool($schoolId, $guardian);

        return view('admin.guardians.edit', compact('guardianRow'));
    }

    public function update(Request $request, int $guardian): RedirectResponse
    {
        $schoolId = $this->schoolId($request);
        $guardianRow = $this->guardianForSchool($schoolId, $guardian);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('guardians', 'email')
                    ->ignore($guardianRow->id)
                    ->where('school_id', $schoolId),
            ],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
        ]);

        DB::transaction(function () use ($data, $schoolId, $guardianRow): void {
            DB::table('guardians')
                ->where('school_id', $schoolId)
                ->where('id', $guardianRow->id)
                ->update([
                    'first_name' => trim($data['first_name']),
                    'last_name' => trim($data['last_name']),
                    'phone' => $this->nullableTrim($data['phone'] ?? null),
                    'email' => $this->nullableTrim($data['email'] ?? null),
                    'status' => $data['status'],
                    'updated_at' => now(),
                ]);

            if ($guardianRow->user_id) {
                $userUpdate = [
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'phone' => $this->nullableTrim($data['phone'] ?? null),
                    'updated_at' => now(),
                ];

                if ($data['status'] !== 'active') {
                    $userUpdate['status'] = 'blocked';
                }

                DB::table('users')
                    ->where('school_id', $schoolId)
                    ->where('id', $guardianRow->user_id)
                    ->update($userUpdate);
            }

            if ($data['status'] !== 'active') {
                DB::table('guardian_credentials')
                    ->where('school_id', $schoolId)
                    ->where('guardian_id', $guardianRow->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                        'revoked_reason' => 'Tutor desactivado o bloqueado desde administración.',
                        'updated_at' => now(),
                    ]);
            }
        }, 3);

        return redirect()
            ->route('admin.guardians.show', $guardian)
            ->with('success', 'Datos del tutor actualizados correctamente.');
    }

    public function uploadPhoto(Request $request, int $guardian): RedirectResponse
    {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=500,min_height=500,max_width=5000,max_height=5000',
            ],
        ]);

        $uploadedPhoto = $data['photo'];
        $storedPath = null;
        $previousPhotoPath = null;
        $credentialCreated = false;

        try {
            DB::transaction(function () use (
                $request,
                $schoolId,
                $guardian,
                $uploadedPhoto,
                &$storedPath,
                &$previousPhotoPath,
                &$credentialCreated
            ): void {
                $guardianRow = DB::table('guardians')
                    ->where('school_id', $schoolId)
                    ->where('id', $guardian)
                    ->lockForUpdate()
                    ->firstOrFail();

                $extension = strtolower($uploadedPhoto->getClientOriginalExtension());

                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $extension = 'jpg';
                }

                $directory = sprintf(
                    'guardians/school_%d/guardian_%d',
                    $schoolId,
                    $guardianRow->id
                );

                $filename = sprintf(
                    'guardian_%d_%s.%s',
                    $guardianRow->id,
                    Str::lower(Str::random(20)),
                    $extension
                );

                $storedPath = $uploadedPhoto->storeAs(
                    $directory,
                    $filename,
                    'public'
                );

                if (! $storedPath) {
                    throw new RuntimeException('No se pudo guardar la fotografía.');
                }

                $previousPhotoPath = $this->normalizeStoragePath($guardianRow->photo_url);

                DB::table('guardians')
                    ->where('school_id', $schoolId)
                    ->where('id', $guardianRow->id)
                    ->update([
                        'photo_url' => '/storage/'.$storedPath,
                        'updated_at' => now(),
                    ]);

                if (
                    $guardianRow->status === 'active'
                    && ! $this->hasActiveCredential($schoolId, (int) $guardianRow->id)
                ) {
                    $this->insertGuardianCredential(
                        schoolId: $schoolId,
                        guardianId: (int) $guardianRow->id,
                        createdBy: (int) $request->user()->id
                    );

                    $credentialCreated = true;
                }
            }, 3);
        } catch (Throwable $exception) {
            if ($storedPath && Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->delete($storedPath);
            }

            report($exception);

            return back()->withErrors([
                'photo' => app()->environment('local')
                    ? $exception->getMessage()
                    : 'No se pudo actualizar la fotografía.',
            ]);
        }

        if (
            $previousPhotoPath
            && $previousPhotoPath !== $storedPath
            && Storage::disk('public')->exists($previousPhotoPath)
        ) {
            Storage::disk('public')->delete($previousPhotoPath);
        }

        return back()->with(
            'success',
            $credentialCreated
                ? 'Fotografía actualizada y credencial QR generada.'
                : 'Fotografía del tutor actualizada correctamente.'
        );
    }

    public function removePhoto(Request $request, int $guardian): RedirectResponse
    {
        $schoolId = $this->schoolId($request);
        $previousPhotoPath = null;

        DB::transaction(function () use (
            $schoolId,
            $guardian,
            &$previousPhotoPath
        ): void {
            $guardianRow = DB::table('guardians')
                ->where('school_id', $schoolId)
                ->where('id', $guardian)
                ->lockForUpdate()
                ->firstOrFail();

            $previousPhotoPath = $this->normalizeStoragePath($guardianRow->photo_url);

            DB::table('guardians')
                ->where('school_id', $schoolId)
                ->where('id', $guardianRow->id)
                ->update([
                    'photo_url' => null,
                    'updated_at' => now(),
                ]);

            DB::table('guardian_credentials')
                ->where('school_id', $schoolId)
                ->where('guardian_id', $guardianRow->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'revoked_reason' => 'Fotografía eliminada desde administración.',
                    'updated_at' => now(),
                ]);
        }, 3);

        if (
            $previousPhotoPath
            && Storage::disk('public')->exists($previousPhotoPath)
        ) {
            Storage::disk('public')->delete($previousPhotoPath);
        }

        return back()->with(
            'success',
            'Fotografía eliminada. Las credenciales QR activas fueron revocadas.'
        );
    }

    public function linkStudent(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        $guardianRow = DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        $isFamilyPrincipal = DB::table('student_guardians')
            ->where('guardian_id', $guardianRow->id)
            ->where('status', 'active')
            ->where('is_primary', true)
            ->exists();

        if (! $isFamilyPrincipal) {
            return back()->withErrors([
                'student' => 'Los alumnos principales solo se agregan desde el expediente de un responsable principal.',
            ]);
        }

        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where('school_id', $schoolId),
            ],
            'relationship' => [
                'required',
                Rule::in(['madre', 'padre', 'tutor']),
            ],
        ]);

        DB::transaction(function () use ($data, $guardianRow): void {
            $student = DB::table('students')
                ->where('id', $data['student_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $existingPrimary = DB::table('student_guardians as sg')
                ->join('guardians as current', 'current.id', '=', 'sg.guardian_id')
                ->where('sg.student_id', $student->id)
                ->where('sg.status', 'active')
                ->where('sg.is_primary', true)
                ->where('sg.guardian_id', '!=', $guardianRow->id)
                ->select([
                    'current.first_name',
                    'current.last_name',
                ])
                ->lockForUpdate()
                ->first();

            if ($existingPrimary) {
                throw ValidationException::withMessages([
                    'student_id' => sprintf(
                        '%s %s ya pertenece al núcleo principal de %s %s. No se cambió el responsable.',
                        $student->first_name,
                        $student->last_name,
                        $existingPrimary->first_name,
                        $existingPrimary->last_name
                    ),
                ]);
            }

            $existing = DB::table('student_guardians')
                ->where('student_id', $student->id)
                ->where('guardian_id', $guardianRow->id)
                ->first();

            $values = [
                'relationship' => $data['relationship'],
                'is_primary' => true,
                'can_receive_notifications' => true,
                'can_view_attendance' => true,
                'can_drop_off' => true,
                'can_pick_up' => true,
                'can_authorize_exit' => true,
                'valid_from' => null,
                'valid_until' => null,
                'status' => 'active',
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('student_guardians')
                    ->where('id', $existing->id)
                    ->update($values);
            } else {
                DB::table('student_guardians')->insert([
                    ...$values,
                    'student_id' => $student->id,
                    'guardian_id' => $guardianRow->id,
                    'created_at' => now(),
                ]);
            }
        }, 3);

        return back()->with(
            'success',
            'Alumno agregado al núcleo como alumno principal.'
        );
    }

    public function updateStudentPermissions(
        Request $request,
        int $guardian,
        int $student
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        DB::table('students')
            ->where('school_id', $schoolId)
            ->where('id', $student)
            ->firstOrFail();

        $currentRelation = DB::table('student_guardians')
            ->where('guardian_id', $guardian)
            ->where('student_id', $student)
            ->where('status', 'active')
            ->firstOrFail();

        $rules = $this->studentPermissionRules($schoolId);
        unset($rules['student_id'], $rules['is_primary']);

        $data = $request->validate($rules);

        $values = (bool) $currentRelation->is_primary
            ? [
                'relationship' => $data['relationship'],
                'is_primary' => true,
                'can_receive_notifications' => true,
                'can_view_attendance' => true,
                'can_drop_off' => true,
                'can_pick_up' => true,
                'can_authorize_exit' => true,
                'valid_from' => $currentRelation->valid_from,
                'valid_until' => $currentRelation->valid_until,
            ]
            : $this->studentPermissionValues(
                $request,
                $data,
                forcePrimary: false
            );

        DB::table('student_guardians')
            ->where('guardian_id', $guardian)
            ->where('student_id', $student)
            ->where('status', 'active')
            ->update([
                ...$values,
                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            (bool) $currentRelation->is_primary
                ? 'Datos del alumno principal actualizados correctamente.'
                : 'Permisos y vigencia actualizados correctamente.'
        );
    }

    public function unlinkStudent(
        Request $request,
        int $guardian,
        int $student
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        DB::table('students')
            ->where('school_id', $schoolId)
            ->where('id', $student)
            ->firstOrFail();

        DB::table('student_guardians')
            ->where('guardian_id', $guardian)
            ->where('student_id', $student)
            ->update([
                'status' => 'inactive',
                'is_primary' => false,
                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            'Vínculo desactivado correctamente.'
        );
    }


    public function transferPrimaryStudent(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where('school_id', $schoolId),
            ],
            'target_guardian_id' => [
                'required',
                'integer',
                Rule::exists('guardians', 'id')->where('school_id', $schoolId),
                Rule::notIn([$guardian]),
            ],
            'relationship' => [
                'required',
                Rule::in(['madre', 'padre', 'tutor']),
            ],
            'keep_previous_as_secondary' => ['nullable', 'boolean'],
        ]);

        $keepPreviousAsSecondary = $request->boolean(
            'keep_previous_as_secondary'
        );

        DB::transaction(function () use (
            $request,
            $data,
            $schoolId,
            $guardian,
            $keepPreviousAsSecondary
        ): void {
            $student = DB::table('students')
                ->where('school_id', $schoolId)
                ->where('id', $data['student_id'])
                ->lockForUpdate()
                ->firstOrFail();

            DB::table('student_guardians')
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->get();

            $currentPrincipal = DB::table('student_guardians')
                ->where('student_id', $student->id)
                ->where('guardian_id', $guardian)
                ->where('status', 'active')
                ->where('is_primary', true)
                ->lockForUpdate()
                ->first();

            if ($currentPrincipal === null) {
                throw ValidationException::withMessages([
                    'student_id' => (
                        'El alumno ya no pertenece como principal a este núcleo.'
                    ),
                ]);
            }

            $targetGuardian = DB::table('guardians')
                ->where('school_id', $schoolId)
                ->where('id', $data['target_guardian_id'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            $targetIsNucleusPrincipal = DB::table('student_guardians')
                ->where('guardian_id', $targetGuardian->id)
                ->where('status', 'active')
                ->where('is_primary', true)
                ->exists();

            if (! $targetIsNucleusPrincipal) {
                throw ValidationException::withMessages([
                    'target_guardian_id' => (
                        'El destino debe ser otro responsable principal '
                        .'con un núcleo familiar activo.'
                    ),
                ]);
            }

            $targetRelation = DB::table('student_guardians')
                ->where('student_id', $student->id)
                ->where('guardian_id', $targetGuardian->id)
                ->lockForUpdate()
                ->first();

            DB::table('student_guardians')
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->where('is_primary', true)
                ->update([
                    'is_primary' => false,
                    'updated_at' => now(),
                ]);

            if ($keepPreviousAsSecondary) {
                DB::table('student_guardians')
                    ->where('id', $currentPrincipal->id)
                    ->update([
                        'is_primary' => false,
                        'status' => 'active',
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('student_guardians')
                    ->where('id', $currentPrincipal->id)
                    ->update([
                        'is_primary' => false,
                        'status' => 'inactive',
                        'updated_at' => now(),
                    ]);
            }

            $targetValues = [
                'relationship' => $data['relationship'],
                'is_primary' => true,
                'can_receive_notifications' => true,
                'can_view_attendance' => true,
                'can_authorize_exit' => true,
                'can_drop_off' => true,
                'can_pick_up' => true,
                'valid_from' => null,
                'valid_until' => null,
                'status' => 'active',
                'updated_at' => now(),
            ];

            if ($targetRelation) {
                DB::table('student_guardians')
                    ->where('id', $targetRelation->id)
                    ->update($targetValues);
            } else {
                DB::table('student_guardians')->insert([
                    ...$targetValues,
                    'student_id' => $student->id,
                    'guardian_id' => $targetGuardian->id,
                    'created_at' => now(),
                ]);
            }

            DB::table('audit_logs')->insert([
                'school_id' => $schoolId,
                'user_id' => $request->user()?->id,
                'actor_type' => 'user',
                'action' => 'family.primary_student_transferred',
                'entity_type' => 'student',
                'entity_id' => $student->id,
                'old_values' => json_encode([
                    'primary_guardian_id' => $guardian,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => json_encode([
                    'primary_guardian_id' => (int) $targetGuardian->id,
                    'previous_guardian_kept_as_secondary' => (
                        $keepPreviousAsSecondary
                    ),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit(
                    (string) $request->userAgent(),
                    512,
                    ''
                ),
                'created_at' => now(),
            ]);
        }, 3);

        return redirect()
            ->route('admin.guardians.show', $data['target_guardian_id'])
            ->with(
                'success',
                'Alumno transferido al nuevo núcleo familiar correctamente.'
            );
    }


    public function storeFamilyMember(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        $principal = DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        $primaryStudentIds = DB::table('student_guardians')
            ->where('guardian_id', $principal->id)
            ->where('status', 'active')
            ->where('is_primary', true)
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id);

        if ($primaryStudentIds->isEmpty()) {
            return back()->withErrors([
                'family_member' => 'Este expediente no corresponde a un núcleo con alumnos principales.',
            ]);
        }

        $data = $request->validate([
            'member_mode' => ['required', Rule::in(['existing', 'new'])],
            'existing_guardian_id' => [
                'nullable',
                'required_if:member_mode,existing',
                'integer',
                Rule::exists('guardians', 'id')->where('school_id', $schoolId),
                Rule::notIn([$principal->id]),
            ],
            'first_name' => ['nullable', 'required_if:member_mode,new', 'string', 'max:100'],
            'last_name' => ['nullable', 'required_if:member_mode,new', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('guardians', 'email')->where('school_id', $schoolId),
            ],
            'create_user' => ['nullable', 'boolean'],
            'relationship' => [
                'required',
                Rule::in([
                    'madre',
                    'padre',
                    'tutor',
                    'abuelo',
                    'abuela',
                    'tio',
                    'tia',
                    'hermano',
                    'hermana',
                    'persona_autorizada',
                    'otro',
                ]),
            ],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'distinct'],
            'can_receive_notifications' => ['nullable', 'boolean'],
            'can_view_attendance' => ['nullable', 'boolean'],
            'can_drop_off' => ['nullable', 'boolean'],
            'can_pick_up' => ['nullable', 'boolean'],
            'can_authorize_exit' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $selectedStudentIds = collect($data['student_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($selectedStudentIds->diff($primaryStudentIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Solo puedes vincular miembros con alumnos principales de este núcleo.',
            ]);
        }

        $generatedCredentials = null;
        $memberName = null;

        DB::transaction(function () use (
            $request,
            $data,
            $schoolId,
            $principal,
            $selectedStudentIds,
            &$generatedCredentials,
            &$memberName
        ): void {
            if ($data['member_mode'] === 'existing') {
                $member = DB::table('guardians')
                    ->where('school_id', $schoolId)
                    ->where('id', $data['existing_guardian_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
            } else {
                $memberId = DB::table('guardians')->insertGetId([
                    'school_id' => $schoolId,
                    'user_id' => null,
                    'first_name' => trim($data['first_name']),
                    'last_name' => trim($data['last_name']),
                    'phone' => $this->nullableTrim($data['phone'] ?? null),
                    'email' => $this->nullableTrim($data['email'] ?? null),
                    'photo_url' => null,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $member = DB::table('guardians')
                    ->where('id', $memberId)
                    ->firstOrFail();

                if ($request->boolean('create_user')) {
                    $generatedCredentials = $this->createGuardianUser(
                        schoolId: $schoolId,
                        guardianId: (int) $member->id,
                        firstName: $member->first_name,
                        lastName: $member->last_name,
                        phone: $member->phone,
                        guardianStatus: $member->status
                    );
                }
            }

            $memberName = trim($member->first_name.' '.$member->last_name);

            foreach ($selectedStudentIds as $studentId) {
                $existing = DB::table('student_guardians')
                    ->where('student_id', $studentId)
                    ->where('guardian_id', $member->id)
                    ->first();

                $values = [
                    'relationship' => $data['relationship'],
                    'is_primary' => false,
                    'can_receive_notifications' => $request->boolean('can_receive_notifications'),
                    'can_view_attendance' => $request->boolean('can_view_attendance'),
                    'can_drop_off' => $request->boolean('can_drop_off'),
                    'can_pick_up' => $request->boolean('can_pick_up'),
                    'can_authorize_exit' => (
                        $request->boolean('can_authorize_exit')
                    ),
                    'valid_from' => $data['valid_from'] ?? null,
                    'valid_until' => $data['valid_until'] ?? null,
                    'status' => 'active',
                    'updated_at' => now(),
                ];

                if ($existing) {
                    if ((bool) $existing->is_primary) {
                        throw ValidationException::withMessages([
                            'existing_guardian_id' => 'La persona seleccionada ya figura como principal de uno de estos alumnos.',
                        ]);
                    }

                    DB::table('student_guardians')
                        ->where('id', $existing->id)
                        ->update($values);
                } else {
                    DB::table('student_guardians')->insert([
                        ...$values,
                        'student_id' => $studentId,
                        'guardian_id' => $member->id,
                        'created_at' => now(),
                    ]);
                }
            }
        }, 3);

        $redirect = back()->with(
            'success',
            sprintf('%s fue agregado al núcleo familiar.', $memberName)
        );

        if ($generatedCredentials) {
            $redirect->with('generated_member_credentials', [
                ...$generatedCredentials,
                'guardian_name' => $memberName,
            ]);
        }

        return $redirect;
    }

    public function unlinkFamilyMember(
        Request $request,
        int $guardian,
        int $member
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $member)
            ->firstOrFail();

        $primaryStudentIds = DB::table('student_guardians')
            ->where('guardian_id', $guardian)
            ->where('status', 'active')
            ->where('is_primary', true)
            ->pluck('student_id');

        DB::table('student_guardians')
            ->where('guardian_id', $member)
            ->whereIn('student_id', $primaryStudentIds)
            ->where('status', 'active')
            ->where('is_primary', false)
            ->update([
                'status' => 'inactive',
                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            'El familiar o autorizado fue desvinculado de este núcleo.'
        );
    }

    public function createCredential(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        try {
            DB::transaction(function () use (
                $request,
                $schoolId,
                $guardian
            ): void {
                $guardianRow = DB::table('guardians')
                    ->where('school_id', $schoolId)
                    ->where('id', $guardian)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertGuardianCanUseQr($guardianRow);

                if ($this->hasActiveCredential($schoolId, $guardian)) {
                    throw new RuntimeException(
                        'El tutor ya tiene una credencial QR activa.'
                    );
                }

                $this->insertGuardianCredential(
                    schoolId: $schoolId,
                    guardianId: $guardian,
                    createdBy: (int) $request->user()->id
                );
            }, 3);
        } catch (Throwable $exception) {
            return back()->withErrors([
                'credential' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'Credencial QR generada correctamente.'
        );
    }

    public function regenerateCredential(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        try {
            DB::transaction(function () use (
                $request,
                $schoolId,
                $guardian
            ): void {
                $guardianRow = DB::table('guardians')
                    ->where('school_id', $schoolId)
                    ->where('id', $guardian)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertGuardianCanUseQr($guardianRow);

                DB::table('guardian_credentials')
                    ->where('school_id', $schoolId)
                    ->where('guardian_id', $guardian)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                        'revoked_reason' => 'Regenerada desde panel administrativo.',
                        'updated_at' => now(),
                    ]);

                $this->insertGuardianCredential(
                    schoolId: $schoolId,
                    guardianId: $guardian,
                    createdBy: (int) $request->user()->id
                );
            }, 3);
        } catch (Throwable $exception) {
            return back()->withErrors([
                'credential' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'Credencial QR regenerada. La credencial anterior quedó revocada.'
        );
    }

    public function revokeCredential(
        Request $request,
        int $guardian,
        int $credential
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        $credentialRow = DB::table('guardian_credentials')
            ->where('school_id', $schoolId)
            ->where('guardian_id', $guardian)
            ->where('id', $credential)
            ->firstOrFail();

        if ($credentialRow->status !== 'active') {
            return back()->withErrors([
                'credential' => 'La credencial ya no está activa.',
            ]);
        }

        DB::table('guardian_credentials')
            ->where('school_id', $schoolId)
            ->where('guardian_id', $guardian)
            ->where('id', $credential)
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revoked_reason' => 'Revocada desde panel administrativo.',
                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            'Credencial QR revocada correctamente.'
        );
    }

    public function printCredential(
        Request $request,
        int $guardian,
        int $credential
    ): View {
        $schoolId = $this->schoolId($request);

        $guardianRow = DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        $credentialRow = DB::table('guardian_credentials')
            ->where('school_id', $schoolId)
            ->where('guardian_id', $guardian)
            ->where('id', $credential)
            ->firstOrFail();

        abort_unless($credentialRow->status === 'active', 404);
        abort_if(
            $credentialRow->expires_at
            && now()->gte($credentialRow->expires_at),
            404
        );
        abort_if(empty($guardianRow->photo_url), 403);

        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->first();

        $students = DB::table('student_guardians as sg')
            ->join('students as s', 's.id', '=', 'sg.student_id')
            ->leftJoin('school_groups as g', 'g.id', '=', 's.current_group_id')
            ->where('sg.guardian_id', $guardian)
            ->where('sg.status', 'active')
            ->where('s.school_id', $schoolId)
            ->select([
                's.first_name',
                's.last_name',
                's.student_code',
                'g.name as group_name',
                'sg.relationship',
                'sg.can_drop_off',
                'sg.can_pick_up',
            ])
            ->orderByDesc('sg.is_primary')
            ->orderBy('s.first_name')
            ->get();

        return view('admin.guardians.print-credential', compact(
            'guardianRow',
            'credentialRow',
            'school',
            'students'
        ));
    }

    public function createAccount(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        $guardianRow = DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        if ($guardianRow->user_id) {
            return back()->withErrors([
                'account' => 'Este tutor ya tiene una cuenta vinculada.',
            ]);
        }

        try {
            $credentials = DB::transaction(
                fn (): array => $this->createGuardianUser(
                    schoolId: $schoolId,
                    guardianId: (int) $guardianRow->id,
                    firstName: $guardianRow->first_name,
                    lastName: $guardianRow->last_name,
                    phone: $guardianRow->phone,
                    guardianStatus: $guardianRow->status
                ),
                3
            );
        } catch (Throwable $exception) {
            return back()->withErrors([
                'account' => app()->environment('local')
                    ? $exception->getMessage()
                    : 'No se pudo crear la cuenta del tutor.',
            ]);
        }

        return back()
            ->with('success', 'Cuenta web/app creada correctamente.')
            ->with('generated_credentials', $credentials);
    }

    public function resetAccountPassword(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        $guardianRow = DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        if (! $guardianRow->user_id) {
            return back()->withErrors([
                'account' => 'El tutor todavía no tiene cuenta web/app.',
            ]);
        }

        $password = $this->generateInitialPassword();

        DB::table('users')
            ->where('id', $guardianRow->user_id)
            ->where('school_id', $schoolId)
            ->update([
                'password' => Hash::make($password),
                'must_change_password' => true,
                'password_changed_at' => null,
                'updated_at' => now(),
            ]);

        $username = DB::table('users')
            ->where('id', $guardianRow->user_id)
            ->value('email');

        return back()
            ->with('success', 'Contraseña restablecida correctamente.')
            ->with('generated_credentials', [
                'username' => $username,
                'password' => $password,
                'is_reset' => true,
            ]);
    }

    public function updateAccountStatus(
        Request $request,
        int $guardian
    ): RedirectResponse {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'blocked'])],
        ]);

        $guardianRow = DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardian)
            ->firstOrFail();

        if (! $guardianRow->user_id) {
            return back()->withErrors([
                'account' => 'El tutor todavía no tiene cuenta web/app.',
            ]);
        }

        if ($data['status'] === 'active' && $guardianRow->status !== 'active') {
            return back()->withErrors([
                'account' => 'Primero debes activar el estado administrativo del tutor.',
            ]);
        }

        DB::table('users')
            ->where('id', $guardianRow->user_id)
            ->where('school_id', $schoolId)
            ->update([
                'status' => $data['status'],
                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            $data['status'] === 'active'
                ? 'Acceso del tutor activado.'
                : 'Acceso del tutor bloqueado.'
        );
    }

    private function guardianForSchool(int $schoolId, int $guardian): object
    {
        return DB::table('guardians as g')
            ->leftJoin('users as u', 'u.id', '=', 'g.user_id')
            ->where('g.school_id', $schoolId)
            ->where('g.id', $guardian)
            ->select([
                'g.*',
                'u.email as access_username',
                'u.status as user_status',
                'u.role as user_role',
                'u.must_change_password',
                'u.password_changed_at',
                'u.created_at as account_created_at',
                'u.updated_at as account_updated_at',
            ])
            ->firstOrFail();
    }

    private function studentPermissionRules(int $schoolId): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where('school_id', $schoolId),
            ],
            'relationship' => [
                'required',
                'string',
                'max:50',
                Rule::in([
                    'madre', 'padre', 'tutor', 'abuelo', 'abuela',
                    'tio', 'tia', 'hermano', 'hermana',
                    'persona_autorizada', 'otro',
                ]),
            ],
            'is_primary' => ['nullable', 'boolean'],
            'can_receive_notifications' => ['nullable', 'boolean'],
            'can_view_attendance' => ['nullable', 'boolean'],
            'can_drop_off' => ['nullable', 'boolean'],
            'can_pick_up' => ['nullable', 'boolean'],
            'can_authorize_exit' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    private function studentPermissionValues(
        Request $request,
        array $data,
        ?bool $forcePrimary = null
    ): array {
        return [
            'relationship' => $data['relationship'],
            'is_primary' => $forcePrimary ?? $request->boolean('is_primary'),
            'can_receive_notifications' =>
                $request->boolean('can_receive_notifications'),
            'can_view_attendance' =>
                $request->boolean('can_view_attendance'),
            'can_drop_off' =>
                $request->boolean('can_drop_off'),
            'can_pick_up' =>
                $request->boolean('can_pick_up'),
            'can_authorize_exit' =>
                $request->boolean('can_authorize_exit'),
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
        ];
    }

    private function assertGuardianCanUseQr(object $guardian): void
    {
        if ($guardian->status !== 'active') {
            throw new RuntimeException(
                'El tutor debe estar activo para utilizar una credencial QR.'
            );
        }

        if (empty($guardian->photo_url)) {
            throw new RuntimeException(
                'Debes registrar una fotografía antes de generar el QR.'
            );
        }
    }

    private function hasActiveCredential(
        int $schoolId,
        int $guardianId
    ): bool {
        return DB::table('guardian_credentials')
            ->where('school_id', $schoolId)
            ->where('guardian_id', $guardianId)
            ->where('type', 'qr')
            ->where('status', 'active')
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    private function insertGuardianCredential(
        int $schoolId,
        int $guardianId,
        int $createdBy
    ): int {
        $rawToken = Str::random(64);

        return DB::table('guardian_credentials')->insertGetId([
            'school_id' => $schoolId,
            'guardian_id' => $guardianId,
            'type' => 'qr',
            'token_hash' => hash('sha256', $rawToken),
            'public_code' => $rawToken,
            'status' => 'active',
            'issued_at' => now(),
            'expires_at' => null,
            'revoked_at' => null,
            'revoked_reason' => null,
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGuardianUser(
        int $schoolId,
        int $guardianId,
        string $firstName,
        string $lastName,
        ?string $phone,
        string $guardianStatus
    ): array {
        $username = $this->generateGuardianUsername(
            schoolId: $schoolId,
            guardianId: $guardianId
        );

        if (User::query()->where('email', $username)->exists()) {
            throw new RuntimeException(
                'No se pudo generar un usuario único para el tutor.'
            );
        }

        $password = $this->generateInitialPassword();

        $userId = DB::table('users')->insertGetId([
            'school_id' => $schoolId,
            'name' => trim($firstName.' '.$lastName),
            'email' => $username,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => 'guardian',
            'status' => $guardianStatus === 'active'
                ? 'active'
                : 'blocked',
            'must_change_password' => true,
            'password_changed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('guardians')
            ->where('school_id', $schoolId)
            ->where('id', $guardianId)
            ->update([
                'user_id' => $userId,
                'updated_at' => now(),
            ]);

        return [
            'username' => $username,
            'password' => $password,
            'is_reset' => false,
        ];
    }

    private function generateGuardianUsername(
        int $schoolId,
        int $guardianId
    ): string {
        return sprintf(
            'sp-s%d-t%06d@schoolpass.local',
            $schoolId,
            $guardianId
        );
    }

    private function generateInitialPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $random = '';

        for ($index = 0; $index < 6; $index++) {
            $random .= $alphabet[
                random_int(0, strlen($alphabet) - 1)
            ];
        }

        return 'SP-'.$random;
    }

    private function normalizeStoragePath(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            return Str::after($path, 'storage/');
        }

        return $path;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function activeCycle(int $schoolId): ?object
    {
        return DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->first();
    }

    private function schoolId(Request $request): int
    {
        $user = $request->user();

        abort_unless($user && $user->school_id, 403);

        return (int) $user->school_id;
    }
}