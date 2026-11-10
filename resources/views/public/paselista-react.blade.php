<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b192e">
    <meta name="description" content="PaseLista: módulo de control de accesos, asistencia y comunicación escolar integrado a SchoolCore.">
    <title>PaseLista | Accesos y asistencia · SchoolCore</title>
    @viteReactRefresh
    @vite('resources/js/paselista/paselista-landing.tsx')
</head>
<body style="margin:0">
@php
    $configuredLoginUrl = trim((string) config('schoolpass_public.login_url'));
    $publicLoginUrl = $configuredLoginUrl !== ''
        ? $configuredLoginUrl
        : (\Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login'));
    $commercialEmail = trim((string) config('schoolpass_public.commercial_email'));
    $commercialUrl = $commercialEmail !== ''
        ? 'mailto:'.$commercialEmail.'?subject='.rawurlencode('Quiero conocer PaseLista')
        : (\Illuminate\Support\Facades\Route::has('public.support') ? route('public.support') : url('/soporte'));
@endphp
<div
    id="paselista-landing-root"
    data-core-url="{{ url('/') }}"
    data-login-url="{{ $publicLoginUrl }}"
    data-demo-url="{{ $commercialUrl }}"
></div>
</body>
</html>
