@extends('layouts.public-site')

@section('title', 'PaseLista · Control de acceso y asistencia escolar')
@section('meta-description', 'PaseLista conecta dirección, personal escolar y familias para gestionar accesos, asistencia, salidas, avisos y reportes desde una sola plataforma.')

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

    $supportUrl = route('public.support');
    $privacyUrl = route('public.privacy');
    $deletionUrl = route('public.data-deletion');
@endphp

@push('styles')
<style>
    .pl-home {
        --pl-primary: #388da8;
        --pl-primary-dark: #2f7489;
        --pl-primary-soft: #edf7fa;
        --pl-ink: #1f2937;
        --pl-heading: #334155;
        --pl-muted: #64748b;
        --pl-line: #e5e7eb;
        --pl-soft: #f8fafc;
        --pl-white: #ffffff;
        --pl-success: #16a34a;
        color: var(--pl-ink);
        overflow: hidden;
    }

    .dark .pl-home {
        --pl-ink: #e5e7eb;
        --pl-heading: #f8fafc;
        --pl-muted: #a3b0c2;
        --pl-line: rgba(148, 163, 184, .18);
        --pl-soft: #0f172a;
        --pl-white: #111827;
        --pl-primary-soft: rgba(56, 141, 168, .14);
    }

    .pl-home *,
    .pl-home *::before,
    .pl-home *::after {
        box-sizing: border-box;
    }

    .pl-container {
        width: min(1160px, calc(100% - 32px));
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }

    .pl-section {
        padding: 92px 0;
    }

    .pl-section-soft {
        background: var(--pl-soft);
    }

    .pl-section-head {
        max-width: 760px;
        margin: 0 auto 48px;
        text-align: center;
    }

    .pl-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--pl-primary);
        font-size: .82rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .pl-section-title {
        margin: 12px 0 14px;
        color: var(--pl-heading);
        font-size: clamp(2rem, 4vw, 3.1rem);
        line-height: 1.12;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .pl-section-copy {
        margin: 0;
        color: var(--pl-muted);
        font-size: 1.04rem;
        line-height: 1.75;
    }

    .pl-button {
        min-height: 48px;
        padding: 0 20px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        font-weight: 750;
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
    }

    .pl-button:hover {
        transform: translateY(-2px);
    }

    .pl-button-primary {
        color: #fff;
        background: var(--pl-primary);
        box-shadow: 0 12px 28px rgba(56, 141, 168, .22);
    }

    .pl-button-primary:hover {
        color: #fff;
        background: var(--pl-primary-dark);
    }

    .pl-button-secondary {
        color: var(--pl-heading);
        border: 1px solid var(--pl-line);
        background: rgba(255, 255, 255, .8);
    }

    .dark .pl-button-secondary {
        background: rgba(15, 23, 42, .55);
    }

    .pl-text-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--pl-primary);
        font-weight: 750;
        text-decoration: none;
    }

    /* HERO */
    .pl-hero {
        position: relative;
        padding: 142px 0 72px;
        background:
            radial-gradient(circle at 50% 18%, rgba(56, 141, 168, .16), transparent 30%),
            linear-gradient(180deg, #f7fcfe 0%, #ffffff 74%);
    }

    .dark .pl-hero {
        background:
            radial-gradient(circle at 50% 18%, rgba(56, 141, 168, .18), transparent 30%),
            linear-gradient(180deg, #0f172a 0%, #111827 74%);
    }

    .pl-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: .35;
        background-image:
            linear-gradient(rgba(56, 141, 168, .06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(56, 141, 168, .06) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: linear-gradient(to bottom, rgba(0,0,0,.7), transparent 72%);
        pointer-events: none;
    }

    .pl-hero-copy {
        max-width: 900px;
        margin: 0 auto;
        text-align: center;
    }

    .pl-hero h1 {
        margin: 15px auto 18px;
        max-width: 920px;
        color: var(--pl-heading);
        font-size: clamp(2.8rem, 6vw, 5.1rem);
        line-height: 1.02;
        letter-spacing: -.055em;
        font-weight: 800;
    }

    .pl-hero h1 span {
        color: var(--pl-primary);
    }

    .pl-hero-copy > p {
        max-width: 760px;
        margin: 0 auto;
        color: var(--pl-muted);
        font-size: clamp(1.06rem, 2vw, 1.24rem);
        line-height: 1.7;
    }

    .pl-hero-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        margin-top: 28px;
    }

    .pl-hero-proof {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 18px 26px;
        margin-top: 25px;
        color: var(--pl-muted);
        font-size: .92rem;
    }

    .pl-hero-proof span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .pl-hero-proof i {
        color: var(--pl-success);
    }

    /* PRODUCT SHOWCASE */
    .pl-product-stage {
        position: relative;
        max-width: 1030px;
        margin: 56px auto 0;
        min-height: 620px;
    }

    .pl-dashboard-shell {
        position: absolute;
        left: 50%;
        top: 0;
        width: min(860px, 86%);
        transform: translateX(-50%);
        padding: 10px;
        border: 1px solid rgba(148, 163, 184, .25);
        border-radius: 19px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 30px 80px rgba(15, 23, 42, .14);
    }

    .dark .pl-dashboard-shell {
        background: rgba(15, 23, 42, .92);
    }

    .pl-dashboard-bar {
        height: 34px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 8px;
    }

    .pl-dashboard-bar span {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #cbd5e1;
    }

    .pl-dashboard-shell img {
        width: 100%;
        display: block;
        border-radius: 12px;
        border: 1px solid var(--pl-line);
    }

    .pl-phone {
        position: absolute;
        bottom: 0;
        width: 205px;
        padding: 9px;
        border-radius: 28px;
        background: #0f172a;
        box-shadow: 0 26px 55px rgba(15, 23, 42, .22);
        z-index: 3;
    }

    .pl-phone img {
        width: 100%;
        display: block;
        border-radius: 20px;
    }

    .pl-phone-staff {
        left: 15px;
        transform: rotate(-3deg);
    }

    .pl-phone-family {
        right: 15px;
        transform: rotate(3deg);
    }

    .pl-float-card {
        position: absolute;
        z-index: 4;
        min-width: 205px;
        padding: 14px 16px;
        border: 1px solid var(--pl-line);
        border-radius: 12px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 18px 40px rgba(15, 23, 42, .12);
    }

    .dark .pl-float-card {
        background: rgba(30, 41, 59, .96);
    }

    .pl-float-card strong,
    .pl-float-card small {
        display: block;
    }

    .pl-float-card strong {
        color: var(--pl-heading);
    }

    .pl-float-card small {
        margin-top: 3px;
        color: var(--pl-muted);
    }

    .pl-float-a {
        left: 9%;
        top: 120px;
    }

    .pl-float-b {
        right: 6%;
        top: 230px;
    }

    /* FEATURED STRIP */
    .pl-featured {
        padding: 0 0 24px;
        background: #fff;
    }

    .dark .pl-featured {
        background: #111827;
    }

    .pl-featured-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .pl-featured-item {
        display: grid;
        grid-template-columns: 54px 1fr;
        gap: 15px;
        align-items: start;
        padding: 24px;
        border: 1px solid var(--pl-line);
        border-radius: 14px;
        background: var(--pl-white);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .pl-featured-item:hover {
        transform: translateY(-3px);
        border-color: rgba(56, 141, 168, .28);
        box-shadow: 0 16px 35px rgba(15, 23, 42, .07);
    }

    .pl-featured-icon {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        background: var(--pl-primary-soft);
        color: var(--pl-primary);
        font-size: 1.45rem;
    }

    .pl-featured-item h3 {
        margin: 2px 0 6px;
        color: var(--pl-heading);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .pl-featured-item p {
        margin: 0;
        color: var(--pl-muted);
        line-height: 1.65;
        font-size: .93rem;
    }

    /* PLATFORM TABS */
    .pl-platform-grid {
        display: grid;
        grid-template-columns: minmax(300px, .9fr) minmax(0, 1.25fr);
        gap: 54px;
        align-items: center;
    }

    .pl-tabs {
        display: grid;
        gap: 12px;
    }

    .pl-tab {
        width: 100%;
        display: grid;
        grid-template-columns: 46px 1fr;
        gap: 14px;
        text-align: left;
        padding: 18px;
        border: 1px solid transparent;
        border-radius: 12px;
        background: transparent;
        cursor: pointer;
        transition: background .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .pl-tab:hover,
    .pl-tab.is-active {
        border-color: rgba(56, 141, 168, .18);
        background: var(--pl-white);
        box-shadow: 0 12px 30px rgba(15, 23, 42, .055);
    }

    .pl-tab-icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        color: var(--pl-primary);
        background: var(--pl-primary-soft);
        font-size: 1.25rem;
    }

    .pl-tab h3 {
        margin: 0 0 4px;
        color: var(--pl-heading);
        font-size: 1rem;
        font-weight: 800;
    }

    .pl-tab p {
        margin: 0;
        color: var(--pl-muted);
        line-height: 1.6;
        font-size: .9rem;
    }

    .pl-tab-panels {
        position: relative;
        min-height: 520px;
    }

    .pl-tab-panel {
        display: none;
        height: 100%;
    }

    .pl-tab-panel.is-active {
        display: block;
        animation: plFade .25s ease;
    }

    @keyframes plFade {
        from { opacity: .35; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .pl-product-card {
        min-height: 520px;
        display: grid;
        place-items: center;
        padding: 26px;
        border-radius: 24px;
        background:
            radial-gradient(circle at 100% 0%, rgba(56, 141, 168, .14), transparent 30%),
            #f8fafc;
        border: 1px solid var(--pl-line);
    }

    .dark .pl-product-card {
        background:
            radial-gradient(circle at 100% 0%, rgba(56, 141, 168, .13), transparent 30%),
            #0f172a;
    }

    .pl-product-card img {
        max-width: 100%;
        max-height: 470px;
        display: block;
        border-radius: 14px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .13);
    }

    .pl-product-card.is-phone img {
        max-width: 245px;
        border-radius: 26px;
    }

    /* DETAILS */
    .pl-detail {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(320px, .85fr);
        gap: 64px;
        align-items: center;
    }

    .pl-detail + .pl-detail {
        margin-top: 86px;
    }

    .pl-detail.reverse .pl-detail-copy {
        order: 1;
    }

    .pl-detail.reverse .pl-detail-media {
        order: 2;
    }

    .pl-detail-media {
        padding: 22px;
        border-radius: 22px;
        border: 1px solid var(--pl-line);
        background: var(--pl-primary-soft);
    }

    .pl-detail-media img {
        display: block;
        width: 100%;
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .11);
    }

    .pl-detail-media.phone-media {
        display: grid;
        place-items: center;
        min-height: 500px;
    }

    .pl-detail-media.phone-media img {
        max-width: 250px;
        border-radius: 26px;
    }

    .pl-detail-copy h2 {
        margin: 12px 0 14px;
        color: var(--pl-heading);
        font-size: clamp(2rem, 3.5vw, 3rem);
        line-height: 1.12;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .pl-detail-copy > p {
        color: var(--pl-muted);
        line-height: 1.75;
        font-size: 1.02rem;
    }

    .pl-checks {
        list-style: none;
        padding: 0;
        margin: 24px 0 0;
        display: grid;
        gap: 12px;
    }

    .pl-checks li {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        color: var(--pl-muted);
        line-height: 1.6;
    }

    .pl-checks i {
        margin-top: 2px;
        color: var(--pl-primary);
        font-size: 1.05rem;
    }

    /* CAPABILITIES */
    .pl-capability-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .pl-capability {
        display: grid;
        grid-template-columns: 58px 1fr;
        gap: 16px;
        padding: 24px;
        border: 1px solid var(--pl-line);
        border-radius: 14px;
        background: var(--pl-white);
    }

    .pl-capability-icon {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: var(--pl-primary-soft);
        color: var(--pl-primary);
        font-size: 1.45rem;
    }

    .pl-capability h3 {
        margin: 2px 0 7px;
        color: var(--pl-heading);
        font-size: 1.06rem;
        font-weight: 800;
    }

    .pl-capability p {
        margin: 0;
        color: var(--pl-muted);
        line-height: 1.65;
        font-size: .93rem;
    }

    /* AI */
    .pl-ai {
        background:
            radial-gradient(circle at 82% 18%, rgba(56, 141, 168, .24), transparent 28%),
            linear-gradient(135deg, #0f172a, #172033 58%, #1d3340);
        color: #fff;
    }

    .pl-ai-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(320px, .85fr);
        gap: 60px;
        align-items: center;
    }

    .pl-ai .pl-eyebrow {
        color: #8ed2e3;
    }

    .pl-ai h2 {
        margin: 12px 0 15px;
        color: #fff;
        font-size: clamp(2rem, 4vw, 3.2rem);
        line-height: 1.1;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .pl-ai p {
        color: #cbd5e1;
        line-height: 1.75;
    }

    .pl-ai-query {
        padding: 24px;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 18px;
        background: rgba(255,255,255,.06);
        box-shadow: 0 25px 55px rgba(0,0,0,.2);
    }

    .pl-ai-query-label {
        color: #8ed2e3;
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .pl-ai-question {
        margin: 10px 0 20px;
        padding: 16px;
        border-radius: 12px;
        background: rgba(255,255,255,.08);
        color: #fff;
        font-weight: 700;
        line-height: 1.55;
    }

    .pl-ai-answer {
        display: grid;
        gap: 10px;
    }

    .pl-ai-answer div {
        padding: 12px 14px;
        border-radius: 10px;
        background: rgba(15,23,42,.55);
        color: #dbe6ed;
        font-size: .92rem;
    }

    /* SECURITY */
    .pl-security-grid {
        display: grid;
        grid-template-columns: .9fr 1.1fr;
        gap: 56px;
        align-items: center;
    }

    .pl-security-panel {
        padding: 34px;
        border-radius: 20px;
        background: #0f172a;
        color: #fff;
    }

    .pl-security-panel > i {
        width: 64px;
        height: 64px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: rgba(56, 141, 168, .18);
        color: #8ed2e3;
        font-size: 1.8rem;
    }

    .pl-security-panel h3 {
        margin: 22px 0 12px;
        font-size: 1.8rem;
        line-height: 1.18;
        font-weight: 800;
    }

    .pl-security-panel p {
        margin: 0;
        color: #cbd5e1;
        line-height: 1.7;
    }

    .pl-security-points {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 26px;
    }

    .pl-security-point {
        padding: 16px;
        border: 1px solid var(--pl-line);
        border-radius: 12px;
        background: var(--pl-white);
    }

    .pl-security-point strong {
        display: block;
        color: var(--pl-heading);
        margin-bottom: 5px;
    }

    .pl-security-point span {
        color: var(--pl-muted);
        line-height: 1.55;
        font-size: .9rem;
    }

    .pl-legal-links {
        display: flex;
        flex-wrap: wrap;
        gap: 16px 24px;
        margin-top: 24px;
    }

    /* FAQ */
    .pl-faq {
        max-width: 900px;
        margin: 0 auto;
        display: grid;
        gap: 12px;
    }

    .pl-faq details {
        border: 1px solid var(--pl-line);
        border-radius: 12px;
        background: var(--pl-white);
        padding: 0 20px;
    }

    .pl-faq summary {
        cursor: pointer;
        list-style: none;
        position: relative;
        padding: 19px 34px 19px 0;
        color: var(--pl-heading);
        font-weight: 800;
    }

    .pl-faq summary::-webkit-details-marker {
        display: none;
    }

    .pl-faq summary::after {
        content: '+';
        position: absolute;
        right: 0;
        top: 15px;
        color: var(--pl-primary);
        font-size: 1.5rem;
        font-weight: 500;
    }

    .pl-faq details[open] summary::after {
        content: '−';
    }

    .pl-faq details p {
        margin: 0;
        padding: 0 0 20px;
        color: var(--pl-muted);
        line-height: 1.7;
    }

    /* CTA */
    .pl-final {
        padding: 0 0 96px;
    }

    .pl-final-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 30px;
        padding: 42px;
        border-radius: 22px;
        background: var(--pl-primary);
        color: #fff;
        box-shadow: 0 26px 60px rgba(56, 141, 168, .22);
    }

    .pl-final-card h2 {
        max-width: 720px;
        margin: 0 0 10px;
        font-size: clamp(1.85rem, 3.5vw, 3rem);
        line-height: 1.12;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .pl-final-card p {
        margin: 0;
        color: rgba(255,255,255,.84);
    }

    .pl-button-white {
        flex: 0 0 auto;
        color: var(--pl-primary-dark);
        background: #fff;
        box-shadow: 0 12px 24px rgba(15,23,42,.13);
    }

    @media (max-width: 1024px) {
        .pl-product-stage {
            min-height: 540px;
        }

        .pl-phone {
            width: 170px;
        }

        .pl-platform-grid,
        .pl-detail,
        .pl-ai-grid,
        .pl-security-grid {
            grid-template-columns: 1fr;
        }

        .pl-detail.reverse .pl-detail-copy,
        .pl-detail.reverse .pl-detail-media {
            order: initial;
        }

        .pl-tab-panels {
            min-height: 0;
        }

        .pl-product-card {
            min-height: 420px;
        }
    }

    @media (max-width: 767px) {
        .pl-hero {
            padding: 118px 0 62px;
        }

        .pl-section {
            padding: 70px 0;
        }

        .pl-featured-grid,
        .pl-capability-grid,
        .pl-security-points {
            grid-template-columns: 1fr;
        }

        .pl-product-stage {
            min-height: auto;
            display: grid;
            gap: 14px;
            margin-top: 42px;
        }

        .pl-dashboard-shell,
        .pl-phone,
        .pl-float-card {
            position: relative;
            inset: auto;
            width: 100%;
            transform: none;
        }

        .pl-dashboard-shell {
            left: auto;
            max-width: 100%;
        }

        .pl-phone {
            max-width: 220px;
            margin: 0 auto;
        }

        .pl-float-card {
            display: none;
        }

        .pl-platform-grid {
            gap: 28px;
        }

        .pl-detail {
            gap: 28px;
        }

        .pl-detail + .pl-detail {
            margin-top: 62px;
        }

        .pl-final-card {
            flex-direction: column;
            align-items: flex-start;
            padding: 30px 24px;
        }
    }
    /* =========================================================
   CONTRASTE EN SECCIONES OSCURAS
   ========================================================= */

/* PaseLista IA */
.pl-ai {
    color: #ffffff;
}

.pl-ai h2 {
    color: #ffffff;
}

.pl-ai > .pl-container p,
.pl-ai .pl-ai-grid > div > p {
    color: #d5dee8;
}

/* La regla general .pl-checks usa --pl-muted.
   Aquí la sobrescribimos específicamente para el fondo oscuro. */
.pl-ai .pl-checks li {
    color: #e2e8f0;
}

.pl-ai .pl-checks li i {
    color: #7dd3e8;
}

.pl-ai .pl-checks li strong {
    color: #ffffff;
}

/* Consulta IA */
.pl-ai-query {
    color: #ffffff;
}

.pl-ai-query-label {
    color: #9edbea;
}

.pl-ai-question {
    color: #ffffff;
}

.pl-ai-answer div {
    color: #dce7ee;
}

.pl-ai-answer strong {
    color: #ffffff;
}


/* =========================================================
   PANEL OSCURO DE SEGURIDAD
   ========================================================= */

.pl-security-panel {
    color: #ffffff;
}

.pl-security-panel > i {
    color: #9edbea;
}

.pl-security-panel h3 {
    color: #ffffff !important;
}

.pl-security-panel p {
    color: #d5dee8 !important;
}

.pl-security-panel strong {
    color: #ffffff;
}
</style>

@endpush

@section('content')
<div class="pl-home">

    {{-- HERO --}}
    <section id="inicio" class="pl-hero">
        <div class="pl-container">
            <div class="pl-hero-copy">
                <span class="pl-eyebrow">
                    <i class="uil uil-shield-check"></i>
                    Plataforma para instituciones educativas
                </span>

                <h1>
                    Control escolar más simple,
                    <span>conectado y seguro.</span>
                </h1>

                <p>
                    Gestiona accesos, asistencia, salidas y comunicación con familias desde una sola plataforma,
                    con herramientas para dirección, personal escolar y tutores.
                </p>

                <div class="pl-hero-actions">
                    <a href="{{ $commercialUrl }}" class="pl-button pl-button-primary">
                        Solicitar demostración
                        <i class="uil uil-arrow-right"></i>
                    </a>

                    <a href="#plataforma" class="pl-button pl-button-secondary">
                        Conocer la plataforma
                    </a>
                </div>

                <div class="pl-hero-proof">
                    <span><i class="uil uil-check-circle"></i> Empieza con QR</span>
                    <span><i class="uil uil-check-circle"></i> Opera desde celular o tablet</span>
                    <span><i class="uil uil-check-circle"></i> Preparado para NFC</span>
                </div>
            </div>

            <div class="pl-product-stage" aria-label="Ecosistema PaseLista">
                <div class="pl-dashboard-shell">
                    <div class="pl-dashboard-bar">
                        <span></span><span></span><span></span>
                    </div>
                    <img
                        src="{{ asset('landing/images/schoolpass/dashboard-schoolpass.jpeg') }}"
                        alt="Panel administrativo de PaseLista"
                    >
                </div>

                <div class="pl-float-card pl-float-a">
                    <strong>Asistencia del día</strong>
                    <small>Movimientos centralizados para dirección</small>
                </div>

                <div class="pl-float-card pl-float-b">
                    <strong>Familias informadas</strong>
                    <small>Avisos e historial desde PaseLista Family</small>
                </div>

                <div class="pl-phone pl-phone-staff">
                    <img
                        src="{{ asset('landing/images/schoolpass/app-staff.jpeg') }}"
                        alt="Aplicación PaseLista Staff"
                    >
                </div>

                <div class="pl-phone pl-phone-family">
                    <img
                        src="{{ asset('landing/images/schoolpass/app-family.jpeg') }}"
                        alt="Aplicación PaseLista Family"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- 3 PILARES --}}
    <section class="pl-featured">
        <div class="pl-container">
            <div class="pl-featured-grid">
                <article class="pl-featured-item">
                    <span class="pl-featured-icon"><i class="uil uil-qrcode-scan"></i></span>
                    <div>
                        <h3>Acceso y asistencia</h3>
                        <p>QR, entradas, salidas, puntualidad, retardos e incidencias desde puntos autorizados.</p>
                    </div>
                </article>

                <article class="pl-featured-item">
                    <span class="pl-featured-icon"><i class="uil uil-users-alt"></i></span>
                    <div>
                        <h3>Comunicación familiar</h3>
                        <p>Credenciales, alumnos vinculados, movimientos, avisos e historial desde PaseLista Family.</p>
                    </div>
                </article>

                <article class="pl-featured-item">
                    <span class="pl-featured-icon"><i class="uil uil-chart-line"></i></span>
                    <div>
                        <h3>Gestión escolar</h3>
                        <p>Panel, reportes, usuarios, auditoría y seguimiento operativo por institución.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- PLATAFORMA --}}
    <section id="plataforma" class="pl-section pl-section-soft">
        <div class="pl-container">
            <div class="pl-section-head">
                <span class="pl-eyebrow">Ecosistema PaseLista</span>
                <h2 class="pl-section-title">Tres experiencias conectadas por la misma operación.</h2>
                <p class="pl-section-copy">
                    Dirección obtiene visibilidad, el personal escolar registra los movimientos y las familias
                    consultan la información que la institución habilita.
                </p>
            </div>

            <div class="pl-platform-grid">
                <div class="pl-tabs" role="tablist" aria-label="Aplicaciones PaseLista">
                    <button type="button" class="pl-tab is-active" data-pl-tab="panel">
                        <span class="pl-tab-icon"><i class="uil uil-dashboard"></i></span>
                        <span>
                            <h3>Panel para dirección</h3>
                            <p>Asistencia, movimientos, grupos, reportes, usuarios y auditoría.</p>
                        </span>
                    </button>

                    <button type="button" class="pl-tab" data-pl-tab="staff">
                        <span class="pl-tab-icon"><i class="uil uil-mobile-android"></i></span>
                        <span>
                            <h3>PaseLista Staff</h3>
                            <p>Operación diaria para acceso, escaneo, incidencias y movimientos.</p>
                        </span>
                    </button>

                    <button type="button" class="pl-tab" data-pl-tab="family">
                        <span class="pl-tab-icon"><i class="uil uil-users-alt"></i></span>
                        <span>
                            <h3>PaseLista Family</h3>
                            <p>Información para madres, padres y tutores vinculados.</p>
                        </span>
                    </button>
                </div>

                <div class="pl-tab-panels">
                    <div class="pl-tab-panel is-active" data-pl-panel="panel">
                        <div class="pl-product-card">
                            <img
                                src="{{ asset('landing/images/schoolpass/dashboard-schoolpass.jpeg') }}"
                                alt="Panel de dirección de PaseLista"
                            >
                        </div>
                    </div>

                    <div class="pl-tab-panel" data-pl-panel="staff">
                        <div class="pl-product-card is-phone">
                            <img
                                src="{{ asset('landing/images/schoolpass/app-staff.jpeg') }}"
                                alt="PaseLista Staff para personal escolar"
                            >
                        </div>
                    </div>

                    <div class="pl-tab-panel" data-pl-panel="family">
                        <div class="pl-product-card is-phone">
                            <img
                                src="{{ asset('landing/images/schoolpass/app-family.jpeg') }}"
                                alt="PaseLista Family para familias"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- DETALLES --}}
    <section id="aplicaciones" class="pl-section">
        <div class="pl-container">
            <div class="pl-detail">
                <div class="pl-detail-media">
                    <img
                        src="{{ asset('landing/images/schoolpass/dashboard-schoolpass.jpeg') }}"
                        alt="Operación escolar en el panel de PaseLista"
                    >
                </div>

                <div class="pl-detail-copy">
                    <span class="pl-eyebrow">Visibilidad para dirección</span>
                    <h2>Lo que ocurre durante el día deja de estar disperso.</h2>
                    <p>
                        El panel concentra asistencia, puntualidad, movimientos, incidencias y actividad operativa
                        para que la institución consulte el estado del día y revise históricos cuando lo necesite.
                    </p>

                    <ul class="pl-checks">
                        <li><i class="uil uil-check-circle"></i> Consulta por plantel, nivel, grupo o alumno.</li>
                        <li><i class="uil uil-check-circle"></i> Resúmenes diarios, históricos y reportes.</li>
                        <li><i class="uil uil-check-circle"></i> Usuarios, roles, dispositivos y puntos de acceso.</li>
                        <li><i class="uil uil-check-circle"></i> Historial operativo y registros de auditoría.</li>
                    </ul>
                </div>
            </div>

            <div class="pl-detail reverse">
                <div class="pl-detail-copy">
                    <span class="pl-eyebrow">PaseLista Family</span>
                    <h2>La información importante llega a quien corresponde.</h2>
                    <p>
                        Madres, padres y tutores vinculados pueden consultar la información habilitada por la escuela
                        desde una experiencia independiente de la operación interna.
                    </p>

                    <ul class="pl-checks">
                        <li><i class="uil uil-check-circle"></i> Alumnos vinculados y credenciales.</li>
                        <li><i class="uil uil-check-circle"></i> Historial de entradas, salidas y movimientos.</li>
                        <li><i class="uil uil-check-circle"></i> Avisos escolares y seguimiento diario.</li>
                        <li><i class="uil uil-check-circle"></i> Acceso protegido bajo las reglas de la institución.</li>
                    </ul>
                </div>

                <div class="pl-detail-media phone-media">
                    <img
                        src="{{ asset('landing/images/schoolpass/app-family.jpeg') }}"
                        alt="PaseLista Family para tutores"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- FUNCIONES --}}
    <section id="funciones" class="pl-section pl-section-soft">
        <div class="pl-container">
            <div class="pl-section-head">
                <span class="pl-eyebrow">Funciones principales</span>
                <h2 class="pl-section-title">Lo necesario para operar el acceso escolar sin agregar complejidad.</h2>
                <p class="pl-section-copy">
                    PaseLista se concentra en identificar, registrar, informar y consultar.
                </p>
            </div>

            <div class="pl-capability-grid">
                @foreach([
                    [
                        'icon' => 'uil-qrcode-scan',
                        'title' => 'Credenciales QR y NFC',
                        'text' => 'Utiliza QR impreso o digital desde el primer día y conserva la opción de incorporar tarjetas NFC.'
                    ],
                    [
                        'icon' => 'uil-user-check',
                        'title' => 'Asistencia y puntualidad',
                        'text' => 'Registra entradas, salidas, retardos, ausencias y salidas anticipadas según las reglas de la institución.'
                    ],
                    [
                        'icon' => 'uil-users-alt',
                        'title' => 'Tutores autorizados',
                        'text' => 'Relaciona alumnos con madres, padres o tutores y administra las autorizaciones definidas por la escuela.'
                    ],
                    [
                        'icon' => 'uil-mobile-android',
                        'title' => 'Operación desde móvil o tablet',
                        'text' => 'El personal escolar trabaja desde dispositivos autorizados sin depender de una computadora fija.'
                    ],
                    [
                        'icon' => 'uil-bell',
                        'title' => 'Avisos para familias',
                        'text' => 'Entrega a las familias la información que la institución haya habilitado para su operación.'
                    ],
                    [
                        'icon' => 'uil-chart-line',
                        'title' => 'Reportes para dirección',
                        'text' => 'Consulta tendencias de asistencia, puntualidad, movimientos e incidencias por periodos y grupos.'
                    ],
                ] as $item)
                    <article class="pl-capability">
                        <span class="pl-capability-icon">
                            <i class="uil {{ $item['icon'] }}"></i>
                        </span>
                        <div>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['text'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- IA --}}
    <section class="pl-section pl-ai">
        <div class="pl-container">
            <div class="pl-ai-grid">
                <div>
                    <span class="pl-eyebrow">
                        <i class="uil uil-brain"></i>
                        PaseLista IA
                    </span>

                    <h2>Convierte registros escolares en respuestas más fáciles de revisar.</h2>

                    <p>
                        Dirección puede consultar patrones de asistencia, puntualidad, accesos e incidencias
                        mediante preguntas en lenguaje natural, con controles por escuela, alcance y periodo.
                    </p>

                    <ul class="pl-checks">
                        <li><i class="uil uil-check-circle"></i> Resúmenes ejecutivos con información autorizada.</li>
                        <li><i class="uil uil-check-circle"></i> Análisis por institución, grupo o alumno autorizado.</li>
                        <li><i class="uil uil-check-circle"></i> Apoyo administrativo sin sustituir la revisión humana.</li>
                    </ul>
                </div>

                <div class="pl-ai-query">
                    <div class="pl-ai-query-label">Consulta de ejemplo</div>

                    <div class="pl-ai-question">
                        ¿Qué grupos tuvieron más retardos durante este mes?
                    </div>

                    <div class="pl-ai-answer">
                        <div><strong>Resumen:</strong> identifica los grupos con mayor incidencia en el periodo seleccionado.</div>
                        <div><strong>Contexto:</strong> compara asistencia y puntualidad usando únicamente el alcance autorizado.</div>
                        <div><strong>Seguimiento:</strong> permite revisar la información antes de tomar decisiones administrativas.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SEGURIDAD --}}
    <section id="seguridad" class="pl-section">
        <div class="pl-container">
            <div class="pl-security-grid">
                <div class="pl-security-panel">
                    <i class="uil uil-lock-shield"></i>
                    <h3>La operación y los usuarios permanecen bajo control institucional.</h3>
                    <p>
                        PaseLista aplica separación por institución, permisos por rol y registros de auditoría.
                        La escuela define altas, vinculaciones, accesos y autorizaciones.
                    </p>
                </div>

                <div>
                    <span class="pl-eyebrow">Privacidad y control</span>
                    <h2 class="pl-section-title" style="margin-left: 0; margin-right: 0;">
                        Seguridad integrada en la operación.
                    </h2>

                    <p class="pl-section-copy">
                        Cada persona accede únicamente a las funciones y datos permitidos para su institución y responsabilidad.
                    </p>

                    <div class="pl-security-points">
                        <div class="pl-security-point">
                            <strong>Roles y permisos</strong>
                            <span>Acceso diferenciado para dirección, personal escolar y familias.</span>
                        </div>

                        <div class="pl-security-point">
                            <strong>Separación por institución</strong>
                            <span>La información se organiza bajo el contexto de cada escuela.</span>
                        </div>

                        <div class="pl-security-point">
                            <strong>Auditoría</strong>
                            <span>Registro de acciones administrativas y movimientos relevantes.</span>
                        </div>

                        <div class="pl-security-point">
                            <strong>Control de credenciales</strong>
                            <span>Emisión, vigencia y revocación conforme a las reglas configuradas.</span>
                        </div>
                    </div>

                    <div class="pl-legal-links">
                        <a href="{{ $privacyUrl }}" class="pl-text-link">
                            Aviso de privacidad <i class="uil uil-arrow-right"></i>
                        </a>

                        <a href="{{ $deletionUrl }}" class="pl-text-link">
                            Derechos ARCO y eliminación <i class="uil uil-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="preguntas" class="pl-section pl-section-soft">
        <div class="pl-container">
            <div class="pl-section-head">
                <span class="pl-eyebrow">Preguntas frecuentes</span>
                <h2 class="pl-section-title">Lo esencial antes de implementar PaseLista.</h2>
            </div>

            <div class="pl-faq">
                <details open>
                    <summary>¿Necesito comprar lectores especiales para empezar?</summary>
                    <p>
                        No. La operación puede iniciar con códigos QR y dispositivos móviles autorizados.
                        La institución puede incorporar NFC cuando la función se encuentre habilitada para su operación.
                    </p>
                </details>

                <details>
                    <summary>¿PaseLista sirve únicamente para registrar asistencia?</summary>
                    <p>
                        No. Además de asistencia, concentra accesos, salidas, puntualidad, incidencias, credenciales,
                        tutores vinculados, avisos y reportes administrativos.
                    </p>
                </details>

                <details>
                    <summary>¿Las familias ven toda la información de la escuela?</summary>
                    <p>
                        No. PaseLista Family muestra únicamente la información y funciones habilitadas para la cuenta
                        y los alumnos vinculados por la institución.
                    </p>
                </details>

                <details>
                    <summary>¿Cómo se administran las cuentas y los datos escolares?</summary>
                    <p>
                        La institución educativa administra las altas, relaciones, permisos y reglas operativas.
                        PaseLista proporciona la plataforma tecnológica y los controles asociados.
                    </p>
                </details>

                <details>
                    <summary>¿Dónde puedo reportar un problema o solicitar ayuda?</summary>
                    <p>
                        Puedes utilizar el centro de soporte público para temas técnicos, acceso, privacidad o información comercial.
                        <a href="{{ $supportUrl }}" class="pl-text-link" style="margin-left: 4px;">
                            Ir a soporte <i class="uil uil-arrow-right"></i>
                        </a>
                    </p>
                </details>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="pl-final">
        <div class="pl-container">
            <div class="pl-final-card">
                <div>
                    <h2>Conoce PaseLista en funcionamiento.</h2>
                    <p>
                        Descubre cómo puede adaptarse al tamaño, nivel educativo y operación diaria de tu institución.
                    </p>
                </div>

                <a href="{{ $commercialUrl }}" class="pl-button pl-button-white">
                    Solicitar demostración
                    <i class="uil uil-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('[data-pl-tab]');
        const panels = document.querySelectorAll('[data-pl-panel]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.plTab;

                tabs.forEach((item) => {
                    item.classList.toggle('is-active', item === tab);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle(
                        'is-active',
                        panel.dataset.plPanel === target
                    );
                });
            });
        });
    });
</script>
@endpush
