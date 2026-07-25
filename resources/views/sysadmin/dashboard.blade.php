@extends('layouts.sysadmin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard global')

@section('content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Administración global</div>
            <h2 class="page-title">Resumen de PaseLista</h2>
            <div class="text-secondary mt-1">
                Escuelas, licencias, capacidad, IA e ingreso recurrente.
            </div>
        </div>

        <div class="col-auto ms-auto">
            <div class="btn-list">
                <a href="{{ route('sysadmin.ai.index') }}" class="btn btn-outline-primary">
                    <i class="ti ti-brain me-2"></i>
                    Control IA
                </a>

                <a href="{{ route('sysadmin.ai.audit.index') }}" class="btn btn-outline-primary">
                    <i class="ti ti-shield-search me-2"></i>
                    Auditoría IA
                </a>

                <a href="{{ route('sysadmin.schools.index') }}" class="btn btn-primary">
                    <i class="ti ti-school me-2"></i>
                    Ver escuelas
                </a>
            </div>
        </div>
    </div>
</div>

@if(! $globalAiEnabled || ! $apiKeyConfigured)
    <div class="alert alert-warning">
        <i class="ti ti-alert-triangle me-2"></i>
        PaseLista IA no está disponible globalmente.
        Estado: <strong>{{ $globalAiEnabled ? 'activado' : 'desactivado' }}</strong>.
        Clave DeepSeek:
        <strong>{{ $apiKeyConfigured ? 'configurada' : 'no configurada' }}</strong>.
    </div>
@endif

<div class="row row-deck row-cards mb-3">
    @php
        $cards = [
            ['Escuelas activas', number_format($metrics['active_schools']), 'ti-school', 'bg-blue-lt text-blue'],
            ['Licencias en prueba', number_format($metrics['trial_licenses']), 'ti-flask', 'bg-yellow-lt text-yellow'],
            ['Vencen en 30 días', number_format($metrics['expiring_soon']), 'ti-calendar-exclamation', 'bg-orange-lt text-orange'],
            ['Licencias vencidas', number_format($metrics['expired_licenses']), 'ti-license-off', 'bg-red-lt text-red'],
            ['Alumnos activos', number_format($metrics['students']), 'ti-users', 'bg-cyan-lt text-cyan'],
            ['Dispositivos activos', number_format($metrics['devices']), 'ti-device-tablet', 'bg-purple-lt text-purple'],
            ['MRR estimado', '$'.number_format($metrics['mrr'], 2), 'ti-cash', 'bg-green-lt text-green'],
            ['ARR estimado', '$'.number_format($metrics['arr'], 2), 'ti-chart-line', 'bg-indigo-lt text-indigo'],
            ['Escuelas con IA', number_format($metrics['ai_enabled_schools']), 'ti-brain', 'bg-azure-lt text-azure'],
            ['Preguntas IA del mes', number_format($metrics['ai_runs_month']), 'ti-message-circle', 'bg-blue-lt text-blue'],
            ['Créditos IA del mes', number_format($metrics['ai_credits_month']), 'ti-coins', 'bg-azure-lt text-azure'],
            ['Tokens IA del mes', number_format($metrics['ai_tokens_month']), 'ti-braces', 'bg-purple-lt text-purple'],
            ['Costo IA del mes', 'USD '.number_format($metrics['ai_cost_month'], 6), 'ti-currency-dollar', 'bg-green-lt text-green'],
        ];
    @endphp

    @foreach($cards as [$label, $value, $icon, $class])
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="subheader">{{ $label }}</div>
                            <div class="h1 mb-0 mt-2">{{ $value }}</div>
                        </div>

                        <span class="sp-stat-icon {{ $class }} ms-auto">
                            <i class="ti {{ $icon }}"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row row-cards mb-3">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Distribución por plan</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th class="text-end">Licencias</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($planDistribution as $plan)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $plan->name }}</div>
                                    <div class="small text-secondary">{{ $plan->code }}</div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-blue-lt">{{ number_format($plan->total) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-secondary text-center py-4">
                                    Sin planes registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Escuelas cerca del límite de alumnos</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Escuela</th>
                            <th>Alumnos</th>
                            <th style="min-width: 11rem;">Uso</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($schoolsNearLimit as $school)
                            <tr>
                                <td>
                                    <a
                                        href="{{ route('sysadmin.schools.show', $school->id) }}"
                                        class="fw-semibold text-reset"
                                    >
                                        {{ $school->name }}
                                    </a>
                                    <div class="small text-secondary">
                                        {{ $school->plan_name ?: 'Sin plan' }}
                                    </div>
                                </td>

                                <td>
                                    {{ number_format($school->students_used) }}
                                    /
                                    {{ number_format($school->student_limit) }}
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress progress-sm flex-fill">
                                            <div
                                                class="progress-bar {{ $school->usage_percent >= 100 ? 'bg-danger' : 'bg-warning' }}"
                                                style="width: {{ min(100, $school->usage_percent) }}%;"
                                            ></div>
                                        </div>
                                        <span class="small fw-semibold">{{ $school->usage_percent }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-secondary text-center py-4">
                                    Ninguna escuela ha llegado al 80%.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Mayor uso de IA este mes</h3>
                    <div class="text-secondary">Ejecuciones, tokens y costo estimado.</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Escuela</th>
                            <th>Preguntas</th>
                            <th>Tokens</th>
                            <th>Costo</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($topAiSchools as $school)
                            <tr>
                                <td>
                                    <a
                                        href="{{ route('sysadmin.ai.schools.show', $school->id) }}"
                                        class="fw-semibold text-reset"
                                    >
                                        {{ $school->name }}
                                    </a>
                                </td>
                                <td>{{ number_format($school->runs) }}</td>
                                <td>{{ number_format($school->total_tokens) }}</td>
                                <td>USD {{ number_format($school->estimated_cost_usd, 6) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-secondary text-center py-4">
                                    Sin consumo de IA este mes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Escuelas cerca de su cuota de créditos IA</h3>
                    <div class="text-secondary">Créditos efectivos después de reinicios.</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Escuela</th>
                            <th>Uso</th>
                            <th style="min-width: 12rem;">Porcentaje</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($schoolsNearAiLimit as $school)
                            <tr>
                                <td>
                                    <a
                                        href="{{ route('sysadmin.ai.schools.show', $school->id) }}"
                                        class="fw-semibold text-reset"
                                    >
                                        {{ $school->name }}
                                    </a>
                                </td>
                                <td>{{ number_format($school->used) }} / {{ number_format($school->limit) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress progress-sm flex-fill">
                                            <div
                                                class="progress-bar {{ $school->usage_percent >= 100 ? 'bg-danger' : 'bg-warning' }}"
                                                style="width: {{ min(100, $school->usage_percent) }}%;"
                                            ></div>
                                        </div>
                                        <strong class="small">{{ $school->usage_percent }}%</strong>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-secondary text-center py-4">
                                    Ninguna escuela ha llegado al 80%.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Errores recientes de IA</h3>
            </div>

            <div class="list-group list-group-flush">
                @forelse($recentAiErrors as $error)
                    <a
                        href="{{ route('sysadmin.ai.audit.show', $error->id) }}"
                        class="list-group-item list-group-item-action"
                    >
                        <div class="d-flex align-items-start gap-3">
                            <span class="avatar avatar-sm bg-danger-lt">
                                <i class="ti ti-alert-triangle"></i>
                            </span>

                            <div class="flex-fill">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong>{{ $error->school_name }}</strong>
                                    <span class="text-secondary small text-nowrap">
                                        {{ \Illuminate\Support\Carbon::parse($error->created_at)->format('d/m H:i') }}
                                    </span>
                                </div>

                                <div class="text-truncate mt-1">{{ $error->question }}</div>
                                <div class="text-secondary small text-truncate">
                                    {{ $error->error_message }}
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-secondary text-center py-4">
                        No hay errores recientes.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Actividad reciente de licencias</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Escuela</th>
                            <th>Evento</th>
                            <th>Cambio</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentEvents as $event)
                            <tr>
                                <td class="text-secondary text-nowrap">
                                    {{ \Illuminate\Support\Carbon::parse($event->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <a
                                        href="{{ route('sysadmin.schools.show', $event->school_id) }}"
                                        class="fw-semibold text-reset"
                                    >
                                        {{ $event->school_name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-azure-lt">
                                        {{ str_replace('_', ' ', $event->event_type) }}
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    {{ $event->previous_status ?: '—' }}
                                    <i class="ti ti-arrow-right mx-1 text-secondary"></i>
                                    {{ $event->new_status ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-secondary text-center py-4">
                                    Sin actividad registrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
