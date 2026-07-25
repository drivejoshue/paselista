<?php

namespace App\Console\Commands;

use App\Services\Authorization\SchoolRolePolicy;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;

class AuditSchoolPermissions extends Command
{
    protected $signature = 'schoolpass:permissions-audit
        {--role=director : Rol que se auditará}
        {--only-denied : Mostrar únicamente rutas bloqueadas}';

    protected $description = (
        'Audita las rutas administrativas contra la matriz de permisos.'
    );

    public function handle(
        Router $router,
        SchoolRolePolicy $policy
    ): int {
        $role = (string)
            $this->option('role');

        if (
            ! in_array(
                $role,
                [
                    'school_admin',
                    'director',
                ],
                true
            )
        ) {
            $this->error(
                'Rol inválido. Usa school_admin o director.'
            );

            return self::FAILURE;
        }

        $rows = [];

        foreach (
            $router->getRoutes()
            as $route
        ) {
            $name = $route->getName();

            if (
                ! is_string($name)
                || ! str_starts_with(
                    $name,
                    'admin.'
                )
            ) {
                continue;
            }

            $methods = array_values(
                array_diff(
                    $route->methods(),
                    [
                        'HEAD',
                    ]
                )
            );

            foreach ($methods as $method) {
                $allowed =
                    $policy->allowsRoute(
                        role: $role,
                        routeName: $name,
                        method: $method
                    );

                if (
                    $this->option(
                        'only-denied'
                    )
                    && $allowed
                ) {
                    continue;
                }

                $capability =
                    $policy->capabilityForRoute(
                        $name
                    );

                $rows[] = [
                    $allowed
                        ? 'PERMITIDO'
                        : 'BLOQUEADO',
                    $method,
                    $name,
                    $capability['key']
                        ?? 'SIN DECLARAR',
                ];
            }
        }

        usort(
            $rows,
            fn (array $a, array $b): int =>
                [$a[0], $a[2], $a[1]]
                <=>
                [$b[0], $b[2], $b[1]]
        );

        $this->table(
            [
                'Decisión',
                'Método',
                'Ruta',
                'Capacidad',
            ],
            $rows
        );

        $undeclared = collect($rows)
            ->where(
                3,
                'SIN DECLARAR'
            )
            ->count();

        if ($undeclared > 0) {
            $this->warn(
                sprintf(
                    'Hay %d rutas administrativas sin declarar.',
                    $undeclared
                )
            );

            return self::FAILURE;
        }

        $this->info(
            'Todas las rutas administrativas están declaradas.'
        );

        return self::SUCCESS;
    }
}
