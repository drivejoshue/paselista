<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SchoolCore\ModuleAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolModule
{
    public function __construct(
        private readonly ModuleAccessService $modules,
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $moduleCode
    ): Response {
        $user = $request->user();

        abort_unless(
            $user && $user->school_id,
            403
        );

        abort_unless(
            $this->modules->canAccess(
                (int) $user->school_id,
                $moduleCode
            ),
            403,
            'El módulo no está habilitado para esta institución.'
        );

        return $next($request);
    }
}
