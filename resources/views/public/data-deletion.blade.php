@extends('layouts.public-site')

@section('title', 'Derechos ARCO y eliminación de datos · PaseLista')
@section('meta-description', 'Formulario para solicitar acceso, rectificación, cancelación, oposición o eliminación de datos en PaseLista.')

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
        max-width: 940px;
        margin: 0 0 16px;
        color: var(--heading-color);
        font-size: clamp(2.35rem, 5vw, 4rem);
        line-height: 1.08;
        letter-spacing: -.045em;
        font-weight: 800;
    }

    .ps-page-hero p {
        max-width: 820px;
        margin: 0;
        color: color-mix(in srgb, var(--default-color), transparent 18%);
        font-size: 1.08rem;
        line-height: 1.75;
    }

    .ps-arco-section {
        padding: 78px 0 96px;
    }

    .ps-info-card,
    .ps-form-card {
        border: 1px solid color-mix(in srgb, var(--default-color), transparent 88%);
        border-radius: 18px;
        background: var(--surface-color);
        box-shadow: 0 14px 38px rgba(15, 23, 42, .06);
    }

    .ps-info-card {
        position: sticky;
        top: 110px;
        padding: 30px;
    }

    .ps-info-icon {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        margin-bottom: 22px;
        border-radius: 15px;
        background: color-mix(in srgb, var(--accent-color), transparent 88%);
        color: var(--accent-color);
        font-size: 1.5rem;
    }

    .ps-info-card h2,
    .ps-form-card h2 {
        margin: 0 0 12px;
        color: var(--heading-color);
        font-size: 1.45rem;
        font-weight: 800;
    }

    .ps-info-card > p {
        margin: 0;
        color: color-mix(in srgb, var(--default-color), transparent 16%);
        line-height: 1.72;
    }

    .ps-check-list {
        list-style: none;
        padding: 0;
        margin: 24px 0 0;
        display: grid;
        gap: 13px;
    }

    .ps-check-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: color-mix(in srgb, var(--default-color), transparent 14%);
        line-height: 1.62;
    }

    .ps-check-list i {
        flex: 0 0 auto;
        margin-top: 2px;
        color: var(--accent-color);
        font-size: 1.05rem;
    }

    .ps-text-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 24px;
        color: var(--accent-color);
        font-weight: 700;
        text-decoration: none;
    }

    .ps-form-card {
        padding: 32px;
    }

    .ps-form-head {
        padding-bottom: 24px;
        margin-bottom: 28px;
        border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
    }

    .ps-form-head p {
        margin: 0;
        color: color-mix(in srgb, var(--default-color), transparent 18%);
        line-height: 1.65;
    }

    .ps-public-form .form-label {
        margin-bottom: 8px;
        color: var(--heading-color);
        font-size: .92rem;
        font-weight: 700;
    }

    .ps-public-form .form-control,
    .ps-public-form .form-select {
        min-height: 48px;
        border: 1px solid color-mix(in srgb, var(--default-color), transparent 82%);
        border-radius: 9px;
        background-color: var(--surface-color);
        color: var(--default-color);
        box-shadow: none;
    }

    .ps-public-form textarea.form-control {
        min-height: 150px;
        resize: vertical;
    }

    .ps-public-form .form-control:focus,
    .ps-public-form .form-select:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--accent-color), transparent 84%);
    }

    .ps-public-form .form-text {
        color: color-mix(in srgb, var(--default-color), transparent 38%);
    }

    .ps-honeypot {
        position: absolute !important;
        left: -10000px !important;
        top: auto !important;
        width: 1px !important;
        height: 1px !important;
        overflow: hidden !important;
    }

    .ps-consent {
        padding: 16px 18px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--accent-color), transparent 94%);
        border: 1px solid color-mix(in srgb, var(--accent-color), transparent 84%);
    }

    .ps-consent .form-check {
        margin: 0;
    }

    .ps-consent .form-check-input {
        width: 1.1rem;
        height: 1.1rem;
        margin-top: .18rem;
    }

    .ps-consent .form-check-input:checked {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
    }

    .ps-consent .form-check-label {
        color: color-mix(in srgb, var(--default-color), transparent 10%);
        line-height: 1.6;
    }

    .ps-consent a {
        color: var(--accent-color);
        font-weight: 700;
    }

    .ps-submit {
        min-height: 49px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 22px;
        border: 0;
        border-radius: 9px;
        background: var(--accent-color);
        color: var(--contrast-color);
        font-weight: 700;
        transition: transform .2s ease, filter .2s ease;
    }

    .ps-submit:hover {
        transform: translateY(-2px);
        filter: brightness(.94);
    }

    .ps-alert {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 16px 18px;
        margin-bottom: 24px;
        border-radius: 11px;
        line-height: 1.6;
    }

    .ps-alert > i {
        flex: 0 0 auto;
        margin-top: 2px;
        font-size: 1.2rem;
    }

    .ps-alert-success {
        color: #166534;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .ps-alert-error {
        color: #991b1b;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    .ps-alert-error ul {
        margin: 8px 0 0;
        padding-left: 18px;
    }

    @media (max-width: 991.98px) {
        .ps-info-card {
            position: static;
        }
    }

    @media (max-width: 767.98px) {
        .ps-page-hero {
            padding: 124px 0 58px;
        }

        .ps-arco-section {
            padding: 58px 0 76px;
        }

        .ps-info-card,
        .ps-form-card {
            padding: 24px;
        }

        .ps-submit {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<section class="ps-page-hero">
    <div class="container position-relative">
        <span class="ps-page-eyebrow">
            <i class="bi bi-shield-check"></i>
            Control de datos personales
        </span>

        <h1>Derechos ARCO y eliminación de datos</h1>

        <p>
            Envía una solicitud para acceder, rectificar, cancelar, oponerte
            o pedir la eliminación de una cuenta y sus datos asociados.
        </p>
    </div>
</section>

<section class="ps-arco-section">
    <div class="container">

        <div class="row g-4 g-lg-5 align-items-start">

            <div class="col-lg-5">
                <aside class="ps-info-card">
                    <span class="ps-info-icon">
                        <i class="bi bi-person-lock"></i>
                    </span>

                    <h2>Antes de enviar</h2>

                    <p>
                        Las cuentas de Staff y Family normalmente son administradas por la
                        institución educativa. Para proteger a alumnos y tutores, PaseLista
                        verificará la identidad y el vínculo antes de entregar, modificar
                        o eliminar información.
                    </p>

                    <ul class="ps-check-list">
                        <li>
                            <i class="bi bi-check-circle"></i>
                            <span>No incluyas contraseñas ni imágenes de identificaciones en este formulario.</span>
                        </li>

                        <li>
                            <i class="bi bi-check-circle"></i>
                            <span>Indica la institución y el correo de la cuenta cuando corresponda.</span>
                        </li>

                        <li>
                            <i class="bi bi-check-circle"></i>
                            <span>PaseLista podrá solicitar evidencia mediante un canal seguro.</span>
                        </li>

                        <li>
                            <i class="bi bi-check-circle"></i>
                            <span>Algunos registros podrán conservarse cuando exista una obligación legal o de seguridad.</span>
                        </li>
                    </ul>

                    <a
                        href="{{ route('public.privacy') }}"
                        class="ps-text-link"
                    >
                        Leer aviso de privacidad
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </aside>
            </div>

            <div class="col-lg-7">
                <div class="ps-form-card">

                    <div class="ps-form-head">
                        <h2>Registrar solicitud</h2>
                        <p>
                            Completa los datos necesarios para identificar la cuenta,
                            institución y derecho que deseas ejercer.
                        </p>
                    </div>

                    @if(session('privacy_request_status'))
                        <div class="ps-alert ps-alert-success" role="status">
                            <i class="bi bi-check-circle-fill"></i>

                            <div>
                                {{ session('privacy_request_status') }}
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="ps-alert ps-alert-error" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i>

                            <div>
                                <strong>Revisa la solicitud.</strong>

                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('public.privacy-requests.store') }}"
                        class="ps-public-form"
                    >
                        @csrf

                        {{-- Honeypot anti-spam --}}
                        <div class="ps-honeypot" aria-hidden="true">
                            <label for="website">Sitio web</label>
                            <input
                                id="website"
                                name="website"
                                type="text"
                                tabindex="-1"
                                autocomplete="off"
                            >
                        </div>

                        <div class="row g-4">

                            <div class="col-12">
                                <label for="request_type" class="form-label">
                                    Tipo de solicitud
                                </label>

                                <select
                                    id="request_type"
                                    name="request_type"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Selecciona una opción</option>

                                    <option value="access" @selected(old('request_type') === 'access')>
                                        Acceso a mis datos
                                    </option>

                                    <option value="rectification" @selected(old('request_type') === 'rectification')>
                                        Rectificación o corrección
                                    </option>

                                    <option value="cancellation" @selected(old('request_type') === 'cancellation')>
                                        Cancelación
                                    </option>

                                    <option value="opposition" @selected(old('request_type') === 'opposition')>
                                        Oposición o limitación
                                    </option>

                                    <option value="account_deletion" @selected(old('request_type') === 'account_deletion')>
                                        Eliminar cuenta
                                    </option>

                                    <option value="data_deletion" @selected(old('request_type') === 'data_deletion')>
                                        Eliminar datos asociados
                                    </option>

                                    <option value="data_copy" @selected(old('request_type') === 'data_copy')>
                                        Solicitar copia de datos
                                    </option>

                                    <option value="security_report" @selected(old('request_type') === 'security_report')>
                                        Reportar acceso no autorizado
                                    </option>

                                    <option value="other" @selected(old('request_type') === 'other')>
                                        Otra solicitud
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="full_name" class="form-label">
                                    Nombre completo
                                </label>

                                <input
                                    id="full_name"
                                    name="full_name"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('full_name') }}"
                                    maxlength="180"
                                    autocomplete="name"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    Correo de contacto
                                </label>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    class="form-control"
                                    value="{{ old('email') }}"
                                    maxlength="190"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="relationship" class="form-label">
                                    Relación con PaseLista
                                </label>

                                <select
                                    id="relationship"
                                    name="relationship"
                                    class="form-select"
                                >
                                    <option value="">Selecciona</option>

                                    <option value="guardian" @selected(old('relationship') === 'guardian')>
                                        Madre, padre o tutor
                                    </option>

                                    <option value="staff" @selected(old('relationship') === 'staff')>
                                        Personal escolar
                                    </option>

                                    <option value="student_representative" @selected(old('relationship') === 'student_representative')>
                                        Representante de alumno
                                    </option>

                                    <option value="school" @selected(old('relationship') === 'school')>
                                        Representante de institución
                                    </option>

                                    <option value="other" @selected(old('relationship') === 'other')>
                                        Otra
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="school_name" class="form-label">
                                    Institución educativa
                                </label>

                                <input
                                    id="school_name"
                                    name="school_name"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('school_name') }}"
                                    maxlength="190"
                                >
                            </div>

                            <div class="col-12">
                                <label for="account_reference" class="form-label">
                                    Referencia de cuenta
                                </label>

                                <input
                                    id="account_reference"
                                    name="account_reference"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('account_reference') }}"
                                    maxlength="190"
                                    placeholder="Correo de acceso, matrícula o folio; no escribas contraseñas"
                                >
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">
                                    Describe la solicitud
                                </label>

                                <textarea
                                    id="description"
                                    name="description"
                                    class="form-control"
                                    rows="6"
                                    minlength="20"
                                    maxlength="3000"
                                    required
                                >{{ old('description') }}</textarea>

                                <div class="form-text">
                                    No adjuntes identificaciones ni información sensible en esta etapa.
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="ps-consent">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="privacy_acknowledgement"
                                            value="1"
                                            id="privacy_acknowledgement"
                                            @checked(old('privacy_acknowledgement'))
                                            required
                                        >

                                        <label
                                            class="form-check-label"
                                            for="privacy_acknowledgement"
                                        >
                                            Leí el
                                            <a
                                                href="{{ route('public.privacy') }}"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                aviso de privacidad
                                            </a>
                                            y autorizo el tratamiento de los datos de este formulario
                                            para atender la solicitud.
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="ps-submit">
                                    Registrar solicitud
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</section>
@endsection