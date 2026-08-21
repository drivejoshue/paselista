@extends('layouts.app')

@section('title', 'Familias | PaseLista')
@section('section-label', 'Dirección')
@section('page-title', 'Núcleos familiares')

@section('topbar-actions')
    <a href="{{ route('admin.guardians.create') }}" class="btn btn-primary btn-sm">
        <i class="ti ti-home-plus me-1"></i>
        Nuevo núcleo familiar
    </a>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">
            <i class="ti ti-circle-check me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="ti ti-alert-circle me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="alert alert-info">
        <div class="d-flex">
            <div><i class="ti ti-users-group icon alert-icon"></i></div>
            <div>
                <h4 class="alert-title">Un núcleo se administra desde su responsable principal</h4>
                <div class="text-secondary">
                    La membresía cubre a todos los alumnos donde esta persona es principal.
                    Padres secundarios, familiares y autorizados se agregan dentro del expediente del núcleo.
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Núcleos familiares registrados</h3>
                <p class="card-subtitle">
                    Se muestran únicamente responsables principales con alumnos activos.
                </p>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.guardians.index') }}" class="row g-2">
                <div class="col-lg-5">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input
                            type="text"
                            name="search"
                            value="{{ $filters['search'] }}"
                            class="form-control"
                            placeholder="Responsable, alumno, correo, teléfono o matrícula"
                        >
                    </div>
                </div>

                <div class="col-sm-4 col-lg-2">
                    <select name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="active" @selected($filters['status'] === 'active')>Activos</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Inactivos</option>
                        <option value="blocked" @selected($filters['status'] === 'blocked')>Bloqueados</option>
                    </select>
                </div>

                <div class="col-sm-4 col-lg-2">
                    <select name="account" class="form-select">
                        <option value="">Cualquier cuenta</option>
                        <option value="with" @selected($filters['account'] === 'with')>Con acceso app</option>
                        <option value="without" @selected($filters['account'] === 'without')>Sin acceso app</option>
                    </select>
                </div>

                <div class="col-sm-4 col-lg-2">
                    <select name="qr" class="form-select">
                        <option value="">Cualquier QR</option>
                        <option value="active" @selected($filters['qr'] === 'active')>Con QR activo</option>
                        <option value="without" @selected($filters['qr'] === 'without')>Sin QR activo</option>
                    </select>
                </div>

                <div class="col-lg-1 d-grid">
                    <button class="btn btn-primary" title="Aplicar filtros">
                        <i class="ti ti-filter"></i>
                    </button>
                </div>

                @if(collect($filters)->filter()->isNotEmpty())
                    <div class="col-12">
                        <a href="{{ route('admin.guardians.index') }}" class="btn btn-link px-0">
                            <i class="ti ti-x me-1"></i>
                            Limpiar filtros
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Responsable principal</th>
                        <th>Alumnos del núcleo</th>
                        <th>Familiares y autorizados</th>
                        <th>PaseLista Family</th>
                        <th>Cuenta / QR</th>
                        <th>Estado</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guardians as $guardian)
                        @php
                            $fullName = trim($guardian->first_name.' '.$guardian->last_name);
                            $membershipUsable = $guardian->membership_status === 'active'
                                && (
                                    empty($guardian->membership_expires_at)
                                    || \Illuminate\Support\Carbon::parse(
                                        $guardian->membership_expires_at
                                    )->endOfDay()->gte(now())
                                );

                            $familyLabel = 'Pendiente de activación';
                            $familyClass = 'bg-warning-lt';

                            if ($familyTrialActive) {
                                $familyLabel = 'Prueba institucional';
                                $familyClass = 'bg-azure-lt';
                            } elseif ($membershipUsable) {
                                $familyLabel = match ($guardian->membership_source) {
                                    'school_sponsorship' => 'Patrocinada',
                                    'google_play' => 'Pagada',
                                    'sysadmin_courtesy' => 'Cortesía',
                                    default => 'Activa',
                                };
                                $familyClass = 'bg-success-lt';
                            } elseif($guardian->membership_status === 'revoked') {
                                $familyLabel = 'Revocada';
                                $familyClass = 'bg-danger-lt';
                            } elseif($guardian->membership_status === 'expired') {
                                $familyLabel = 'Vencida';
                                $familyClass = 'bg-secondary-lt';
                            }
                        @endphp

                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($guardian->photo_url)
                                        <span
                                            class="avatar avatar-sm me-2"
                                            style="background-image: url('{{ $guardian->photo_url }}')"
                                        ></span>
                                    @else
                                        <span class="avatar avatar-sm bg-blue-lt me-2">
                                            {{ mb_strtoupper(mb_substr($guardian->first_name, 0, 1)) }}
                                        </span>
                                    @endif

                                    <div>
                                        <a
                                            href="{{ route('admin.guardians.show', $guardian->id) }}"
                                            class="fw-bold text-reset"
                                        >
                                            {{ $fullName }}
                                        </a>
                                        <div class="text-secondary small">
                                            Responsable principal · {{ $guardian->phone ?? 'Sin teléfono' }}
                                        </div>
                                        <div class="text-secondary small text-break">
                                            {{ $guardian->email ?? 'Sin correo' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-blue-lt mb-1">
                                    {{ $guardian->primary_students_count }} alumno(s)
                                </span>
                                <div class="text-secondary small" style="max-width: 310px;">
                                    {{ $guardian->primary_students_names ?: 'Sin nombres disponibles' }}
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-purple-lt">
                                    {{ $guardian->family_members_count }} miembro(s)
                                </span>
                                <div class="text-secondary small mt-1">
                                    Secundarios y personas autorizadas
                                </div>
                            </td>

                            <td>
                                <span class="badge {{ $familyClass }}">{{ $familyLabel }}</span>
                                @if($membershipUsable && $guardian->membership_expires_at)
                                    <div class="text-secondary small mt-1">
                                        Hasta {{ \Illuminate\Support\Carbon::parse($guardian->membership_expires_at)->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($guardian->user_id)
                                    <span class="badge {{ $guardian->user_status === 'active' ? 'bg-success-lt' : 'bg-danger-lt' }}">
                                        App {{ $guardian->user_status === 'active' ? 'activa' : 'bloqueada' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-lt">Sin cuenta</span>
                                @endif

                                <div class="mt-1">
                                    @if((int) $guardian->active_credentials_count > 0)
                                        <span class="badge bg-green-lt"><i class="ti ti-qrcode me-1"></i>QR activo</span>
                                    @elseif(!$guardian->photo_url)
                                        <span class="badge bg-yellow-lt">QR requiere foto</span>
                                    @else
                                        <span class="badge bg-secondary-lt">Sin QR</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if($guardian->status === 'active')
                                    <span class="badge bg-success-lt">Activo</span>
                                @elseif($guardian->status === 'blocked')
                                    <span class="badge bg-danger-lt">Bloqueado</span>
                                @else
                                    <span class="badge bg-secondary-lt">Inactivo</span>
                                @endif
                            </td>

                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a
                                        href="{{ route('admin.guardians.show', $guardian->id) }}"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Abrir núcleo
                                    </a>
                                    <a
                                        href="{{ route('admin.guardians.edit', $guardian->id) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Editar responsable"
                                    >
                                        <i class="ti ti-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                No hay núcleos familiares que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($guardians->hasPages())
            <div class="card-footer">{{ $guardians->links() }}</div>
        @endif
    </div>
@endsection