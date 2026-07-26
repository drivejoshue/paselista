@extends('layouts.public-site')

@section('title', 'Soporte · PaseLista')
@section('meta-description', 'Canales de soporte, acceso, privacidad e información para instituciones que utilizan PaseLista.')

@push('styles')
<style>
    .ps-page-hero {
        position: relative;
        padding: 150px 0 72px;
        overflow: hidden;
        background:
            radial-gradient(circle at 85% 15%, color-mix(in srgb, var(--accent-color), transparent 84%), transparent 28%),
            linear-gradient(180deg,
                color-mix(in srgb, var(--accent-color), transparent 96%) 0%,
                var(--background-color) 100%
            );
    }

    .ps-page-hero::after {
        content: '';
        position: absolute;
        width: 420px;
        height: 420px;
        right: -180px;
        bottom: -270px;
        border-radius: 50%;
        border: 1px solid color-mix(in srgb, var(--accent-color), transparent 78%);
        pointer-events: none;
    }

    .ps-page-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        color: var(--accent-color);
        font-size: .82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .055em;
    }

    .ps-page-hero h1 {
        max-width: 820px;
        margin: 0 0 16px;
        color: var(--heading-color);
        font-size: clamp(2.35rem, 5vw, 4rem);
        line-height: 1.08;
        letter-spacing: -.045em;
        font-weight: 800;
    }

    .ps-page-hero p {
        max-width: 720px;
        margin: 0;
        color: color-mix(in srgb, var(--default-color), transparent 18%);
        font-size: 1.08rem;
        line-height: 1.75;
    }

    .ps-support-section {
        padding: 78px 0 96px;
    }

    .ps-support-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 28px;
        border: 1px solid color-mix(in srgb, var(--default-color), transparent 88%);
        border-radius: 16px;
        background: var(--surface-color);
        box-shadow: 0 12px 34px rgba(15, 23, 42, .055);
        transition:
            transform .22s ease,
            box-shadow .22s ease,
            border-color .22s ease;
    }

    .ps-support-card:hover {
        transform: translateY(-4px);
        border-color: color-mix(in srgb, var(--accent-color), transparent 70%);
        box-shadow: 0 20px 46px rgba(15, 23, 42, .09);
    }

    .ps-support-icon {
        width: 56px;
        height: 56px;
        display: grid;
        place-items: center;
        margin-bottom: 22px;
        border-radius: 14px;
        background: color-mix(in srgb, var(--accent-color), transparent 88%);
        color: var(--accent-color);
        font-size: 1.45rem;
    }

    .ps-support-card h2 {
        margin: 0 0 10px;
        color: var(--heading-color);
        font-size: 1.18rem;
        line-height: 1.3;
        font-weight: 800;
    }

    .ps-support-card p {
        margin: 0 0 22px;
        color: color-mix(in srgb, var(--default-color), transparent 17%);
        line-height: 1.7;
    }

    .ps-support-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: auto;
        color: var(--accent-color);
        font-weight: 700;
        text-decoration: none;
        overflow-wrap: anywhere;
    }

    .ps-support-link:hover {
        color: color-mix(in srgb, var(--accent-color), #000 16%);
    }

    .ps-support-note {
        margin-top: 38px;
        padding: 24px 26px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        border-radius: 15px;
        background: color-mix(in srgb, var(--accent-color), transparent 92%);
        border: 1px solid color-mix(in srgb, var(--accent-color), transparent 82%);
    }

    .ps-support-note > i {
        flex: 0 0 auto;
        margin-top: 2px;
        color: var(--accent-color);
        font-size: 1.35rem;
    }

    .ps-support-note strong {
        display: block;
        margin-bottom: 4px;
        color: var(--heading-color);
    }

    .ps-support-note span {
        color: color-mix(in srgb, var(--default-color), transparent 14%);
        line-height: 1.65;
    }

    @media (max-width: 767.98px) {
        .ps-page-hero {
            padding: 124px 0 58px;
        }

        .ps-support-section {
            padding: 58px 0 76px;
        }

        .ps-support-card {
            padding: 24px;
        }
    }
</style>
@endpush

@section('content')
@php
    $configuredLoginUrl = trim((string) config('schoolpass_public.login_url'));

    $supportLoginUrl = $configuredLoginUrl !== ''
        ? $configuredLoginUrl
        : (\Illuminate\Support\Facades\Route::has('login')
            ? route('login')
            : url('/login'));

    $supportEmail = trim((string) config('schoolpass_public.support_email'));
    $commercialEmail = trim((string) config('schoolpass_public.commercial_email'));
@endphp

<section class="ps-page-hero">
    <div class="container position-relative">
        <span class="ps-page-eyebrow">
            <i class="bi bi-life-preserver"></i>
            Ayuda y soporte
        </span>

        <h1>¿Necesitas entrar o reportar un problema?</h1>

        <p>
            Usa el canal correspondiente para que tu solicitud llegue al equipo
            o a la institución adecuada.
        </p>
    </div>
</section>

<section class="ps-support-section">
    <div class="container">

        <div class="row g-4">

            <div class="col-lg-4 col-md-6">
                <article class="ps-support-card">
                    <span class="ps-support-icon">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </span>

                    <h2>Acceso a la plataforma</h2>

                    <p>
                        Ingresa con la cuenta entregada por tu institución educativa.
                    </p>

                    <a href="{{ $supportLoginUrl }}" class="ps-support-link">
                        Entrar a PaseLista
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </article>
            </div>

            <div class="col-lg-4 col-md-6">
                <article class="ps-support-card">
                    <span class="ps-support-icon">
                        <i class="bi bi-building"></i>
                    </span>

                    <h2>Cuenta o datos escolares</h2>

                    <p>
                        Para altas, vinculaciones, correcciones de alumno o permisos de tutor,
                        contacta primero a tu institución educativa.
                    </p>

                    <span class="ps-support-link">
                        Gestión institucional
                        <i class="bi bi-shield-check"></i>
                    </span>
                </article>
            </div>

            <div class="col-lg-4 col-md-6">
                <article class="ps-support-card">
                    <span class="ps-support-icon">
                        <i class="bi bi-tools"></i>
                    </span>

                    <h2>Problema técnico</h2>

                    <p>
                        Reporta errores de acceso, aplicación, cámara,
                        notificaciones o funcionamiento.
                    </p>

                    @if($supportEmail !== '')
                        <a
                            href="mailto:{{ $supportEmail }}"
                            class="ps-support-link"
                        >
                            {{ $supportEmail }}
                            <i class="bi bi-envelope"></i>
                        </a>
                    @else
                        <a
                            href="{{ route('public.data-deletion') }}"
                            class="ps-support-link"
                        >
                            Abrir canal de atención
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    @endif
                </article>
            </div>

            <div class="col-lg-4 col-md-6">
                <article class="ps-support-card">
                    <span class="ps-support-icon">
                        <i class="bi bi-shield-lock"></i>
                    </span>

                    <h2>Privacidad y eliminación</h2>

                    <p>
                        Solicita acceso, corrección, cancelación, oposición
                        o eliminación de cuenta y datos asociados.
                    </p>

                    <a
                        href="{{ route('public.data-deletion') }}"
                        class="ps-support-link"
                    >
                        Abrir formulario
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </article>
            </div>

            <div class="col-lg-4 col-md-6">
                <article class="ps-support-card">
                    <span class="ps-support-icon">
                        <i class="bi bi-mortarboard"></i>
                    </span>

                    <h2>Información para escuelas</h2>

                    <p>
                        Solicita una demostración o información sobre implementación,
                        operación y licenciamiento.
                    </p>

                    @if($commercialEmail !== '')
                        <a
                            href="mailto:{{ $commercialEmail }}?subject={{ rawurlencode('Quiero conocer PaseLista') }}"
                            class="ps-support-link"
                        >
                            {{ $commercialEmail }}
                            <i class="bi bi-envelope"></i>
                        </a>
                    @endif
                </article>
            </div>

            <div class="col-lg-4 col-md-6">
                <article class="ps-support-card">
                    <span class="ps-support-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </span>

                    <h2>Aviso de privacidad</h2>

                    <p>
                        Consulta cómo se distribuyen las responsabilidades
                        entre la institución educativa y PaseLista.
                    </p>

                    <a
                        href="{{ route('public.privacy') }}"
                        class="ps-support-link"
                    >
                        Consultar aviso
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </article>
            </div>

        </div>

        <div class="ps-support-note">
            <i class="bi bi-info-circle"></i>

            <div>
                <strong>¿No sabes qué canal usar?</strong>
                <span>
                    Para información académica, alumnos, grupos, permisos o vinculaciones,
                    contacta primero a tu escuela. Para fallas de la plataforma, utiliza
                    el canal de soporte técnico de PaseLista.
                </span>
            </div>
        </div>

    </div>
</section>
@endsection