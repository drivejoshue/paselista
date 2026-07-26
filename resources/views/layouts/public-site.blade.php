<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'PaseLista')</title>
    <meta
        name="description"
        content="@yield('meta-description', 'PaseLista: control de acceso, asistencia y comunicación escolar.')"
    >
    <meta name="theme-color" content="#388da8">

    {{-- Favicons QuickStart / PaseLista --}}
    <link href="{{ asset('landing/paselista/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('landing/paselista/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    {{-- Fonts usadas por QuickStart --}}
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet"
    >

    {{--
        Compatibilidad temporal con las vistas legales actuales.
        ARCO / Soporte / Privacidad todavía utilizan utilidades Tailwind y Unicons.
        Cuando esas vistas se migren a Bootstrap, estos dos archivos pueden retirarse.
    --}}
    <link href="{{ asset('landing/assets/css/tailwind.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/libs/@iconscout/unicons/css/line.css') }}" rel="stylesheet">

    {{-- Vendor CSS de QuickStart --}}
    <link
        href="{{ asset('landing/paselista/vendor/bootstrap/css/bootstrap.min.css') }}"
        rel="stylesheet"
    >
    <link
        href="{{ asset('landing/paselista/vendor/bootstrap-icons/bootstrap-icons.css') }}"
        rel="stylesheet"
    >
    <link
        href="{{ asset('landing/paselista/vendor/aos/aos.css') }}"
        rel="stylesheet"
    >
    <link
        href="{{ asset('landing/paselista/vendor/glightbox/css/glightbox.min.css') }}"
        rel="stylesheet"
    >
    <link
        href="{{ asset('landing/paselista/vendor/swiper/swiper-bundle.min.css') }}"
        rel="stylesheet"
    >

    {{-- CSS principal QuickStart --}}
    <link
        href="{{ asset('landing/paselista/css/main.css') }}"
        rel="stylesheet"
    >

    {{--
        Estilos propios de PaseLista.
        Se carga después de QuickStart para que nuestras reglas tengan prioridad.
    --}}
    <link
        href="{{ asset('landing/css/schoolpass-site.css') }}"
        rel="stylesheet"
    >

    @stack('styles')
</head>

@php
    $configuredLoginUrl = trim((string) config('schoolpass_public.login_url'));

    $loginUrl = $configuredLoginUrl !== ''
        ? $configuredLoginUrl
        : (\Illuminate\Support\Facades\Route::has('login')
            ? route('login')
            : url('/login'));

    $commercialEmail = trim((string) config('schoolpass_public.commercial_email'));

    $commercialUrl = $commercialEmail !== ''
        ? 'mailto:'.$commercialEmail.'?subject='.rawurlencode('Quiero conocer PaseLista')
        : route('public.support');

    $homeUrl = route('public.home');

    $isHome = request()->routeIs('public.home');
    $isSupport = request()->routeIs('public.support');
    $isPrivacy = request()->routeIs('public.privacy');
    $isDataDeletion = request()->routeIs('public.data-deletion')
        || request()->routeIs('public.privacy-requests.*');
@endphp

