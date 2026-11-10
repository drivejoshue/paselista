<?php
declare(strict_types=1);

namespace App\Services\SchoolCore;

use App\Services\Authorization\SchoolRolePolicy;
use Illuminate\Support\Facades\Route;

class NavigationService
{
    public function __construct(
        private readonly SchoolRolePolicy $roles,
        private readonly ModuleAccessService $modules,
    ) {
    }

    public function forSchool(int $schoolId, string $role): array
    {
        if ($schoolId <= 0) {
            return [];
        }

        $definitions = [
            // Funciones obligatorias del núcleo.
            ['students', 'Alumnos', 'admin.students.index', 'core', 'core'],
            ['guardians', 'Familias y tutores', 'admin.guardians.index', 'core', 'core'],
            ['cycles', 'Ciclos escolares', 'admin.cycles.index', 'core', 'core'],
            ['groups', 'Grados y grupos', 'admin.groups.index', 'core', 'core'],
            ['calendar', 'Calendario escolar', 'admin.calendar.index', 'core', 'core'],
            ['promotions', 'Promoción y reinscripción', 'admin.promotions.index', 'core', 'core'],
            ['users', 'Usuarios', 'admin.users.index', 'core', 'core'],
            ['roles', 'Permisos por rol', 'admin.role-permissions.index', 'core', 'core'],

            // Módulo PaseLista.
            ['attendance', 'PaseLista · Asistencia', 'admin.dashboard', 'modules', 'attendance'],
            [
    'academic',
    'Academic · Calificaciones',
    'admin.academic.index',
    'modules',
    'academic',
],
        ];

        $result = [];

        foreach ($definitions as [
            $key,
            $label,
            $routeName,
            $group,
            $moduleCode,
        ]) {
            if (! $this->routeAllowed($role, $routeName)) {
                continue;
            }

            if (! $this->modules->canAccess($schoolId, $moduleCode)) {
                continue;
            }

            $result[] = [
                'key' => $key,
                'label' => $label,
                'group' => $group,
                'url' => route($routeName),
            ];
        }

        // Módulos gratuitos activados explícitamente por escuela.
        foreach (config('schoolcore_modules.free', []) as $code => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if (($definition['placement'] ?? null) !== 'sidebar') {
                continue;
            }

            if (! in_array($role, $definition['roles'] ?? [], true)) {
                continue;
            }

            if (! $this->modules->canAccess($schoolId, (string) $code)) {
                continue;
            }

            $routeName = (string) ($definition['route'] ?? '');

            if (! $this->routeAllowed($role, $routeName)) {
                continue;
            }

            $result[] = [
                'key' => (string) $code,
                'label' => (string) ($definition['label'] ?? $code),
                'group' => 'free',
                'url' => route($routeName),
            ];
        }

        return $result;
    }

    private function routeAllowed(string $role, string $routeName): bool
    {
        if ($routeName === '' || ! Route::has($routeName)) {
            return false;
        }

        return $this->roles->allowsRoute(
            $role,
            $routeName,
            'GET'
        );
    }
}
