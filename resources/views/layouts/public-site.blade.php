<!doctype html>
<html lang="es" class="light scroll-smooth" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'PaseLista')</title>
    <meta name="description" content="@yield('meta-description', 'PaseLista: control de acceso, asistencia y comunicación escolar.')">
    <meta name="theme-color" content="#2563eb">

    <link rel="shortcut icon" href="{{ asset('landing/assets/images/favicon.ico') }}">
    <link href="{{ asset('landing/assets/libs/@iconscout/unicons/css/line.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/libs/@mdi/font/css/materialdesignicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/css/tailwind.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/css/schoolpass-site.css') }}" rel="stylesheet">

    @stack('styles')
</head>
@php
    $configuredLoginUrl = trim((string) config('schoolpass_public.login_url'));
    $loginUrl = $configuredLoginUrl !== ''
        ? $configuredLoginUrl
        : (\Illuminate\Support\Facades\Route::has('login')
            ? route('login')
            : url('/login'));
@endphp
<body class="font-nunito text-base text-slate-900 bg-white dark:text-white dark:bg-slate-950">
    <nav id="sp-public-nav" class="sp-public-nav">
        <div class="container relative">
            <div class="sp-public-nav-inner">
                <div class="sp-public-nav-left">
                    <a href="{{ $loginUrl }}" class="sp-login-link">
                        <i class="uil uil-sign-in-alt"></i>
                        <span>Entrar a PaseLista</span>
                    </a>

                    <a href="{{ route('public.home') }}" class="sp-public-brand" aria-label="PaseLista">
                        <span class="sp-public-brand-mark">PL</span>
                        <span>
                            <strong>PaseLista</strong>
                            <small>Acceso escolar inteligente</small>
                        </span>
                    </a>
                </div>

                <button
                    type="button"
                    id="sp-public-menu-button"
                    class="sp-public-menu-button"
                    aria-controls="sp-public-menu"
                    aria-expanded="false"
                    aria-label="Abrir menú"
                >
                    <i class="uil uil-bars"></i>
                </button>

                <div id="sp-public-menu" class="sp-public-menu">
                    <a href="{{ route('public.home') }}#funciones">Funciones</a>
                    <a href="{{ route('public.home') }}#aplicaciones">Aplicaciones</a>
                    <a href="{{ route('public.home') }}#seguridad">Privacidad</a>
                    <a href="{{ route('public.support') }}">Soporte</a>
                    <button type="button" id="sp-theme-toggle" class="sp-theme-toggle" aria-label="Cambiar tema">
                        <i class="uil uil-moon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="sp-public-footer">
        <div class="container relative">
            <div class="grid lg:grid-cols-12 md:grid-cols-2 grid-cols-1 gap-8 py-14">
                <div class="lg:col-span-5">
                    <a href="{{ route('public.home') }}" class="sp-footer-brand">
                        <span class="sp-public-brand-mark">SP</span>
                        <span>PaseLista</span>
                    </a>
                    <p class="mt-5 text-slate-400 max-w-md">
                        Plataforma para control de accesos, asistencia, entrega segura de alumnos y comunicación con tutores.
                    </p>
                </div>

                <div class="lg:col-span-3">
                    <h3 class="text-white font-semibold">Plataforma</h3>
                    <ul class="sp-footer-links">
                        <li><a href="{{ route('public.home') }}#funciones">Funciones</a></li>
                        <li><a href="{{ route('public.home') }}#aplicaciones">Staff y Family</a></li>
                        <li><a href="{{ $loginUrl }}">Iniciar sesión</a></li>
                        <li><a href="{{ route('public.support') }}">Soporte</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-4">
                    <h3 class="text-white font-semibold">Privacidad y datos</h3>
                    <ul class="sp-footer-links">
                        <li><a href="{{ route('public.privacy') }}">Aviso de privacidad</a></li>
                        <li><a href="{{ route('public.data-deletion') }}">Derechos ARCO y eliminación</a></li>
                        <li>
                            <a href="mailto:{{ config('schoolpass_public.privacy_email') }}">
                                {{ config('schoolpass_public.privacy_email') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sp-footer-bottom">
                <span>© {{ date('Y') }} PaseLista. Todos los derechos reservados.</span>
                <span>Versión de privacidad {{ config('schoolpass_public.privacy_version') }}</span>
            </div>
        </div>
    </footer>

    <a href="#" id="sp-back-to-top" class="sp-back-to-top" aria-label="Volver arriba">
        <i class="uil uil-arrow-up"></i>
    </a>

    <script src="{{ asset('landing/js/schoolpass-site.js') }}"></script>
    @stack('scripts')
</body>
</html>
