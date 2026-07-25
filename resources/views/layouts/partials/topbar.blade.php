@php
    $topbarUser = auth()->user();

    $topbarSchoolId = (int) (
        $topbarUser?->school_id
        ?? 0
    );

    $topbarAiVisible = false;

    if (
        $topbarSchoolId > 0
        && in_array(
            $topbarUser?->role,
            [
                'school_admin',
                'director',
            ],
            true
        )
        && \Illuminate\Support\Facades\Route::has(
            'admin.ai.index'
        )
        && (bool) config(
            'schoolpass_ai.enabled'
        )
        && trim(
            (string) config(
                'schoolpass_ai.deepseek.api_key'
            )
        ) !== ''
    ) {
        $topbarAiSettings = app(
            \App\Services\Ai\AiSettingsService::class
        )->forSchool(
            $topbarSchoolId
        );

        $topbarAiVisible =
            (bool) $topbarAiSettings->enabled;
    }
@endphp

<header
    class="navbar navbar-expand-md
           d-print-none sp-topbar"
>
    <div class="container-fluid sp-container">
        <div class="d-flex align-items-center">
            <div>
                <div class="text-secondary small">
                    @yield(
                        'section-label',
                        'Sistema'
                    )
                </div>

                <h2 class="page-title mb-0">
                    @yield(
                        'page-title',
                        'PaseLista'
                    )
                </h2>
            </div>
        </div>

        <div
            class="navbar-nav flex-row order-md-last
                   ms-auto align-items-center gap-2"
        >
            @hasSection('topbar-actions')
                <div
                    class="d-none d-md-flex
                           align-items-center gap-2 me-2"
                >
                    @yield('topbar-actions')
                </div>
            @endif

            @if($topbarAiVisible)
                <div class="nav-item">
                    <a
                        href="{{ route('admin.ai.index') }}"
                        class="nav-link px-2 {{
                            request()->routeIs('admin.ai.*')
                                ? 'active'
                                : ''
                        }}"
                        title="Abrir PaseLista IA"
                        aria-label="Abrir PaseLista IA"
                    >
                        <i class="ti ti-sparkles fs-2"></i>
                    </a>
                </div>
            @endif

            <div class="nav-item">
                @include(
                    'partials.theme-toggle'
                )
            </div>

            <a
                href="#"
                class="nav-link px-2 disabled"
                title="Buscar"
                aria-disabled="true"
            >
                <i class="ti ti-search fs-2"></i>
            </a>

            <a
                href="#"
                class="nav-link px-2 disabled"
                title="Notificaciones"
                aria-disabled="true"
            >
                <i class="ti ti-bell fs-2"></i>
            </a>

            <div class="nav-item dropdown">
                <a
                    href="#"
                    class="nav-link d-flex lh-1
                           text-reset p-0"
                    data-bs-toggle="dropdown"
                    aria-label="Abrir menú de usuario"
                >
                    <span
                        class="avatar avatar-sm bg-primary-lt"
                    >
                        {{
                            strtoupper(
                                substr(
                                    $topbarUser?->name
                                        ?? 'U',
                                    0,
                                    1
                                )
                            )
                        }}
                    </span>

                    <div class="d-none d-xl-block ps-2">
                        <div>
                            {{
                                $topbarUser?->name
                                    ?? 'Usuario'
                            }}
                        </div>

                        <div class="mt-1 small text-secondary">
                            {{
                                match (
                                    $topbarUser?->role
                                        ?? ''
                                ) {
                                    'superadmin' =>
                                        'Superadministrador',

                                    'school_admin' =>
                                        'Administrador escolar',

                                    'director' =>
                                        'Dirección',

                                    'prefect' =>
                                        'Prefectura',

                                    'kiosk' =>
                                        'Kiosco',

                                    'guardian' =>
                                        'Tutor',

                                    'student' =>
                                        'Alumno',

                                    default =>
                                        $topbarUser?->role
                                            ?? '',
                                }
                            }}
                        </div>
                    </div>
                </a>

                <div
                    class="dropdown-menu
                           dropdown-menu-end
                           dropdown-menu-arrow"
                >
                    <div class="dropdown-item-text">
                        <div class="fw-semibold">
                            {{
                                $topbarUser?->name
                                    ?? 'Usuario'
                            }}
                        </div>

                        <div class="small text-secondary">
                            {{
                                $topbarUser?->email
                                    ?? ''
                            }}
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <button
                        type="button"
                        class="dropdown-item"
                        data-schoolpass-theme-toggle
                    >
                        <span
                            class="sp-theme-toggle-icons me-2"
                            aria-hidden="true"
                        >
                            <i
                                class="ti ti-sun
                                       sp-theme-icon
                                       sp-theme-icon-sun"
                            ></i>

                            <i
                                class="ti ti-moon
                                       sp-theme-icon
                                       sp-theme-icon-moon"
                            ></i>
                        </span>

                        <span data-schoolpass-theme-label>
                            Cambiar tema
                        </span>
                    </button>

                    <div class="dropdown-divider"></div>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="dropdown-item text-danger"
                        >
                            <i class="ti ti-logout me-2"></i>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('logout') }}"
                class="d-none d-md-block ms-2"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-outline-danger btn-sm"
                >
                    <i class="ti ti-logout me-1"></i>
                    Salir
                </button>
            </form>
        </div>
    </div>
</header>
