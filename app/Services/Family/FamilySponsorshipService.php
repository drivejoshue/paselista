<?php

namespace App\Services\Family;

use App\Models\GuardianMembership;
use App\Models\User;
use App\Services\Licensing\SchoolLicenseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilySponsorshipService
{
    public function __construct(
        private readonly SchoolLicenseService $licenseService,
    ) {
    }

    /**
     * Resumen de la bolsa de patrocinios del ciclo escolar activo.
     */
    public function summary(
        int $schoolId,
    ): array {
        $cycle = $this->activeCycle(
            schoolId: $schoolId,
            lockForUpdate: false,
        );

        if ($cycle === null) {
            return [
                'cycle' => null,

                'pool' => [
                    'id' => null,
                    'quota_total' => 0,
                    'used' => 0,
                    'remaining' => 0,
                    'status' => 'unavailable',
                ],

                'memberships' => [
                    'active_total' => 0,
                    'google_play' => 0,
                    'school_sponsorship' => 0,
                    'sysadmin_courtesy' => 0,
                ],

                'primary_guardians' => 0,

                'can_assign' => false,

                'blocked_reason' => (
                    'La institución no tiene un ciclo escolar activo.'
                ),
            ];
        }

        $pool = DB::table(
            'school_family_sponsorship_pools'
        )
            ->where('school_id', $schoolId)
            ->where(
                'academic_cycle_id',
                $cycle->id
            )
            ->first();

        $quotaTotal = $pool !== null
            ? (int) $pool->quota_total
            : (int) config(
                'family_memberships.default_sponsorship_quota',
                20
            );

        $used = $pool !== null
            ? (int) DB::table(
                'school_family_sponsorship_assignments'
            )
                ->where('pool_id', $pool->id)
                ->where('status', 'assigned')
                ->count()
            : 0;

        $today = now()->toDateString();

        $activeMemberships = DB::table(
            'guardian_memberships'
        )
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhereDate(
                        'starts_at',
                        '<=',
                        $today
                    );
            })
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhereDate(
                        'expires_at',
                        '>=',
                        $today
                    );
            });

        $membershipCounts = (clone $activeMemberships)
            ->select([
                'source',
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy('source')
            ->pluck(
                'total',
                'source'
            );

        $primaryGuardians = DB::table(
            'student_guardians as sg'
        )
            ->join(
                'students as s',
                's.id',
                '=',
                'sg.student_id'
            )
            ->where('s.school_id', $schoolId)
            ->where('s.status', 'active')
            ->where('sg.status', 'active')
            ->where('sg.is_primary', true)
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
            ->distinct()
            ->count('sg.guardian_id');

        $license = $this->licenseService
            ->current($schoolId);

        $trialActive = $this->licenseService
            ->isInTrial($schoolId);

        $licenseUsable = $this->licenseService
            ->isActive($schoolId);

        $canAssign = $licenseUsable
            && ! $trialActive
            && $quotaTotal > $used
            && ($pool === null || $pool->status === 'active');

        $blockedReason = null;

        if (! $licenseUsable) {
            $blockedReason = (
                'La licencia de la institución no está activa.'
            );
        } elseif ($trialActive) {
            $blockedReason = (
                'Durante el periodo de prueba todas las familias '
                .'tienen acceso completo y no se consumen patrocinios.'
            );
        } elseif (
            $pool !== null
            && $pool->status !== 'active'
        ) {
            $blockedReason = (
                'La bolsa de patrocinios está inactiva.'
            );
        } elseif ($used >= $quotaTotal) {
            $blockedReason = (
                'La institución agotó sus patrocinios del ciclo.'
            );
        }

        return [
            'cycle' => [
                'id' => (int) $cycle->id,
                'name' => $cycle->name,
                'starts_on' => $cycle->starts_on,
                'ends_on' => $cycle->ends_on,
                'status' => $cycle->status,
            ],

            'license' => [
                'status' => $license?->status,
                'trial_active' => $trialActive,
                'expires_at' => $license?->expires_at
                    ?->toDateString(),
            ],

            'pool' => [
                'id' => $pool !== null
                    ? (int) $pool->id
                    : null,

                'quota_total' => $quotaTotal,
                'used' => $used,

                'remaining' => max(
                    0,
                    $quotaTotal - $used
                ),

                'status' => $pool?->status
                    ?? 'pending_creation',
            ],

            'memberships' => [
                'active_total' => (int) (
                    clone $activeMemberships
                )->count(),

                'google_play' => (int) (
                    $membershipCounts['google_play']
                    ?? 0
                ),

                'school_sponsorship' => (int) (
                    $membershipCounts[
                        'school_sponsorship'
                    ]
                    ?? 0
                ),

                'sysadmin_courtesy' => (int) (
                    $membershipCounts[
                        'sysadmin_courtesy'
                    ]
                    ?? 0
                ),
            ],

            'primary_guardians' => $primaryGuardians,

            'can_assign' => $canAssign,

            'blocked_reason' => $blockedReason,
        ];
    }

    /**
     * Asigna uno de los patrocinios de la escuela a un tutor principal.
     */
    public function assign(
        User $actor,
        int $guardianId,
        ?string $reason = null,
    ): array {
        $this->assertActorCanAssign(
            actor: $actor,
        );

        $schoolId = (int) $actor->school_id;

        if (
            ! $this->licenseService->isActive(
                $schoolId
            )
        ) {
            throw ValidationException::withMessages([
                'guardian' => [
                    'La licencia de la institución no está activa.',
                ],
            ]);
        }

        if (
            $this->licenseService->isInTrial(
                $schoolId
            )
        ) {
            throw ValidationException::withMessages([
                'guardian' => [
                    'No es necesario asignar patrocinios durante '
                    .'el periodo de prueba. Family ya tiene acceso '
                    .'completo mientras el trial esté vigente.',
                ],
            ]);
        }

        $reason = trim((string) $reason);

        return DB::transaction(
            function () use (
                $actor,
                $schoolId,
                $guardianId,
                $reason,
            ): array {
                $cycle = $this->activeCycle(
                    schoolId: $schoolId,
                    lockForUpdate: true,
                );

                if ($cycle === null) {
                    throw ValidationException::withMessages([
                        'guardian' => [
                            'La institución no tiene un ciclo '
                            .'escolar activo.',
                        ],
                    ]);
                }

                $guardian = DB::table('guardians')
                    ->where('id', $guardianId)
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($guardian === null) {
                    throw ValidationException::withMessages([
                        'guardian' => [
                            'El tutor no existe o no está activo '
                            .'en esta institución.',
                        ],
                    ]);
                }

                $primaryStudentIds = $this
                    ->primaryStudentIds(
                        schoolId: $schoolId,
                        guardianId: $guardianId,
                    );

                if ($primaryStudentIds === []) {
                    throw ValidationException::withMessages([
                        'guardian' => [
                            'Solo puede patrocinarse a una persona '
                            .'que sea tutor principal de al menos '
                            .'un alumno activo.',
                        ],
                    ]);
                }

                $pool = $this->poolForCycle(
                    schoolId: $schoolId,
                    academicCycleId: (int) $cycle->id,
                    actorId: (int) $actor->id,
                );

                if ($pool->status !== 'active') {
                    throw ValidationException::withMessages([
                        'guardian' => [
                            'La bolsa de patrocinios está inactiva.',
                        ],
                    ]);
                }

                $existingAssignment = DB::table(
                    'school_family_sponsorship_assignments'
                )
                    ->where('pool_id', $pool->id)
                    ->where('guardian_id', $guardianId)
                    ->lockForUpdate()
                    ->first();

                $membership = GuardianMembership::query()
                    ->where('school_id', $schoolId)
                    ->where('guardian_id', $guardianId)
                    ->lockForUpdate()
                    ->first();

                if (
                    $existingAssignment !== null
                    && $existingAssignment->status === 'assigned'
                ) {
                    if (
                        $membership !== null
                        && $membership->isUsable()
                        && $membership->source
                            === 'school_sponsorship'
                        && (int) $membership->academic_cycle_id
                            === (int) $cycle->id
                    ) {
                        return $this->resultPayload(
                            cycle: $cycle,
                            pool: $pool,
                            guardian: $guardian,
                            membership: $membership,
                            assignmentId: (
                                (int) $existingAssignment->id
                            ),
                            primaryStudentIds: $primaryStudentIds,
                            created: false,
                        );
                    }

                    throw ValidationException::withMessages([
                        'guardian' => [
                            'El tutor ya tiene un patrocinio '
                            .'asignado en este ciclo, pero su '
                            .'membresía requiere revisión por Sysadmin.',
                        ],
                    ]);
                }

                if (
                    $existingAssignment !== null
                    && $existingAssignment->status === 'released'
                ) {
                    throw ValidationException::withMessages([
                        'guardian' => [
                            'Este patrocinio fue liberado por Sysadmin. '
                            .'Debe revisarse antes de volver a asignarlo.',
                        ],
                    ]);
                }

                if (
                    $membership !== null
                    && $membership->isUsable()
                ) {
                    $sourceLabel = match (
                        $membership->source
                    ) {
                        'google_play' => (
                            'una membresía pagada en Google Play'
                        ),

                        'sysadmin_courtesy' => (
                            'una cortesía de Sysadmin'
                        ),

                        'school_sponsorship' => (
                            'un patrocinio escolar'
                        ),

                        default => (
                            'una membresía activa'
                        ),
                    };

                    throw ValidationException::withMessages([
                        'guardian' => [
                            'El tutor ya cuenta con '
                            .$sourceLabel
                            .'. No se consumirá otro lugar.',
                        ],
                    ]);
                }

                $used = (int) DB::table(
                    'school_family_sponsorship_assignments'
                )
                    ->where('pool_id', $pool->id)
                    ->where('status', 'assigned')
                    ->count();

                if ($used >= (int) $pool->quota_total) {
                    throw ValidationException::withMessages([
                        'guardian' => [
                            'La institución agotó sus '
                            .'patrocinios del ciclo escolar.',
                        ],
                    ]);
                }

                $today = now()->startOfDay();

                $cycleStartsAt = Carbon::parse(
                    $cycle->starts_on
                )->startOfDay();

                $cycleEndsAt = Carbon::parse(
                    $cycle->ends_on
                )->endOfDay();

                if ($cycleEndsAt->lt(now())) {
                    throw ValidationException::withMessages([
                        'guardian' => [
                            'El ciclo escolar activo ya terminó.',
                        ],
                    ]);
                }

                $membershipStartsAt = $cycleStartsAt->gt(
                    $today
                )
                    ? $cycleStartsAt
                    : $today;

                $oldMembershipValues = $membership !== null
                    ? $membership->getAttributes()
                    : null;

                if ($membership === null) {
                    $membership = new GuardianMembership();

                    $membership->created_by = $actor->id;
                }

                $membership->forceFill([
                    'school_id' => $schoolId,
                    'guardian_id' => $guardianId,
                    'academic_cycle_id' => $cycle->id,

                    'status' => 'active',

                    'source' => 'school_sponsorship',

                    'starts_at' => $membershipStartsAt
                        ->toDateString(),

                    'expires_at' => $cycleEndsAt
                        ->toDateString(),

                    'google_play_product_id' => null,
                    'google_play_order_id' => null,

                    'google_play_purchase_token_hash' => null,

                    'last_verified_at' => null,

                    'sponsored_by_user_id' => $actor->id,

                    'revoked_at' => null,
                    'revoked_by_user_id' => null,
                    'revocation_reason' => null,

                    'updated_by' => $actor->id,
                ]);

                $membership->save();

                $assignmentId = DB::table(
                    'school_family_sponsorship_assignments'
                )->insertGetId([
                    'pool_id' => $pool->id,
                    'school_id' => $schoolId,

                    'academic_cycle_id' => $cycle->id,

                    'guardian_id' => $guardianId,

                    'guardian_membership_id' => (
                        $membership->id
                    ),

                    'status' => 'assigned',

                    'reason' => $reason !== ''
                        ? $reason
                        : null,

                    'assigned_by_user_id' => $actor->id,
                    'assigned_at' => now(),

                    'released_by_user_id' => null,
                    'released_at' => null,
                    'release_reason' => null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->writeAuditLog(
                    actor: $actor,

                    action: (
                        'family_sponsorship.assigned'
                    ),

                    entityType: (
                        GuardianMembership::class
                    ),

                    entityId: (int) $membership->id,

                    oldValues: $oldMembershipValues,

                    newValues: [
                        'membership' => (
                            $membership->fresh()
                                ?->getAttributes()
                        ),

                        'assignment_id' => $assignmentId,

                        'academic_cycle_id' => (
                            (int) $cycle->id
                        ),

                        'primary_student_ids' => (
                            $primaryStudentIds
                        ),

                        'reason' => $reason !== ''
                            ? $reason
                            : null,
                    ],
                );

                return $this->resultPayload(
                    cycle: $cycle,
                    pool: $pool,
                    guardian: $guardian,
                    membership: $membership,
                    assignmentId: $assignmentId,
                    primaryStudentIds: $primaryStudentIds,
                    created: true,
                );
            },
            3
        );
    }

    private function assertActorCanAssign(
        User $actor,
    ): void {
        if ($actor->school_id === null) {
            throw new AuthorizationException(
                'No existe contexto de institución.'
            );
        }

        if (
            ! in_array(
                $actor->role,
                [
                    'school_admin',
                    'director',
                    'superadmin',
                ],
                true
            )
        ) {
            throw new AuthorizationException(
                'El usuario no puede asignar patrocinios.'
            );
        }
    }

 private function activeCycle(
    int $schoolId,
    bool $lockForUpdate,
): ?object {
    $today = now()->toDateString();

    $query = DB::table('academic_cycles')
        ->where('school_id', $schoolId)
        ->where('is_active', true)
        ->where('status', 'active')
        ->whereDate(
            'starts_on',
            '<=',
            $today
        )
        ->whereDate(
            'ends_on',
            '>=',
            $today
        )
        ->orderByDesc('starts_on')
        ->orderByDesc('id');

    if ($lockForUpdate) {
        $query->lockForUpdate();
    }

    return $query->first();
}

    private function poolForCycle(
        int $schoolId,
        int $academicCycleId,
        int $actorId,
    ): object {
        $pool = DB::table(
            'school_family_sponsorship_pools'
        )
            ->where('school_id', $schoolId)
            ->where(
                'academic_cycle_id',
                $academicCycleId
            )
            ->lockForUpdate()
            ->first();

        if ($pool !== null) {
            return $pool;
        }

        $poolId = DB::table(
            'school_family_sponsorship_pools'
        )->insertGetId([
            'school_id' => $schoolId,

            'academic_cycle_id' => (
                $academicCycleId
            ),

            'quota_total' => (int) config(
                'family_memberships.default_sponsorship_quota',
                20
            ),

            'status' => 'active',

            'created_by' => $actorId,
            'updated_by' => $actorId,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table(
            'school_family_sponsorship_pools'
        )
            ->where('id', $poolId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function primaryStudentIds(
        int $schoolId,
        int $guardianId,
    ): array {
        $today = now()->toDateString();

        return DB::table('student_guardians as sg')
            ->join(
                'students as s',
                's.id',
                '=',
                'sg.student_id'
            )
            ->where('s.school_id', $schoolId)
            ->where('s.status', 'active')
            ->where('sg.guardian_id', $guardianId)
            ->where('sg.status', 'active')
            ->where('sg.is_primary', true)
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
            ->orderBy('s.id')
            ->pluck('s.id')
            ->map(
                fn ($studentId): int => (
                    (int) $studentId
                )
            )
            ->values()
            ->all();
    }

    private function resultPayload(
        object $cycle,
        object $pool,
        object $guardian,
        GuardianMembership $membership,
        int $assignmentId,
        array $primaryStudentIds,
        bool $created,
    ): array {
        $used = (int) DB::table(
            'school_family_sponsorship_assignments'
        )
            ->where('pool_id', $pool->id)
            ->where('status', 'assigned')
            ->count();

        return [
            'created' => $created,

            'message' => $created
                ? (
                    'Membresía Family patrocinada '
                    .'correctamente.'
                )
                : (
                    'El tutor ya tenía este patrocinio '
                    .'asignado.'
                ),

            'guardian' => [
                'id' => (int) $guardian->id,

                'name' => trim(
                    $guardian->first_name
                    .' '
                    .$guardian->last_name
                ),

                'primary_student_ids' => (
                    $primaryStudentIds
                ),
            ],

            'cycle' => [
                'id' => (int) $cycle->id,
                'name' => $cycle->name,
                'starts_on' => $cycle->starts_on,
                'ends_on' => $cycle->ends_on,
            ],

            'membership' => [
                'id' => (int) $membership->id,
                'status' => $membership->status,
                'source' => $membership->source,

                'starts_at' => $membership->starts_at
                    ?->toDateString(),

                'expires_at' => $membership->expires_at
                    ?->toDateString(),

                'usable' => $membership->isUsable(),
            ],

            'assignment' => [
                'id' => $assignmentId,
                'status' => 'assigned',
            ],

            'pool' => [
                'id' => (int) $pool->id,

                'quota_total' => (
                    (int) $pool->quota_total
                ),

                'used' => $used,

                'remaining' => max(
                    0,
                    (int) $pool->quota_total - $used
                ),
            ],
        ];
    }

    private function writeAuditLog(
        User $actor,
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues,
        array $newValues,
    ): void {
        $request = request();

        DB::table('audit_logs')->insert([
            'school_id' => $actor->school_id,
            'user_id' => $actor->id,
            'actor_type' => 'user',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,

            'old_values' => $oldValues !== null
                ? json_encode(
                    $oldValues,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
                : null,

            'new_values' => json_encode(
                $newValues,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            ),

            'ip_address' => app()->runningInConsole()
                ? null
                : $request->ip(),

            'user_agent' => app()->runningInConsole()
                ? null
                : mb_substr(
                    (string) $request->userAgent(),
                    0,
                    512
                ),

            'created_at' => now(),
        ]);
    }
}