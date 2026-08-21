<?php

namespace App\Services\Family;

use App\Models\GuardianMembership;
use App\Models\User;
use App\Services\Licensing\SchoolLicenseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FamilyAccessService
{
    public function __construct(
        private readonly SchoolLicenseService $licenseService,
    ) {
    }

    public function snapshotForUser(
        User $user,
    ): array {
        $guardian = $this->guardianForUser($user);

        $schoolId = (int) $user->school_id;
        $guardianId = (int) $guardian->id;

        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->first();

        if ($school === null) {
            throw new AuthorizationException(
                'La institución asignada no existe.'
            );
        }

        $license = $this->licenseService
            ->current($schoolId);

        $schoolTrialActive = $this->licenseService
            ->isInTrial($schoolId);

        $students = $this->linkedStudents(
            schoolId: $schoolId,
            guardianId: $guardianId,
        );

        $studentIds = $students
            ->pluck('id')
            ->map(
                fn ($id): int => (int) $id
            )
            ->values();

        $primaryRelationships = $this
            ->primaryRelationshipsForStudents(
                studentIds: $studentIds,
            );

        $primaryGuardianIds = $primaryRelationships
            ->pluck('guardian_id')
            ->filter()
            ->map(
                fn ($id): int => (int) $id
            )
            ->unique()
            ->values();

        /*
         * Incluimos al tutor actual aunque todavía no aparezca como
         * principal, para devolver siempre su estado de membresía.
         */
        if (! $primaryGuardianIds->contains($guardianId)) {
            $primaryGuardianIds->push($guardianId);
        }

        $memberships = GuardianMembership::query()
            ->where('school_id', $schoolId)
            ->whereIn(
                'guardian_id',
                $primaryGuardianIds->all()
            )
            ->get()
            ->keyBy('guardian_id');

        /** @var GuardianMembership|null $ownMembership */
        $ownMembership = $memberships->get(
            $guardianId
        );

        $ownMembershipUsable = $ownMembership?->isUsable()
            ?? false;

        $studentItems = $students
            ->map(function (object $student) use (
                $guardianId,
                $primaryRelationships,
                $memberships,
                $schoolTrialActive,
            ): array {
                $primaryRelationship = $primaryRelationships
                    ->get((int) $student->id);

                $primaryGuardianId = $primaryRelationship !== null
                    ? (int) $primaryRelationship->guardian_id
                    : null;

                /** @var GuardianMembership|null $primaryMembership */
                $primaryMembership = $primaryGuardianId !== null
                    ? $memberships->get($primaryGuardianId)
                    : null;

                $membershipUsable = $primaryMembership?->isUsable()
                    ?? false;

                $fullAccess = $schoolTrialActive
                    || $membershipUsable;

                $accessSource = null;
                $accessStatus = 'locked';
                $lockedReason = null;

                if ($schoolTrialActive) {
                    $accessSource = 'school_trial';
                    $accessStatus = 'active';
                } elseif ($membershipUsable) {
                    $accessSource = $primaryMembership?->source;
                    $accessStatus = 'active';
                } elseif ($primaryGuardianId === null) {
                    $lockedReason = 'primary_guardian_missing';
                } elseif ((bool) $student->is_primary) {
                    $lockedReason = 'payment_required';
                } else {
                    $lockedReason = 'primary_activation_required';
                }

                return [
                    'id' => (int) $student->id,

                    'student_code' => $student->student_code,

                    'name' => trim(
                        $student->first_name
                        .' '
                        .$student->last_name
                    ),

                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,

                    'photo_url' => $this->fullUrl(
                        $student->photo_url
                    ),

                    'level' => $student->level_name,
                    'group' => $student->group_name,

                    'relationship' => $student->relationship,

                    'relationship_role' => (
                        (bool) $student->is_primary
                    )
                        ? 'primary'
                        : 'secondary',

                    'primary_guardian_id' => $primaryGuardianId,

                    'access' => [
                        'status' => $accessStatus,
                        'source' => $accessSource,
                        'full_access' => $fullAccess,
                        'locked_reason' => $lockedReason,
                    ],

                    /*
                     * Los permisos efectivos se apagan cuando no existe
                     * membresía o trial, aunque la relación escolar sí
                     * tenga los permisos configurados.
                     */
                    'features' => [
                        'can_view_attendance' => (
                            $fullAccess
                            && (bool) $student->can_view_attendance
                        ),

                        'can_receive_notifications' => (
                            $fullAccess
                            && (bool) $student->can_receive_notifications
                        ),

                        'can_authorize_exit' => (
                            $fullAccess
                            && (bool) $student->can_authorize_exit
                        ),

                        'can_pick_up' => (
                            $fullAccess
                            && (bool) $student->can_pick_up
                        ),

                        'can_view_student_credential' => $fullAccess,

                        'can_view_guardian_credential' => $fullAccess,
                    ],
                ];
            })
            ->values();

        $hasPrimaryStudents = $students->contains(
            fn (object $student): bool => (
                (bool) $student->is_primary
            )
        );

        $canPurchase = ! $schoolTrialActive
            && $hasPrimaryStudents
            && ! $ownMembershipUsable;

        $hasAnyFullAccess = $studentItems->contains(
            fn (array $student): bool => (
                (bool) data_get(
                    $student,
                    'access.full_access',
                    false
                )
            )
        );

        $allStudentsAccessible = $studentItems->isNotEmpty()
            && $studentItems->every(
                fn (array $student): bool => (
                    (bool) data_get(
                        $student,
                        'access.full_access',
                        false
                    )
                )
            );

        $lockedStudentCount = $studentItems->filter(
            fn (array $student): bool => ! (
                (bool) data_get(
                    $student,
                    'access.full_access',
                    false
                )
            )
        )->count();

        return [
            'school' => [
                'id' => (int) $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'status' => $school->status,
                'logo_url' => $this->fullUrl(
                    $school->logo_path
                ),
                'primary_color' => $school->primary_color,
                'secondary_color' => $school->secondary_color,
            ],

            'guardian' => [
                'id' => $guardianId,

                'name' => trim(
                    $guardian->first_name
                    .' '
                    .$guardian->last_name
                ),

                'email' => $guardian->email,
                'phone' => $guardian->phone,

                'photo_url' => $this->fullUrl(
                    $guardian->photo_url
                ),

                'has_primary_students' => $hasPrimaryStudents,
            ],

            'license' => [
                'status' => $license?->status,

                'trial_active' => $schoolTrialActive,

                'trial_ends_at' => $license?->trial_ends_at
                    ?->toDateString(),

                'expires_at' => $license?->expires_at
                    ?->toDateString(),
            ],

            /*
             * Esta es la membresía propia del usuario actual.
             *
             * Un secundario puede no tener membresía propia y aun así
             * obtener acceso por la membresía del tutor principal.
             */
            'membership' => $this->membershipPayload(
                $ownMembership
            ),

            'access' => [
                'has_any_full_access' => $hasAnyFullAccess,
                'all_students_accessible' => $allStudentsAccessible,
                'locked_student_count' => $lockedStudentCount,

                'can_purchase' => $canPurchase,
                'show_paywall' => $canPurchase,

                'annual_price' => (float) config(
                    'family_memberships.annual_price',
                    149
                ),

                'currency' => (string) config(
                    'family_memberships.currency',
                    'MXN'
                ),

                'google_play_product_id' => (string) config(
                    'family_memberships.google_play.product_id',
                    'paselista_family_access'
                ),

                'google_play_base_plan_id' => (string) config(
                    'family_memberships.google_play.base_plan_id',
                    'annual-prepaid-mx'
                ),
            ],

            'students' => $studentItems,
        ];
    }

    public function hasAnyFullAccess(
        User $user,
    ): bool {
        return (bool) data_get(
            $this->snapshotForUser($user),
            'access.has_any_full_access',
            false
        );
    }

    public function studentHasFullAccess(
        User $user,
        int $studentId,
    ): bool {
        $snapshot = $this->snapshotForUser($user);

        $student = collect(
            $snapshot['students'] ?? []
        )->firstWhere(
            'id',
            $studentId
        );

        return $student !== null
            && (bool) data_get(
                $student,
                'access.full_access',
                false
            );
    }

    public function assertStudentHasFullAccess(
        User $user,
        int $studentId,
    ): void {
        if (
            ! $this->studentHasFullAccess(
                user: $user,
                studentId: $studentId,
            )
        ) {
            throw new AuthorizationException(
                'La membresía no permite utilizar esta función para el alumno.'
            );
        }
    }

    private function guardianForUser(
        User $user,
    ): object {
        if ($user->role !== 'guardian') {
            throw new AuthorizationException(
                'Usuario no autorizado para PaseLista Family.'
            );
        }

        if ($user->school_id === null) {
            throw new AuthorizationException(
                'El usuario no tiene una institución asignada.'
            );
        }

        $guardian = DB::table('guardians')
            ->where('user_id', $user->id)
            ->where('school_id', $user->school_id)
            ->where('status', 'active')
            ->first();

        if ($guardian === null) {
            throw new AuthorizationException(
                'No existe un perfil de tutor activo.'
            );
        }

        return $guardian;
    }

    private function linkedStudents(
        int $schoolId,
        int $guardianId,
    ): Collection {
        $today = now()->toDateString();

        return DB::table('student_guardians as sg')
            ->join(
                'students as s',
                's.id',
                '=',
                'sg.student_id'
            )
            ->leftJoin(
                'school_groups as g',
                'g.id',
                '=',
                's.current_group_id'
            )
            ->leftJoin(
                'academic_levels as al',
                'al.id',
                '=',
                'g.academic_level_id'
            )
            ->where('sg.guardian_id', $guardianId)
            ->where('sg.status', 'active')
            ->where('s.school_id', $schoolId)
            ->where('s.status', 'active')
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('sg.valid_from')
                    ->orWhereDate(
                        'sg.valid_from',
                        '<=',
                        $today
                    );
            })
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('sg.valid_until')
                    ->orWhereDate(
                        'sg.valid_until',
                        '>=',
                        $today
                    );
            })
            ->select([
                's.id',
                's.student_code',
                's.first_name',
                's.last_name',
                's.photo_url',
                'g.name as group_name',
                'al.name as level_name',
                'sg.relationship',
                'sg.is_primary',
                'sg.can_view_attendance',
                'sg.can_receive_notifications',
                'sg.can_authorize_exit',
                'sg.can_pick_up',
            ])
            ->orderByDesc('sg.is_primary')
            ->orderBy('s.first_name')
            ->orderBy('s.last_name')
            ->get();
    }

    private function primaryRelationshipsForStudents(
        Collection $studentIds,
    ): Collection {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        $today = now()->toDateString();

        return DB::table('student_guardians')
            ->whereIn(
                'student_id',
                $studentIds->all()
            )
            ->where('is_primary', true)
            ->where('status', 'active')
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('valid_from')
                    ->orWhereDate(
                        'valid_from',
                        '<=',
                        $today
                    );
            })
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('valid_until')
                    ->orWhereDate(
                        'valid_until',
                        '>=',
                        $today
                    );
            })
            ->orderByDesc('id')
            ->get([
                'student_id',
                'guardian_id',
            ])
            ->unique('student_id')
            ->keyBy('student_id');
    }

    private function membershipPayload(
        ?GuardianMembership $membership,
    ): array {
        if ($membership === null) {
            return [
                'id' => null,
                'status' => 'inactive',
                'source' => null,
                'starts_at' => null,
                'expires_at' => null,
                'google_play_started_at' => null,
                'google_play_expires_at' => null,
                'subscription_state' => null,
                'acknowledgement_state' => null,
                'base_plan_id' => null,
                'is_test_purchase' => false,
                'usable' => false,
            ];
        }

        return [
            'id' => $membership->id,
            'status' => $membership->status,
            'source' => $membership->source,

            'starts_at' => $membership->starts_at
                ?->toDateString(),

            'expires_at' => $membership->expires_at
                ?->toDateString(),

            'google_play_started_at' => (
                $membership->google_play_started_at
                    ?->toIso8601String()
            ),

            'google_play_expires_at' => (
                $membership->google_play_expires_at
                    ?->toIso8601String()
            ),

            'subscription_state' => (
                $membership->google_play_subscription_state
            ),

            'acknowledgement_state' => (
                $membership->google_play_acknowledgement_state
            ),

            'base_plan_id' => (
                $membership->google_play_base_plan_id
            ),

            'is_test_purchase' => (
                (bool) $membership->google_play_is_test_purchase
            ),

            'usable' => $membership->isUsable(),
        ];
    }

    private function fullUrl(
        ?string $path,
    ): ?string {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
        ) {
            if (
                request()->isSecure()
                || request()->header(
                    'x-forwarded-proto'
                ) === 'https'
            ) {
                return preg_replace(
                    '/^http:\/\//i',
                    'https://',
                    $path
                );
            }

            return $path;
        }

        $url = url($path);

        if (
            request()->isSecure()
            || request()->header(
                'x-forwarded-proto'
            ) === 'https'
        ) {
            return preg_replace(
                '/^http:\/\//i',
                'https://',
                $url
            );
        }

        return $url;
    }
}