<body class="@yield('body-class', 'index-page')">

    {{-- Header QuickStart adaptado a PaseLista --}}
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">

            <a
                href="{{ $homeUrl }}"
                class="logo d-flex align-items-center me-auto"
                aria-label="PaseLista"
            >
                {{-- Sustituye por tu logo final cuando esté exportado --}}
                @if(file_exists(public_path('landing/images/schoolpass/ic_logo.png')))
                    <img
                        src="{{ asset('landing/images/schoolpass/ic_logo.png') }}"
                        alt="PaseLista"
                    >
                @endif

                <h1 class="sitename">PaseLista</h1>
            </a>

            <nav id="navmenu" class="navmenu" aria-label="Navegación principal">
                <ul>
                    <li>
                        <a
                            href="{{ $homeUrl }}#inicio"
                            @class(['active' => $isHome])
                        >
                            Inicio
                        </a>
                    </li>

                    <li>
                        <a href="{{ $homeUrl }}#plataforma">
                            Plataforma
                        </a>
                    </li>

                    <li>
                        <a href="{{ $homeUrl }}#aplicaciones">
                            Aplicaciones
                        </a>
                    </li>

                    <li>
                        <a href="{{ $homeUrl }}#funciones">
                            Funciones
                        </a>
                    </li>

                    <li>
                        <a href="{{ $homeUrl }}#seguridad">
                            Seguridad
                        </a>
                    </li>

                    <li class="dropdown">
                        <a
                            href="#"
                            @class([
                                'active' => $isSupport || $isPrivacy || $isDataDeletion
                            ])
                        >
                            <span>Ayuda</span>
                            <i class="bi bi-chevron-down toggle-dropdown"></i>
                        </a>

                        <ul>
                            <li>
                                <a href="{{ route('public.support') }}">
                                    Soporte
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('public.privacy') }}">
                                    Aviso de privacidad
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('public.data-deletion') }}">
                                    Derechos ARCO
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="d-xl-none">
                        <a href="{{ $loginUrl }}">
                            Iniciar sesión
                        </a>
                    </li>
                </ul>

                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a
                class="btn-getstarted d-none d-sm-inline-flex"
                href="{{ $commercialUrl }}"
            >
                Solicitar demo
            </a>

            <a
                href="{{ $loginUrl }}"
                class="ps-login-button d-none d-xl-inline-flex"
            >
                <i class="bi bi-box-arrow-in-right"></i>
                Entrar
            </a>

        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

    {{-- Footer QuickStart adaptado a PaseLista --}}
    <footer id="footer" class="footer position-relative light-background">
        <div class="container footer-top">
            <div class="row gy-4">

                <div class="col-lg-5 col-md-12 footer-about">
                    <a
                        href="{{ $homeUrl }}"
                        class="logo d-flex align-items-center"
                    >
                        <span class="sitename">PaseLista</span>
                    </a>

                    <p class="mt-3">
                        Plataforma para control de accesos, asistencia,
                        salidas y comunicación escolar desde una sola operación.
                    </p>

                    <div class="footer-contact pt-2">
                        @if(config('schoolpass_public.commercial_email'))
                            <p>
                                <strong>Información:</strong>
                                <span>{{ config('schoolpass_public.commercial_email') }}</span>
                            </p>
                        @endif

                        @if(config('schoolpass_public.support_email'))
                            <p>
                                <strong>Soporte:</strong>
                                <span>{{ config('schoolpass_public.support_email') }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="col-lg-2 col-6 footer-links">
                    <h4>Plataforma</h4>

                    <ul>
                        <li>
                            <a href="{{ $homeUrl }}#plataforma">
                                Plataforma
                            </a>
                        </li>
                        <li>
                            <a href="{{ $homeUrl }}#aplicaciones">
                                Staff y Family
                            </a>
                        </li>
                        <li>
                            <a href="{{ $homeUrl }}#funciones">
                                Funciones
                            </a>
                        </li>
                        <li>
                            <a href="{{ $loginUrl }}">
                                Iniciar sesión
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-2 col-6 footer-links">
                    <h4>Ayuda</h4>

                    <ul>
                        <li>
                            <a href="{{ route('public.support') }}">
                                Soporte
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('public.privacy') }}">
                                Aviso de privacidad
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('public.data-deletion') }}">
                                Derechos ARCO
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-12 footer-links">
                    <h4>Privacidad y datos</h4>

                    <p>
                        La institución mantiene el control de su operación
                        y PaseLista proporciona la infraestructura tecnológica.
                    </p>

                    @if(config('schoolpass_public.privacy_email'))
                        <p class="mt-3 mb-0">
                            <a
                                href="mailto:{{ config('schoolpass_public.privacy_email') }}"
                                class="ps-footer-email"
                            >
                                <i class="bi bi-envelope"></i>
                                {{ config('schoolpass_public.privacy_email') }}
                            </a>
                        </p>
                    @endif
                </div>

            </div>
        </div>

        <div class="container copyright text-center mt-4">
            <p>
                © <span>{{ date('Y') }}</span>
                <strong class="px-1 sitename">PaseLista</strong>
                <span>Todos los derechos reservados.</span>
            </p>

            <div class="credits">
                @if(config('schoolpass_public.privacy_version'))
                    Privacidad v{{ config('schoolpass_public.privacy_version') }}
                    <span class="mx-1">·</span>
                @endif

                {{--
                    QuickStart incluye atribución a BootstrapMade en su versión gratuita.
                    Elimina/modifica esta línea únicamente si tu licencia permite hacerlo.
                --}}
                Diseño base
                <a
                    href="https://bootstrapmade.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    BootstrapMade
                </a>
            </div>
        </div>
    </footer>

    {{-- Scroll Top de QuickStart --}}
    <a
        href="#"
        id="scroll-top"
        class="scroll-top d-flex align-items-center justify-content-center"
        aria-label="Volver arriba"
    >
        <i class="bi bi-arrow-up-short"></i>
    </a>

    {{-- Preloader de QuickStart --}}
    <div id="preloader"></div>

    {{-- Vendor JS QuickStart --}}
    <script
        src="{{ asset('landing/paselista/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"
    ></script>

    <script
        src="{{ asset('landing/paselista/vendor/aos/aos.js') }}"
    ></script>

    <script
        src="{{ asset('landing/paselista/vendor/glightbox/js/glightbox.min.js') }}"
    ></script>

    <script
        src="{{ asset('landing/paselista/vendor/swiper/swiper-bundle.min.js') }}"
    ></script>

    {{-- JS principal QuickStart --}}
    <script
        src="{{ asset('landing/paselista/js/main.js') }}"
    ></script>

    {{-- Ajustes mínimos propios del layout PaseLista --}}
    <style>
        .ps-login-button {
            align-items: center;
            gap: 7px;
            margin-left: 12px;
            color: var(--nav-color);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition: color .2s ease;
        }

        .ps-login-button:hover {
            color: var(--accent-color);
        }

        .ps-footer-email {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        @media (max-width: 1199px) {
            .header .btn-getstarted {
                margin-left: 10px;
                margin-right: 42px;
            }
        }
    </style>

    @stack('scripts')
</body>
</html>