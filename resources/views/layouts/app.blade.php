<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,
                 initial-scale=1,
                 viewport-fit=cover"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'PaseLista')
    </title>

    {{--
        Se ejecuta antes de Vite para evitar que primero
        aparezca el tema claro y después cambie a oscuro.
    --}}
    @include('partials.theme-bootstrap')

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <style>
        /*
        |--------------------------------------------------------------------------
        | Geometría principal
        |--------------------------------------------------------------------------
        |
        | El sidebar real mide 240 px. Tabler estaba desplazando
        | page-wrapper 256 px, lo que dejaba una franja de 16 px.
        | Esta única variable controla ambas medidas.
        |
        */

        :root {
            --sp-sidebar-width: 240px;
        }

        /*
        |--------------------------------------------------------------------------
        | Variables de tema SchoolPass
        |--------------------------------------------------------------------------
        */

        html[data-bs-theme="light"] {
            --sp-page-bg: #f4f6fa;
            --sp-surface: #ffffff;
            --sp-surface-secondary: #f8fafc;
            --sp-sidebar-bg: #ffffff;
            --sp-topbar-bg: rgba(
                255,
                255,
                255,
                .94
            );
            --sp-border: #e2e8f0;
            --sp-text: #172033;
            --sp-muted: #64748b;
            --sp-shadow: rgba(
                15,
                23,
                42,
                .06
            );
        }

        html[data-bs-theme="dark"] {
            --sp-page-bg: #101827;
            --sp-surface: #172234;
            --sp-surface-secondary: #1d2a3e;
            --sp-sidebar-bg: #172234;
            --sp-topbar-bg: rgba(
                23,
                34,
                52,
                .94
            );
            --sp-border: rgba(
                148,
                163,
                184,
                .17
            );
            --sp-text: #e5edf7;
            --sp-muted: #94a3b8;
            --sp-shadow: rgba(
                0,
                0,
                0,
                .18
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Documento y estructura
        |--------------------------------------------------------------------------
        */

        html,
body {
    width: 100%;
    min-height: 100%;

    margin: 0 !important;
    padding: 0 !important;
}

body {
    overflow-x: hidden;

    background-color:
        var(--sp-page-bg) !important;

    color: var(--sp-text);
}

     .page {
    width: 100%;
    max-width: none !important;
    min-height: 100vh;

    margin: 0 !important;
    padding: 0 !important;

    background-color:
        var(--sp-page-bg) !important;
}

        .page-wrapper {
            min-width: 0;
            min-height: 100vh;
            background-color:
                var(--sp-page-bg) !important;
        }

        .sp-page-body {
            min-height: calc(100vh - 64px);
            padding-top: 1.25rem;
            padding-bottom: 2rem;
            background-color:
                var(--sp-page-bg) !important;
        }

        .sp-container {
            width: 100%;
            max-width: none !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Sidebar
        |--------------------------------------------------------------------------
        */

        .sp-sidebar {
            background-color:
                var(--sp-sidebar-bg) !important;
            border-color:
                var(--sp-border) !important;
        }

        .sp-sidebar .navbar-brand,
        .sp-sidebar .nav-link {
            color: var(--sp-text);
        }

        .sp-sidebar .nav-link:hover {
            background-color: rgba(
                59,
                130,
                246,
                .08
            );
        }

        .sp-sidebar .nav-link.active {
            background-color: rgba(
                59,
                130,
                246,
                .14
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Alineación exacta de escritorio
        |--------------------------------------------------------------------------
        */

        @media (min-width: 992px) {
            .page > .sp-sidebar.navbar-vertical.navbar-expand-lg {
                width:
                    var(--sp-sidebar-width) !important;
                min-width:
                    var(--sp-sidebar-width) !important;
                max-width:
                    var(--sp-sidebar-width) !important;
            }

            .page
            > .sp-sidebar.navbar-vertical.navbar-expand-lg
            + .page-wrapper {
                width: calc(
                    100% - var(--sp-sidebar-width)
                ) !important;

                max-width: calc(
                    100% - var(--sp-sidebar-width)
                ) !important;

                margin-left:
                    var(--sp-sidebar-width) !important;

                padding-left: 0 !important;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Topbar
        |--------------------------------------------------------------------------
        */

        .page-wrapper > .sp-topbar {
            position: sticky;
            top: 0;
            left: 0;
            z-index: 1020;

            width: 100% !important;
            max-width: 100% !important;
            min-height: 64px;

            margin: 0 !important;
            padding: 0 !important;

            background-color:
                var(--sp-topbar-bg) !important;

            border-bottom:
                1px solid var(--sp-border);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .page-wrapper
        > .sp-topbar
        > .sp-container {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Componentes generales
        |--------------------------------------------------------------------------
        */

        .card,
        .dropdown-menu,
        .modal-content,
        .offcanvas {
            border-color:
                var(--sp-border);
        }

        html[data-bs-theme="dark"] .card,
        html[data-bs-theme="dark"] .dropdown-menu,
        html[data-bs-theme="dark"] .modal-content,
        html[data-bs-theme="dark"] .offcanvas {
            box-shadow:
                0 10px 30px var(--sp-shadow);
        }

        /*
        |--------------------------------------------------------------------------
        | Control de tema
        |--------------------------------------------------------------------------
        */

        .sp-theme-toggle {
            min-width: 2.25rem;
            min-height: 2.25rem;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: .5rem;
            color: inherit;
            cursor: pointer;
        }

        .sp-theme-toggle:hover {
            background-color: rgba(
                59,
                130,
                246,
                .11
            );
        }

        .sp-theme-toggle-icons {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .sp-theme-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        html[data-bs-theme="light"]
        .sp-theme-icon-sun {
            display: none;
        }

        html[data-bs-theme="dark"]
        .sp-theme-icon-moon {
            display: none;
        }

        /*
        |--------------------------------------------------------------------------
        | Tablet y móvil
        |--------------------------------------------------------------------------
        */

        @media (max-width: 991.98px) {
            .page > .sp-sidebar.navbar-vertical {
                width: 100% !important;
                min-width: 0 !important;
                max-width: none !important;
            }

            .page
            > .sp-sidebar.navbar-vertical
            + .page-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                padding-left: 0 !important;
            }
        }

        @media (max-width: 767.98px) {
            .sp-page-body {
                padding-top: 1rem;
            }

            .page-wrapper > .sp-topbar {
                min-height: 58px;
            }

            .page-wrapper
            > .sp-topbar
            > .sp-container {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }

        .sp-brand-logo {
    width: 36px;
    height: 36px;
    object-fit: contain;
    flex-shrink: 0;
}

/*
|--------------------------------------------------------------------------
| Tema claro
|--------------------------------------------------------------------------
*/

.sp-brand-logo-light {
    display: block;
}

.sp-brand-logo-dark {
    display: none;
}

/*
|--------------------------------------------------------------------------
| Tema oscuro
|--------------------------------------------------------------------------
*/

[data-bs-theme="dark"] .sp-brand-logo-light {
    display: none;
}

[data-bs-theme="dark"] .sp-brand-logo-dark {
    display: block;
}
    </style>

    @stack('styles')
</head>

<body>
    @include(
        'partials.support-impersonation-bar'
    )

    @include(
        'partials.license-warning'
    )

    <div class="page">
        @include(
            'layouts.partials.sidebar'
        )

        <div class="page-wrapper">
            @include(
                'layouts.partials.topbar'
            )

            <main class="page-body sp-page-body">
                <div class="container-fluid sp-container">
                    @yield('content')
                </div>
            </main>

            @include(
                'layouts.partials.footer'
            )
        </div>
    </div>

    @include(
        'layouts.partials.theme-controller'
    )

    @stack('scripts')
</body>
</html>
