@extends('layouts.sysadmin')

@section('title', 'IA · '.$school->name)
@section('page_title', 'PaseLista IA · '.$school->name)

@section('content')
@php
    $usedCredits = (int) (
        $usage->credits
        ?? $usage->runs
        ?? 0
    );

    $pendingCredits = (int) (
        $usage->pending_credits
        ?? 0
    );

    $committedCredits =
        $usedCredits
        + $pendingCredits;

    $usagePercent = $settings->monthly_query_limit > 0
        ? round(
            (
                $committedCredits
                / $settings->monthly_query_limit
            ) * 100,
            1
        )
        : 0;
@endphp

<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Control por escuela</div>
            <h2 class="page-title">{{ $school->name }}</h2>
            <div class="text-secondary mt-1">
                Configuración, cuota y consumo técnico de IA.
            </div>
        </div>

        <div class="col-auto ms-auto">
            <div class="btn-list">
                <a
                    href="{{ route('sysadmin.ai.audit.index', ['school_id' => $school->id]) }}"
                    class="btn btn-outline-primary"
                >
                    <i class="ti ti-shield-search me-1"></i>
                    Auditoría
                </a>

                <a href="{{ route('sysadmin.ai.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>
                    Escuelas
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@if(! $globalEnabled || ! $apiKeyConfigured)
    <div class="alert alert-warning">
        La configuración puede guardarse, pero la IA no funcionará hasta que
        <code>SCHOOLPASS_AI_ENABLED</code> y <code>DEEPSEEK_API_KEY</code>
        estén disponibles globalmente.
    </div>
@endif

