<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sysadmin\ResetSchoolAdministratorPasswordRequest;
use App\Http\Requests\Sysadmin\StoreSchoolAdministratorRequest;
use App\Http\Requests\Sysadmin\UpdateSchoolAdministratorRequest;
use App\Models\School;
use App\Services\Auditing\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolAdministratorController extends Controller
{
    private const ADMINISTRATOR_ROLES = [
        'school_admin',
        'director',
    ];

    private const ACTIVE_STATUS = 'active';
    private const BLOCKED_STATUS = 'blocked';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

    public function index(School $school): View
    {
        $administrators = DB::table('users')
            ->where('school_id', $school->id)
            ->whereIn(
                'role',
                self::ADMINISTRATOR_ROLES
            )
            ->orderByRaw(
                "CASE role
                    WHEN 'school_admin' THEN 1
                    WHEN 'director' THEN 2
                    ELSE 3
                END"
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'phone',
                'role',
                'status',
                'must_change_password',
                'email_verified_at',
                'password_changed_at',
                'last_login_at',
                'created_at',
                'updated_at',
            ]);

        return view(
            'sysadmin.schools.administrators.index',
            compact(
                'school',
                'administrators'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreSchoolAdministratorRequest $request,
        School $school,
    ): RedirectResponse {
        $data = $request->validated();

        $email = mb_strtolower(
            trim($data['email'])
        );

        $phone = $this->nullableString(
            $data['phone'] ?? null
        );

        $administratorId = DB::transaction(
            function () use (
                $data,
                $school,
                $email,
                $phone,
            ): int {
                return (int) DB::table('users')
                    ->insertGetId([
                        'school_id' => $school->id,

                        'name' => trim(
                            $data['name']
                        ),

                        'email' => $email,
                        'phone' => $phone,

                        'email_verified_at' => now(),

                        'password' => Hash::make(
                            $data['password']
                        ),

                        'must_change_password' => false,
                        'password_changed_at' => now(),
                        'last_login_at' => null,

                        'role' => $data['role'],
                        'status' => self::ACTIVE_STATUS,

                        'remember_token' => null,

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        );

        $this->auditLogger->record(
            action: 'school_administrator_created',
            schoolId: $school->id,
            actorId: $request->user()->id,
            actorType: 'superadmin',
            entityType: 'users',
            entityId: $administratorId,

            oldValues: [],

            newValues: [
                'name' => trim(
                    $data['name']
                ),
                'email' => $email,
                'phone' => $phone,
                'role' => $data['role'],
                'status' => self::ACTIVE_STATUS,
            ],

            request: $request,
        );

        return redirect()
            ->route(
                'sysadmin.schools.administrators.index',
                $school
            )
            ->with(
                'status',
                'Administrador creado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateSchoolAdministratorRequest $request,
        School $school,
        int $administrator,
    ): RedirectResponse {
        $current = $this->administratorOrFail(
            $school,
            $administrator
        );

        $data = $request->validated();

        $email = mb_strtolower(
            trim($data['email'])
        );

        $phone = $this->nullableString(
            $data['phone'] ?? null
        );

        /*
         * No permitimos dejar a la escuela sin ningún
         * administrador/director activo.
         */
        if (
            $current->status === self::ACTIVE_STATUS
            && $data['status'] !== self::ACTIVE_STATUS
        ) {
            $this->ensureAnotherActiveAdministrator(
                $school,
                $administrator
            );
        }

        $roleChanged =
            $current->role !== $data['role'];

        $statusChanged =
            $current->status !== $data['status'];

        DB::transaction(
            function () use (
                $school,
                $administrator,
                $data,
                $email,
                $phone,
                $roleChanged,
                $statusChanged,
            ): void {
                DB::table('users')
                    ->where('school_id', $school->id)
                    ->where('id', $administrator)
                    ->lockForUpdate()
                    ->firstOrFail();

                $update = [
                    'name' => trim(
                        $data['name']
                    ),

                    'email' => $email,
                    'phone' => $phone,

                    'role' => $data['role'],
                    'status' => $data['status'],

                    'updated_at' => now(),
                ];

                /*
                 * Al bloquear una cuenta eliminamos también
                 * el remember token del login web.
                 */
                if (
                    $data['status']
                    !== self::ACTIVE_STATUS
                ) {
                    $update['remember_token'] = null;
                }

                DB::table('users')
                    ->where('school_id', $school->id)
                    ->where('id', $administrator)
                    ->update($update);

                /*
                 * Un cambio de rol o estado invalida
                 * sesiones API existentes.
                 */
                if (
                    $roleChanged
                    || $statusChanged
                ) {
                    DB::table('personal_access_tokens')
                        ->where(
                            'tokenable_type',
                            'App\\Models\\User'
                        )
                        ->where(
                            'tokenable_id',
                            $administrator
                        )
                        ->delete();
                }
            }
        );

        $this->auditLogger->record(
            action: 'school_administrator_updated',
            schoolId: $school->id,
            actorId: $request->user()->id,
            actorType: 'superadmin',
            entityType: 'users',
            entityId: $administrator,

            oldValues: [
                'name' => $current->name,
                'email' => $current->email,
                'phone' => $current->phone,
                'role' => $current->role,
                'status' => $current->status,
            ],

            newValues: [
                'name' => trim(
                    $data['name']
                ),
                'email' => $email,
                'phone' => $phone,
                'role' => $data['role'],
                'status' => $data['status'],
            ],

            request: $request,
        );

        return redirect()
            ->route(
                'sysadmin.schools.administrators.index',
                $school
            )
            ->with(
                'status',
                'Administrador actualizado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Restablecer contraseña
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        ResetSchoolAdministratorPasswordRequest $request,
        School $school,
        int $administrator,
    ): RedirectResponse {
        $current = $this->administratorOrFail(
            $school,
            $administrator
        );

        $data = $request->validated();

        DB::transaction(
            function () use (
                $administrator,
                $data,
            ): void {
                DB::table('users')
                    ->where('id', $administrator)
                    ->update([
                        'password' => Hash::make(
                            $data['password']
                        ),

                        'must_change_password' => false,
                        'password_changed_at' => now(),

                        'remember_token' => null,

                        'updated_at' => now(),
                    ]);

                DB::table('personal_access_tokens')
                    ->where(
                        'tokenable_type',
                        'App\\Models\\User'
                    )
                    ->where(
                        'tokenable_id',
                        $administrator
                    )
                    ->delete();
            }
        );

        $this->auditLogger->record(
            action:
                'school_administrator_password_reset',

            schoolId: $school->id,

            actorId:
                $request->user()->id,

            actorType:
                'superadmin',

            entityType:
                'users',

            entityId:
                $administrator,

            oldValues: [],

            newValues: [
                'email' => $current->email,
                'tokens_revoked' => true,
                'password_changed_at' => now()
                    ->toDateTimeString(),
            ],

            request: $request,
        );

        return redirect()
            ->route(
                'sysadmin.schools.administrators.index',
                $school
            )
            ->with(
                'status',
                'Contraseña restablecida y sesiones revocadas.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolver administrador
    |--------------------------------------------------------------------------
    */

    private function administratorOrFail(
        School $school,
        int $administratorId,
    ): object {
        $administrator = DB::table('users')
            ->where(
                'id',
                $administratorId
            )
            ->where(
                'school_id',
                $school->id
            )
            ->whereIn(
                'role',
                self::ADMINISTRATOR_ROLES
            )
            ->first();

        abort_unless(
            $administrator,
            404
        );

        return $administrator;
    }

    /*
    |--------------------------------------------------------------------------
    | Protección último administrador activo
    |--------------------------------------------------------------------------
    */

    private function ensureAnotherActiveAdministrator(
        School $school,
        int $administratorId,
    ): void {
        $others = DB::table('users')
            ->where(
                'school_id',
                $school->id
            )
            ->whereIn(
                'role',
                self::ADMINISTRATOR_ROLES
            )
            ->where(
                'status',
                self::ACTIVE_STATUS
            )
            ->where(
                'id',
                '<>',
                $administratorId
            )
            ->count();

        if ($others === 0) {
            throw ValidationException::withMessages([
                'status' => (
                    'No puedes bloquear al último '
                    .'administrador activo de la escuela.'
                ),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function nullableString(
        mixed $value
    ): ?string {
        $value = trim(
            (string) ($value ?? '')
        );

        return $value === ''
            ? null
            : $value;
    }
}