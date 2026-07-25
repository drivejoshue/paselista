@extends('layouts.public-site')

@section('title', 'Soporte · PaseLista')
@section('meta-description', 'Canales de soporte y acceso a PaseLista.')

@section('content')
<section class="sp-legal-hero">
    <div class="container relative">
        <span class="sp-eyebrow">Ayuda y soporte</span>
        <h1>¿Necesitas entrar o reportar un problema?</h1>
        <p>Usa el canal correspondiente para que tu solicitud llegue al equipo o institución adecuada.</p>
    </div>
</section>

<section class="sp-legal-section">
    <div class="container relative">
        <div class="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-6">
            <article class="sp-support-card">
                <span><i class="uil uil-sign-in-alt"></i></span>
                <h2>Acceso a la plataforma</h2>
                <p>Ingresa con la cuenta entregada por tu institución educativa.</p>
                @php
                    $configuredLoginUrl = trim((string) config('schoolpass_public.login_url'));
                    $supportLoginUrl = $configuredLoginUrl !== ''
                        ? $configuredLoginUrl
                        : (\Illuminate\Support\Facades\Route::has('login')
                            ? route('login')
                            : url('/login'));
                @endphp
                <a href="{{ $supportLoginUrl }}" class="sp-text-link">Entrar a PaseLista <i class="uil uil-arrow-right"></i></a>
            </article>

            <article class="sp-support-card">
                <span><i class="uil uil-school"></i></span>
                <h2>Cuenta o datos escolares</h2>
                <p>Para altas, vinculaciones, correcciones de alumno o permisos de tutor, contacta primero a tu escuela.</p>
            </article>

            <article class="sp-support-card">
                <span><i class="uil uil-wrench"></i></span>
                <h2>Problema técnico</h2>
                <p>Reporta errores de acceso, aplicación, cámara, notificaciones o funcionamiento.</p>
                <a href="mailto:{{ config('schoolpass_public.support_email') }}" class="sp-text-link">{{ config('schoolpass_public.support_email') }}</a>
            </article>

            <article class="sp-support-card">
                <span><i class="uil uil-shield"></i></span>
                <h2>Privacidad y eliminación</h2>
                <p>Solicita acceso, corrección, cancelación, oposición o eliminación de cuenta y datos.</p>
                <a href="{{ route('public.data-deletion') }}" class="sp-text-link">Abrir formulario <i class="uil uil-arrow-right"></i></a>
            </article>

            <article class="sp-support-card">
                <span><i class="uil uil-building"></i></span>
                <h2>Información para escuelas</h2>
                <p>Solicita una demostración o información sobre implementación y licenciamiento.</p>
                <a href="mailto:{{ config('schoolpass_public.commercial_email') }}" class="sp-text-link">{{ config('schoolpass_public.commercial_email') }}</a>
            </article>

            <article class="sp-support-card">
                <span><i class="uil uil-file-info-alt"></i></span>
                <h2>Aviso de privacidad</h2>
                <p>Consulta cómo se distribuyen las responsabilidades entre la institución y PaseLista.</p>
                <a href="{{ route('public.privacy') }}" class="sp-text-link">Consultar aviso <i class="uil uil-arrow-right"></i></a>
            </article>
        </div>
    </div>
</section>
@endsection
