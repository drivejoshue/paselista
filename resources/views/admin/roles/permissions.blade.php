@extends('layouts.app')

@section('title', 'Permisos por rol | PaseLista')
@section('section-label', 'Administración')
@section('page-title', 'Permisos por rol')

@section('topbar-actions')
    <a
        href="{{ route('admin.dashboard') }}"
        class="btn btn-outline-secondary btn-sm"
    >
        <i class="ti ti-arrow-left me-1"></i>
        Dashboard
    </a>
@endsection

@section('content')
    @php
        $roleKeys = array_keys($roles);

        $grouped = collect($matrix)
            ->groupBy('group');
    @endphp

    <div class="alert alert-info">
        <div class="d-flex gap-2">
            <i class="ti ti-shield-check mt-1"></i>

            <div>
                <strong>
                    Esta matriz define los permisos reales del panel.
                </strong>

                <div class="mt-1">
                    El cargo laboral puede ser Director, pero cuando esa
                    misma persona administra toda la plataforma su rol
                    técnico recomendado es
                    <code>school_admin</code>.
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-3">
        @foreach($roles as $roleKey => $role)
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <span class="avatar bg-blue-lt">
                                <i class="ti ti-user-shield"></i>
                            </span>

                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="card-title mb-0">
                                        {{ $role['label'] }}
                                    </h3>

                                    @if($currentRole === $roleKey)
                                        <span class="badge bg-primary-lt">
                                            Tu rol
                                        </span>
                                    @endif
                                </div>

                                <div class="text-secondary mt-2">
                                    {{ $role['description'] }}
                                </div>

                                <div class="mt-2">
                                    <code>{{ $roleKey }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @foreach($grouped as $group => $capabilities)
        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        {{ $group }}
                    </h3>

                    <div class="text-secondary">
                        Permisos efectivos dentro del panel escolar.
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Función</th>

                            @foreach($roleKeys as $roleKey)
                                <th class="text-center">
                                    {{ $roles[$roleKey]['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($capabilities as $capability)
                            <tr>
                                <td style="min-width: 340px;">
                                    <div class="fw-semibold">
                                        {{ $capability['label'] }}
                                    </div>

                                    <div class="text-secondary small mt-1">
                                        {{ $capability['description'] }}
                                    </div>

                                    <details class="mt-2">
                                        <summary class="small text-secondary">
                                            Rutas protegidas
                                        </summary>

                                        <div class="mt-1">
                                            @foreach(
                                                $capability['routes']
                                                as $routePattern
                                            )
                                                <code class="me-1">
                                                    {{ $routePattern }}
                                                </code>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>

                                @foreach($roleKeys as $roleKey)
                                    <td class="text-center">
                                        @if(
                                            $capability['roles'][
                                                $roleKey
                                            ] ?? false
                                        )
                                            <span
                                                class="badge bg-success-lt"
                                                title="Permitido"
                                            >
                                                <i class="ti ti-check"></i>
                                            </span>
                                        @else
                                            <span
                                                class="badge bg-secondary-lt"
                                                title="No permitido"
                                            >
                                                <i class="ti ti-minus"></i>
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="alert alert-warning">
        <i class="ti ti-alert-triangle me-2"></i>

        El director puede administrar usuarios institucionales, pero
        <strong>SystemUserController</strong> continúa limitándolo a
        directores, prefectos y kioscos. No puede crear ni modificar
        administradores escolares o superadministradores.
    </div>
@endsection
