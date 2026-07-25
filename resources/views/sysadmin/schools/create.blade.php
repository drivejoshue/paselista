@extends('layouts.sysadmin')

@section('title', 'Nueva escuela · PaseLista')
@section('page_title', 'Alta unificada de escuela')

@section('content')
    @php
        $weekdayLabels = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    @endphp

    @if(session('error'))
        <div class="alert alert-danger">
            <div class="d-flex">
                <div>
                    <i class="ti ti-alert-circle icon alert-icon"></i>
                </div>

                <div>
                    <h4 class="alert-title">
                        No se completó el alta
                    </h4>

                    <div class="text-secondary">
                        {{ session('error') }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="d-flex">
                <div>
                    <i class="ti ti-alert-circle icon alert-icon"></i>
                </div>

                <div>
                    <h4 class="alert-title">
                        Revisa la información
                    </h4>

                    <div class="text-secondary">
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('sysadmin.schools.store') }}"
        enctype="multipart/form-data"
        id="school-onboarding-form"
    >
        @csrf

        <div class="row row-cards">
            <div class="col-xl-8">

                {{-- Institución --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                1. Institución e identidad
                            </h3>

                            <div class="text-secondary">
                                Datos canónicos utilizados por el panel,
                                reportes, credenciales y aplicaciones.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label required">
                                    Nombre de la escuela
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    id="school-name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    maxlength="150"
                                    required
                                    placeholder="Colegio Ejemplo Veracruz"
                                >

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Estado
                                </label>

                                <select
                                    name="status"
                                    class="form-select"
                                    required
                                >
                                    <option
                                        value="active"
                                        @selected(
                                            old('status', 'active')
                                            === 'active'
                                        )
                                    >
                                        Activa
                                    </option>

                                    <option
                                        value="suspended"
                                        @selected(
                                            old('status')
                                            === 'suspended'
                                        )
                                    >
                                        Suspendida
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label">
                                    Razón social
                                </label>

                                <input
                                    type="text"
                                    name="legal_name"
                                    class="form-control"
                                    value="{{ old('legal_name') }}"
                                    maxlength="180"
                                >
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">
                                    Slug
                                </label>

                                <input
                                    type="text"
                                    name="slug"
                                    class="form-control"
                                    value="{{ old('slug') }}"
                                    maxlength="100"
                                    placeholder="Se genera automáticamente"
                                >

                                <div class="form-hint">
                                    Déjalo vacío para generarlo desde el nombre.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Zona horaria
                                </label>

                                <select
                                    name="timezone"
                                    class="form-select"
                                    required
                                >
                                    @foreach([
                                        'America/Mexico_City' => 'Centro de México',
                                        'America/Cancun' => 'Quintana Roo',
                                        'America/Chihuahua' => 'Chihuahua',
                                        'America/Hermosillo' => 'Sonora',
                                        'America/Tijuana' => 'Tijuana',
                                    ] as $timezone => $label)
                                        <option
                                            value="{{ $timezone }}"
                                            @selected(
                                                old(
                                                    'timezone',
                                                    'America/Mexico_City'
                                                ) === $timezone
                                            )
                                        >
                                            {{ $label }} · {{ $timezone }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    RFC / identificación fiscal
                                </label>

                                <input
                                    type="text"
                                    name="tax_id"
                                    class="form-control"
                                    value="{{ old('tax_id') }}"
                                    maxlength="30"
                                >
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">
                                    Logotipo institucional
                                </label>

                                <input
                                    type="file"
                                    name="logo"
                                    id="school-logo"
                                    class="form-control @error('logo') is-invalid @enderror"
                                    accept="image/png,image/jpeg,image/webp"
                                >

                                @error('logo')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="form-hint">
                                    PNG, JPG o WebP. Máximo 4 MB.
                                    Recomendado: imagen cuadrada de 1024 × 1024.
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required">
                                    Color principal
                                </label>

                                <div class="input-group">
                                    <input
                                        type="color"
                                        class="form-control form-control-color"
                                        id="primary-color-picker"
                                        value="{{ old(
                                            'primary_color',
                                            '#2563EB'
                                        ) }}"
                                        title="Color principal"
                                    >

                                    <input
                                        type="text"
                                        name="primary_color"
                                        id="primary-color"
                                        class="form-control"
                                        value="{{ old(
                                            'primary_color',
                                            '#2563EB'
                                        ) }}"
                                        maxlength="7"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required">
                                    Color secundario
                                </label>

                                <div class="input-group">
                                    <input
                                        type="color"
                                        class="form-control form-control-color"
                                        id="secondary-color-picker"
                                        value="{{ old(
                                            'secondary_color',
                                            '#0F172A'
                                        ) }}"
                                        title="Color secundario"
                                    >

                                    <input
                                        type="text"
                                        name="secondary_color"
                                        id="secondary-color"
                                        class="form-control"
                                        value="{{ old(
                                            'secondary_color',
                                            '#0F172A'
                                        ) }}"
                                        maxlength="7"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label">
                                    Vista
                                </label>

                                <div
                                    class="avatar avatar-xl"
                                    id="school-logo-preview-box"
                                    style="
                                        background:
                                            {{ old(
                                                'primary_color',
                                                '#2563EB'
                                            ) }};
                                        color: #fff;
                                    "
                                >
                                    <img
                                        id="school-logo-preview"
                                        alt="Vista previa"
                                        class="d-none"
                                        style="
                                            width: 100%;
                                            height: 100%;
                                            object-fit: contain;
                                        "
                                    >

                                    <span id="school-logo-fallback">
                                        <i class="ti ti-school fs-1"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Nombre del contacto
                                </label>

                                <input
                                    type="text"
                                    name="contact_name"
                                    class="form-control"
                                    value="{{ old('contact_name') }}"
                                    maxlength="160"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Correo institucional
                                </label>

                                <input
                                    type="email"
                                    name="contact_email"
                                    class="form-control"
                                    value="{{ old('contact_email') }}"
                                    maxlength="180"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Teléfono
                                </label>

                                <input
                                    type="tel"
                                    name="contact_phone"
                                    class="form-control"
                                    value="{{ old('contact_phone') }}"
                                    maxlength="30"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Correo de soporte
                                </label>

                                <input
                                    type="email"
                                    name="support_email"
                                    class="form-control"
                                    value="{{ old('support_email') }}"
                                    maxlength="180"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    WhatsApp institucional
                                </label>

                                <input
                                    type="tel"
                                    name="whatsapp_number"
                                    class="form-control"
                                    value="{{ old('whatsapp_number') }}"
                                    maxlength="30"
                                >
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    Dirección
                                </label>

                                <input
                                    type="text"
                                    name="address"
                                    class="form-control"
                                    value="{{ old('address') }}"
                                    maxlength="255"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Licencia --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                2. Plan y licencia
                            </h3>

                            <div class="text-secondary">
                                La escuela quedará operativa desde el primer
                                acceso, incluso cuando se encuentre en trial.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label required">
                                    Plan
                                </label>

                                <select
                                    name="subscription_plan_id"
                                    id="subscription-plan"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        Selecciona un plan
                                    </option>

                                    @foreach($plans as $plan)
                                        <option
                                            value="{{ $plan->id }}"
                                            data-student-limit="{{ $plan->student_limit }}"
                                            data-device-limit="{{ $plan->device_limit }}"
                                            data-staff-limit="{{ $plan->staff_limit }}"
                                            data-campus-limit="{{ $plan->campus_limit }}"
                                            data-monthly-price="{{ $plan->monthly_price }}"
                                            data-annual-price="{{ $plan->annual_price }}"
                                            @selected(
                                                (string) old(
                                                    'subscription_plan_id'
                                                )
                                                === (string) $plan->id
                                            )
                                        >
                                            {{ $plan->name }}
                                            · {{ $plan->code }}
                                        </option>
                                    @endforeach
                                </select>

                                @if($plans->isEmpty())
                                    <div class="form-hint text-danger">
                                        No existen planes activos.
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-5">
                                <label class="form-label required">
                                    Modalidad inicial
                                </label>

                                <select
                                    name="license_status"
                                    id="license-status"
                                    class="form-select"
                                    required
                                >
                                    <option
                                        value="trial"
                                        @selected(
                                            old(
                                                'license_status',
                                                'trial'
                                            ) === 'trial'
                                        )
                                    >
                                        Periodo de prueba
                                    </option>

                                    <option
                                        value="active"
                                        @selected(
                                            old('license_status')
                                            === 'active'
                                        )
                                    >
                                        Licencia activa
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Inicio de licencia
                                </label>

                                <input
                                    type="date"
                                    name="license_starts_at"
                                    class="form-control"
                                    value="{{ old(
                                        'license_starts_at',
                                        $defaults[
                                            'license_starts_at'
                                        ]
                                    ) }}"
                                    required
                                >
                            </div>

                            <div
                                class="col-md-4"
                                id="trial-days-field"
                            >
                                <label class="form-label required">
                                    Días de prueba
                                </label>

                                <input
                                    type="number"
                                    name="trial_days"
                                    class="form-control"
                                    value="{{ old(
                                        'trial_days',
                                        30
                                    ) }}"
                                    min="1"
                                    max="365"
                                >
                            </div>

                            <div
                                class="col-md-4"
                                id="billing-cycle-field"
                                hidden
                            >
                                <label class="form-label required">
                                    Ciclo de cobro
                                </label>

                                <select
                                    name="billing_cycle"
                                    id="billing-cycle"
                                    class="form-select"
                                >
                                    <option
                                        value="monthly"
                                        @selected(
                                            old('billing_cycle')
                                            === 'monthly'
                                        )
                                    >
                                        Mensual
                                    </option>

                                    <option
                                        value="annual"
                                        @selected(
                                            old('billing_cycle')
                                            === 'annual'
                                        )
                                    >
                                        Anual
                                    </option>

                                    <option
                                        value="custom"
                                        @selected(
                                            old('billing_cycle')
                                            === 'custom'
                                        )
                                    >
                                        Personalizado
                                    </option>
                                </select>
                            </div>

                            <div
                                class="col-md-4"
                                id="license-expires-field"
                                hidden
                            >
                                <label class="form-label">
                                    Vencimiento personalizado
                                </label>

                                <input
                                    type="date"
                                    name="license_expires_at"
                                    class="form-control"
                                    value="{{ old(
                                        'license_expires_at'
                                    ) }}"
                                >
                            </div>

                            <div
                                class="col-md-4"
                                id="contract-price-field"
                                hidden
                            >
                                <label class="form-label">
                                    Precio contratado
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        $
                                    </span>

                                    <input
                                        type="number"
                                        name="contract_price"
                                        class="form-control"
                                        value="{{ old(
                                            'contract_price'
                                        ) }}"
                                        min="0"
                                        step="0.01"
                                        placeholder="Usar precio del plan"
                                    >
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Renovación
                                </label>

                                <label class="form-check form-switch border rounded p-3 ps-5">
                                    <input
                                        type="checkbox"
                                        name="auto_renew"
                                        value="1"
                                        class="form-check-input"
                                        @checked(
                                            old('auto_renew')
                                        )
                                    >

                                    <span class="form-check-label">
                                        Renovación automática
                                    </span>
                                </label>
                            </div>

                            <div class="col-12">
                                <hr class="my-1">
                            </div>

                            @foreach([
                                'student_limit' => [
                                    'Alumnos',
                                    'ti-users',
                                ],
                                'device_limit' => [
                                    'Dispositivos',
                                    'ti-device-tablet',
                                ],
                                'staff_limit' => [
                                    'Personal',
                                    'ti-users-cog',
                                ],
                                'campus_limit' => [
                                    'Planteles',
                                    'ti-building',
                                ],
                            ] as $field => [$label, $icon])
                                <div class="col-md-3">
                                    <label class="form-label">
                                        {{ $label }}
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="ti {{ $icon }}"></i>
                                        </span>

                                        <input
                                            type="number"
                                            name="{{ $field }}"
                                            id="{{ str_replace(
                                                '_',
                                                '-',
                                                $field
                                            ) }}"
                                            class="form-control"
                                            value="{{ old($field) }}"
                                            min="1"
                                            placeholder="Plan"
                                        >
                                    </div>
                                </div>
                            @endforeach

                            <div class="col-12">
                                <label class="form-label">
                                    Notas de licencia
                                </label>

                                <textarea
                                    name="license_notes"
                                    class="form-control"
                                    rows="2"
                                    maxlength="1000"
                                >{{ old('license_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ciclo y estructura --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                3. Ciclo, plantel y grupos
                            </h3>

                            <div class="text-secondary">
                                Prepara toda la estructura antes de importar
                                alumnos.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label required">
                                    Ciclo escolar
                                </label>

                                <input
                                    type="text"
                                    name="cycle_name"
                                    class="form-control"
                                    value="{{ old(
                                        'cycle_name',
                                        $defaults['cycle_name']
                                    ) }}"
                                    maxlength="100"
                                    required
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required">
                                    Inicio
                                </label>

                                <input
                                    type="date"
                                    name="cycle_starts_on"
                                    class="form-control"
                                    value="{{ old(
                                        'cycle_starts_on',
                                        $defaults[
                                            'cycle_starts_on'
                                        ]
                                    ) }}"
                                    required
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required">
                                    Fin
                                </label>

                                <input
                                    type="date"
                                    name="cycle_ends_on"
                                    class="form-control"
                                    value="{{ old(
                                        'cycle_ends_on',
                                        $defaults[
                                            'cycle_ends_on'
                                        ]
                                    ) }}"
                                    required
                                >
                            </div>

                            <div class="col-md-2">
                                <label class="form-label required">
                                    Estado
                                </label>

                                <select
                                    name="cycle_status"
                                    class="form-select"
                                    required
                                >
                                    <option
                                        value="active"
                                        @selected(
                                            old(
                                                'cycle_status',
                                                'active'
                                            ) === 'active'
                                        )
                                    >
                                        Activo
                                    </option>

                                    <option
                                        value="draft"
                                        @selected(
                                            old('cycle_status')
                                            === 'draft'
                                        )
                                    >
                                        Borrador
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Plantel principal
                                </label>

                                <input
                                    type="text"
                                    name="campus_name"
                                    class="form-control"
                                    value="{{ old(
                                        'campus_name',
                                        'Plantel principal'
                                    ) }}"
                                    maxlength="150"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Dirección del plantel
                                </label>

                                <input
                                    type="text"
                                    name="campus_address"
                                    class="form-control"
                                    value="{{ old(
                                        'campus_address'
                                    ) }}"
                                    maxlength="255"
                                >
                            </div>

                            <div class="col-12">
                                <label class="form-label required">
                                    Niveles y grupos
                                </label>

                                <textarea
                                    name="structure_lines"
                                    class="form-control font-monospace"
                                    rows="7"
                                    required
                                    placeholder="Preescolar|1A,2A,3A&#10;Primaria|1A,1B,2A,2B,3A,3B&#10;Secundaria|1A,2A,3A"
                                >{{ old('structure_lines') }}</textarea>

                                <div class="form-hint">
                                    Una línea por nivel. Formato:
                                    <code>Nivel|Grupo1,Grupo2</code>.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-check form-switch border rounded p-3 ps-5 h-100">
                                    <input
                                        type="checkbox"
                                        name="requires_guardian_scan"
                                        value="1"
                                        class="form-check-input"
                                        @checked(
                                            old(
                                                'requires_guardian_scan'
                                            )
                                        )
                                    >

                                    <span class="form-check-label">
                                        <span class="fw-semibold d-block">
                                            Requerir escaneo de tutor
                                        </span>

                                        <span class="text-secondary small">
                                            Se aplicará inicialmente a todos
                                            los grupos creados.
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Transición automática
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        name="auto_transition_minutes"
                                        class="form-control"
                                        value="{{ old(
                                            'auto_transition_minutes',
                                            30
                                        ) }}"
                                        min="0"
                                        max="1440"
                                        required
                                    >

                                    <span class="input-group-text">
                                        minutos
                                    </span>
                                </div>

                                <div class="form-hint">
                                    Tiempo para alternar automáticamente
                                    entrada y salida.
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-check form-switch border rounded p-3 ps-5">
                                    <input
                                        type="checkbox"
                                        name="schedule_enabled"
                                        value="1"
                                        id="schedule-enabled"
                                        class="form-check-input"
                                        @checked(
                                            old(
                                                'schedule_enabled',
                                                true
                                            )
                                        )
                                    >

                                    <span class="form-check-label">
                                        <span class="fw-semibold">
                                            Crear horario común para todos
                                            los grupos
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div
                                class="col-12"
                                id="schedule-fields"
                            >
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">
                                            Días con clase
                                        </label>

                                      @php
    $selectedWeekdays = collect(
        old('weekdays', [1, 2, 3, 4, 5])
    )
        ->map(fn ($day) => (int) $day)
        ->all();
