<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAiAccess
{
    private const ALLOWED_ROLES = [
        'superadmin',
        'school_admin',
        'director',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);
        abort_unless($user->status === 'active', 403, 'Tu cuenta está inactiva.');
        abort_unless(
            in_array($user->role, self::ALLOWED_ROLES, true),
            403,
            'No tienes acceso a PaseLista IA.'
        );
        abort_unless($user->school_id, 403, 'Debes operar dentro de una escuela.');

        $schoolStatus = DB::table('schools')
            ->where('id', $user->school_id)
            ->value('status');

        abort_unless(
            $schoolStatus === 'active',
            403,
            'La escuela está suspendida o inactiva.'
        );

        return $next($request);
    }
}
