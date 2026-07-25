@php
    $user = auth()->user();
    $role = $user?->role;

    $policy = app(
        \App\Services\Authorization\SchoolRolePolicy::class
    );

    $isAdministrativeRole = in_array(
        $role,
        [
            'superadmin',
            'school_admin',
            'director',
        ],
        true
    );

    $sectionLabelClass =
        'px-3 pt-3 pb-1 text-uppercase text-secondary fw-bold';

    $sectionLabelStyle =
        'font-size: .67rem; letter-spacing: .08em;';

    $sections = [];

    /*
    |--------------------------------------------------------------------------
    | Administración escolar y dirección
    |--------------------------------------------------------------------------
    |
    | La visibilidad de cada ruta administrativa se resuelve con
    | SchoolRolePolicy. El director ya no depende de manage_only.
    |
    */

    if ($isAdministrativeRole) {
        $sections = [
            [
                'label' => 'Inicio',

                'items' => [
                    [
                        'title' => 'Dashboard',
                        'route' => 'admin.dashboard',

                        'active' => [
                            'admin.dashboard',
                        ],

                        'icon' => 'ti-layout-dashboard',
                    ],
                ],
            ],

            [
                'label' => 'Operación diaria',

                'items' => [
                    [
                        'title' => 'Escáner de prefectura',
                        'route' => 'prefect.access',

                        'active' => [
                            'prefect.access',
                        ],

                        'icon' => 'ti-scan',
                    ],

                    [
                        'title' => 'Kiosco de acceso',
                        'route' => 'kiosk.access',

                        'active' => [
                            'kiosk.access',
                        ],

                        'icon' => 'ti-device-desktop',
                    ],

                    [
                        'title' => 'Asistencia diaria',
                        'route' => 'admin.reports.attendance',

                        'active' => [
                            'admin.reports.attendance',
                        ],

                        'icon' => 'ti-calendar-check',
                    ],

                    [
                        'title' => 'Pantalla en vivo',
                        'route' => 'admin.direction-live.index',

                        'active' => [
                            'admin.direction-live.*',
                        ],

                        'icon' => 'ti-device-desktop-analytics',
                    ],
                ],
            ],

            [
                'label' => 'Alumnos y familias',

                'items' => [
                    [
                        'title' => 'Alumnos',
                        'route' => 'admin.students.index',

                        'active' => [
                            'admin.students.*',
                        ],

                        'icon' => 'ti-users',
                    ],

                    [
                        'title' => 'Tutores',
                        'route' => 'admin.guardians.index',

                        'active' => [
                            'admin.guardians.*',
                        ],

                        'icon' => 'ti-user-heart',
                    ],

                    [
                        'title' => 'Credenciales',
                        'route' => 'admin.credentials.index',

                        'active' => [
                            'admin.credentials.*',
                        ],

                        'icon' => 'ti-id-badge-2',
                    ],

                    [
                        'title' => 'Avisos escolares',
                        'route' => 'admin.notices.index',

                        'active' => [
                            'admin.notices.*',
                        ],

                        'icon' => 'ti-speakerphone',
                    ],
                ],
            ],

            [
                'label' => 'Ciclo y organización',

                'items' => [
                    [
                        'title' => 'Ciclos escolares',
                        'route' => 'admin.cycles.index',

                        'active' => [
                            'admin.cycles.*',
                            'admin.cycle-enrollments.*',
                        ],

                        'icon' => 'ti-calendar-stats',
                    ],

                    [
                        'title' => 'Grupos y horarios',
                        'route' => 'admin.groups.index',

                        'active' => [
                            'admin.groups.*',
                        ],

                        'icon' => 'ti-users-group',
                    ],

                    [
                        'title' => 'Calendario escolar',
                        'route' => 'admin.calendar.index',

                        'active' => [
                            'admin.calendar.*',
                        ],

                        'icon' => 'ti-calendar-event',
                    ],

                    [
                        'title' => 'Promoción y reinscripción',
                        'route' => 'admin.promotions.index',

                        'active' => [
                            'admin.promotions.*',
                            'admin.cycle-enrollments.*',
                        ],

                        'icon' => 'ti-arrow-big-up-lines',
                    ],
                ],
            ],

            [
                'label' => 'Control de acceso',

                'items' => [
                    [
                        'title' => 'Áreas',
                        'route' => 'admin.areas.index',

                        'active' => [
                            'admin.areas.*',
                        ],

                        'icon' => 'ti-map-pin',
                    ],

                    [
                        'title' => 'Reglas de acceso',
                        'route' => 'admin.area-rules.index',

                        'active' => [
                            'admin.area-rules.*',
                        ],

                        'icon' => 'ti-shield-check',
                    ],

                    [
                        'title' => 'Dispositivos',
                        'route' => 'admin.devices.index',

                        'active' => [
                            'admin.devices.*',
                        ],

                        'icon' => 'ti-device-tablet',
                    ],

                    [
                        'title' => 'Usuarios del sistema',
                        'route' => 'admin.users.index',

                        'active' => [
                            'admin.users.*',
                        ],

                        'icon' => 'ti-users-cog',
                    ],

                    [
                        'title' => 'Bitácora de accesos',
                        'route' => 'admin.reports.access',

                        'active' => [
                            'admin.reports.access',
                        ],

                        'icon' => 'ti-door-enter',
                    ],
                ],
            ],

            [
                'label' => 'Reportes y seguimiento',

                'items' => [
                    [
                        'title' => 'Asistencia mensual',
                        'route' =>
                            'admin.reports.monthly-attendance.index',

                        'active' => [
                            'admin.reports.monthly-attendance.*',
                        ],

                        'icon' => 'ti-calendar-month',
                    ],

                    [
                        'title' => 'Reporte individual',
                        'route' =>
                            'admin.reports.student-individual.index',

                        'active' => [
                            'admin.reports.student-individual.*',
                        ],

                        'icon' => 'ti-user-search',
                    ],

                    [
                        'title' => 'Incidencias',
                        'route' =>
                            'admin.reports.student-incidents.index',

                        'active' => [
                            'admin.reports.student-incidents.*',
                        ],

                        'icon' => 'ti-alert-triangle',
                    ],

                    [
                        'title' => 'Analítica',
                        'route' =>
                            'admin.reports.analytics.index',

                        'active' => [
                            'admin.reports.analytics.*',
                        ],

                        'icon' => 'ti-chart-histogram',
                    ],

                    [
                        'title' => 'Exportaciones',
                        'route' =>
                            'admin.reports.exports.index',

                        'active' => [
                            'admin.reports.exports.*',
                        ],

                        'icon' => 'ti-file-export',
                    ],

                    [
                        'title' => 'Auditoría de reportes',
                        'route' =>
                            'admin.reports.export-audit.index',

                        'active' => [
                            'admin.reports.export-audit.*',
                        ],

                        'icon' => 'ti-file-check',
                    ],
                ],
            ],

            [
                'label' => 'Inteligencia artificial',

                'items' => [
                    [
                        'title' => 'PaseLista IA',
                        'route' => 'admin.ai.index',

                        'active' => [
                            'admin.ai.*',
                        ],

                        'icon' => 'ti-brain',
                    ],
                ],
            ],

            [
                'label' => 'Administración',

                'items' => [
                    [
                        'title' => 'Importar alumnos',
                        'route' =>
                            'admin.imports.students.index',

                        'active' => [
                            'admin.imports.*',
                        ],

                        'icon' => 'ti-file-import',
                    ],

                    [
                        'title' => 'Permisos por rol',
                        'route' =>
                            'admin.role-permissions.index',

                        'active' => [
                            'admin.role-permissions.*',
                        ],

                        'icon' => 'ti-shield-check',

                        /*
                         * Esta ruta está registrada solo para
                         * school_admin y director.
                         */
                        'roles' => [
                            'school_admin',
                            'director',
                        ],
                    ],

                    [
                        'title' => 'Configuración y herramientas',
                        'route' => 'admin.tools.index',

                        'active' => [
                            'admin.tools.*',
                        ],

                        'icon' => 'ti-tool',
                    ],

                    [
                        'title' => 'Licencia',
                        'route' => 'admin.license.show',

                        'active' => [
                            'admin.license.*',
                        ],

                        'icon' => 'ti-license',
                    ],
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prefectura
    |--------------------------------------------------------------------------
    */

    elseif ($role === 'prefect') {
        $sections = [
            [
                'label' => 'Operación',

                'items' => [
                    [
                        'title' => 'Control de acceso',
                        'route' => 'prefect.access',

                        'active' => [
                            'prefect.access',
                        ],

                        'icon' => 'ti-scan',
                    ],
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tutor
    |--------------------------------------------------------------------------
    */

    elseif ($role === 'guardian') {
        $sections = [
            [
                'label' => 'Familia',

                'items' => [
                    [
                        'title' => 'Mis hijos',
                        'route' => 'guardian.home',

                        'active' => [
                            'guardian.home',
                        ],

                        'icon' => 'ti-user-heart',
                    ],
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Alumno
    |--------------------------------------------------------------------------
    */

    elseif ($role === 'student') {
        $sections = [
            [
                'label' => 'Alumno',

                'items' => [
                    [
                        'title' => 'Mi credencial',
                        'route' => 'student.home',

                        'active' => [
                            'student.home',
                        ],

                        'icon' => 'ti-id-badge',
                    ],
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Kiosco
    |--------------------------------------------------------------------------
    */

    elseif ($role === 'kiosk') {
        $sections = [
            [
                'label' => 'Kiosco',

                'items' => [
                    [
                        'title' => 'Punto de acceso',
                        'route' => 'kiosk.access',

                        'active' => [
                            'kiosk.access',
                        ],

                        'icon' => 'ti-device-desktop',
                    ],
                ],
            ],
        ];
    }
@endphp

<aside
    class="navbar navbar-vertical navbar-expand-lg sp-sidebar"
>
    <div class="container-fluid">

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu"
            aria-expanded="false"
            aria-label="Abrir menú"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark">
            <a
                href="{{ route('home') }}"
                class="text-decoration-none"
            >
                <span class="d-flex align-items-center gap-2">

                    <span class="avatar avatar-sm bg-primary text-white">
                        <i class="ti ti-shield-lock"></i>
                    </span>

                    <span>
                        PaseLista
                    </span>

                </span>
            </a>
        </h1>

        <div
            class="collapse navbar-collapse"
            id="sidebar-menu"
        >
            <ul class="navbar-nav pt-lg-3">

                @foreach($sections as $section)
                    @php
                        $visibleItems = collect(
                            $section['items']
                        )
                            ->filter(
                                function (
                                    array $item
                                ) use (
                                    $policy,
                                    $role
                                ): bool {
                                    /*
                                     * No dibuja enlaces de rutas
                                     * que todavía no existan.
                                     */
                                    if (
                                        ! \Illuminate\Support\Facades\Route::has(
                                            $item['route']
                                        )
                                    ) {
                                        return false;
                                    }

                                    /*
                                     * Restricción adicional de un
                                     * elemento concreto.
                                     */
                                    if (
                                        isset($item['roles'])
                                        && ! in_array(
                                            $role,
                                            $item['roles'],
                                            true
                                        )
                                    ) {
                                        return false;
                                    }

                                    /*
                                     * Las rutas ajenas al prefijo admin
                                     * conservan su visibilidad por rol.
                                     */
                                    if (
                                        ! str_starts_with(
                                            $item['route'],
                                            'admin.'
                                        )
                                    ) {
                                        return true;
                                    }

                                    /*
                                     * Las rutas administrativas usan la
                                     * misma política que RoleMiddleware.
                                     */
                                    return $policy->allowsRoute(
                                        role: (string) $role,
                                        routeName:
                                            $item['route'],
                                        method: 'GET'
                                    );
                                }
                            )
                            ->values();
                    @endphp

                    @if($visibleItems->isNotEmpty())
                        <li
                            class="{{ $sectionLabelClass }}"
                            style="{{ $sectionLabelStyle }}"
                        >
                            {{ $section['label'] }}
                        </li>

                        @foreach($visibleItems as $item)
                            @php
                                $itemIsActive = collect(
                                    $item['active']
                                )
                                    ->contains(
                                        fn (
                                            string $pattern
                                        ): bool =>
                                            request()->routeIs(
                                                $pattern
                                            )
                                    );
                            @endphp

                            <li class="nav-item">
                                <a
                                    class="nav-link {{
                                        $itemIsActive
                                            ? 'active'
                                            : ''
                                    }}"
                                    href="{{ route(
                                        $item['route']
                                    ) }}"
                                >
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti {{ $item['icon'] }}"></i>
                                    </span>

                                    <span class="nav-link-title">
                                        {{ $item['title'] }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</aside>
