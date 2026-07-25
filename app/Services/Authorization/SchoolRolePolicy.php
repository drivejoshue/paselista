<?php

namespace App\Services\Authorization;

use Illuminate\Support\Str;

class SchoolRolePolicy
{
    public function allowsRoute(
        string $role,
        ?string $routeName,
        string $method
    ): bool {
        if ($role === 'superadmin') {
            return true;
        }

        if (
            $role === 'school_admin'
            && (bool) config(
                'school_permissions.security.school_admin_bypass',
                true
            )
        ) {
            return true;
        }

        if (
            $role !== 'director'
            || ! is_string($routeName)
            || $routeName === ''
        ) {
            return false;
        }

        $capability = $this->capabilityForRoute(
            $routeName
        );

        if ($capability === null) {
            return ! (bool) config(
                'school_permissions.security.deny_unknown_admin_routes_for_director',
                true
            );
        }

        if (
            ! in_array(
                $role,
                $capability['roles'] ?? [],
                true
            )
        ) {
            return false;
        }

        $methods = array_map(
            'strtoupper',
            $capability['methods'] ?? ['*']
        );

        return in_array('*', $methods, true)
            || in_array(
                strtoupper($method),
                $methods,
                true
            );
    }

    public function denialMessage(
        string $role,
        ?string $routeName
    ): string {
        if (
            $role === 'director'
            && is_string($routeName)
        ) {
            $capability = $this->capabilityForRoute(
                $routeName
            );

            if ($capability !== null) {
                return sprintf(
                    'El perfil de director no tiene permiso para: %s.',
                    mb_strtolower(
                        (string) (
                            $capability['label']
                            ?? 'esta operación'
                        )
                    )
                );
            }
        }

        return (
            'Esta operación no está autorizada para el rol actual.'
        );
    }

    public function capabilityForRoute(
        string $routeName
    ): ?array {
        foreach (
            $this->capabilities()
            as $key => $capability
        ) {
            foreach (
                $capability['routes'] ?? []
                as $pattern
            ) {
                if (
                    Str::is(
                        $pattern,
                        $routeName
                    )
                ) {
                    return [
                        'key' => $key,
                        ...$capability,
                    ];
                }
            }
        }

        return null;
    }

    public function roleCan(
        string $role,
        string $capabilityKey
    ): bool {
        $capability = $this
            ->capabilities()[
                $capabilityKey
            ] ?? null;

        if (! is_array($capability)) {
            return false;
        }

        return in_array(
            $role,
            $capability['roles'] ?? [],
            true
        );
    }

    public function matrix(): array
    {
        $roles = config(
            'school_permissions.roles',
            []
        );

        return collect(
            $this->capabilities()
        )
            ->map(
                function (
                    array $capability,
                    string $key
                ) use ($roles): array {
                    $allowedRoles = [];

                    foreach (
                        array_keys($roles)
                        as $role
                    ) {
                        $allowedRoles[$role] =
                            in_array(
                                $role,
                                $capability['roles']
                                    ?? [],
                                true
                            );
                    }

                    return [
                        'key' => $key,
                        'label' =>
                            $capability['label']
                            ?? $key,
                        'group' =>
                            $capability['group']
                            ?? 'Otros',
                        'description' =>
                            $capability['description']
                            ?? '',
                        'roles' => $allowedRoles,
                        'routes' =>
                            $capability['routes']
                            ?? [],
                    ];
                }
            )
            ->sortBy([
                ['group', 'asc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();
    }

    public function capabilities(): array
    {
        return config(
            'school_permissions.capabilities',
            []
        );
    }
}