<div class="row row-cards mb-3">
    @foreach([
        ['Créditos comprometidos', number_format($committedCredits).' / '.number_format($settings->monthly_query_limit), 'ti-coins', 'bg-blue-lt text-blue'],
        ['Tokens del periodo', number_format($usage->total_tokens), 'ti-braces', 'bg-purple-lt text-purple'],
        ['Costo estimado', 'USD '.number_format($usage->estimated_cost_usd, 6), 'ti-currency-dollar', 'bg-green-lt text-green'],
        ['Errores', number_format($usage->errors), 'ti-alert-triangle', $usage->errors > 0 ? 'bg-red-lt text-red' : 'bg-green-lt text-green'],
    ] as [$label, $value, $icon, $class])
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="subheader">{{ $label }}</div>
                            <div class="h2 mt-2 mb-0">{{ $value }}</div>
                        </div>
                        <span class="avatar {{ $class }} ms-auto">
                            <i class="ti {{ $icon }}"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row row-cards">
    <div class="col-xl-7">
        <form
            method="POST"
            action="{{ route('sysadmin.ai.schools.update', $school) }}"
            class="card"
        >
            @csrf
            @method('PUT')

            <div class="card-header">
                <div>
                    <h3 class="card-title">Configuración</h3>
                    <div class="text-secondary">
                        Solo Sysadmin puede modificar estos valores.
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Créditos IA:</strong>
                    una consulta rápida consume
                    {{ config('schoolpass_ai.quota.fast_units', 1) }}
                    crédito; una consulta Pro consume
                    {{ config('schoolpass_ai.quota.pro_units', 6) }}
                    créditos.
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <input type="hidden" name="enabled" value="0">
                        <label class="form-check form-switch border rounded p-3 ps-5">
                            <input
                                type="checkbox"
                                name="enabled"
                                value="1"
                                class="form-check-input"
                                @checked(old('enabled', (bool) $settings->enabled))
                            >
                            <span class="form-check-label">
                                <span class="fw-semibold d-block">Activar PaseLista IA</span>
                                <span class="text-secondary small">
                                    Desactivarla no elimina conversaciones ni auditoría.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="col-md-6">
                        <input
                            type="hidden"
                            name="default_model"
                            value="fast"
                        >

                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold">
                                Modo normal: análisis rápido
                            </div>

                            <div class="text-secondary small mt-1">
                                Cada consulta inicia en modo rápido y consume
                                {{ config('schoolpass_ai.quota.fast_units', 1) }} crédito.
                                Pro solo se usa cuando el usuario activa el toggle
                                de esa consulta.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <input type="hidden" name="allow_pro" value="0">
                        <label class="form-check form-switch border rounded p-3 ps-5 h-100">
                            <input
                                type="checkbox"
                                name="allow_pro"
                                value="1"
                                class="form-check-input"
                                @checked(old('allow_pro', (bool) $settings->allow_pro))
                            >
                            <span class="form-check-label">
                                <span class="fw-semibold d-block">Permitir modelo avanzado</span>
                                <span class="text-secondary small">
                                    Muestra el toggle Pro a administradores y directores.
                                    El toggle inicia apagado en cada consulta.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">Créditos mensuales</label>
                        <input
                            type="number"
                            name="monthly_query_limit"
                            class="form-control"
                            value="{{ old('monthly_query_limit', $settings->monthly_query_limit) }}"
                            min="1"
                            max="100000"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">Periodo máximo</label>
                        <div class="input-group">
                            <input
                                type="number"
                                name="max_range_days"
                                class="form-control"
                                value="{{ old('max_range_days', $settings->max_range_days) }}"
                                min="1"
                                max="730"
                                required
                            >
                            <span class="input-group-text">días</span>
                        </div>
                    </div>

                    @foreach([
                        'allow_school_analysis' => ['Análisis de escuela', 'Consultas institucionales generales.'],
                        'allow_group_analysis' => ['Análisis de grupo', 'Análisis y comparación de grupos.'],
                        'allow_student_analysis' => ['Análisis individual', 'Análisis de alumnos específicos.'],
                    ] as $field => [$label, $description])
                        <div class="col-md-4">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <label class="form-check form-switch border rounded p-3 ps-5 h-100">
                                <input
                                    type="checkbox"
                                    name="{{ $field }}"
                                    value="1"
                                    class="form-check-input"
                                    @checked(old($field, (bool) $settings->{$field}))
                                >
                                <span class="form-check-label">
                                    <span class="fw-semibold d-block">{{ $label }}</span>
                                    <span class="text-secondary small">{{ $description }}</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>
                    Guardar configuración
                </button>
            </div>
        </form>
    </div>

    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Consumo vigente</h3>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ number_format($committedCredits) }} créditos</span>
                    <strong>{{ number_format($usagePercent, 1) }}%</strong>
                </div>

                <div class="progress mb-3">
                    <div
                        class="progress-bar {{ $usagePercent >= 100 ? 'bg-danger' : ($usagePercent >= 80 ? 'bg-warning' : 'bg-primary') }}"
                        style="width: {{ min(100, $usagePercent) }}%;"
                    ></div>
                </div>

                <dl class="row mb-0">
                    <dt class="col-6">Desde</dt>
                    <dd class="col-6 text-end">{{ $usage->window_start->format('d/m/Y H:i') }}</dd>
                    <dt class="col-6">Consumidos</dt>
                    <dd class="col-6 text-end">{{ number_format($usedCredits) }}</dd>
                    <dt class="col-6">Pendientes</dt>
                    <dd class="col-6 text-end">{{ number_format($pendingCredits) }}</dd>
                    <dt class="col-6">Entrada</dt>
                    <dd class="col-6 text-end">{{ number_format($usage->input_tokens) }}</dd>
                    <dt class="col-6">Salida</dt>
                    <dd class="col-6 text-end">{{ number_format($usage->output_tokens) }}</dd>
                    <dt class="col-6">Duración promedio</dt>
                    <dd class="col-6 text-end">{{ number_format($usage->average_duration_ms / 1000, 2) }} s</dd>
                </dl>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('sysadmin.ai.schools.reset-usage', $school) }}"
            class="card"
            onsubmit="return confirm('¿Reiniciar el contador mensual de esta escuela?');"
        >
            @csrf

            <div class="card-header">
                <h3 class="card-title">Reiniciar cuota</h3>
            </div>

            <div class="card-body">
                <p class="text-secondary">
                    Inicia un nuevo contador desde este momento. No elimina historial.
                </p>

                <label class="form-label">Motivo</label>
                <textarea
                    name="reason"
                    class="form-control"
                    rows="2"
                    maxlength="500"
                    placeholder="Cortesía, ajuste comercial o corrección"
                ></textarea>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-outline-warning">
                    <i class="ti ti-refresh me-1"></i>
                    Reiniciar contador
                </button>
            </div>
        </form>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ejecuciones recientes</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Pregunta</th>
                            <th>Modelo</th>
                            <th>Créditos</th>
                            <th>Tokens</th>
                            <th>Costo</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentRuns as $run)
                            <tr>
                                <td class="text-nowrap">{{ $run->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $run->user?->name ?: 'Usuario eliminado' }}</td>
                                <td style="max-width: 320px;">
                                    <div class="text-truncate">{{ $run->question }}</div>
                                    <div class="text-secondary small">
                                        {{ $run->conversation?->title ?: 'Sin conversación' }}
                                    </div>
                                </td>
                                <td>
                                    {{ $run->model ?: '—' }}
                                    <div class="text-secondary small">
                                        {{ $run->requested_model_tier === 'pro'
                                            ? 'Avanzado'
                                            : 'Rápido'
                                        }}
                                    </div>
                                </td>
                                <td>
                                    {{ number_format(
                                        $run->quota_units ?: 1
                                    ) }}
                                </td>
                                <td>{{ number_format($run->total_tokens) }}</td>
                                <td>USD {{ number_format((float) $run->estimated_cost_usd, 6) }}</td>
                                <td>
                                    <span class="badge {{ $run->status === 'success' ? 'bg-success-lt' : ($run->status === 'error' ? 'bg-danger-lt' : 'bg-warning-lt') }}">
                                        {{ $run->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a
                                        href="{{ route('sysadmin.ai.audit.show', $run->id) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-5">
                                    La escuela todavía no tiene ejecuciones.
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
