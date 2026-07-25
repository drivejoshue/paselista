@extends('layouts.public-site')

@section('title', 'PaseLista · Acceso y asistencia escolar')
@section('meta-description', 'PaseLista ayuda a las escuelas a controlar accesos, asistencia, puntualidad y entrega autorizada de alumnos desde una sola plataforma.')

@php
    $configuredLoginUrl = trim((string) config('schoolpass_public.login_url'));

    $publicLoginUrl = $configuredLoginUrl !== ''
        ? $configuredLoginUrl
        : (\Illuminate\Support\Facades\Route::has('login')
            ? route('login')
            : url('/login'));

    $commercialEmail = trim((string) config('schoolpass_public.commercial_email'));
    $commercialUrl = $commercialEmail !== ''
        ? 'mailto:'.$commercialEmail.'?subject='.rawurlencode('Quiero conocer PaseLista')
        : route('public.support');
@endphp

@push('styles')
<style>
    .sp-landing {
        --sp-primary: #2563eb;
        --sp-primary-dark: #1d4ed8;
        --sp-secondary: #7c3aed;
        --sp-ink: #0f172a;
        --sp-muted: #64748b;
        --sp-line: rgba(148, 163, 184, .24);
        --sp-soft: #f8fafc;
        overflow: hidden;
    }

    .dark .sp-landing {
        --sp-ink: #f8fafc;
        --sp-muted: #94a3b8;
        --sp-line: rgba(148, 163, 184, .18);
        --sp-soft: #0f172a;
    }

    .sp-landing-container {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }

    .sp-landing-hero {
        position: relative;
        padding: 138px 0 92px;
        background:
            radial-gradient(circle at 78% 18%, rgba(37, 99, 235, .18), transparent 30%),
            radial-gradient(circle at 12% 30%, rgba(124, 58, 237, .12), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }

    .dark .sp-landing-hero {
        background:
            radial-gradient(circle at 78% 18%, rgba(59, 130, 246, .18), transparent 30%),
            radial-gradient(circle at 12% 30%, rgba(139, 92, 246, .14), transparent 28%),
            linear-gradient(180deg, #0f172a 0%, #111827 100%);
    }

    .sp-landing-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 0;
        height: 120px;
        background: linear-gradient(180deg, transparent, rgba(248, 250, 252, .82));
        pointer-events: none;
    }

    .dark .sp-landing-hero::after {
        background: linear-gradient(180deg, transparent, rgba(15, 23, 42, .82));
    }

    .sp-landing-hero-grid,
    .sp-landing-split {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        align-items: center;
        gap: 64px;
    }

    .sp-landing-kicker {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 8px 13px;
        border-radius: 999px;
        background: rgba(37, 99, 235, .09);
        color: var(--sp-primary);
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .sp-landing-title {
        margin: 22px 0 20px;
        max-width: 720px;
        color: var(--sp-ink);
        font-size: clamp(2.55rem, 5vw, 4.7rem);
        line-height: 1.02;
        letter-spacing: -.055em;
        font-weight: 850;
    }

    .sp-landing-title strong {
        color: var(--sp-primary);
        font-weight: inherit;
    }

    .sp-landing-copy,
    .sp-landing-section-copy {
        color: var(--sp-muted);
        font-size: 1.08rem;
        line-height: 1.8;
    }

    .sp-landing-copy {
        max-width: 650px;
    }

    .sp-landing-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 30px;
    }

    .sp-landing-button {
        min-height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 20px;
        border-radius: 12px;
        font-weight: 750;
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .sp-landing-button:hover {
        transform: translateY(-2px);
    }

    .sp-landing-button-primary {
        color: #fff;
        background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark));
        box-shadow: 0 16px 30px rgba(37, 99, 235, .24);
    }

    .sp-landing-button-secondary {
        color: var(--sp-ink);
        background: rgba(255, 255, 255, .82);
        border: 1px solid var(--sp-line);
        box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
    }

    .dark .sp-landing-button-secondary {
        background: rgba(15, 23, 42, .72);
    }

    .sp-landing-proof {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-top: 34px;
        color: var(--sp-muted);
        font-size: .9rem;
    }

    .sp-landing-proof span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .sp-landing-proof i {
        color: #16a34a;
        font-size: 1.05rem;
    }

    .sp-browser {
        position: relative;
        padding: 11px;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 24px;
        background: rgba(255, 255, 255, .82);
        box-shadow: 0 32px 80px rgba(15, 23, 42, .18);
        backdrop-filter: blur(18px);
        transform: perspective(1200px) rotateY(-3deg) rotateX(1deg);
    }

    .dark .sp-browser {
        background: rgba(15, 23, 42, .82);
    }

    .sp-browser-bar {
        height: 38px;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 0 10px;
    }

    .sp-browser-bar span {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #cbd5e1;
    }

    .sp-browser-bar span:nth-child(1) { background: #fb7185; }
    .sp-browser-bar span:nth-child(2) { background: #fbbf24; }
    .sp-browser-bar span:nth-child(3) { background: #34d399; }

    .sp-browser-image {
        display: block;
        width: 100%;
        border-radius: 16px;
        border: 1px solid var(--sp-line);
        background: #e2e8f0;
    }

    .sp-floating-note {
        position: absolute;
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 190px;
        padding: 13px 15px;
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: 15px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .15);
        color: #0f172a;
    }

    .dark .sp-floating-note {
        background: rgba(30, 41, 59, .96);
        color: #f8fafc;
    }

    .sp-floating-note i {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        background: rgba(37, 99, 235, .11);
        color: var(--sp-primary);
        font-size: 1.2rem;
    }

    .sp-floating-note strong,
    .sp-floating-note small {
        display: block;
    }

    .sp-floating-note small {
        color: var(--sp-muted);
        margin-top: 2px;
    }

    .sp-floating-note-one {
        left: -30px;
        bottom: 52px;
    }

    .sp-floating-note-two {
        right: -22px;
        top: 78px;
    }

    .sp-landing-strip {
        position: relative;
        z-index: 4;
        margin-top: -28px;
    }

    .sp-landing-strip-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        border: 1px solid var(--sp-line);
        border-radius: 20px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 20px 50px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .dark .sp-landing-strip-grid {
        background: rgba(15, 23, 42, .96);
    }

    .sp-strip-item {
        padding: 23px 22px;
        border-right: 1px solid var(--sp-line);
    }

    .sp-strip-item:last-child {
        border-right: 0;
    }

    .sp-strip-item strong,
    .sp-strip-item span {
        display: block;
    }

    .sp-strip-item strong {
        color: var(--sp-ink);
        font-size: 1.02rem;
    }

    .sp-strip-item span {
        margin-top: 5px;
        color: var(--sp-muted);
        font-size: .86rem;
    }

    .sp-landing-section {
        padding: 96px 0;
    }

    .sp-landing-section-soft {
        background: var(--sp-soft);
    }

    .sp-section-head {
        max-width: 770px;
        margin: 0 auto 46px;
        text-align: center;
    }

    .sp-section-title,
    .sp-split-title {
        color: var(--sp-ink);
        font-weight: 830;
        letter-spacing: -.035em;
        line-height: 1.12;
    }

    .sp-section-title {
        margin: 16px 0 14px;
        font-size: clamp(2rem, 3.5vw, 3.2rem);
    }

    .sp-split-title {
        margin: 17px 0 15px;
        font-size: clamp(2rem, 3.4vw, 3.05rem);
    }

    .sp-feature-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .sp-feature-card,
    .sp-role-card,
    .sp-step-card {
        border: 1px solid var(--sp-line);
        background: rgba(255, 255, 255, .9);
        border-radius: 20px;
        box-shadow: 0 12px 36px rgba(15, 23, 42, .055);
    }

    .dark .sp-feature-card,
    .dark .sp-role-card,
    .dark .sp-step-card {
        background: rgba(30, 41, 59, .72);
    }

    .sp-feature-card {
        padding: 25px;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .sp-feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 46px rgba(15, 23, 42, .1);
    }

    .sp-feature-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: rgba(37, 99, 235, .1);
        color: var(--sp-primary);
        font-size: 1.4rem;
    }

    .sp-feature-card h3,
    .sp-role-card h3,
    .sp-step-card h3 {
        color: var(--sp-ink);
        font-size: 1.08rem;
        font-weight: 780;
        margin: 17px 0 9px;
    }

    .sp-feature-card p,
    .sp-role-card p,
    .sp-step-card p {
        color: var(--sp-muted);
        line-height: 1.7;
        margin: 0;
    }

    .sp-media-frame {
        position: relative;
        border: 1px solid var(--sp-line);
        border-radius: 26px;
        background: linear-gradient(145deg, rgba(37, 99, 235, .09), rgba(124, 58, 237, .07));
        padding: 24px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .1);
    }

    .sp-media-frame img {
        display: block;
        width: 100%;
        border-radius: 18px;
        box-shadow: 0 16px 35px rgba(15, 23, 42, .16);
    }

    .sp-checks {
        display: grid;
        gap: 13px;
        margin-top: 24px;
    }

    .sp-checks li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: var(--sp-muted);
        line-height: 1.6;
    }

    .sp-checks i {
        color: #16a34a;
        margin-top: 2px;
        font-size: 1.05rem;
    }

    .sp-role-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-top: 28px;
    }

    .sp-role-card {
        padding: 20px;
    }

    .sp-role-card span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--sp-primary);
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .045em;
    }

    .sp-ai-section {
        position: relative;
        background:
            radial-gradient(circle at 80% 20%, rgba(59, 130, 246, .22), transparent 30%),
            linear-gradient(135deg, #0f172a, #111827 58%, #172554);
        color: #fff;
    }

    .sp-ai-section .sp-split-title,
    .sp-ai-section .sp-landing-section-copy {
        color: #fff;
    }

    .sp-ai-section .sp-landing-section-copy {
        color: #cbd5e1;
    }

    .sp-ai-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid rgba(147, 197, 253, .22);
        background: rgba(59, 130, 246, .12);
        color: #bfdbfe;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .sp-ai-note {
        margin-top: 25px;
        padding: 16px 18px;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 15px;
        background: rgba(255, 255, 255, .06);
        color: #cbd5e1;
        line-height: 1.65;
    }

    .sp-steps-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
    }

    .sp-step-card {
        padding: 23px;
        position: relative;
        overflow: hidden;
    }

    .sp-step-number {
        display: inline-grid;
        width: 42px;
        height: 42px;
        place-items: center;
        border-radius: 12px;
        background: var(--sp-primary);
        color: #fff;
        font-weight: 850;
    }

    .sp-privacy-box {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-top: 26px;
    }

    .sp-privacy-card {
        padding: 22px;
        border-radius: 18px;
        border: 1px solid var(--sp-line);
        background: rgba(255, 255, 255, .86);
    }

    .dark .sp-privacy-card {
        background: rgba(30, 41, 59, .72);
    }

    .sp-privacy-card strong,
    .sp-privacy-card span {
        display: block;
    }

    .sp-privacy-card strong {
        color: var(--sp-ink);
        margin-bottom: 7px;
    }

    .sp-privacy-card span {
        color: var(--sp-muted);
        line-height: 1.65;
        font-size: .92rem;
    }

    .sp-final-cta {
        padding: 0 0 96px;
    }

    .sp-final-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
        padding: 44px;
        border-radius: 28px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8 50%, #7c3aed);
        box-shadow: 0 28px 65px rgba(37, 99, 235, .24);
        color: #fff;
    }

    .sp-final-card h2 {
        max-width: 720px;
        margin: 0 0 10px;
        font-size: clamp(1.8rem, 3.4vw, 3rem);
        line-height: 1.12;
        font-weight: 830;
        letter-spacing: -.035em;
    }

    .sp-final-card p {
        margin: 0;
        color: rgba(255, 255, 255, .82);
    }

    .sp-button-white {
        flex: 0 0 auto;
        color: #1d4ed8;
        background: #fff;
        box-shadow: 0 14px 30px rgba(15, 23, 42, .18);
    }

    @media (max-width: 1023px) {
        .sp-landing-hero-grid,
        .sp-landing-split {
            grid-template-columns: 1fr;
            gap: 42px;
        }

        .sp-landing-hero {
            padding-top: 118px;
        }

        .sp-browser {
            transform: none;
        }

        .sp-floating-note-one {
            left: 14px;
            bottom: 28px;
        }

        .sp-floating-note-two {
            right: 14px;
            top: 58px;
        }

        .sp-feature-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sp-steps-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .sp-landing-hero {
            padding: 108px 0 70px;
        }

        .sp-landing-container {
            width: min(100% - 22px, 1180px);
        }

        .sp-landing-title {
            font-size: 2.55rem;
        }

        .sp-landing-copy,
        .sp-landing-section-copy {
            font-size: 1rem;
        }

        .sp-floating-note {
            position: static;
            margin-top: 12px;
        }

        .sp-landing-strip {
            margin-top: 0;
        }

        .sp-landing-strip-grid,
        .sp-feature-grid,
        .sp-role-grid,
        .sp-steps-grid,
        .sp-privacy-box {
            grid-template-columns: 1fr;
        }

        .sp-strip-item {
            border-right: 0;
            border-bottom: 1px solid var(--sp-line);
        }

        .sp-strip-item:last-child {
            border-bottom: 0;
        }

        .sp-landing-section {
            padding: 72px 0;
        }

        .sp-final-card {
            align-items: flex-start;
            flex-direction: column;
            padding: 30px 24px;
        }
    }
