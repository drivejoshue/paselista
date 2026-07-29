
@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $role = (string) ($user?->role ?? '');

    /*
    |--------------------------------------------------------------------------
    | Roles de PaseLista V1
    |--------------------------------------------------------------------------
    |
    | Director:
    |   Opera la escuela: alumnos, tutores, ciclos, asistencia, accesos,
    |   reportes, avisos, IA e importaciones.
    |
    | School Admin:
    |   Tiene además administración técnica: usuarios, dispositivos,
    |   permisos, herramientas y licencia.
    |
    */

    $schoolManagementRoles = [
        'superadmin',
        'school_admin',
        'director',
    ];

    $platformAdminRoles = [
        'superadmin',
        'school_admin',
    ];

    $sectionLabelClass =
        'px-3 pt-3 pb-1 text-uppercase text-secondary fw-bold';

    $sectionLabelStyle =
        'font-size: .67rem; letter-spacing: .08em;';

    $sections = [];

    /*
    |--------------------------------------------------------------------------
    | Administración escolar / Dirección
    |--------------------------------------------------------------------------
    */

    if (in_array($role, $schoolManagementRoles, true)) {
        $sections = [

            /*
            |--------------------------------------------------------------------------
            | Inicio
            |--------------------------------------------------------------------------
            */

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

                        'roles' => $schoolManagementRoles,
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Operación diaria
            |--------------------------------------------------------------------------
            */

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

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Kiosco de acceso',
                        'route' => 'kiosk.access',

                        'active' => [
                            'kiosk.access',
                        ],

                        'icon' => 'ti-device-desktop',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Asistencia diaria',
                        'route' => 'admin.reports.attendance',

                        'active' => [
                            'admin.reports.attendance',
                        ],

                        'icon' => 'ti-calendar-check',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Pantalla en vivo',
                        'route' => 'admin.direction-live.index',

                        'active' => [
                            'admin.direction-live.*',
                        ],

                        'icon' => 'ti-device-desktop-analytics',

                        'roles' => $schoolManagementRoles,
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Alumnos y familias
            |--------------------------------------------------------------------------
            */

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

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Tutores',
                        'route' => 'admin.guardians.index',

                        'active' => [
                            'admin.guardians.*',
                        ],

                        'icon' => 'ti-user-heart',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Credenciales',
                        'route' => 'admin.credentials.index',

                        'active' => [
                            'admin.credentials.*',
                        ],

                        'icon' => 'ti-id-badge-2',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Avisos escolares',
                        'route' => 'admin.notices.index',

                        'active' => [
                            'admin.notices.*',
                        ],

                        'icon' => 'ti-speakerphone',

                        'roles' => $schoolManagementRoles,
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Ciclo y organización
            |--------------------------------------------------------------------------
            */

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

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Grupos y horarios',
                        'route' => 'admin.groups.index',

                        'active' => [
                            'admin.groups.*',
                        ],

                        'icon' => 'ti-users-group',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Calendario escolar',
                        'route' => 'admin.calendar.index',

                        'active' => [
                            'admin.calendar.*',
                        ],

                        'icon' => 'ti-calendar-event',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Promoción y reinscripción',
                        'route' => 'admin.promotions.index',

                        'active' => [
                            'admin.promotions.*',
                            'admin.cycle-enrollments.*',
                        ],

                        'icon' => 'ti-arrow-big-up-lines',

                        'roles' => $schoolManagementRoles,
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Control de acceso
            |--------------------------------------------------------------------------
            */

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

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Reglas de acceso',
                        'route' => 'admin.area-rules.index',

                        'active' => [
                            'admin.area-rules.*',
                        ],

                        'icon' => 'ti-shield-check',

                        'roles' => $schoolManagementRoles,
                    ],

                    /*
                    |--------------------------------------------------------------------------
                    | Solo administración técnica
                    |--------------------------------------------------------------------------
                    */

                    [
                        'title' => 'Dispositivos',
                        'route' => 'admin.devices.index',

                        'active' => [
                            'admin.devices.*',
                        ],

                        'icon' => 'ti-device-tablet',

                        'roles' => $platformAdminRoles,
                    ],

                    [
                        'title' => 'Usuarios del sistema',
                        'route' => 'admin.users.index',

                        'active' => [
                            'admin.users.*',
                        ],

                        'icon' => 'ti-users-cog',

                        'roles' => $platformAdminRoles,
                    ],

                    [
                        'title' => 'Bitácora de accesos',
                        'route' => 'admin.reports.access',

                        'active' => [
                            'admin.reports.access',
                        ],

                        'icon' => 'ti-door-enter',

                        'roles' => $schoolManagementRoles,
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Reportes y seguimiento
            |--------------------------------------------------------------------------
            */

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

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Reporte individual',
                        'route' =>
                            'admin.reports.student-individual.index',

                        'active' => [
                            'admin.reports.student-individual.*',
                        ],

                        'icon' => 'ti-user-search',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Incidencias',
                        'route' =>
                            'admin.reports.student-incidents.index',

                        'active' => [
                            'admin.reports.student-incidents.*',
                        ],

                        'icon' => 'ti-alert-triangle',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Analítica',
                        'route' =>
                            'admin.reports.analytics.index',

                        'active' => [
                            'admin.reports.analytics.*',
                        ],

                        'icon' => 'ti-chart-histogram',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Exportaciones',
                        'route' =>
                            'admin.reports.exports.index',

                        'active' => [
                            'admin.reports.exports.*',
                        ],

                        'icon' => 'ti-file-export',

                        'roles' => $schoolManagementRoles,
                    ],

                    [
                        'title' => 'Auditoría de reportes',
                        'route' =>
                            'admin.reports.export-audit.index',

                        'active' => [
                            'admin.reports.export-audit.*',
                        ],

                        'icon' => 'ti-file-check',

                        'roles' => $schoolManagementRoles,
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Inteligencia artificial
            |--------------------------------------------------------------------------
            */

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

                        'roles' => $schoolManagementRoles,
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Administración escolar
            |--------------------------------------------------------------------------
            */

            [
                'label' => 'Administración',

                'items' => [
                    /*
                    |--------------------------------------------------------------------------
                    | El director sí puede importar alumnos.
                    |--------------------------------------------------------------------------
                    */

                    [
                        'title' => 'Importar alumnos',
                        'route' =>
                            'admin.imports.students.index',

                        'active' => [
                            'admin.imports.*',
                        ],

                        'icon' => 'ti-file-import',

                        'roles' => $schoolManagementRoles,
                    ],

                    /*
                    |--------------------------------------------------------------------------
                    | Funciones exclusivas de administración de plataforma
                    |--------------------------------------------------------------------------
                    */

                    [
                        'title' => 'Permisos por rol',
                        'route' =>
                            'admin.role-permissions.index',

                        'active' => [
                            'admin.role-permissions.*',
                        ],

                        'icon' => 'ti-shield-lock',

                        'roles' => $platformAdminRoles,
                    ],

                    [
                        'title' => 'Configuración y herramientas',
                        'route' => 'admin.tools.index',

                        'active' => [
                            'admin.tools.*',
                        ],

                        'icon' => 'ti-settings',

                        'roles' => $platformAdminRoles,
                    ],

                    [
                        'title' => 'Licencia',
                        'route' => 'admin.license.show',

                        'active' => [
                            'admin.license.*',
                        ],

                        'icon' => 'ti-license',

                        'roles' => $platformAdminRoles,
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

                        'roles' => [
                            'prefect',
                        ],
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

                        'roles' => [
                            'guardian',
                        ],
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

                        'roles' => [
                            'student',
                        ],
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

                        'roles' => [
                            'kiosk',
                        ],
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

            {{-- Logo para tema claro --}}
            <img
                src="{{ asset('images/ic_logo.png') }}"
                alt="PaseLista"
                class="sp-brand-logo sp-brand-logo-light"
            >

            {{-- Logo para tema oscuro --}}
            <img
                src="{{ asset('images/logo.png') }}"
                alt="PaseLista"
                class="sp-brand-logo sp-brand-logo-dark"
            >

            <span class="fw-semibold sp-brand-name">
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
                        /*
                         * La sidebar decide únicamente qué enlaces mostrar.
                         *
                         * La autorización real continúa en middleware,
                         * policies y controladores.
                         */

                        $visibleItems = collect(
                            $section['items']
                        )
                            ->filter(
                                function (
                                    array $item
                                ) use (
                                    $role
                                ): bool {
                                    /*
                                     * Nunca mostrar rutas inexistentes.
                                     */
                                    if (
                                        ! Route::has(
                                            $item['route']
                                        )
                                    ) {
                                        return false;
                                    }

                                    /*
                                     * Cada elemento define explícitamente
                                     * qué roles pueden verlo.
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

                                    return true;
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
                                    href="{{
                                        route(
                                            $item['route']
                                        )
                                    }}"
                                >
                                    <span
                                        class="
                                            nav-link-icon
                                            d-md-none
                                            d-lg-inline-block
                                        "
                                    >
                                        <i
                                            class="ti {{
                                                $item['icon']
                                            }}"
                                        ></i>
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