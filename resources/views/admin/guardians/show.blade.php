@extends('layouts.app')

@section(
    'title',
    $familyMembershipContext['is_primary_guardian']
        ? 'Núcleo familiar | PaseLista'
        : 'Persona vinculada | PaseLista'
)
@section('section-label', 'Dirección')
@section(
    'page-title',
    $familyMembershipContext['is_primary_guardian']
        ? 'Núcleo familiar'
        : 'Expediente de familiar o autorizado'
)

@section('topbar-actions')
    <div class="btn-list">
        <a
            href="{{ route('admin.guardians.index') }}"
            class="btn btn-outline-secondary btn-sm"
        >
            <i class="ti ti-arrow-left me-1"></i>
            Familias
        </a>

        <a
            href="{{ route('admin.guardians.edit', $guardianRow->id) }}"
            class="btn btn-primary btn-sm"
        >
            <i class="ti ti-edit me-1"></i>
            {{ $familyMembershipContext['is_primary_guardian']
                ? 'Editar responsable'
                : 'Editar persona' }}
        </a>
    </div>
@endsection

@section('content')
    @php
        $generatedAccess = session('generated_credentials');
        $guardianName = trim($guardianRow->first_name.' '.$guardianRow->last_name);

        $phoneDigits = preg_replace('/\D+/', '', (string) ($guardianRow->phone ?? ''));

        if ($phoneDigits !== '' && strlen($phoneDigits) === 10) {
            $phoneDigits = '52'.$phoneDigits;
        }

        $accessText = $generatedAccess
            ? "PaseLista\n"
                ."Tutor: {$guardianName}\n"
                ."Usuario: {$generatedAccess['username']}\n"
                ."Contraseña temporal: {$generatedAccess['password']}\n\n"
                ."Por seguridad, cambia la contraseña después de iniciar sesión."
            : '';

        $whatsappUrl = $generatedAccess && $phoneDigits !== ''
            ? 'https://wa.me/'.$phoneDigits.'?text='.rawurlencode($accessText)
            : null;

        $emailUrl = $generatedAccess && $guardianRow->email
            ? 'mailto:'.$guardianRow->email
                .'?subject='.rawurlencode('Acceso a PaseLista')
                .'&body='.rawurlencode($accessText)
            : null;

        $eventLabels = [
            'entry' => 'Entrada',
            'exit' => 'Salida',
        ];

        $statusLabels = [
            'on_time' => 'Puntual',
            'late' => 'Retardo',
            'very_late' => 'Muy tarde',
            'normal_exit' => 'Salida normal',
            'early_exit' => 'Salida anticipada',
            'duplicate' => 'Duplicado',
            'manual' => 'Manual',
            'guardian_required' => 'Tutor requerido',
            'student_not_enrolled' => 'Sin inscripción',
            'cycle_not_started' => 'Ciclo no iniciado',
        ];
    @endphp

    <script type="application/json" id="guardian-page-feedback">
        {!! json_encode(
            [
                'success' => session('success'),
                'error' => $errors->first(),
            ],
            JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        ) !!}
    </script>

    @if($generatedAccess)
        <div class="alert alert-warning">
            <div class="d-flex">
                <div>
                    <i class="ti ti-key icon alert-icon"></i>
                </div>

                <div class="w-100">
                    <h4 class="alert-title">
                        {{ ! empty($generatedAccess['is_reset'])
                            ? 'Nueva contraseña generada'
                            : 'Acceso generado' }}
                    </h4>

                    <div class="text-secondary mb-3">
                        Esta contraseña se muestra una sola vez.
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Usuario</label>
                            <div class="input-group">
                                <input
                                    id="guardian-access-user"
                                    class="form-control"
                                    value="{{ $generatedAccess['username'] }}"
                                    readonly
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-copy-target="guardian-access-user"
                                >
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contraseña temporal</label>
                            <div class="input-group">
                                <input
                                    id="guardian-access-password"
                                    class="form-control fw-bold"
                                    value="{{ $generatedAccess['password'] }}"
                                    readonly
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-copy-target="guardian-access-password"
                                >
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="btn-list mt-3">
                        @if($whatsappUrl)
                            <a
                                href="{{ $whatsappUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-success"
                            >
                                <i class="ti ti-brand-whatsapp me-1"></i>
                                Enviar por WhatsApp
                            </a>
                        @endif

                        @if($emailUrl)
                            <a href="{{ $emailUrl }}" class="btn btn-outline-primary">
                                <i class="ti ti-mail me-1"></i>
                                Enviar por correo
                            </a>
                        @endif

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-copy-text="{{ $accessText }}"
                        >
                            <i class="ti ti-copy me-1"></i>
                            Copiar mensaje
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(session('generated_member_credentials'))
        @php
            $memberAccess = session('generated_member_credentials');
        @endphp
        <div class="alert alert-warning">
            <div class="d-flex">
                <div><i class="ti ti-key icon alert-icon"></i></div>
                <div class="w-100">
                    <h4 class="alert-title">Acceso generado para {{ $memberAccess['guardian_name'] }}</h4>
                    <div class="text-secondary mb-2">
                        Esta contraseña se muestra una sola vez.
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Usuario</label>
                            <input class="form-control" value="{{ $memberAccess['username'] }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña temporal</label>
                            <input class="form-control fw-bold" value="{{ $memberAccess['password'] }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row row-cards">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body text-center">
                    @if($guardianRow->photo_url)
                        <span
                            class="avatar avatar-2xl mb-3"
                            style="background-image: url('{{ $guardianRow->photo_url }}')"
                        ></span>
                    @else
                        <span class="avatar avatar-2xl bg-blue-lt mb-3">
                            {{ mb_strtoupper(mb_substr($guardianRow->first_name, 0, 1)) }}
                        </span>
                    @endif

                    <h2 class="mb-1">{{ $guardianName }}</h2>

                    <div class="text-secondary">
                        <i class="ti ti-phone me-1"></i>
                        {{ $guardianRow->phone ?? 'Sin teléfono' }}
                    </div>

                    <div class="text-secondary">
                        <i class="ti ti-mail me-1"></i>
                        {{ $guardianRow->email ?? 'Sin correo' }}
                    </div>

                    <div class="mt-3 d-flex justify-content-center flex-wrap gap-2">
                        @if($guardianRow->status === 'active')
                            <span class="badge bg-success-lt">Persona activa</span>
                        @elseif($guardianRow->status === 'blocked')
                            <span class="badge bg-danger-lt">Persona bloqueada</span>
                        @else
                            <span class="badge bg-secondary-lt">Persona inactiva</span>
                        @endif

                        @if($familyMembershipContext['is_primary_guardian'])
                            <span class="badge bg-blue-lt">Responsable principal</span>
                        @else
                            <span class="badge bg-purple-lt">Familiar o autorizado</span>
                        @endif
                    </div>
                </div>

                <div class="card-footer">
                    <form
                        method="POST"
                        action="{{ route('admin.guardians.photo.upload', $guardianRow->id) }}"
                        enctype="multipart/form-data"
                    >
                        @csrf

                        <label class="form-label">
                            {{ $guardianRow->photo_url ? 'Cambiar fotografía' : 'Registrar fotografía' }}
                        </label>

                        <input
                            type="file"
                            name="photo"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >

                        <div class="form-hint mb-3">
                            Imagen cuadrada, mínimo 500 × 500 px, máximo 5 MB.
                        </div>

                        <button class="btn btn-primary w-100">
                            <i class="ti ti-camera me-1"></i>
                            Guardar fotografía
                        </button>
                    </form>

                    @if($guardianRow->photo_url)
                        <form
                            method="POST"
                            action="{{ route('admin.guardians.photo.remove', $guardianRow->id) }}"
                            class="mt-2"
                            data-swal-confirm
                            data-swal-title="Eliminar fotografía"
                            data-swal-text="La fotografía será eliminada y los códigos QR activos del tutor serán revocados."
                            data-swal-confirm-text="Sí, eliminar"
                            data-swal-danger="1"
                        >
                            @csrf
                            @method('DELETE')

                            <button class="btn btn-outline-danger w-100">
                                <i class="ti ti-trash me-1"></i>
                                Eliminar fotografía
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Credencial QR</h3>
                        <div class="text-secondary">
                            Requiere tutor activo y fotografía.
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if($activeCredential)
                        <div
                            class="guardian-qr-box mb-3"
                            data-qr-payload="{{ $activeCredential->public_code }}"
                            data-qr-size="220"
                        >
                            <div class="text-secondary py-5">
                                Generando QR…
                            </div>
                        </div>

                        <dl class="row mb-3">
                            <dt class="col-5">Estado</dt>
                            <dd class="col-7">
                                <span class="badge bg-success-lt">Activo</span>
                            </dd>

                            <dt class="col-5">Emitido</dt>
                            <dd class="col-7">
                                {{ \Illuminate\Support\Carbon::parse($activeCredential->issued_at)->format('d/m/Y H:i') }}
                            </dd>

                            <dt class="col-5">Vigencia</dt>
                            <dd class="col-7">
                                {{ $activeCredential->expires_at
                                    ? \Illuminate\Support\Carbon::parse($activeCredential->expires_at)->format('d/m/Y H:i')
                                    : 'Sin vencimiento' }}
                            </dd>
                        </dl>

                        <div class="btn-list">
                            <a
                                href="{{ route('admin.guardians.credentials.print', [
                                    $guardianRow->id,
                                    $activeCredential->id,
                                ]) }}"
                                target="_blank"
                                class="btn btn-primary"
                            >
                                <i class="ti ti-printer me-1"></i>
                                Ver / imprimir
                            </a>

                            <form
                                method="POST"
                                action="{{ route('admin.guardians.credentials.regenerate', $guardianRow->id) }}"
                                data-swal-confirm
                                data-swal-title="Regenerar código QR"
                                data-swal-text="El código QR actual dejará de funcionar y se generará uno nuevo."
                                data-swal-confirm-text="Sí, regenerar"
                            >
                                @csrf

                                <button class="btn btn-outline-primary">
                                    <i class="ti ti-refresh me-1"></i>
                                    Regenerar
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('admin.guardians.credentials.revoke', [
                                    $guardianRow->id,
                                    $activeCredential->id,
                                ]) }}"
                                data-swal-confirm
                                data-swal-title="Revocar credencial"
                                data-swal-text="El código QR dejará de funcionar inmediatamente."
                                data-swal-confirm-text="Sí, revocar"
                                data-swal-danger="1"
                            >
                                @csrf
                                @method('PATCH')

                                <button class="btn btn-outline-danger">
                                    <i class="ti ti-qrcode-off me-1"></i>
                                    Revocar
                                </button>
                            </form>
                        </div>
                    @else
                        @if(!$guardianRow->photo_url)
                            <div class="alert alert-warning">
                                <i class="ti ti-camera me-2"></i>
                                Registra la fotografía para habilitar el QR.
                            </div>
                        @elseif($guardianRow->status !== 'active')
                            <div class="alert alert-warning">
                                <i class="ti ti-user-off me-2"></i>
                                Activa al tutor antes de generar el QR.
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="ti ti-qrcode me-2"></i>
                                El tutor no tiene una credencial activa.
                            </div>

                            <form
                                method="POST"
                                action="{{ route('admin.guardians.credentials.create', $guardianRow->id) }}"
                            >
                                @csrf

                                <button class="btn btn-primary w-100">
                                    <i class="ti ti-qrcode me-1"></i>
                                    Generar credencial QR
                                </button>
                            </form>
                        @endif
                    @endif
                </div>

                @if($credentials->count() > 1 || (!$activeCredential && $credentials->isNotEmpty()))
                    <div class="card-footer">
                        <details>
                            <summary class="fw-semibold">
                                Historial de credenciales ({{ $credentials->count() }})
                            </summary>

                            <div class="mt-3">
                                @foreach($credentials as $credential)
                                    <div class="border rounded p-2 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold">
                                                QR #{{ $credential->id }}
                                            </span>

                                            <span class="badge {{ $credential->status === 'active'
                                                ? 'bg-success-lt'
                                                : 'bg-secondary-lt' }}">
                                                {{ ucfirst($credential->status) }}
                                            </span>
                                        </div>

                                        <div class="text-secondary small mt-1">
                                            Emitido:
                                            {{ \Illuminate\Support\Carbon::parse($credential->issued_at)->format('d/m/Y H:i') }}
                                        </div>

                                        @if($credential->revoked_at)
                                            <div class="text-secondary small">
                                                Revocado:
                                                {{ \Illuminate\Support\Carbon::parse($credential->revoked_at)->format('d/m/Y H:i') }}
                                            </div>
                                        @endif

                                        @if($credential->revoked_reason)
                                            <div class="text-secondary small">
                                                {{ $credential->revoked_reason }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    </div>
                @endif
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Cuenta de acceso</h3>

                        <div class="text-secondary">
                            Usuario y contraseña de PaseLista Family
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if($guardianRow->user_id)
                        <dl class="row mb-3">
                            <dt class="col-4">Usuario</dt>
                            <dd class="col-8 text-break">
                                {{ $guardianRow->access_username }}
                            </dd>

                            <dt class="col-4">Estado</dt>
                            <dd class="col-8">
                                <span class="badge {{ $guardianRow->user_status === 'active'
                                    ? 'bg-success-lt'
                                    : 'bg-danger-lt' }}">
                                    {{ $guardianRow->user_status === 'active' ? 'Activo' : 'Bloqueado' }}
                                </span>
                            </dd>

                            <dt class="col-4">Contraseña</dt>
                            <dd class="col-8">
                                {{ $guardianRow->must_change_password
                                    ? 'Cambio pendiente'
                                    : 'Actualizada por el tutor' }}
                            </dd>
                        </dl>

                        <div class="btn-list">
                            <form
                                method="POST"
                                action="{{ route('admin.guardians.account.reset', $guardianRow->id) }}"
                                data-swal-confirm
                                data-swal-title="Restablecer contraseña"
                                data-swal-text="La contraseña actual dejará de funcionar y se generará una nueva contraseña temporal."
                                data-swal-confirm-text="Sí, restablecer"
                            >
                                @csrf
                                @method('PATCH')

                                <button class="btn btn-outline-primary">
                                    <i class="ti ti-refresh me-1"></i>
                                    Restablecer contraseña
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('admin.guardians.account.status', $guardianRow->id) }}"
                                data-swal-confirm
                                data-swal-title="{{ $guardianRow->user_status === 'active'
                                    ? 'Bloquear acceso'
                                    : 'Activar acceso' }}"
                                data-swal-text="{{ $guardianRow->user_status === 'active'
                                    ? 'El tutor no podrá iniciar sesión en PaseLista Family.'
                                    : 'El tutor podrá volver a iniciar sesión en PaseLista Family.' }}"
                                data-swal-confirm-text="{{ $guardianRow->user_status === 'active'
                                    ? 'Sí, bloquear'
                                    : 'Sí, activar' }}"
                                data-swal-danger="{{ $guardianRow->user_status === 'active' ? '1' : '0' }}"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="status"
                                    value="{{ $guardianRow->user_status === 'active' ? 'blocked' : 'active' }}"
                                >

                                <button
                                    class="btn {{ $guardianRow->user_status === 'active'
                                        ? 'btn-outline-danger'
                                        : 'btn-outline-success' }}"
                                >
                                    <i class="ti {{ $guardianRow->user_status === 'active'
                                        ? 'ti-lock'
                                        : 'ti-lock-open' }} me-1"></i>

                                    {{ $guardianRow->user_status === 'active'
                                        ? 'Bloquear acceso'
                                        : 'Activar acceso' }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            Este tutor todavía no tiene acceso a la app.
                        </div>

                        <form
                            method="POST"
                            action="{{ route('admin.guardians.account.create', $guardianRow->id) }}"
                        >
                            @csrf

                            <button class="btn btn-primary w-100">
                                <i class="ti ti-user-check me-1"></i>
                                Generar usuario y contraseña
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-block">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h3 class="card-title mb-0">
                            Membresía PaseLista Family
                        </h3>

                        <span
                            class="badge flex-shrink-0 text-nowrap
                                {{ $familyMembershipContext['status']['badge_class'] }}"
                        >
                            {{ $familyMembershipContext['status']['label'] }}
                        </span>
                    </div>

                    <div class="text-secondary mt-2">
                        Una membresía cubre a todos los alumnos donde esta
                        persona es tutor principal.
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="ti ti-info-circle me-2"></i>
                        {{ $familyMembershipContext['status']['description'] }}
                    </div>

                    @php
                        $familyMembership = $familyMembershipContext['membership'];
                        $familyAssignment = $familyMembershipContext['assignment'];
                        $familySummary = $familyMembershipContext['summary'];
                        $familyPool = $familySummary['pool'] ?? [];
                        $familyCycle = $familySummary['cycle'] ?? null;
                        $familyLicense = $familySummary['license'] ?? [];
                        $familyPrimaryStudents = $familyMembershipContext['primary_students'];

                        $familySourceLabels = [
                            'google_play' => 'Google Play',
                            'school_sponsorship' => 'Patrocinio escolar',
                            'sysadmin_courtesy' => 'Cortesía Sysadmin',
                            'migration' => 'Migración administrativa',
                        ];

                        $familyMembershipStatusLabels = [
                            'active' => 'Activa',
                            'inactive' => 'Inactiva',
                            'expired' => 'Vencida',
                            'revoked' => 'Revocada',
                            'pending' => 'Pendiente',
                        ];
                    @endphp

                    <dl class="row mb-3">
                        <dt class="col-5">Ciclo escolar</dt>

                        <dd class="col-7">
                            @if($familyCycle)
                                {{ $familyCycle['name'] }}

                                <div class="text-secondary small">
                                    {{ \Illuminate\Support\Carbon::parse(
                                        $familyCycle['starts_on']
                                    )->format('d/m/Y') }}
                                    a
                                    {{ \Illuminate\Support\Carbon::parse(
                                        $familyCycle['ends_on']
                                    )->format('d/m/Y') }}
                                </div>
                            @else
                                <span class="text-secondary">
                                    Sin ciclo activo
                                </span>
                            @endif
                        </dd>

                        <dt class="col-5">Tutor principal</dt>

                        <dd class="col-7">
                            @if($familyMembershipContext['is_primary_guardian'])
                                <span class="badge bg-success-lt">
                                    Sí
                                </span>

                                <div class="text-secondary small mt-1">
                                    {{ $familyPrimaryStudents->count() }}
                                    alumno(s) cubierto(s)
                                </div>
                            @else
                                <span class="badge bg-secondary-lt">
                                    No
                                </span>
                            @endif
                        </dd>

                        <dt class="col-5">Acceso institucional</dt>

                        <dd class="col-7">
                            @if($familyMembershipContext['trial_active'])
                                <span class="badge bg-azure-lt">
                                    Prueba activa
                                </span>

                                @if(! empty($familyLicense['expires_at']))
                                    <div class="text-secondary small mt-1">
                                        Hasta
                                        {{ \Illuminate\Support\Carbon::parse(
                                            $familyLicense['expires_at']
                                        )->format('d/m/Y') }}
                                    </div>
                                @endif
                            @elseif(($familyLicense['status'] ?? null) === 'active')
                                <span class="badge bg-success-lt">
                                    Licencia institucional activa
                                </span>

                                @if(! empty($familyLicense['expires_at']))
                                    <div class="text-secondary small mt-1">
                                        Vigente hasta
                                        {{ \Illuminate\Support\Carbon::parse(
                                            $familyLicense['expires_at']
                                        )->format('d/m/Y') }}
                                    </div>
                                @endif
                            @else
                                <span class="text-secondary">
                                    Sin acceso institucional
                                </span>
                            @endif
                        </dd>

                        @if($familyMembership)
                            <dt class="col-5">Fuente</dt>

                            <dd class="col-7">
                                {{
                                    $familySourceLabels[
                                        $familyMembership->source
                                    ]
                                    ?? ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) $familyMembership->source
                                        )
                                    )
                                }}
                            </dd>

                            <dt class="col-5">Estado</dt>

                            <dd class="col-7">
                                {{
                                    $familyMembershipStatusLabels[
                                        $familyMembership->status
                                    ]
                                    ?? ucfirst($familyMembership->status)
                                }}
                            </dd>

                            <dt class="col-5">Inicio</dt>

                            <dd class="col-7">
                                {{
                                    $familyMembership->starts_at
                                        ? \Illuminate\Support\Carbon::parse(
                                            $familyMembership->starts_at
                                        )->format('d/m/Y')
                                        : 'Sin fecha'
                                }}
                            </dd>

                            <dt class="col-5">Vencimiento</dt>

                            <dd class="col-7">
                                {{
                                    $familyMembership->expires_at
                                        ? \Illuminate\Support\Carbon::parse(
                                            $familyMembership->expires_at
                                        )->format('d/m/Y')
                                        : 'Sin vencimiento'
                                }}
                            </dd>
                        @endif
                    </dl>

                    @if($familyPrimaryStudents->isNotEmpty())
                        <div class="mb-3">
                            <div class="fw-semibold mb-2">
                                Alumnos cubiertos como tutor principal
                            </div>

                            <div class="list-group list-group-flush border rounded">
                                @foreach($familyPrimaryStudents as $student)
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            @if($student->student_photo_url)
                                                <span
                                                    class="avatar avatar-sm me-2"
                                                    style="background-image: url('{{ $student->student_photo_url }}')"
                                                ></span>
                                            @else
                                                <span class="avatar avatar-sm bg-blue-lt me-2">
                                                    {{
                                                        mb_strtoupper(
                                                            mb_substr(
                                                                $student->first_name,
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    }}
                                                </span>
                                            @endif

                                            <div>
                                                <div class="fw-semibold">
                                                    {{ $student->first_name }}
                                                    {{ $student->last_name }}
                                                </div>

                                                <div class="text-secondary small">
                                                    {{ $student->student_code }}
                                                    ·
                                                    {{ $student->group_name ?? 'Sin grupo' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="border rounded p-2 text-center h-100">
                                <div class="text-secondary small">
                                    Incluidos
                                </div>

                                <div class="h3 mb-0">
                                    {{ (int) ($familyPool['quota_total'] ?? 0) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="border rounded p-2 text-center h-100">
                                <div class="text-secondary small">
                                    Utilizados
                                </div>

                                <div class="h3 mb-0">
                                    {{ (int) ($familyPool['used'] ?? 0) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="border rounded p-2 text-center h-100">
                                <div class="text-secondary small">
                                    Disponibles
                                </div>

                                <div class="h3 mb-0">
                                    {{ (int) ($familyPool['remaining'] ?? 0) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(
                        $familyAssignment
                        && $familyAssignment->status === 'assigned'
                    )
                        <div class="alert alert-success d-flex align-items-start gap-2">
                            <i class="ti ti-gift mt-1"></i>

                            <div class="w-100">
                                <div class="fw-semibold">
                                    Patrocinio asignado
                                </div>

                                <div class="text-secondary small mt-1">
                                    Asignado el
                                    {{ \Illuminate\Support\Carbon::parse(
                                        $familyAssignment->assigned_at
                                    )->format('d/m/Y H:i') }}

                                    @if($familyAssignment->assigned_by_name)
                                        por {{ $familyAssignment->assigned_by_name }}
                                    @endif
                                </div>

                                @if($familyAssignment->reason)
                                    <div class="small mt-2">
                                        <span class="fw-semibold">Motivo:</span>
                                        {{ $familyAssignment->reason }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif(
                        $familyAssignment
                        && $familyAssignment->status === 'released'
                    )
                        <div class="alert alert-warning">
                            <div class="fw-semibold">
                                <i class="ti ti-alert-triangle me-1"></i>
                                Patrocinio liberado
                            </div>

                            <div class="small mt-1">
                                Esta asignación requiere revisión de Sysadmin.
                            </div>

                            @if($familyAssignment->release_reason)
                                <div class="small mt-1">
                                    Motivo:
                                    {{ $familyAssignment->release_reason }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(
                        ! $guardianRow->user_id
                        && (
                            $familyMembershipContext['trial_active']
                            || $familyMembershipContext['membership_usable']
                        )
                    )
                        <div class="alert alert-warning">
                            <i class="ti ti-user-exclamation me-2"></i>

                            La membresía está disponible, pero el tutor
                            todavía no tiene una cuenta para ingresar a
                            PaseLista Family.
                        </div>
                    @endif

                    @if($familyMembershipContext['can_sponsor'])
                        <form
                            method="POST"
                            action="{{ route(
                                'admin.guardians.family-sponsorship.store',
                                $guardianRow->id
                            ) }}"
                            data-swal-confirm
                            data-swal-title="Asignar patrocinio Family"
                            data-swal-text="Se consumirá 1 patrocinio. Quedarán {{ max(0, (int) ($familyPool['remaining'] ?? 0) - 1) }} disponibles y se cubrirán {{ $familyPrimaryStudents->count() }} alumno(s)."
                            data-swal-confirm-text="Sí, patrocinar"
                        >
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">
                                    Nota interna
                                </label>

                                <textarea
                                    name="reason"
                                    class="form-control"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Motivo o referencia opcional"
                                >{{ old('reason') }}</textarea>

                                <div class="form-hint">
                                    Esta nota quedará registrada en
                                    la asignación y en auditoría.
                                </div>
                            </div>

                            <button class="btn btn-success w-100">
                                <i class="ti ti-gift me-1"></i>
                                Patrocinar membresía
                                <span class="ms-1">
                                    · quedan
                                    {{ (int) ($familyPool['remaining'] ?? 0) }}
                                </span>
                            </button>
                        </form>
                    @elseif($familyMembershipContext['trial_active'])
                        <div class="alert alert-azure mb-0">
                            <i class="ti ti-clock me-2"></i>

                            No se consume patrocinio durante el trial.
                            Todas las familias tienen acceso completo.
                        </div>
                    @elseif(! $familyMembershipContext['is_primary_guardian'])
                        <div class="alert alert-info mb-0">
                            <i class="ti ti-users me-2"></i>

                            Solo el tutor principal puede comprar o
                            recibir la membresía familiar.
                        </div>
                    @elseif($guardianRow->status !== 'active')
                        <div class="alert alert-warning mb-0">
                            <i class="ti ti-user-off me-2"></i>

                            Activa al tutor antes de asignarle
                            un patrocinio.
                        </div>
                    @elseif(
                        $familyMembershipContext['membership_usable']
                        && !(
                            $familyAssignment
                            && $familyAssignment->status === 'assigned'
                        )
                    )
                        <div class="alert alert-success mb-0">
                            <i class="ti ti-circle-check me-2"></i>

                            La familia ya tiene una membresía vigente.
                            No se consumirá otro patrocinio.
                        </div>
                    @elseif(
                        $familyAssignment
                        && $familyAssignment->status === 'released'
                    )
                        <div class="alert alert-warning mb-0">
                            <i class="ti ti-alert-triangle me-2"></i>

                            La asignación fue liberada. Solo Sysadmin
                            puede resolver este caso.
                        </div>
                    @elseif((int) ($familyPool['remaining'] ?? 0) <= 0)
                        <div class="alert alert-danger mb-0">
                            <i class="ti ti-gift-off me-2"></i>

                            La institución agotó los patrocinios
                            disponibles para este ciclo.
                        </div>
                    @elseif(! empty($familySummary['blocked_reason']))
                        <div class="alert alert-warning mb-0">
                            <i class="ti ti-alert-circle me-2"></i>

                            {{ $familySummary['blocked_reason'] }}
                        </div>
                    @endif
                </div>
            </div>

            @if($familyMembershipContext['is_primary_guardian'])
                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Agregar alumno al núcleo</h3>
                            <div class="text-secondary">
                                Quedará asociado automáticamente como alumno principal.
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.guardians.students.link', $guardianRow->id) }}">
                        @csrf

                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Alumno</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">Seleccionar alumno</option>
                                    @foreach($availableStudents as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->last_name }}, {{ $student->first_name }}
                                            · {{ $student->student_code }}
                                            · {{ $student->group_name ?? 'Sin grupo' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Relación del responsable</label>
                                <select name="relationship" class="form-select" required>
                                    <option value="madre" @selected(old('relationship') === 'madre')>Madre</option>
                                    <option value="padre" @selected(old('relationship') === 'padre')>Padre</option>
                                    <option value="tutor" @selected(old('relationship') === 'tutor')>Tutor legal</option>
                                </select>
                            </div>

                            <div class="alert alert-info mb-0">
                                <i class="ti ti-info-circle me-2"></i>
                                Se habilitarán permisos de entrega, recogida, avisos y asistencia.
                            </div>
                        </div>

                        <div class="card-footer">
                            <button class="btn btn-primary w-100" @disabled($availableStudents->isEmpty())>
                                <i class="ti ti-user-plus me-1"></i>
                                Agregar como alumno principal
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Agregar familiar o autorizado</h3>
                            <div class="text-secondary">
                                Vincúlalo únicamente con los alumnos que correspondan.
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.guardians.members.store', $guardianRow->id) }}">
                        @csrf

                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Origen de la persona</label>
                                <select name="member_mode" class="form-select" id="family-member-mode" required>
                                    <option value="new" @selected(old('member_mode', 'new') === 'new')>
                                        Registrar nueva persona
                                    </option>
                                    <option value="existing" @selected(old('member_mode') === 'existing')>
                                        Usar persona existente
                                    </option>
                                </select>
                            </div>

                            <div id="family-existing-member-fields" class="mb-3 d-none">
                                <label class="form-label">Buscar persona</label>
                                <div class="input-icon mb-2">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search"></i>
                                    </span>
                                    <input
                                        type="search"
                                        id="family-existing-member-search"
                                        class="form-control"
                                        placeholder="Nombre, teléfono, correo o usuario"
                                    >
                                </div>

                                <label class="form-label required">Persona existente</label>
                                <select
                                    name="existing_guardian_id"
                                    id="family-existing-member-select"
                                    class="form-select"
                                >
                                    <option value="">Seleccionar persona</option>
                                    @foreach($availableFamilyGuardians as $candidate)
                                        <option value="{{ $candidate->id }}" @selected((int) old('existing_guardian_id') === (int) $candidate->id)>
                                            {{ $candidate->last_name }}, {{ $candidate->first_name }}
                                            · {{ $candidate->phone ?? $candidate->email ?? $candidate->access_username ?? 'Sin contacto' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="family-new-member-fields">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label required">Nombre</label>
                                        <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control" maxlength="100">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label required">Apellidos</label>
                                        <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control" maxlength="150">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Teléfono</label>
                                        <input type="tel" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="30">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Correo</label>
                                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" maxlength="150">
                                    </div>
                                </div>

                                <label class="form-check form-switch border rounded p-3 ps-5 mb-3">
                                    <input class="form-check-input" type="checkbox" name="create_user" value="1" @checked(old('create_user'))>
                                    <span class="form-check-label">
                                        <span class="fw-semibold d-block">Generar cuenta para la app</span>
                                        <span class="text-secondary small">Útil para padre o madre secundaria que recibirá avisos.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Relación</label>
                                <select name="relationship" class="form-select" required>
                                    <option value="madre">Madre</option>
                                    <option value="padre">Padre</option>
                                    <option value="tutor">Tutor</option>
                                    <option value="abuelo" @selected(old('relationship') === 'abuelo')>Abuelo</option>
                                    <option value="abuela" @selected(old('relationship') === 'abuela')>Abuela</option>
                                    <option value="tio" @selected(old('relationship') === 'tio')>Tío</option>
                                    <option value="tia" @selected(old('relationship') === 'tia')>Tía</option>
                                    <option value="hermano" @selected(old('relationship') === 'hermano')>Hermano</option>
                                    <option value="hermana" @selected(old('relationship') === 'hermana')>Hermana</option>
                                    <option value="persona_autorizada" @selected(old('relationship') === 'persona_autorizada')>Persona autorizada</option>
                                    <option value="otro" @selected(old('relationship') === 'otro')>Otro</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Alumnos relacionados</label>
                                <div class="list-group list-group-flush border rounded">
                                    @foreach($familyMembershipContext['primary_students'] as $student)
                                        <label class="list-group-item py-2">
                                            <input
                                                class="form-check-input me-2"
                                                type="checkbox"
                                                name="student_ids[]"
                                                value="{{ $student->student_id }}"
                                                @checked(in_array(
                                                    (string) $student->student_id,
                                                    old('student_ids', []),
                                                    true
                                                ))
                                            >
                                            {{ $student->first_name }} {{ $student->last_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Permisos</label>
                                @foreach([
                                    'can_drop_off' => 'Puede entregar',
                                    'can_pick_up' => 'Puede recoger',
                                    'can_receive_notifications' => 'Recibe avisos',
                                    'can_view_attendance' => 'Ve asistencia',
                                ] as $permission => $label)
                                    <label class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="{{ $permission }}"
                                            value="1"
                                            @checked(old(
                                                $permission,
                                                in_array(
                                                    $permission,
                                                    ['can_drop_off', 'can_pick_up'],
                                                    true
                                                ) ? '1' : null
                                            ))
                                        >
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                @endforeach

                                <label class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="can_authorize_exit"
                                        value="1"
                                        @checked(old('can_authorize_exit'))
                                    >
                                    <span class="form-check-label">
                                        Puede autorizar salida
                                    </span>
                                </label>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Válido desde</label>
                                    <input type="date" name="valid_from" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Válido hasta</label>
                                    <input type="date" name="valid_until" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button class="btn btn-primary w-100">
                                <i class="ti ti-users-plus me-1"></i>
                                Agregar al núcleo
                            </button>
                        </div>
                    </form>
                </div>
            @endif

        </div>

        <div class="col-xl-8">
            <div class="row row-cards mb-3">
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Alumnos del núcleo</div>
                        <div class="h2 mb-0">{{ $summary['primary_students'] }}</div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Familiares y autorizados</div>
                        <div class="h2 mb-0">{{ $familyMembers->count() }}</div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Pueden entregar</div>
                        <div class="h2 mb-0">{{ $summary['can_drop_off'] }}</div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Pueden recoger</div>
                        <div class="h2 mb-0">{{ $summary['can_pick_up'] }}</div>
                    </div></div>
                </div>
            </div>

            @if($familyMembershipContext['is_primary_guardian'])
            <div class="card mb-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Familiares y personas autorizadas</h3>
                        <div class="text-secondary">
                            Miembros secundarios vinculados con uno o más alumnos del núcleo.
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Persona</th>
                                <th>Alumnos y relación</th>
                                <th>Permisos</th>
                                <th>Acceso</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($familyMembers as $member)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($member->photo_url)
                                                <span class="avatar avatar-sm me-2" style="background-image: url('{{ $member->photo_url }}')"></span>
                                            @else
                                                <span class="avatar avatar-sm bg-purple-lt me-2">
                                                    {{ mb_strtoupper(mb_substr($member->first_name, 0, 1)) }}
                                                </span>
                                            @endif
                                            <div>
                                                <a class="fw-semibold text-reset" href="{{ route('admin.guardians.show', $member->guardian_id) }}">
                                                    {{ $member->first_name }} {{ $member->last_name }}
                                                </a>
                                                <div class="text-secondary small">
                                                    {{ $member->phone ?? $member->email ?? 'Sin contacto' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @foreach($member->students as $relation)
                                            <div class="mb-1">
                                                <span class="fw-semibold">{{ $relation->student_first_name }} {{ $relation->student_last_name }}</span>
                                                <span class="badge bg-blue-lt ms-1">
                                                    {{ ucfirst(str_replace('_', ' ', $relation->relationship)) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td>
                                        @foreach($member->students as $relation)
                                            <div class="small mb-1">
                                                <span class="text-secondary">
                                                    {{ $relation->student_first_name }}:
                                                </span>
                                                {{ $relation->can_drop_off ? 'Entrega' : '' }}
                                                {{ $relation->can_pick_up ? ' · Recoge' : '' }}
                                                {{ $relation->can_authorize_exit ? ' · Autoriza salida' : '' }}
                                                {{ $relation->can_receive_notifications ? ' · Avisos' : '' }}
                                                {{ $relation->can_view_attendance ? ' · Asistencia' : '' }}
                                            </div>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($member->user_id)
                                            <span class="badge {{ $member->user_status === 'active' ? 'bg-success-lt' : 'bg-danger-lt' }}">
                                                App {{ $member->user_status === 'active' ? 'activa' : 'bloqueada' }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-lt">Sin cuenta</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="{{ route('admin.guardians.show', $member->guardian_id) }}" class="btn btn-sm btn-outline-primary">
                                                Ver
                                            </a>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.guardians.members.unlink', [$guardianRow->id, $member->guardian_id]) }}"
                                                data-swal-confirm
                                                data-swal-title="Desvincular del núcleo"
                                                data-swal-text="La persona dejará de estar relacionada con los alumnos de este núcleo. No se eliminará su expediente."
                                                data-swal-confirm-text="Sí, desvincular"
                                                data-swal-danger="1"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-danger">Quitar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">No hay familiares secundarios ni autorizados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Alumnos principales del núcleo</h3>
                        <div class="text-secondary">
                            Todos están cubiertos por la misma membresía del responsable principal.
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>Relación</th>
                                <th>Cobertura</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($linkedStudents->where('is_primary', 1) as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($student->student_photo_url)
                                                <span class="avatar avatar-sm me-2" style="background-image: url('{{ $student->student_photo_url }}')"></span>
                                            @else
                                                <span class="avatar avatar-sm bg-blue-lt me-2">
                                                    {{ mb_strtoupper(mb_substr($student->first_name, 0, 1)) }}
                                                </span>
                                            @endif
                                            <div>
                                                <div class="fw-bold">{{ $student->first_name }} {{ $student->last_name }}</div>
                                                <div class="text-secondary small">
                                                    {{ $student->student_code }} · {{ $student->level_name ?? 'Sin nivel' }} · {{ $student->group_name ?? 'Sin grupo' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.guardians.students.permissions', [$guardianRow->id, $student->student_id]) }}"
                                            id="permissions-{{ $student->student_id }}"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <select name="relationship" class="form-select form-select-sm">
                                                <option value="madre" @selected($student->relationship === 'madre')>Madre</option>
                                                <option value="padre" @selected($student->relationship === 'padre')>Padre</option>
                                                <option value="tutor" @selected($student->relationship === 'tutor')>Tutor legal</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-lt mb-1">Alumno principal</span>
                                        <div class="text-secondary small">
                                            Entrega · Recogida · Avisos · Asistencia
                                        </div>
                                    </td>
                                    <td>
                                        <button
                                            form="permissions-{{ $student->student_id }}"
                                            class="btn btn-sm btn-primary"
                                        >
                                            Guardar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-5">Este expediente no tiene alumnos principales.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Transferir alumno a otro núcleo</h3>
                        <div class="text-secondary">
                            Cambia el responsable principal mediante una operación explícita y auditada.
                        </div>
                    </div>
                </div>

                @if($availablePrincipalGuardians->isNotEmpty())
                    <form
                        method="POST"
                        action="{{ route('admin.guardians.students.transfer', $guardianRow->id) }}"
                        data-swal-confirm
                        data-swal-title="Transferir alumno"
                        data-swal-text="El alumno pasará a otro núcleo. La membresía aplicable será la del nuevo responsable principal."
                        data-swal-confirm-text="Sí, transferir"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Alumno</label>
                                    <select name="student_id" class="form-select" required>
                                        <option value="">Seleccionar alumno</option>
                                        @foreach($linkedStudents->where('is_primary', 1) as $student)
                                            <option value="{{ $student->student_id }}">
                                                {{ $student->last_name }}, {{ $student->first_name }}
                                                · {{ $student->student_code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">Nuevo núcleo responsable</label>
                                    <select name="target_guardian_id" class="form-select" required>
                                        <option value="">Seleccionar responsable principal</option>
                                        @foreach($availablePrincipalGuardians as $candidate)
                                            <option value="{{ $candidate->id }}">
                                                {{ $candidate->last_name }}, {{ $candidate->first_name }}
                                                · {{ $candidate->primary_students_count }} alumno(s)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">
                                        Relación del nuevo responsable
                                    </label>
                                    <select name="relationship" class="form-select" required>
                                        <option value="madre">Madre</option>
                                        <option value="padre">Padre</option>
                                        <option value="tutor">Tutor legal</option>
                                    </select>
                                </div>

                                <div class="col-md-6 d-flex align-items-end">
                                    <label class="form-check border rounded p-3 w-100">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="keep_previous_as_secondary"
                                            value="1"
                                            checked
                                        >
                                        <span class="form-check-label">
                                            Conservar al responsable anterior como tutor secundario del alumno.
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="ti ti-alert-triangle me-2"></i>
                                Esta operación no transfiere la membresía. El acceso del alumno dependerá
                                inmediatamente de la membresía o patrocinio del nuevo responsable principal.
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <button class="btn btn-warning">
                                <i class="ti ti-arrows-exchange me-1"></i>
                                Transferir alumno
                            </button>
                        </div>
                    </form>
                @else
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            No existe otro núcleo familiar activo al cual transferir alumnos.
                        </div>
                    </div>
                @endif
            </div>

            @endif

            @if($linkedStudents->where('is_primary', 0)->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                Participación en otros núcleos
                            </h3>
                            <div class="text-secondary">
                                Alumnos donde esta persona participa como familiar secundario
                                o persona autorizada. Cada acceso depende del responsable principal
                                de ese alumno.
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Alumno</th>
                                    <th>Núcleo responsable</th>
                                    <th>Relación y vigencia</th>
                                    <th>Permisos</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($linkedStudents->where('is_primary', 0) as $student)
                                    @php
                                        $secondaryFormId = 'secondary-relation-'.$student->student_id;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $student->first_name }} {{ $student->last_name }}
                                            </div>
                                            <div class="text-secondary small">
                                                {{ $student->student_code }}
                                                · {{ $student->group_name ?? 'Sin grupo' }}
                                            </div>
                                        </td>

                                        <td>
                                            @if($student->primary_guardian_id)
                                                <a
                                                    href="{{ route(
                                                        'admin.guardians.show',
                                                        $student->primary_guardian_id
                                                    ) }}"
                                                    class="fw-semibold"
                                                >
                                                    {{ $student->primary_guardian_name }}
                                                </a>
                                                <div class="text-secondary small">
                                                    Responsable principal
                                                </div>
                                            @else
                                                <span class="badge bg-warning-lt">
                                                    Sin responsable principal
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <form
                                                id="{{ $secondaryFormId }}"
                                                method="POST"
                                                action="{{ route(
                                                    'admin.guardians.students.permissions',
                                                    [$guardianRow->id, $student->student_id]
                                                ) }}"
                                            >
                                                @csrf
                                                @method('PATCH')
                                            </form>

                                            <select
                                                form="{{ $secondaryFormId }}"
                                                name="relationship"
                                                class="form-select form-select-sm mb-2"
                                                required
                                            >
                                                @foreach([
                                                    'madre' => 'Madre',
                                                    'padre' => 'Padre',
                                                    'tutor' => 'Tutor',
                                                    'abuelo' => 'Abuelo',
                                                    'abuela' => 'Abuela',
                                                    'tio' => 'Tío',
                                                    'tia' => 'Tía',
                                                    'hermano' => 'Hermano',
                                                    'hermana' => 'Hermana',
                                                    'persona_autorizada' => 'Persona autorizada',
                                                    'otro' => 'Otro',
                                                ] as $relationshipKey => $relationshipLabel)
                                                    <option
                                                        value="{{ $relationshipKey }}"
                                                        @selected(
                                                            $student->relationship === $relationshipKey
                                                        )
                                                    >
                                                        {{ $relationshipLabel }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <div class="d-flex gap-2">
                                                <input
                                                    form="{{ $secondaryFormId }}"
                                                    type="date"
                                                    name="valid_from"
                                                    value="{{ $student->valid_from }}"
                                                    class="form-control form-control-sm"
                                                    title="Válido desde"
                                                >
                                                <input
                                                    form="{{ $secondaryFormId }}"
                                                    type="date"
                                                    name="valid_until"
                                                    value="{{ $student->valid_until }}"
                                                    class="form-control form-control-sm"
                                                    title="Válido hasta"
                                                >
                                            </div>
                                        </td>

                                        <td>
                                            @foreach([
                                                'can_drop_off' => 'Entrega',
                                                'can_pick_up' => 'Recoge',
                                                'can_authorize_exit' => 'Autoriza salida',
                                                'can_receive_notifications' => 'Avisos',
                                                'can_view_attendance' => 'Asistencia',
                                            ] as $permission => $label)
                                                <label class="form-check mb-1">
                                                    <input
                                                        form="{{ $secondaryFormId }}"
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="{{ $permission }}"
                                                        value="1"
                                                        @checked((bool) $student->{$permission})
                                                    >
                                                    <span class="form-check-label">
                                                        {{ $label }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </td>

                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <button
                                                    form="{{ $secondaryFormId }}"
                                                    class="btn btn-sm btn-primary"
                                                >
                                                    Guardar
                                                </button>

                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'admin.guardians.students.unlink',
                                                        [$guardianRow->id, $student->student_id]
                                                    ) }}"
                                                    data-swal-confirm
                                                    data-swal-title="Desvincular persona"
                                                    data-swal-text="La persona dejará de estar vinculada con este alumno."
                                                    data-swal-confirm-text="Sí, desvincular"
                                                    data-swal-danger="1"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        Quitar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif


            <div class="card mt-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Historial de movimientos</h3>
                        <div class="text-secondary">
                            {{ $accessLogs->total() }} movimientos asociados al tutor.
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Alumno</th>
                                <th>Movimiento</th>
                                <th>Resultado</th>
                                <th>Origen</th>
                                <th>Operador / dispositivo</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($accessLogs as $log)
                                <tr>
                                    <td class="text-nowrap">
                                        {{ \Illuminate\Support\Carbon::parse($log->scanned_at)->format('d/m/Y H:i:s') }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            {{ trim(($log->student_first_name ?? '').' '.($log->student_last_name ?? ''))
                                                ?: 'Alumno no disponible' }}
                                        </div>

                                        <div class="text-secondary small">
                                            {{ $log->student_code ?? '—' }}
                                            @if($log->group_name)
                                                · {{ $log->group_name }}
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge {{ $log->event_type === 'entry'
                                            ? 'bg-blue-lt'
                                            : 'bg-purple-lt' }}">
                                            {{ $eventLabels[$log->event_type] ?? ucfirst($log->event_type) }}
                                        </span>

                                        <div class="text-secondary small mt-1">
                                            {{ $log->event_type === 'entry'
                                                ? 'Entrega'
                                                : 'Recogida' }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge {{ $log->decision === 'allowed'
                                            ? 'bg-success-lt'
                                            : ($log->decision === 'duplicate'
                                                ? 'bg-yellow-lt'
                                                : 'bg-danger-lt') }}">
                                            {{ $statusLabels[$log->event_status]
                                                ?? ucfirst(str_replace('_', ' ', $log->event_status)) }}
                                        </span>

                                        @if($log->reason)
                                            <div class="text-secondary small mt-1">
                                                {{ $log->reason }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div>{{ ucfirst(str_replace('_', ' ', $log->source)) }}</div>
                                        <div class="text-secondary small">
                                            {{ $log->reader_type }}
                                            @if($log->area_name)
                                                · {{ $log->area_name }}
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div>{{ $log->performed_by_name ?? 'Sin operador' }}</div>
                                        <div class="text-secondary small">
                                            {{ $log->device_name ?? 'Sin dispositivo' }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-secondary py-5"
                                    >
                                        Este tutor todavía no tiene movimientos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($accessLogs->total() > 0)
                    <div
                        class="card-footer d-flex flex-column
                            flex-md-row align-items-md-center gap-2"
                    >
                        <div class="text-secondary">
                            Mostrando
                            <strong>{{ $accessLogs->firstItem() }}</strong>
                            a
                            <strong>{{ $accessLogs->lastItem() }}</strong>
                            de
                            <strong>{{ $accessLogs->total() }}</strong>
                            movimientos
                        </div>

                        @if($accessLogs->hasPages())
                            <div class="ms-md-auto">
                                {{ $accessLogs->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mode = document.getElementById('family-member-mode');
            const existingBlock = document.getElementById('family-existing-member-fields');
            const newBlock = document.getElementById('family-new-member-fields');
            const memberSearch = document.getElementById(
                'family-existing-member-search'
            );
            const memberSelect = document.getElementById(
                'family-existing-member-select'
            );

            const syncMemberMode = () => {
                if (!mode || !existingBlock || !newBlock) {
                    return;
                }

                const useExisting = mode.value === 'existing';

                existingBlock.classList.toggle('d-none', !useExisting);
                newBlock.classList.toggle('d-none', useExisting);

                existingBlock
                    .querySelectorAll('select, input, textarea')
                    .forEach((field) => {
                        field.disabled = !useExisting;
                    });

                newBlock
                    .querySelectorAll('select, input, textarea')
                    .forEach((field) => {
                        field.disabled = useExisting;
                    });
            };

            const filterExistingMembers = () => {
                if (!memberSearch || !memberSelect) {
                    return;
                }

                const term = memberSearch.value
                    .trim()
                    .toLocaleLowerCase('es-MX');

                Array.from(memberSelect.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = term !== ''
                        && !option.text
                            .toLocaleLowerCase('es-MX')
                            .includes(term);
                });
            };

            mode?.addEventListener('change', syncMemberMode);
            memberSearch?.addEventListener('input', filterExistingMembers);

            syncMemberMode();
            filterExistingMembers();
        });
    </script>

    @vite('resources/js/guardian-credential.js')
@endsection