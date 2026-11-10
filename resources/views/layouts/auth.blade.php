<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Acceso') · {{ config('app.name') }}
    </title>

    @include('partials.theme-bootstrap')

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <style>
        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(37, 99, 235, .20),
                    transparent 32rem
                ),
                linear-gradient(
                    145deg,
                    #081426 0%,
                    #10213a 54%,
                    #16345c 100%
                );
        }

        /* CONTENEDOR GENERAL */

        .sp-auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns:
                minmax(0, 1.05fr)
                minmax(28rem, .95fr);
        }

        /* PANEL IZQUIERDO */

        .sp-auth-intro {
            min-height: 100vh;
            padding: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .sp-auth-intro-inner {
            width: 100%;
            max-width: 39rem;
        }

        /* IDENTIDAD TEMPORAL SC */

        .sc-auth-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 88px;
            height: 88px;

            border-radius: 22px;

            background: linear-gradient(
                145deg,
                #2563eb,
                #1d4ed8
            );

            box-shadow:
                0 12px 30px rgba(37, 99, 235, .25),
                inset 0 1px 0 rgba(255,255,255,.18);

            color: #fff;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -2px;
            line-height: 1;

            flex-shrink: 0;
            user-select: none;
        }

        .sc-auth-logo-small {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            font-size: 20px;
            letter-spacing: -1px;
        }

        /* IDENTIDAD Y TEXTO */

        .sp-auth-eyebrow {
            margin-top: 2rem;
            color: rgba(255,255,255,.78);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .sp-auth-hero-title {
            margin: 1.5rem 0 1rem;

            font-size: clamp(
                3rem,
                5.5vw,
                5rem
            );

            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.045em;

            overflow-wrap: anywhere;

            background: linear-gradient(
                90deg,
                #ffffff 0%,
                #bfdbfe 65%,
                #93c5fd 100%
            );

            background-clip: text;
            -webkit-background-clip: text;

            color: #fff;
            -webkit-text-fill-color: transparent;
        }

        .sp-auth-description {
            max-width: 34rem;
            margin-top: 1.4rem;

            color: rgba(255,255,255,.73);

            font-size: 1.05rem;
            line-height: 1.75;
        }

        /* BENEFICIOS */

        .sp-auth-benefits {
            margin-top: 2.25rem;
            display: grid;
            gap: .9rem;
        }

        .sp-auth-benefit {
            display: flex;
            align-items: center;
            gap: .8rem;

            color: rgba(255,255,255,.87);
            font-size: .9rem;
        }

        .sp-auth-benefit-icon {
            width: 2.25rem;
            height: 2.25rem;

            border-radius: .75rem;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.12);
        }

        /* PANEL DERECHO */

        .sp-auth-panel {
            min-height: 100vh;
            padding: 2.5rem 2rem;

            display: flex;
            align-items: center;
            justify-content: center;

            background: rgba(247,249,253,.98);
            border-left: 1px solid rgba(255,255,255,.10);
        }

        [data-bs-theme="dark"] .sp-auth-panel {
            background: rgba(16,24,39,.98);
        }

        .sp-auth-card {
            width: 100%;
            max-width: 31rem;
            margin: auto 0;
        }

        .sp-auth-card .card {
            border: 1px solid var(--tblr-border-color);
            border-radius: 1.15rem;

            box-shadow:
                0 1.5rem 4rem rgba(15,23,42,.12);
        }

        /* IDENTIDAD MÓVIL */

        .sp-auth-mobile-brand {
            display: none;

            align-items: center;
            justify-content: center;
            gap: .85rem;

            margin-bottom: 1.5rem;
        }

        .sp-auth-mobile-name {
            font-size: 1.3rem;
            font-weight: 750;
            line-height: 1.2;
        }

        .sp-auth-mobile-subtitle {
            color: var(--tblr-secondary-color);
            font-size: .85rem;
        }

        /* PIE */

        .sp-auth-footer {
            margin-top: 1.5rem;
            color: var(--tblr-secondary-color);
            font-size: .75rem;
            text-align: center;
            line-height: 1.6;
        }

        /* TABLET Y MÓVIL */

        @media (max-width: 991.98px) {
            .sp-auth-shell {
                display: block;
            }

            .sp-auth-intro {
                display: none;
            }

            .sp-auth-panel {
                min-height: 100vh;
                padding: 1.25rem;
                border-left: 0;
            }

            .sp-auth-mobile-brand {
                display: flex;
            }

            .sp-auth-card {
                max-width: 31rem;
            }
        }

        @media (max-width: 575.98px) {
            .sp-auth-panel {
                padding: 1rem;
            }

            .sp-auth-card .card {
                border-radius: 1rem;
            }

            .sp-auth-footer {
                padding: 0 1rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

<div class="sp-auth-shell">

    {{-- PANEL IZQUIERDO: IDENTIDAD SCHOOLCORE --}}
    <section class="sp-auth-intro">

        <div class="sp-auth-intro-inner">

            {{-- Isotipo temporal --}}
            <div
                class="sc-auth-logo"
                aria-label="{{ config('app.name') }}"
            >
                SC
            </div>

            <div class="sp-auth-eyebrow">
                Plataforma educativa institucional
            </div>

            <h1 class="sp-auth-hero-title">
                {{ config('app.name') }}
            </h1>

            <p class="sp-auth-description">
                Una plataforma para la administración
                escolar, la gestión académica y la
                comunicación entre instituciones,
                docentes, estudiantes y familias.
            </p>

            <div class="sp-auth-benefits">

                <div class="sp-auth-benefit">

                    <span class="sp-auth-benefit-icon">
                        <i class="ti ti-shield-check"></i>
                    </span>

                    <span>
                        Acceso seguro mediante roles
                        y permisos institucionales
                    </span>

                </div>

                <div class="sp-auth-benefit">

                    <span class="sp-auth-benefit-icon">
                        <i class="ti ti-school"></i>
                    </span>

                    <span>
                        Planes académicos, docentes,
                        grupos y calificaciones
                    </span>

                </div>

                <div class="sp-auth-benefit">

                    <span class="sp-auth-benefit-icon">
                        <i class="ti ti-users"></i>
                    </span>

                    <span>
                        Servicios escolares para
                        estudiantes y familias
                    </span>

                </div>

                <div class="sp-auth-benefit">

                    <span class="sp-auth-benefit-icon">
                        <i class="ti ti-building-community"></i>
                    </span>

                    <span>
                        Administración independiente
                        para cada institución
                    </span>

                </div>

            </div>

        </div>

    </section>

    {{-- PANEL DERECHO: FORMULARIO --}}
    <section class="sp-auth-panel">

        <div class="sp-auth-card">

            {{--
                Identidad móvil para las páginas de
                recuperación y otros formularios.

                El login ya muestra SC dentro de
                auth/login.blade.php, por eso evitamos
                duplicar la marca en esa pantalla.
            --}}
            @unless(request()->routeIs('login'))

                <div class="sp-auth-mobile-brand">

                    <div
                        class="sc-auth-logo sc-auth-logo-small"
                        aria-hidden="true"
                    >
                        SC
                    </div>

                    <div>
                        <div class="sp-auth-mobile-name">
                            {{ config('app.name') }}
                        </div>

                        <div class="sp-auth-mobile-subtitle">
                            Plataforma educativa
                        </div>
                    </div>

                </div>

            @endunless

            {{-- CONTENIDO DE CADA PÁGINA --}}
            @yield('content')

            {{-- PIE INSTITUCIONAL --}}
            <div class="sp-auth-footer">
                © {{ date('Y') }}
                {{ config('app.name') }}
                · Acceso institucional protegido
            </div>

        </div>

    </section>

</div>

@stack('scripts')

</body>
</html>
