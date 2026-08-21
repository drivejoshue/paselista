<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Family\FamilyAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyNoticeController extends Controller
{
    public function __construct(
        private readonly FamilyAccessService $familyAccessService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $guardian = $this->guardianOrFail($request);

        $accessibleStudentIds = $this->accessibleStudentIds(
            $request
        );

        $noticeIds = $this->accessibleNoticeIds(
            guardian: $guardian,
            studentIds: $accessibleStudentIds,
        );

        $items = DB::table('school_notices as n')
            ->leftJoin(
                'school_notice_reads as r',
                function ($join) use ($guardian): void {
                    $join
                        ->on(
                            'r.school_notice_id',
                            '=',
                            'n.id'
                        )
                        ->where(
                            'r.user_id',
                            '=',
                            $guardian->user_id
                        );
                }
            )
            ->whereIn('n.id', $noticeIds)
            ->where(
                'n.school_id',
                $guardian->school_id
            )
            ->where('n.status', 'published')
            ->where(function ($query): void {
                $query
                    ->whereNull('n.publish_at')
                    ->orWhere(
                        'n.publish_at',
                        '<=',
                        now()
                    );
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('n.expires_at')
                    ->orWhere(
                        'n.expires_at',
                        '>=',
                        now()
                    );
            })
            ->orderByRaw(
                "FIELD(
                    n.priority,
                    'urgent',
                    'important',
                    'normal'
                )"
            )
            ->orderByDesc('n.publish_at')
            ->orderByDesc('n.created_at')
            ->orderByDesc('n.id')
            ->select([
                'n.*',
                'r.read_at',
                'r.acknowledged_at',
            ])
            ->limit(100)
            ->get()
            ->map(
                fn (object $notice): array => (
                    $this->mapNotice($notice)
                )
            );

        return response()->json([
            'ok' => true,
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function show(
        Request $request,
        int $notice,
    ): JsonResponse {
        $guardian = $this->guardianOrFail($request);

        $this->authorizeNotice(
            request: $request,
            guardian: $guardian,
            noticeId: $notice,
        );

        $row = DB::table('school_notices as n')
            ->leftJoin(
                'school_notice_reads as r',
                function ($join) use ($guardian): void {
                    $join
                        ->on(
                            'r.school_notice_id',
                            '=',
                            'n.id'
                        )
                        ->where(
                            'r.user_id',
                            '=',
                            $guardian->user_id
                        );
                }
            )
            ->where('n.id', $notice)
            ->where(
                'n.school_id',
                $guardian->school_id
            )
            ->where('n.status', 'published')
            ->where(function ($query): void {
                $query
                    ->whereNull('n.publish_at')
                    ->orWhere(
                        'n.publish_at',
                        '<=',
                        now()
                    );
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('n.expires_at')
                    ->orWhere(
                        'n.expires_at',
                        '>=',
                        now()
                    );
            })
            ->select([
                'n.*',
                'r.read_at',
                'r.acknowledged_at',
            ])
            ->first();

        if ($row === null) {
            throw new AuthorizationException(
                'Aviso no disponible.'
            );
        }

        return response()->json([
            'ok' => true,
            'notice' => $this->mapNotice($row),
        ]);
    }

    public function modal(
        Request $request,
    ): JsonResponse {
        $guardian = $this->guardianOrFail($request);

        $accessibleStudentIds = $this->accessibleStudentIds(
            $request
        );

        $noticeIds = $this->accessibleNoticeIds(
            guardian: $guardian,
            studentIds: $accessibleStudentIds,
        );

        $row = DB::table('school_notices as n')
            ->leftJoin(
                'school_notice_reads as r',
                function ($join) use ($guardian): void {
                    $join
                        ->on(
                            'r.school_notice_id',
                            '=',
                            'n.id'
                        )
                        ->where(
                            'r.user_id',
                            '=',
                            $guardian->user_id
                        );
                }
            )
            ->whereIn('n.id', $noticeIds)
            ->where(
                'n.school_id',
                $guardian->school_id
            )
            ->where('n.status', 'published')
            ->where('n.show_as_modal', true)
            ->whereNull('r.read_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('n.publish_at')
                    ->orWhere(
                        'n.publish_at',
                        '<=',
                        now()
                    );
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('n.expires_at')
                    ->orWhere(
                        'n.expires_at',
                        '>=',
                        now()
                    );
            })
            ->orderByRaw(
                "FIELD(
                    n.priority,
                    'urgent',
                    'important',
                    'normal'
                )"
            )
            ->orderByDesc('n.publish_at')
            ->orderByDesc('n.created_at')
            ->orderByDesc('n.id')
            ->select([
                'n.*',
                'r.read_at',
                'r.acknowledged_at',
            ])
            ->first();

        return response()->json([
            'ok' => true,

            'notice' => $row !== null
                ? $this->mapNotice($row)
                : null,
        ]);
    }

    public function markAsRead(
        Request $request,
        int $notice,
    ): JsonResponse {
        $guardian = $this->guardianOrFail($request);

        $this->authorizeNotice(
            request: $request,
            guardian: $guardian,
            noticeId: $notice,
        );

        $this->upsertRead(
            guardian: $guardian,
            noticeId: $notice,
            read: true,
            acknowledged: false,
        );

        return response()->json([
            'ok' => true,
            'message' => 'Aviso marcado como leído.',
        ]);
    }

    public function acknowledge(
        Request $request,
        int $notice,
    ): JsonResponse {
        $guardian = $this->guardianOrFail($request);

        $this->authorizeNotice(
            request: $request,
            guardian: $guardian,
            noticeId: $notice,
        );

        $noticeRow = DB::table('school_notices')
            ->where('id', $notice)
            ->where(
                'school_id',
                $guardian->school_id
            )
            ->where('status', 'published')
            ->first();

        if ($noticeRow === null) {
            throw new AuthorizationException(
                'Aviso no disponible.'
            );
        }

        if (! (bool) $noticeRow->requires_ack) {
            return response()->json([
                'ok' => true,

                'message' => (
                    'El aviso no requiere confirmación.'
                ),
            ]);
        }

        $this->upsertRead(
            guardian: $guardian,
            noticeId: $notice,
            read: true,
            acknowledged: true,
        );

        return response()->json([
            'ok' => true,
            'message' => 'Aviso confirmado.',
        ]);
    }

    private function upsertRead(
        object $guardian,
        int $noticeId,
        bool $read,
        bool $acknowledged,
    ): void {
        DB::transaction(
            function () use (
                $guardian,
                $noticeId,
                $read,
                $acknowledged,
            ): void {
                $existing = DB::table(
                    'school_notice_reads'
                )
                    ->where(
                        'school_notice_id',
                        $noticeId
                    )
                    ->where(
                        'user_id',
                        $guardian->user_id
                    )
                    ->lockForUpdate()
                    ->first();

                $payload = [
                    'school_id' => $guardian->school_id,

                    'school_notice_id' => $noticeId,

                    'user_id' => $guardian->user_id,

                    'guardian_id' => $guardian->id,

                    'updated_at' => now(),
                ];

                if ($read) {
                    $payload['read_at'] = (
                        $existing?->read_at
                        ?? now()
                    );
                }

                if ($acknowledged) {
                    $payload['acknowledged_at'] = (
                        $existing?->acknowledged_at
                        ?? now()
                    );
                }

                if ($existing !== null) {
                    DB::table('school_notice_reads')
                        ->where('id', $existing->id)
                        ->update($payload);

                    return;
                }

                $payload['created_at'] = now();

                DB::table('school_notice_reads')
                    ->insert($payload);
            },
            3
        );
    }

    private function authorizeNotice(
        Request $request,
        object $guardian,
        int $noticeId,
    ): void {
        $accessibleStudentIds = $this
            ->accessibleStudentIds($request);

        $noticeIds = $this->accessibleNoticeIds(
            guardian: $guardian,
            studentIds: $accessibleStudentIds,
        );

        if (
            ! in_array(
                $noticeId,
                $noticeIds,
                true
            )
        ) {
            throw new AuthorizationException(
                'Aviso no disponible.'
            );
        }
    }

    private function accessibleNoticeIds(
        object $guardian,
        array $studentIds,
    ): array {
        $groupIds = [];

        if ($studentIds !== []) {
            $groupIds = DB::table('students')
                ->whereIn('id', $studentIds)
                ->where(
                    'school_id',
                    $guardian->school_id
                )
                ->where('status', 'active')
                ->pluck('current_group_id')
                ->filter()
                ->map(
                    fn ($groupId): int => (
                        (int) $groupId
                    )
                )
                ->unique()
                ->values()
                ->all();
        }

        return DB::table(
            'school_notice_targets as target'
        )
            ->join(
                'school_notices as notice',
                'notice.id',
                '=',
                'target.school_notice_id'
            )
            ->where(
                'target.school_id',
                $guardian->school_id
            )
            ->where(
                'notice.school_id',
                $guardian->school_id
            )
            ->where(
                'notice.status',
                'published'
            )
            ->where(function ($query): void {
                $query
                    ->whereNull('notice.publish_at')
                    ->orWhere(
                        'notice.publish_at',
                        '<=',
                        now()
                    );
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('notice.expires_at')
                    ->orWhere(
                        'notice.expires_at',
                        '>=',
                        now()
                    );
            })
            ->where(
                function ($query) use (
                    $guardian,
                    $studentIds,
                    $groupIds,
                ): void {
                    /*
                     * Avisos generales de la institución.
                     *
                     * El middleware family.access garantiza que
                     * el tutor tenga acceso completo a por lo
                     * menos un alumno.
                     */
                    $query->where(
                        'target.target_type',
                        'all_school'
                    );

                    /*
                     * Avisos dirigidos a grupos de alumnos con
                     * acceso Family completo.
                     */
                    $query->orWhere(
                        function ($groupQuery) use (
                            $groupIds
                        ): void {
                            $groupQuery->where(
                                'target.target_type',
                                'group'
                            );

                            if ($groupIds === []) {
                                $groupQuery->whereRaw(
                                    '1 = 0'
                                );

                                return;
                            }

                            $groupQuery->whereIn(
                                'target.target_id',
                                $groupIds
                            );
                        }
                    );

                    /*
                     * Avisos dirigidos a alumnos específicos.
                     *
                     * Aquí se evita que la membresía de un hijo
                     * permita consultar avisos de otro hijo que
                     * permanezca bloqueado.
                     */
                    $query->orWhere(
                        function ($studentQuery) use (
                            $studentIds
                        ): void {
                            $studentQuery->where(
                                'target.target_type',
                                'student'
                            );

                            if ($studentIds === []) {
                                $studentQuery->whereRaw(
                                    '1 = 0'
                                );

                                return;
                            }

                            $studentQuery->whereIn(
                                'target.target_id',
                                $studentIds
                            );
                        }
                    );

                    /*
                     * Avisos enviados directamente a la persona
                     * tutora o a su cuenta de usuario.
                     */
                    $query->orWhere(
                        function ($guardianQuery) use (
                            $guardian
                        ): void {
                            $guardianQuery
                                ->where(
                                    'target.target_type',
                                    'guardian'
                                )
                                ->where(
                                    'target.target_id',
                                    $guardian->id
                                );
                        }
                    );

                    $query->orWhere(
                        function ($userQuery) use (
                            $guardian
                        ): void {
                            $userQuery
                                ->where(
                                    'target.target_type',
                                    'user'
                                )
                                ->where(
                                    'target.target_id',
                                    $guardian->user_id
                                );
                        }
                    );
                }
            )
            ->pluck('target.school_notice_id')
            ->map(
                fn ($noticeId): int => (
                    (int) $noticeId
                )
            )
            ->unique()
            ->values()
            ->all();
    }

    private function accessibleStudentIds(
        Request $request,
    ): array {
        $snapshot = $this->familyAccessSnapshot(
            $request
        );

        return collect(
            $snapshot['students'] ?? []
        )
            ->filter(
                fn (array $student): bool => (
                    (bool) data_get(
                        $student,
                        'access.full_access',
                        false
                    )
                )
            )
            ->pluck('id')
            ->filter(
                fn ($studentId): bool => (
                    $studentId !== null
                )
            )
            ->map(
                fn ($studentId): int => (
                    (int) $studentId
                )
            )
            ->unique()
            ->values()
            ->all();
    }

    private function familyAccessSnapshot(
        Request $request,
    ): array {
        $snapshot = $request->attributes->get(
            'family_access_snapshot'
        );

        if (is_array($snapshot)) {
            return $snapshot;
        }

        $snapshot = $this->familyAccessService
            ->snapshotForUser(
                $this->userOrFail($request)
            );

        $request->attributes->set(
            'family_access_snapshot',
            $snapshot
        );

        return $snapshot;
    }

    private function guardianOrFail(
        Request $request,
    ): object {
        $user = $this->userOrFail($request);

        $guardian = DB::table('guardians')
            ->where('user_id', $user->id)
            ->where(
                'school_id',
                $user->school_id
            )
            ->where('status', 'active')
            ->first();

        if ($guardian === null) {
            throw new AuthorizationException(
                'No existe perfil de tutor activo.'
            );
        }

        return $guardian;
    }

    private function userOrFail(
        Request $request,
    ): User {
        $user = $request->user();

        if (
            ! $user instanceof User
            || $user->role !== 'guardian'
        ) {
            throw new AuthorizationException(
                'Usuario no autorizado para Family.'
            );
        }

        if ($user->school_id === null) {
            throw new AuthorizationException(
                'No existe contexto de institución.'
            );
        }

        return $user;
    }

    private function mapNotice(
        object $notice,
    ): array {
        return [
            'id' => (int) $notice->id,

            'title' => $notice->title,

            'subtitle' => $notice->subtitle,

            'header' => $notice->header,

            'body' => $notice->body,

            'footer' => $notice->footer,

            'banner_url' => $this->fullUrl(
                $notice->banner_path
            ),

            'banner_alt' => $notice->banner_alt,

            'priority' => $notice->priority,

            'show_as_modal' => (
                (bool) $notice->show_as_modal
            ),

            'requires_ack' => (
                (bool) $notice->requires_ack
            ),

            'cta_label' => $notice->cta_label,

            'cta_url' => $notice->cta_url,

            'publish_at' => $notice->publish_at,

            'expires_at' => $notice->expires_at,

            'created_at' => $notice->created_at,

            'read_at' => $notice->read_at,

            'acknowledged_at' => (
                $notice->acknowledged_at
            ),

            'is_read' => (
                $notice->read_at !== null
            ),

            'is_acknowledged' => (
                $notice->acknowledged_at !== null
            ),
        ];
    }

    private function fullUrl(
        ?string $path,
    ): ?string {
        if (
            $path === null
            || trim($path) === ''
        ) {
            return null;
        }

        if (
            str_starts_with(
                $path,
                'http://'
            )
            || str_starts_with(
                $path,
                'https://'
            )
        ) {
            return $path;
        }

        return url($path);
    }
}