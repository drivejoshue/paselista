@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('content')

<div class="card">
    <div class="card-body p-4 p-md-5">

        {{-- IDENTIDAD SCHOOLCORE --}}
        <div class="d-flex align-items-center gap-3 mb-4">

            <div
                class="d-flex align-items-center justify-content-center
                       rounded-3 text-white fw-bold flex-shrink-0"
                style="
                    width: 56px;
                    height: 56px;
                    background: #2563eb;
                    font-size: 23px;
                    letter-spacing: -1px;
                    box-shadow: 0 4px 12px rgba(37,99,235,.18);
                "
            >
                SC
            </div>

            <div>
                <div
                    class="fw-bold text-body"
                    style="font-size: 21px; line-height: 1.2;"
                >
                    {{ config('app.name') }}
                </div>

                <div class="small text-secondary mt-1">
                    Plataforma educativa
                </div>
            </div>
        </div>

        <div class="mb-4">

            <div class="text-uppercase text-primary small fw-bold mb-2">
                Acceso institucional
            </div>

            <h1 class="card-title h2 mb-2">
                Iniciar sesión
            </h1>

            <p class="text-secondary mb-0">
                Accede a tu espacio escolar con las
                credenciales asignadas por tu institución.
            </p>

        </div>

        {{-- MENSAJES DEL SISTEMA --}}
        @if(session('status'))
            <div class="alert alert-success" role="alert">
                <i class="ti ti-circle-check me-2"></i>
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="ti ti-alert-circle me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        {{-- FORMULARIO DE AUTENTICACIÓN --}}
        <form
            method="POST"
            action="{{ route('login.store') }}"
            autocomplete="on"
        >
            @csrf

            <div class="mb-3">
                <label
                    for="login-email"
                    class="form-label fw-medium"
                >
                    Correo electrónico o usuario
                </label>

                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="ti ti-user"></i>
                    </span>

                    <input
                        id="login-email"
                        type="text"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        class="form-control form-control-lg
                            @error('email') is-invalid @enderror"
                        placeholder="Correo o nombre de usuario"
                    >
                </div>

                @error('email')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-2">

                <div class="d-flex justify-content-between align-items-center gap-2">

                    <label
                        for="login-password"
                        class="form-label fw-medium mb-0"
                    >
                        Contraseña
                    </label>

                    <a
                        href="{{ route('password.request') }}"
                        class="small text-decoration-none"
                    >
                        ¿Olvidaste tu contraseña?
                    </a>

                </div>

                <div class="input-icon mt-2">
                    <span class="input-icon-addon">
                        <i class="ti ti-lock"></i>
                    </span>

                    <input
                        id="login-password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="form-control form-control-lg
                            @error('password') is-invalid @enderror"
                        placeholder="Escribe tu contraseña"
                    >
                </div>

                @error('password')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- RECORDAR SESIÓN --}}
            <div class="mb-4 mt-3">
                <label class="form-check">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        class="form-check-input"
                        {{ old('remember') ? 'checked' : '' }}
                    >

                    <span class="form-check-label">
                        Mantener sesión iniciada en este equipo
                    </span>

                </label>
            </div>

            {{-- ACCESO --}}
            <button
                type="submit"
                class="btn btn-primary btn-lg w-100"
            >
                <i class="ti ti-login-2 me-2"></i>
                Iniciar sesión
            </button>

        </form>

        {{-- INFORMACIÓN INSTITUCIONAL --}}
        <div
            class="text-center text-secondary small mt-4 pt-3 border-top"
        >
            <i class="ti ti-shield-lock me-1"></i>

            Acceso reservado a usuarios autorizados
            por su institución educativa.
        </div>

    </div>
</div>

{{-- ACCESOS PARA DEMOSTRACIÓN --}}
@if(
    config('demo.show_accesses') &&
    app()->environment(['local', 'demo'])
)

<div class="card mt-3 border-primary">

    <div class="card-header">
        <div>
            <div class="fw-bold">
                <i class="ti ti-device-desktop-analytics me-2"></i>
                Explora la plataforma
            </div>

            <div class="text-secondary small mt-1">
                Cuentas de prueba para conocer
                las funciones de cada perfil.
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="row g-2">

            @foreach(config('demo.accounts', []) as $account)

                @if(!empty($account['email']))
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 h-100">

                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="{{ $account['icon'] ?? 'ti ti-user' }} text-primary"></i>

                                <strong class="small">
                                    {{ $account['label'] }}
                                </strong>
                            </div>

                            <div
                                class="text-secondary small mb-2"
                                style="overflow-wrap:anywhere;"
                            >
                                {{ $account['email'] }}
                            </div>

                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm w-100"
                                data-demo-email="{{ $account['email'] }}"
                                onclick="
                                    document.getElementById('login-email').value =
                                        this.dataset.demoEmail;
                                    document.getElementById('login-password').focus();
                                "
                            >
                                <i class="ti ti-arrow-up-right me-1"></i>
                                Usar esta cuenta
                            </button>

                        </div>
                    </div>
                @endif

            @endforeach

        </div>

        @if(config('demo.password'))
            <div class="alert alert-info mt-3 mb-0">

                <div class="fw-semibold mb-1">
                    <i class="ti ti-key me-1"></i>
                    Contraseña de demostración
                </div>

                <code class="fw-bold">
                    {{ config('demo.password') }}
                </code>

                <div class="small mt-2">
                    Utilízala con las cuentas demo
                    que tengan habilitada esta clave.
                </div>
            </div>
        @endif

        <div class="border-top mt-3 pt-3">

            <div class="fw-semibold small mb-2">
                <i class="ti ti-report me-1"></i>
                Alumno con calificaciones publicadas
            </div>

            <div class="small mb-1">
                <strong>Mariana Castillo Vega</strong>
            </div>

            <div class="small text-secondary">
                Matrícula: IADEMO-1-6-001
            </div>

            <div class="small text-secondary mb-2">
                CURP ficticia: DEMO140101HVZBHZA8
            </div>

            @if(config('demo.school_slug'))
                <a
                    href="{{ route('school.portal.grades', [
                        'school' => config('demo.school_slug')
                    ]) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-outline-success btn-sm w-100"
                >
                    <i class="ti ti-file-text me-1"></i>
                    Consultar calificaciones demo
                </a>
            @endif

        </div>

    </div>
</div>

@endif


@endsection
