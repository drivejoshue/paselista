@extends('layouts.sysadmin')

@section('title', 'Control de IA')
@section('page_title', 'PaseLista IA por escuela')

@section('content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Administración global</div>
            <h2 class="page-title">Control de PaseLista IA</h2>
            <div class="text-secondary mt-1">
                Cuotas, modelos, alcance y consumo por institución.
            </div>
        </div>

        <div class="col-auto ms-auto">
            <div class="btn-list">
                <a href="{{ route('sysadmin.ai.audit.index') }}" class="btn btn-outline-primary">
                    <i class="ti ti-shield-search me-2"></i>
                    Auditoría IA
                </a>

                <a href="{{ route('sysadmin.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-layout-dashboard me-2"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

@if(! $globalEnabled || ! $apiKeyConfigured)
    <div class="alert alert-warning">
        <strong>La IA no está disponible globalmente.</strong>
        Estado global: {{ $globalEnabled ? 'activado' : 'desactivado' }}.
        Clave DeepSeek: {{ $apiKeyConfigured ? 'configurada' : 'no configurada' }}.
    </div>
@endif

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Filtros</h3>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('sysadmin.ai.index') }}">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Buscar</label>
                    <input
                        type="search"
                        name="q"
                        class="form-control"
                        value="{{ request('q') }}"
                        placeholder="Escuela o plan"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estado escolar</label>
                    <select name="school_status" class="form-select">
                        <option value="">Todos</option>
                        @foreach([
                            'active' => 'Activa',
                            'suspended' => 'Suspendida',
                            'cancelled' => 'Cancelada',
                        ] as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(request('school_status') === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">IA</label>
                    <select name="ai_state" class="form-select">
                        <option value="">Todas</option>
                        <option value="enabled" @selected(request('ai_state') === 'enabled')>
                            Activada
                        </option>
                        <option value="disabled" @selected(request('ai_state') === 'disabled')>
                            Desactivada
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-filter me-1"></i>
                        Aplicar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Escuela</th>
                    <th>Licencia</th>
                    <th>IA</th>
                    <th>Uso mensual</th>
                    <th>Tokens</th>
                    <th>Costo</th>
                    <th>Errores</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($schools as $school)
                    @php
                        $settings = $school->ai_settings;
                        $usage = $school->ai_usage;
                        $progressClass = $school->ai_usage_percent >= 100
                            ? 'bg-danger'
                            : ($school->ai_usage_percent >= 80 ? 'bg-warning' : 'bg-primary');
                    @endphp

                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="avatar avatar-sm"
                                    style="background: {{ $school->primary_color ?: '#206bc4' }}; color: #fff;"
                                >
                                    <i class="ti ti-school"></i>
                                </span>

                                <div>
                                    <a
                                        href="{{ route('sysadmin.ai.schools.show', $school->id) }}"
                                        class="fw-semibold text-reset"
                                    >
                                        {{ $school->name }}
                                    </a>
                                    <div class="text-secondary small">
                                        {{ $school->plan_name ?: 'Sin plan' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="badge bg-blue-lt">
                                {{ $school->license_status ?: 'sin licencia' }}
                            </span>
                        </td>

                        <td>
                            @if($school->effective_ai_enabled)
                                <span class="badge bg-success-lt">Activada</span>
                            @elseif((bool) $settings->enabled)
                                <span class="badge bg-warning-lt">Configurada, no global</span>
                            @else
                                <span class="badge bg-secondary-lt">Desactivada</span>
                            @endif

                            <div class="text-secondary small mt-1">
                                Rápido base
                                ·
                                {{ $settings->allow_pro
                                    ? 'Pro permitido'
                                    : 'Pro bloqueado'
                                }}
                            </div>
                        </td>

                        <td style="min-width: 190px;">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>
                                    {{ number_format(
                                        $school->ai_committed_credits
                                    ) }}
                                    créditos de
                                    {{ number_format(
                                        $settings->monthly_query_limit
                                    ) }}
                                </span>
                                <span>{{ number_format($school->ai_usage_percent, 1) }}%</span>
                            </div>

                            <div class="progress progress-sm">
                                <div
                                    class="progress-bar {{ $progressClass }}"
                                    style="width: {{ min(100, $school->ai_usage_percent) }}%;"
                                ></div>
                            </div>
                        </td>

                        <td>{{ number_format($usage->total_tokens) }}</td>
                        <td>USD {{ number_format($usage->estimated_cost_usd, 6) }}</td>
                        <td>
                            <span class="badge {{ $usage->errors > 0 ? 'bg-danger-lt' : 'bg-success-lt' }}">
                                {{ number_format($usage->errors) }}
                            </span>
                        </td>

                        <td class="text-end">
                            <a
                                href="{{ route('sysadmin.ai.schools.show', $school->id) }}"
                                class="btn btn-sm btn-outline-primary"
                            >
                                Configurar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-5">
                            No hay escuelas para mostrar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($schools->hasPages())
        <div class="card-footer">
            {{ $schools->links() }}
        </div>
    @endif
</div>
@endsection