@endphp

<div class="d-flex flex-wrap gap-2">
    @foreach($weekdayLabels as $weekday => $label)
        <input
            type="checkbox"
            name="weekdays[]"
            value="{{ $weekday }}"
            id="onboarding-weekday-{{ $weekday }}"
            class="btn-check"
            autocomplete="off"
            @checked(
                in_array(
                    (int) $weekday,
                    $selectedWeekdays,
                    true
                )
            )
        >

        <label
            class="btn btn-outline-secondary"
            for="onboarding-weekday-{{ $weekday }}"
        >
            {{ $label }}
        </label>
    @endforeach
</div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Entrada
                                        </label>

                                        <input
                                            type="time"
                                            name="entry_time"
                                            class="form-control"
                                            value="{{ old(
                                                'entry_time',
                                                '07:00'
                                            ) }}"
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Tolerancia
                                        </label>

                                        <input
                                            type="time"
                                            name="grace_until"
                                            class="form-control"
                                            value="{{ old(
                                                'grace_until',
                                                '07:10'
                                            ) }}"
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Muy tarde desde
                                        </label>

                                        <input
                                            type="time"
                                            name="late_until"
                                            class="form-control"
                                            value="{{ old(
                                                'late_until',
                                                '07:30'
                                            ) }}"
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Salida
                                        </label>

                                        <input
                                            type="time"
                                            name="exit_time"
                                            class="form-control"
                                            value="{{ old(
                                                'exit_time',
                                                '13:30'
                                            ) }}"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    Notas del ciclo
                                </label>

                                <textarea
                                    name="cycle_notes"
                                    class="form-control"
                                    rows="2"
                                    maxlength="1000"
                                >{{ old('cycle_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Usuarios --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                4. Usuarios iniciales
                            </h3>

                            <div class="text-secondary">
                                El administrador escolar es obligatorio.
                                Los demás usuarios son opcionales.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Cuenta responsable:</strong>
                            usa <code>school_admin</code>. Puede pertenecer
                            a la misma persona que ocupa el cargo de director.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label required">
                                    Nombre del administrador
                                </label>

                                <input
                                    type="text"
                                    name="admin_name"
                                    class="form-control"
                                    value="{{ old('admin_name') }}"
                                    maxlength="255"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Correo
                                </label>

                                <input
                                    type="email"
                                    name="admin_email"
                                    class="form-control"
                                    value="{{ old('admin_email') }}"
                                    maxlength="255"
                                    required
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    Teléfono
                                </label>

                                <input
                                    type="tel"
                                    name="admin_phone"
                                    class="form-control"
                                    value="{{ old('admin_phone') }}"
                                    maxlength="30"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Contraseña temporal
                                </label>

                                <input
                                    type="password"
                                    name="admin_password"
                                    class="form-control"
                                    required
                                    autocomplete="new-password"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Confirmar contraseña
                                </label>

                                <input
                                    type="password"
                                    name="admin_password_confirmation"
                                    class="form-control"
                                    required
                                    autocomplete="new-password"
                                >
                            </div>
                        </div>

                        <hr>

                        @foreach([
                            'director' => [
                                'Director adicional',
                                'Crea esta cuenta solo cuando es una persona distinta del administrador.',
                                true,
                            ],
                            'prefect' => [
                                'Prefecto inicial',
                                'Puede operar escaneo y búsquedas desde Staff o web.',
                                true,
                            ],
                            'kiosk' => [
                                'Kiosco inicial',
                                'Después deberá vincularse al dispositivo correspondiente.',
                                false,
                            ],
                        ] as $userKey => [$title, $help, $hasPhone])
                            <div class="border rounded p-3 mb-3">
                                <label class="form-check form-switch">
                                    <input
                                        type="checkbox"
                                        name="create_{{ $userKey }}"
                                        value="1"
                                        class="form-check-input initial-user-toggle"
                                        data-target="#{{ $userKey }}-fields"
                                        @checked(
                                            old(
                                                'create_'.$userKey
                                            )
                                        )
                                    >

                                    <span class="form-check-label">
                                        <span class="fw-semibold d-block">
                                            {{ $title }}
                                        </span>

                                        <span class="text-secondary small">
                                            {{ $help }}
                                        </span>
                                    </span>
                                </label>

                                <div
                                    id="{{ $userKey }}-fields"
                                    class="row g-3 mt-1"
                                    hidden
                                >
                                    <div class="col-md-{{ $hasPhone ? '4' : '6' }}">
                                        <label class="form-label">
                                            Nombre
                                        </label>

                                        <input
                                            type="text"
                                            name="{{ $userKey }}_name"
                                            class="form-control"
                                            value="{{ old(
                                                $userKey.'_name'
                                            ) }}"
                                            maxlength="255"
                                        >
                                    </div>

                                    <div class="col-md-{{ $hasPhone ? '4' : '6' }}">
                                        <label class="form-label">
                                            Correo
                                        </label>

                                        <input
                                            type="email"
                                            name="{{ $userKey }}_email"
                                            class="form-control"
                                            value="{{ old(
                                                $userKey.'_email'
                                            ) }}"
                                            maxlength="255"
                                        >
                                    </div>

                                    @if($hasPhone)
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Teléfono
                                            </label>

                                            <input
                                                type="tel"
                                                name="{{ $userKey }}_phone"
                                                class="form-control"
                                                value="{{ old(
                                                    $userKey.'_phone'
                                                ) }}"
                                                maxlength="30"
                                            >
                                        </div>
                                    @endif

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Contraseña temporal
                                        </label>

                                        <input
                                            type="password"
                                            name="{{ $userKey }}_password"
                                            class="form-control"
                                            autocomplete="new-password"
                                        >
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Confirmar contraseña
                                        </label>

                                        <input
                                            type="password"
                                            name="{{ $userKey }}_password_confirmation"
                                            class="form-control"
                                            autocomplete="new-password"
                                        >
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card sticky-xl-top" style="top: 1rem;">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                Resumen del alta
                            </h3>

                            <div class="text-secondary">
                                Una sola operación transaccional.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span
                                class="avatar avatar-xl"
                                id="summary-logo-box"
                                style="
                                    background: {{ old(
                                        'primary_color',
                                        '#2563EB'
                                    ) }};
                                    color: #fff;
                                "
                            >
                                <img
                                    id="summary-logo"
                                    class="d-none"
                                    alt="Logo"
                                    style="
                                        width: 100%;
                                        height: 100%;
                                        object-fit: contain;
                                    "
                                >

                                <i
                                    id="summary-logo-fallback"
                                    class="ti ti-school fs-1"
                                ></i>
                            </span>

                            <div>
                                <div
                                    class="h3 mb-1"
                                    id="summary-school-name"
                                >
                                    Nueva escuela
                                </div>

                                <div class="text-secondary">
                                    PaseLista
                                </div>
                            </div>
                        </div>

                        <div class="list-group list-group-flush">
                            @foreach([
                                [
                                    'ti-license',
                                    'Plan y licencia',
                                    'Se asignan al crear',
                                ],
                                [
                                    'ti-calendar-stats',
                                    'Ciclo escolar',
                                    'Se crea listo para operar',
                                ],
                                [
                                    'ti-building',
                                    'Plantel principal',
                                    'Se crea automáticamente',
                                ],
                                [
                                    'ti-users-group',
                                    'Niveles y grupos',
                                    'Según el editor de estructura',
                                ],
                                [
                                    'ti-user-shield',
                                    'Administrador escolar',
                                    'Cuenta responsable obligatoria',
                                ],
                                [
                                    'ti-device-mobile',
                                    'Staff y Family',
                                    'Heredan logo y colores',
                                ],
                            ] as [$icon, $title, $description])
                                <div class="list-group-item px-0">
                                    <div class="d-flex gap-2">
                                        <i class="ti {{ $icon }} text-primary mt-1"></i>

                                        <div>
                                            <div class="fw-semibold">
                                                {{ $title }}
                                            </div>

                                            <div class="text-secondary small">
                                                {{ $description }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="alert alert-warning mt-4 mb-0">
                            El logotipo se guarda como identidad institucional.
                            Después puede existir un logo distinto para las
                            aplicaciones desde Configuración de apps.
                        </div>
                    </div>

                    <div class="card-footer d-grid gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            @disabled($plans->isEmpty())
                        >
                            <i class="ti ti-school-plus me-1"></i>
                            Crear escuela y preparar operación
                        </button>

                        <a
                            href="{{ route(
                                'sysadmin.schools.index'
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById(
        'school-onboarding-form'
    );

    const logoInput = document.getElementById(
        'school-logo'
    );

    const logoPreviews = [
        {
            image: document.getElementById(
                'school-logo-preview'
            ),
            fallback: document.getElementById(
                'school-logo-fallback'
            ),
        },
        {
            image: document.getElementById(
                'summary-logo'
            ),
            fallback: document.getElementById(
                'summary-logo-fallback'
            ),
        },
    ];

    let previewUrl = null;

    logoInput?.addEventListener(
        'change',
        function () {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            const file = logoInput.files?.[0];

            logoPreviews.forEach(function (preview) {
                if (!file) {
                    preview.image.classList.add('d-none');
                    preview.fallback.classList.remove('d-none');
                    preview.image.removeAttribute('src');
                    return;
                }

                previewUrl =
                    previewUrl
                    || URL.createObjectURL(file);

                preview.image.src = previewUrl;
                preview.image.classList.remove('d-none');
                preview.fallback.classList.add('d-none');
            });
        }
    );

    const schoolName = document.getElementById(
        'school-name'
    );

    const summaryName = document.getElementById(
        'summary-school-name'
    );

    schoolName?.addEventListener(
        'input',
        function () {
            summaryName.textContent =
                schoolName.value.trim()
                || 'Nueva escuela';
        }
    );

    const primaryText = document.getElementById(
        'primary-color'
    );

    const primaryPicker = document.getElementById(
        'primary-color-picker'
    );

    const secondaryText = document.getElementById(
        'secondary-color'
    );

    const secondaryPicker = document.getElementById(
        'secondary-color-picker'
    );

    const previewBoxes = [
        document.getElementById(
            'school-logo-preview-box'
        ),
        document.getElementById(
            'summary-logo-box'
        ),
    ];

    function validHex(value) {
        return /^#[0-9A-F]{6}$/i.test(
            value.trim()
        );
    }

    function syncColor(
        textInput,
        picker,
        applyPreview
    ) {
        picker.addEventListener(
            'input',
            function () {
                textInput.value =
                    picker.value.toUpperCase();

                applyPreview(
                    textInput.value
                );
            }
        );

        textInput.addEventListener(
            'input',
            function () {
                const color =
                    textInput.value.trim();

                if (!validHex(color)) {
                    return;
                }

                picker.value = color;
                applyPreview(color);
            }
        );
    }

    syncColor(
        primaryText,
        primaryPicker,
        function (color) {
            previewBoxes.forEach(
                function (box) {
                    box.style.background = color;
                }
            );
        }
    );

    syncColor(
        secondaryText,
        secondaryPicker,
        function () {}
    );

    const licenseStatus = document.getElementById(
        'license-status'
    );

    const trialDaysField = document.getElementById(
        'trial-days-field'
    );

    const billingCycleField = document.getElementById(
        'billing-cycle-field'
    );

    const billingCycle = document.getElementById(
        'billing-cycle'
    );

    const expiresField = document.getElementById(
        'license-expires-field'
    );

    const priceField = document.getElementById(
        'contract-price-field'
    );

    function syncLicenseFields() {
        const isTrial =
            licenseStatus.value === 'trial';

        trialDaysField.hidden = !isTrial;
        billingCycleField.hidden = isTrial;
        priceField.hidden = isTrial;

        expiresField.hidden =
            isTrial
            || billingCycle.value !== 'custom';
    }

    licenseStatus.addEventListener(
        'change',
        syncLicenseFields
    );

    billingCycle.addEventListener(
        'change',
        syncLicenseFields
    );

    syncLicenseFields();

    const planSelect = document.getElementById(
        'subscription-plan'
    );

    const limitFields = {
        studentLimit: document.getElementById(
            'student-limit'
        ),
        deviceLimit: document.getElementById(
            'device-limit'
        ),
        staffLimit: document.getElementById(
            'staff-limit'
        ),
        campusLimit: document.getElementById(
            'campus-limit'
        ),
    };

    function applyPlanLimits() {
        const option =
            planSelect.selectedOptions?.[0];

        if (!option || !option.value) {
            return;
        }

        const mappings = [
            [
                limitFields.studentLimit,
                option.dataset.studentLimit,
            ],
            [
                limitFields.deviceLimit,
                option.dataset.deviceLimit,
            ],
            [
                limitFields.staffLimit,
                option.dataset.staffLimit,
            ],
            [
                limitFields.campusLimit,
                option.dataset.campusLimit,
            ],
        ];

        mappings.forEach(
            function ([field, value]) {
                if (
                    field
                    && field.value === ''
                    && value
                ) {
                    field.placeholder =
                        value;
                }
            }
        );
    }

    planSelect.addEventListener(
        'change',
        applyPlanLimits
    );

    applyPlanLimits();

    const scheduleEnabled = document.getElementById(
        'schedule-enabled'
    );

    const scheduleFields = document.getElementById(
        'schedule-fields'
    );

    function syncSchedule() {
        scheduleFields.hidden =
            !scheduleEnabled.checked;
    }

    scheduleEnabled.addEventListener(
        'change',
        syncSchedule
    );

    syncSchedule();

    document
        .querySelectorAll(
            '.initial-user-toggle'
        )
        .forEach(function (toggle) {
            const target = document.querySelector(
                toggle.dataset.target
            );

            function syncUserFields() {
                target.hidden = !toggle.checked;
            }

            toggle.addEventListener(
                'change',
                syncUserFields
            );

            syncUserFields();
        });

    form?.addEventListener(
        'submit',
        function () {
            const button = form.querySelector(
                'button[type="submit"]'
            );

            if (!button) {
                return;
            }

            button.disabled = true;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Preparando escuela
            `;
        }
    );
});
</script>
@endpush
