<?php

namespace App\Services\Sysadmin;

use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Services\Auditing\AuditLogger;
use App\Services\Licensing\SchoolLicenseManager;
use App\Services\SchoolAppConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SchoolOnboardingService
{
    public function __construct(
        private readonly SchoolLicenseManager $licenseManager,
        private readonly SchoolAppConfigService $configService,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function create(
        Request $request,
        array $data,
        int $actorId,
    ): School {
        $logoPath = null;
        $createdSchoolId = null;

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $data,
                    $actorId,
                    &$logoPath,
                    &$createdSchoolId,
                ): School {
                    $school = School::query()->create([
                        'name' => trim($data['name']),
                        'legal_name' =>
                            $this->nullableString(
                                $data['legal_name'] ?? null
                            ),
                        'slug' => $this->uniqueSlug(
                            $data['slug'] ?? null,
                            $data['name']
                        ),
                        'status' => $data['status'],
                        'timezone' => $data['timezone'],
                        'logo_path' => null,
                        'primary_color' => strtoupper(
                            $data['primary_color']
                        ),
                        'secondary_color' => strtoupper(
                            $data['secondary_color']
                        ),
                        'contact_name' =>
                            $this->nullableString(
                                $data['contact_name'] ?? null
                            ),
                        'contact_email' =>
                            $this->nullableLowercase(
                                $data['contact_email'] ?? null
                            ),
                        'contact_phone' =>
                            $this->nullableString(
                                $data['contact_phone'] ?? null
                            ),
                        'address' =>
                            $this->nullableString(
                                $data['address'] ?? null
                            ),
                        'tax_id' =>
                            $this->nullableString(
                                $data['tax_id'] ?? null
                            ),
                        'support_email' =>
                            $this->nullableLowercase(
                                $data['support_email'] ?? null
                            ),
                        'whatsapp_number' =>
                            $this->nullableString(
                                $data['whatsapp_number'] ?? null
                            ),
                        'suspended_at' =>
                            $data['status'] === 'suspended'
                                ? now()
                                : null,
                        'cancelled_at' => null,
                    ]);

                    $createdSchoolId = $school->id;

                    /*
                    |--------------------------------------------------------------------------
                    | Logotipo institucional canónico
                    |--------------------------------------------------------------------------
                    |
                    | Se guarda en schools.logo_path. El panel, los PDF y la
                    | configuración inicial de Staff/Family heredan esta imagen.
                    |
                    */

                    if ($request->hasFile('logo')) {
                        $logoPath = $request
                            ->file('logo')
                            ->store(
                                "schools/{$school->id}/branding",
                                'public'
                            );

                        $school->forceFill([
                            'logo_path' => $logoPath,
                        ])->save();
                    }

                    $plan = SubscriptionPlan::query()
                        ->where('status', 'active')
                        ->with('features')
                        ->findOrFail(
                            $data['subscription_plan_id']
                        );

                    $licenseData = [
                        'status' =>
                            $data['license_status'],

                        'billing_cycle' =>
                            $data['license_status']
                                === 'trial'
                                    ? 'trial'
                                    : $data['billing_cycle'],

                        'starts_at' =>
                            $data['license_starts_at'],

                        'trial_days' =>
                            $data['license_status']
                                === 'trial'
                                    ? (int) $data['trial_days']
                                    : null,

                        'expires_at' =>
                            $data['license_status']
                                === 'active'
                                    ? (
                                        $data['license_expires_at']
                                        ?? null
                                    )
                                    : null,

                        'contract_price' =>
                            $data['license_status']
                                === 'trial'
                                    ? 0
                                    : (
                                        $data['contract_price']
                                        ?? null
                                    ),

                        'auto_renew' =>
                            (bool) $data['auto_renew'],

                        'notes' =>
                            $this->nullableString(
                                $data['license_notes']
                                ?? null
                            ),
                    ];

                    foreach (
                        [
                            'student_limit',
                            'device_limit',
                            'staff_limit',
                            'campus_limit',
                        ]
                        as $limitField
                    ) {
                        if (
                            array_key_exists(
                                $limitField,
                                $data
                            )
                            && $data[$limitField] !== null
                            && $data[$limitField] !== ''
                        ) {
                            $licenseData[$limitField] =
                                (int) $data[$limitField];
                        }
                    }

                    $license = $this->licenseManager->assign(
                        school: $school,
                        plan: $plan,
                        data: $licenseData,
                        actorId: $actorId,
                    );

                    $cycleId = DB::table(
                        'academic_cycles'
                    )->insertGetId([
                        'school_id' => $school->id,
                        'name' => trim($data['cycle_name']),
                        'starts_on' =>
                            $data['cycle_starts_on'],
                        'ends_on' =>
                            $data['cycle_ends_on'],
                        'is_active' =>
                            $data['cycle_status']
                                === 'active',
                        'closed_at' => null,
                        'status' =>
                            $data['cycle_status'],
                        'notes' =>
                            $this->nullableString(
                                $data['cycle_notes']
                                ?? null
                            ),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $campusId = DB::table(
                        'campuses'
                    )->insertGetId([
                        'school_id' => $school->id,
                        'name' => trim($data['campus_name']),
                        'address' =>
                            $this->nullableString(
                                $data['campus_address']
                                ?? null
                            ),
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $structure = $this->parseStructure(
                        $data['structure_lines']
                    );

                    $groupIds = [];
                    $levelIds = [];

                    foreach (
                        $structure
                        as $sortOrder => $level
                    ) {
                        $levelId = DB::table(
                            'academic_levels'
                        )->insertGetId([
                            'school_id' => $school->id,
                            'name' => $level['name'],
                            'sort_order' =>
                                $sortOrder + 1,
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $levelIds[] = $levelId;

                        foreach (
                            $level['groups']
                            as $groupName
                        ) {
                            $groupIds[] = DB::table(
                                'school_groups'
                            )->insertGetId([
                                'school_id' => $school->id,
                                'campus_id' => $campusId,
                                'academic_level_id' =>
                                    $levelId,
                                'academic_cycle_id' =>
                                    $cycleId,
                                'name' => $groupName,
                                'grade_label' =>
                                    $this->gradeLabel(
                                        $groupName
                                    ),
                                'requires_guardian_scan' =>
                                    (bool) $data[
                                        'requires_guardian_scan'
                                    ],
                                'auto_transition_minutes' =>
                                    (int) $data[
                                        'auto_transition_minutes'
                                    ],
                                'status' => 'active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    if (
                        (bool) $data['schedule_enabled']
                    ) {
                        $this->createSchedules(
                            schoolId: $school->id,
                            groupIds: $groupIds,
                            weekdays: $data['weekdays'],
                            entryTime: $data['entry_time'],
                            graceUntil:
                                $data['grace_until'],
                            lateUntil:
                                $data['late_until'],
                            exitTime: $data['exit_time'],
                        );
                    }

                    $userIds = [];

                    $userIds['school_admin'] =
                        $this->createUser(
                            schoolId: $school->id,
                            name: $data['admin_name'],
                            email: $data['admin_email'],
                            phone:
                                $data['admin_phone']
                                ?? null,
                            password:
                                $data['admin_password'],
                            role: 'school_admin',
                        );

                    if (
                        (bool) $data['create_director']
                    ) {
                        $userIds['director'] =
                            $this->createUser(
                                schoolId: $school->id,
                                name:
                                    $data['director_name'],
                                email:
                                    $data['director_email'],
                                phone:
                                    $data['director_phone']
                                    ?? null,
                                password:
                                    $data['director_password'],
                                role: 'director',
                            );
                    }

                    if (
                        (bool) $data['create_prefect']
                    ) {
                        $userIds['prefect'] =
                            $this->createUser(
                                schoolId: $school->id,
                                name:
                                    $data['prefect_name'],
                                email:
                                    $data['prefect_email'],
                                phone:
                                    $data['prefect_phone']
                                    ?? null,
                                password:
                                    $data['prefect_password'],
                                role: 'prefect',
                            );
                    }

                    if (
                        (bool) $data['create_kiosk']
                    ) {
                        $userIds['kiosk'] =
                            $this->createUser(
                                schoolId: $school->id,
                                name:
                                    $data['kiosk_name'],
                                email:
                                    $data['kiosk_email'],
                                phone: null,
                                password:
                                    $data['kiosk_password'],
                                role: 'kiosk',
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Configuración inicial de aplicaciones
                    |--------------------------------------------------------------------------
                    |
                    | Debe ejecutarse después de guardar schools.logo_path.
                    | Así Staff y Family reciben el logo institucional por defecto.
                    |
                    */

                    $school->refresh();

                    $this->configService->save(
                        school: $school,
                        config:
                            $this->configService
                                ->defaults($school),
                        actorId: $actorId,
                    );

                    $this->auditLogger->record(
                        action: 'school_onboarding_completed',
                        schoolId: $school->id,
                        actorId: $actorId,
                        actorType: 'superadmin',
                        entityType: School::class,
                        entityId: $school->id,
                        newValues: [
                            'name' => $school->name,
                            'slug' => $school->slug,
                            'status' => $school->status,
                            'logo_path' =>
                                $school->logo_path,
                            'primary_color' =>
                                $school->primary_color,
                            'secondary_color' =>
                                $school->secondary_color,
                            'license_id' => $license->id,
                            'plan_id' => $plan->id,
                            'license_status' =>
                                $license->status,
                            'cycle_id' => $cycleId,
                            'campus_id' => $campusId,
                            'academic_level_ids' =>
                                $levelIds,
                            'group_count' =>
                                count($groupIds),
                            'initial_users' =>
                                $userIds,
                        ],
                        request: $request,
                    );

                    return $school->fresh();
                }
            );
        } catch (Throwable $exception) {
            if ($logoPath !== null) {
                Storage::disk('public')->delete(
                    $logoPath
                );
            }

            if ($createdSchoolId !== null) {
                Storage::disk('public')->deleteDirectory(
                    "schools/{$createdSchoolId}"
                );
            }

            throw $exception;
        }
    }

    private function createUser(
        int $schoolId,
        string $name,
        string $email,
        ?string $phone,
        string $password,
        string $role,
    ): int {
        return DB::table('users')->insertGetId([
            'school_id' => $schoolId,
            'name' => trim($name),
            'email' => mb_strtolower(
                trim($email)
            ),
            'phone' =>
                $this->nullableString($phone),
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'must_change_password' => true,
            'password_changed_at' => null,
            'last_login_at' => null,
            'role' => $role,
            'status' => 'active',
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchedules(
        int $schoolId,
        array $groupIds,
        array $weekdays,
        string $entryTime,
        string $graceUntil,
        string $lateUntil,
        string $exitTime,
    ): void {
        $rows = [];

        foreach ($groupIds as $groupId) {
            foreach (
                array_values(
                    array_unique(
                        array_map(
                            'intval',
                            $weekdays
                        )
                    )
                )
                as $weekday
            ) {
                $rows[] = [
                    'school_id' => $schoolId,
                    'group_id' => $groupId,
                    'weekday' => $weekday,
                    'entry_time' => $entryTime,
                    'grace_until' => $graceUntil,
                    'late_until' => $lateUntil,
                    'exit_time' => $exitTime,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (
            array_chunk($rows, 500)
            as $chunk
        ) {
            DB::table(
                'group_access_schedules'
            )->insert($chunk);
        }
    }

    private function parseStructure(
        string $input
    ): array {
        $lines = preg_split(
            '/\R/u',
            trim($input)
        ) ?: [];

        $levels = [];
        $seenLevels = [];
        $seenGroups = [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (! str_contains($line, '|')) {
                throw ValidationException::withMessages([
                    'structure_lines' => sprintf(
                        'La línea %d debe usar el formato Nivel|Grupo1,Grupo2.',
                        $lineNumber + 1
                    ),
                ]);
            }

            [$levelName, $groupsText] =
                array_map(
                    'trim',
                    explode('|', $line, 2)
                );

            if (
                $levelName === ''
                || $groupsText === ''
            ) {
                throw ValidationException::withMessages([
                    'structure_lines' => sprintf(
                        'La línea %d debe incluir nivel y grupos.',
                        $lineNumber + 1
                    ),
                ]);
            }

            $levelKey = $this->normalizeKey(
                $levelName
            );

            if (isset($seenLevels[$levelKey])) {
                throw ValidationException::withMessages([
                    'structure_lines' => sprintf(
                        'El nivel "%s" está repetido.',
                        $levelName
                    ),
                ]);
            }

            $groups = collect(
                preg_split(
                    '/[,;]+/u',
                    $groupsText
                ) ?: []
            )
                ->map(
                    fn (string $group): string =>
                        trim($group)
                )
                ->filter()
                ->unique(
                    fn (string $group): string =>
                        $this->normalizeKey($group)
                )
                ->values()
                ->all();

            if ($groups === []) {
                throw ValidationException::withMessages([
                    'structure_lines' => sprintf(
                        'El nivel "%s" no contiene grupos.',
                        $levelName
                    ),
                ]);
            }

            foreach ($groups as $groupName) {
                $groupKey =
                    $levelKey
                    .'|'
                    .$this->normalizeKey(
                        $groupName
                    );

                if (isset($seenGroups[$groupKey])) {
                    throw ValidationException::withMessages([
                        'structure_lines' => sprintf(
                            'El grupo "%s" está repetido en "%s".',
                            $groupName,
                            $levelName
                        ),
                    ]);
                }

                $seenGroups[$groupKey] = true;
            }

            $seenLevels[$levelKey] = true;

            $levels[] = [
                'name' => $levelName,
                'groups' => $groups,
            ];
        }

        if ($levels === []) {
            throw ValidationException::withMessages([
                'structure_lines' =>
                    'Escribe al menos un nivel y sus grupos.',
            ]);
        }

        return $levels;
    }

    private function gradeLabel(
        string $groupName
    ): ?string {
        if (
            preg_match(
                '/^\s*(\d{1,2})/u',
                $groupName,
                $matches
            )
        ) {
            return $matches[1];
        }

        return null;
    }

    private function normalizeKey(
        string $value
    ): string {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches(
                '/[^a-z0-9]+/',
                '-'
            )
            ->trim('-')
            ->toString();
    }

    private function uniqueSlug(
        ?string $requestedSlug,
        string $schoolName
    ): string {
        $base = Str::slug(
            trim(
                (string) $requestedSlug
            )
        );

        if ($base === '') {
            $base = Str::slug($schoolName);
        }

        if ($base === '') {
            $base = 'escuela';
        }

        $slug = $base;
        $counter = 2;

        while (
            School::query()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $value = trim(
            (string) $value
        );

        return $value === ''
            ? null
            : $value;
    }

    private function nullableLowercase(
        mixed $value
    ): ?string {
        $value = $this->nullableString(
            $value
        );

        return $value === null
            ? null
            : mb_strtolower($value);
    }
}
