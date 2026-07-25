<?php

namespace App\Http\Middleware;

use App\Services\Authorization\SchoolRolePolicy;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Roles globalmente reconocidos por PaseLista.
     */
    private const VALID_ROLES = [
        'superadmin',
        'school_admin',
        'director',
        'prefect',
        'kiosk',
        'guardian',
        'student',
    ];

    public function __construct(
        private readonly SchoolRolePolicy $policy
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $this->unauthenticatedResponse(
                $request
            );
        }

        $invalidRoles = array_values(
            array_diff(
                $roles,
                self::VALID_ROLES
            )
        );

        if ($invalidRoles !== []) {
            report(
                new RuntimeException(
                    'RoleMiddleware recibió roles inválidos: '
                    .implode(', ', $invalidRoles)
                )
            );

            return $this->forbiddenResponse(
                $request,
                'La ruta tiene una configuración de permisos inválida.'
            );
        }

        if ($user->status !== 'active') {
            $user->currentAccessToken()?->delete();

            return $this->forbiddenResponse(
                $request,
                'Tu cuenta está inactiva o bloqueada.'
            );
        }

        if (
            ! in_array(
                $user->role,
                $roles,
                true
            )
        ) {
            return $this->forbiddenResponse(
                $request,
                'No tienes permiso para acceder a esta sección.'
            );
        }

        if (! $user->school_id) {
            return $this->forbiddenResponse(
                $request,
                $user->role === 'superadmin'
                    ? (
                        'El superadministrador debe entrar mediante '
                        .'una sesión de soporte para operar una escuela.'
                    )
                    : (
                        'El usuario no tiene una institución asignada.'
                    )
            );
        }

        $schoolStatus = DB::table('schools')
            ->where(
                'id',
                $user->school_id
            )
            ->value('status');

        if ($schoolStatus === null) {
            $user->currentAccessToken()?->delete();

            return $this->forbiddenResponse(
                $request,
                'La institución asignada no existe.'
            );
        }

        if ($schoolStatus !== 'active') {
            $user->currentAccessToken()?->delete();

            return $this->forbiddenResponse(
                $request,
                'La institución está suspendida o inactiva.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Permisos administrativos por capacidad
        |--------------------------------------------------------------------------
        |
        | Ya no existe un bloqueo general de escritura para director.
        | Cada ruta /admin debe estar declarada en config/school_permissions.php.
        |
        */

        if (
            $request->is('admin/*')
            && in_array(
                $user->role,
                [
                    'school_admin',
                    'director',
                    'superadmin',
                ],
                true
            )
        ) {
            $routeName = $request
                ->route()
                ?->getName();

            if (
                ! $this->policy->allowsRoute(
                    role: (string) $user->role,
                    routeName: is_string($routeName)
                        ? $routeName
                        : null,
                    method: $request->method()
                )
            ) {
                return $this->forbiddenResponse(
                    $request,
                    $this->policy->denialMessage(
                        role: (string) $user->role,
                        routeName: is_string($routeName)
                            ? $routeName
                            : null
                    )
                );
            }
        }

        return $next($request);
    }

    private function unauthenticatedResponse(
        Request $request
    ): JsonResponse|RedirectResponse {
        if (
            $request->expectsJson()
            || $request->is('api/*')
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        return redirect()
            ->route('login')
            ->with(
                'error',
                'Debes iniciar sesión.'
            );
    }

    private function forbiddenResponse(
        Request $request,
        string $message
    ): JsonResponse|Response {
        if (
            $request->expectsJson()
            || $request->is('api/*')
        ) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }
}