</style>
<style>
    .sp-landing-split-apps {
        align-items: center;
        gap: 3rem;
    }

    .sp-apps-showcase {
        position: relative;
        min-height: 560px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sp-phone-card {
        position: absolute;
        width: 245px;
        max-width: 100%;
        background: #ffffff;
        border-radius: 28px;
        padding: 14px;
        box-shadow: 0 22px 60px rgba(15, 23, 42, 0.18);
        border: 1px solid rgba(148, 163, 184, 0.18);
        overflow: hidden;
    }

    .sp-phone-card img {
        display: block;
        width: 100%;
        height: auto;
        border-radius: 20px;
    }

    .sp-phone-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        background: rgba(176, 111, 36, 0.10);
        color: #8a5a1f;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .sp-phone-card-back {
        left: 20px;
        top: 40px;
        transform: rotate(-5deg);
        z-index: 1;
    }

    .sp-phone-card-front {
        right: 10px;
        top: 120px;
        transform: rotate(4deg);
        z-index: 2;
    }

    @media (max-width: 1199.98px) {
        .sp-apps-showcase {
            min-height: 500px;
        }

        .sp-phone-card {
            width: 220px;
        }
    }

    @media (max-width: 991.98px) {
        .sp-apps-showcase {
            min-height: auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .sp-phone-card {
            position: relative;
            width: 100%;
            max-width: 260px;
            margin: 0 auto;
        }

        .sp-phone-card-back,
        .sp-phone-card-front {
            left: auto;
            right: auto;
            top: auto;
            transform: none;
        }
    }

    @media (max-width: 575.98px) {
        .sp-apps-showcase {
            grid-template-columns: 1fr;
        }

        .sp-phone-card {
            max-width: 240px;
        }
    }
</style>
@endpush

@section('content')
<div class="sp-landing">
    <section class="sp-landing-hero">
        <div class="sp-landing-container">
            <div class="sp-landing-hero-grid">
                <div>
                    <span class="sp-landing-kicker">
                        <i class="uil uil-shield-check"></i>
                        Tecnología práctica para escuelas
                    </span>

                    <h1 class="sp-landing-title">
                        Más control en la entrada escolar. <strong>Menos procesos manuales.</strong>
                    </h1>

                    <p class="sp-landing-copy">
                        PaseLista ayuda a registrar entradas, salidas, asistencia y recogidas autorizadas desde celular, tablet o panel web. Dirección obtiene visibilidad y las familias reciben información más clara.
                    </p>

                    <div class="sp-landing-actions">
                        <a href="{{ $commercialUrl }}" class="sp-landing-button sp-landing-button-primary">
                            <i class="uil uil-calendar-alt"></i>
                            Solicitar demostración
                        </a>

                        <a href="{{ $publicLoginUrl }}" class="sp-landing-button sp-landing-button-secondary">
                            <i class="uil uil-sign-in-alt"></i>
                            Entrar a PaseLista
                        </a>
                    </div>

                    <div class="sp-landing-proof">
                        <span><i class="uil uil-check-circle"></i> Empieza con QR</span>
                        <span><i class="uil uil-check-circle"></i> Sin equipo especializado</span>
                        <span><i class="uil uil-check-circle"></i> Preparado para NFC</span>
                    </div>
                </div>

                <div>
                    <div class="sp-browser">
                        <div class="sp-browser-bar">
                            <span></span><span></span><span></span>
                        </div>

                        <img
                            src="{{ asset('landing/images/schoolpass/dashboard-schoolpass.jpeg') }}"
                            class="sp-browser-image"
                            alt="Panel administrativo de SchoolPass"
                        >

                        <div class="sp-floating-note sp-floating-note-one">
                            <i class="uil uil-user-check"></i>
                            <div>
                                <strong>Asistencia actualizada</strong>
                                <small>Información del día en un solo lugar</small>
                            </div>
                        </div>

                        <div class="sp-floating-note sp-floating-note-two">
                            <i class="uil uil-bell"></i>
                            <div>
                                <strong>Aviso registrado</strong>
                                <small>Entrada o salida confirmada</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="sp-landing-strip">
        <div class="sp-landing-container">
            <div class="sp-landing-strip-grid">
                <div class="sp-strip-item">
                    <strong>Acceso escolar</strong>
                    <span>QR, NFC y registros manuales controlados</span>
                </div>
                <div class="sp-strip-item">
                    <strong>Asistencia</strong>
                    <span>Entradas, salidas, retardos y ausencias</span>
                </div>
                <div class="sp-strip-item">
                    <strong>Familias</strong>
                    <span>Credenciales, avisos e historial</span>
                </div>
                <div class="sp-strip-item">
                    <strong>Dirección</strong>
                    <span>Reportes, auditoría y análisis operativo</span>
                </div>
            </div>
        </div>
    </section>

    <section id="funciones" class="sp-landing-section">
        <div class="sp-landing-container">
            <div class="sp-section-head">
                <span class="sp-landing-kicker">Operación escolar simple</span>
                <h2 class="sp-section-title">Lo necesario para controlar accesos sin complicar el trabajo diario.</h2>
                <p class="sp-landing-section-copy">
                    PaseLista se concentra en lo que realmente ocurre en la entrada: identificar, validar, registrar, informar y consultar.
                </p>
            </div>

            <div class="sp-feature-grid">
                @foreach([
                    ['icon' => 'uil-qrcode-scan', 'title' => 'Credenciales QR y NFC', 'text' => 'Usa QR impreso o digital desde el primer día y conserva la opción de incorporar tarjetas NFC.'],
                    ['icon' => 'uil-user-check', 'title' => 'Asistencia automática', 'text' => 'Un acceso puede registrar entrada, salida, puntualidad, retardo o salida anticipada.'],
                    ['icon' => 'uil-users-alt', 'title' => 'Tutores autorizados', 'text' => 'La escuela controla quién puede recoger a cada alumno y conserva evidencia del movimiento.'],
                    ['icon' => 'uil-mobile-android', 'title' => 'Trabajo desde móvil o tablet', 'text' => 'Prefectura y personal de acceso operan sin depender de una computadora fija.'],
                    ['icon' => 'uil-bell', 'title' => 'Avisos para familias', 'text' => 'Madres, padres y tutores reciben la información habilitada por su institución.'],
                    ['icon' => 'uil-chart-line', 'title' => 'Reportes para dirección', 'text' => 'Consulta asistencia, puntualidad, incidencias y tendencias por periodo, grupo o alumno.'],
                ] as $feature)
                    <article class="sp-feature-card">
                        <span class="sp-feature-icon"><i class="uil {{ $feature['icon'] }}"></i></span>
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="sp-landing-section sp-landing-section-soft">
        <div class="sp-landing-container">
            <div class="sp-landing-split">
                <div class="sp-media-frame">
                    <img
                        src="{{ asset('landing/images/schoolpass/dashboard-schoolpass.jpeg') }}"
                        alt="Vista de reportes y operación escolar en SchoolPass"
                    >
                </div>

                <div>
                    <span class="sp-landing-kicker">Visibilidad para dirección</span>
                    <h2 class="sp-split-title">Lo que sucede en la entrada deja de estar disperso.</h2>
                    <p class="sp-landing-section-copy">
                        El panel reúne movimientos, asistencia, puntualidad, incidencias y dispositivos. La dirección puede revisar el día, comparar periodos y detectar qué requiere atención.
                    </p>

                    <ul class="sp-checks">
                        <li><i class="uil uil-check-circle"></i> Resúmenes diarios y mensuales.</li>
                        <li><i class="uil uil-check-circle"></i> Consulta por plantel, nivel, grupo o alumno.</li>
                        <li><i class="uil uil-check-circle"></i> Historial de accesos y acciones administrativas.</li>
                        <li><i class="uil uil-check-circle"></i> Control de dispositivos y puntos de acceso.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

   <section id="aplicaciones" class="sp-landing-section">
    <div class="sp-landing-container">
        <div class="sp-landing-split sp-landing-split-apps">
            <div>
                <span class="sp-landing-kicker">Aplicaciones PaseLista</span>

                <h2 class="sp-split-title">
                    Dos experiencias conectadas: operación escolar y panel familiar.
                </h2>

                <p class="sp-landing-section-copy">
                    PaseLista separa claramente la operación interna de la experiencia de las familias.
                    El personal escolar trabaja con acceso, asistencia y control diario, mientras que
                    madres, padres y tutores consultan credenciales, avisos e historial de sus alumnos
                    desde una app simple, clara y segura.
                </p>

                <div class="sp-role-grid">
                    <article class="sp-role-card">
                        <span>
                            <i class="uil uil-shield-check"></i>
                            PaseLista Staff
                        </span>
                        <h3>Para personal escolar</h3>
                        <p>
                            Escaneo de accesos, asistencia, incidencias, movimientos del día y control operativo.
                        </p>
                    </article>

                    <article class="sp-role-card">
                        <span>
                            <i class="uil uil-users-alt"></i>
                            PaseLista Family
                        </span>
                        <h3>Para madres, padres y tutores</h3>
                        <p>
                            Consulta de alumnos vinculados, QR, historial, avisos escolares y seguimiento diario.
                        </p>
                    </article>
                </div>
            </div>

            <div class="sp-apps-showcase">
                <div class="sp-phone-card sp-phone-card-back">
                    <div class="sp-phone-badge">PaseLista Staff</div>
                    <img
                        src="{{ asset('landing/images/schoolpass/app-staff.jpeg') }}"
                        alt="Aplicación SchoolPass Staff para personal escolar"
                    >
                </div>

                <div class="sp-phone-card sp-phone-card-front">
                    <div class="sp-phone-badge">PaseLista Family</div>
                    <img
                        src="{{ asset('landing/images/schoolpass/app-family.jpeg') }}"
                        alt="Aplicación SchoolPass Family para tutores"
                    >
                </div>
            </div>
        </div>
    </div>
</section>

    <section class="sp-landing-section sp-ai-section">
        <div class="sp-landing-container">
            <div class="sp-landing-split">
                <div class="sp-media-frame">
                    <img
                        src="{{ asset('landing/images/schoolpass/ai-schoolpass.jpeg') }}"
                        alt="SchoolPass IA para análisis de asistencia y accesos"
                    >
                </div>

                <div>
                    <span class="sp-ai-pill"><i class="uil uil-brain"></i> PaseLista IA</span>
                    <h2 class="sp-split-title">Convierte registros escolares en respuestas más fáciles de revisar.</h2>
                    <p class="sp-landing-section-copy">
                        Dirección puede consultar patrones de asistencia, puntualidad, accesos e incidencias mediante preguntas en lenguaje natural, con controles por escuela, alcance, periodo y cuota.
                    </p>

                    <ul class="sp-checks">
                        <li><i class="uil uil-check-circle"></i> Resúmenes ejecutivos con datos de la institución.</li>
                        <li><i class="uil uil-check-circle"></i> Análisis por escuela, grupo o alumno autorizado.</li>
                        <li><i class="uil uil-check-circle"></i> Gráficas opcionales y reportes exportables.</li>
                        <li><i class="uil uil-check-circle"></i> Auditoría, límites de uso y privacidad por diseño.</li>
                    </ul>

                    <div class="sp-ai-note">
                        PaseLista IA funciona como apoyo administrativo. Las decisiones escolares continúan bajo revisión de personal autorizado.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="sp-landing-section">
        <div class="sp-landing-container">
            <div class="sp-section-head">
                <span class="sp-landing-kicker">Cómo funciona</span>
                <h2 class="sp-section-title">Una operación clara desde la configuración hasta el aviso.</h2>
            </div>

            <div class="sp-steps-grid">
                @foreach([
                    ['n' => '01', 'title' => 'Configura', 'text' => 'La institución crea ciclos, grupos, horarios, accesos y usuarios autorizados.'],
                    ['n' => '02', 'title' => 'Vincula', 'text' => 'Alumnos y tutores quedan relacionados mediante credenciales y permisos definidos por la escuela.'],
                    ['n' => '03', 'title' => 'Registra', 'text' => 'El personal escanea o registra el movimiento y PaseLista aplica las reglas configuradas.'],
                    ['n' => '04', 'title' => 'Consulta', 'text' => 'Dirección revisa reportes y las familias reciben los avisos habilitados.'],
                ] as $step)
                    <article class="sp-step-card">
                        <span class="sp-step-number">{{ $step['n'] }}</span>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="seguridad" class="sp-landing-section sp-landing-section-soft">
        <div class="sp-landing-container">
            <div class="sp-landing-split">
                <div>
                    <span class="sp-landing-kicker">Privacidad y control institucional</span>
                    <h2 class="sp-split-title">La escuela conserva el control de su operación y de sus usuarios.</h2>
                    <p class="sp-landing-section-copy">
                        PaseLista separa la información por institución, aplica permisos por rol y mantiene registros de auditoría. La escuela define altas, vinculaciones, horarios, accesos y autorizaciones.
                    </p>

                    <div class="sp-privacy-box">
                        <div class="sp-privacy-card">
                            <strong>Institución educativa</strong>
                            <span>Determina las finalidades escolares, personas autorizadas, reglas operativas y atención inicial a familias.</span>
                        </div>

                        <div class="sp-privacy-card">
                            <strong>PaseLista</strong>
                            <span>Proporciona la tecnología y procesa la información conforme al contrato, instrucciones y controles de seguridad aplicables.</span>
                        </div>
                    </div>

                    <div class="sp-landing-actions">
                        <a href="{{ route('public.privacy') }}" class="sp-landing-button sp-landing-button-primary">
                            Leer aviso de privacidad
                        </a>
                        <a href="{{ route('public.data-deletion') }}" class="sp-landing-button sp-landing-button-secondary">
                            Solicitar derechos o eliminación
                        </a>
                    </div>
                </div>

                <div class="sp-media-frame">
                    <div style="padding: 26px; border-radius: 18px; background: linear-gradient(145deg, #0f172a, #1e3a8a); color: #fff; min-height: 380px; display: grid; align-content: center; gap: 18px;">
                        <span style="width: 64px; height: 64px; display: grid; place-items: center; border-radius: 18px; background: rgba(255,255,255,.1); font-size: 2rem;">
                            <i class="uil uil-lock-shield"></i>
                        </span>
                        <h3 style="font-size: 2rem; line-height: 1.15; font-weight: 820; margin: 0;">Acceso por roles, auditoría y separación por escuela.</h3>
                        <p style="color: #cbd5e1; line-height: 1.75; margin: 0;">Cada usuario accede únicamente a las funciones y datos permitidos para su institución y responsabilidad.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="sp-final-cta">
        <div class="sp-landing-container">
            <div class="sp-final-card">
                <div>
                    <h2>Haz más ágil el acceso escolar y mejora la visibilidad del día.</h2>
                    <p>Conoce cómo PaseLista puede adaptarse al tamaño y operación de tu institución.</p>
                </div>

                <a href="{{ $commercialUrl }}" class="sp-landing-button sp-button-white">
                    Solicitar presentación
                    <i class="uil uil-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
