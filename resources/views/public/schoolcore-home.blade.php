<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SchoolCore integra administración escolar, gestión académica, asistencia, accesos y servicios para familias en una plataforma modular.">
    <meta name="theme-color" content="#08152e">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name') }} · Una plataforma para toda tu escuela">
    <meta property="og:description" content="Gestión institucional, calificaciones y módulos para tu escuela.">
    <title>{{ config('app.name') }} · Una plataforma para toda tu escuela</title>
    <link rel="icon" type="image/webp" href="{{ asset('landing/images/schoolcore/mark.webp') }}">
    @vite('resources/js/schoolcore-public/main.tsx')
</head>
<body>
@php
    $commercialEmail = trim((string) config('schoolpass_public.commercial_email'));
    $demoUrl = $commercialEmail !== ''
        ? 'mailto:' . $commercialEmail . '?subject=' . rawurlencode('Demostración de SchoolCore')
        : route('public.support');
@endphp
<div
    id="schoolcore-public-root"
    data-app-name="{{ config('app.name') }}"
    data-assets-url="{{ asset('landing/images/schoolcore') }}"
    data-login-url="{{ route('login') }}"
    data-support-url="{{ route('public.support') }}"
    data-privacy-url="{{ route('public.privacy') }}"
    data-deletion-url="{{ route('public.data-deletion') }}"
    data-demo-url="{{ $demoUrl }}"
    data-paselista-url="{{ \Illuminate\Support\Facades\Route::has('public.paselista') ? route('public.paselista') : '#paselista' }}"
></div>
</body>
</html>
