@extends('layouts.sysadmin')

@section('title', 'Auditoría de IA')
@section('page_title', 'Auditoría global de IA')

@section('content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Administración global</div>
            <h2 class="page-title">Auditoría de PaseLista IA</h2>
            <div class="text-secondary mt-1">
                Preguntas, modelos, tokens, costos, errores y etapas públicas.
            </div>
        </div>

        <div class="col-auto ms-auto">
            <a href="{{ route('sysadmin.ai.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-settings me-1"></i>
                Control por escuela
            </a>
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    @foreach([
        ['Ejecuciones', number_format($summary->total_runs ?? 0), 'ti-brain', 'bg-blue-lt text-blue'],
        ['Correctas', number_format($summary->success_runs ?? 0), 'ti-circle-check', 'bg-green-lt text-green'],
        ['Errores', number_format($summary->error_runs ?? 0), 'ti-alert-triangle', 'bg-red-lt text-red'],
        ['Créditos', number_format($summary->total_credits ?? 0), 'ti-coins', 'bg-blue-lt text-blue'],
        ['Tokens', number_format($summary->total_tokens ?? 0), 'ti-braces', 'bg-purple-lt text-purple'],
        ['Costo', 'USD '.number_format($summary->estimated_cost_usd ?? 0, 6), 'ti-currency-dollar', 'bg-green-lt text-green'],
        ['Duración promedio', number_format(($summary->average_duration_ms ?? 0) / 1000, 2).' s', 'ti-clock', 'bg-azure-lt text-azure'],
    ] as [$label, $value, $icon, $class])
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ $label }}</div>
                    <div class="d-flex align-items-center mt-2">
                        <div class="h2 mb-0">{{ $value }}</div>
                        <span class="avatar avatar-sm {{ $class }} ms-auto">
                            <i class="ti {{ $icon }}"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Filtros</h3>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('sysadmin.ai.audit.index') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Texto</label>
                    <input
                        type="search"
                        name="q"
                        class="form-control"
                        value="{{ request('q') }}"
                        placeholder="Pregunta, alias o hash"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Escuela</label>
                    <select name="school_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($schools as $school)
                            <option
                                value="{{ $school->id }}"
                                @selected((int) request('school_id') === (int) $school->id)
                            >
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach(['queued', 'processing', 'success', 'error'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Modelo</label>
                    <select name="model" class="form-select">
                        <option value="">Todos</option>
                        @foreach($models as $model)
                            <option value="{{ $model }}" @selected(request('model') === $model)>
                                {{ $model }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <input
                        type="text"
                        name="request_type"
                        class="form-control"
                        value="{{ request('request_type') }}"
                        placeholder="question"
                    >
                </div>

                <div class="col-md-3 d-flex align-items-end">
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
                    <th>Fecha</th>
                    <th>Escuela / usuario</th>
                    <th>Pregunta</th>
                    <th>Alcance</th>
                    <th>Modelo / nivel</th>
                    <th>Tokens</th>
                    <th>Costo</th>
                    <th>Duración</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($runs as $run)
                    <tr>
                        <td class="text-nowrap">{{ $run->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a
                                href="{{ route('sysadmin.ai.schools.show', $run->school_id) }}"
                                class="fw-semibold text-reset"
                            >
                                {{ $run->school?->name ?: 'Escuela eliminada' }}
                            </a>
                            <div class="text-secondary small">
                                {{ $run->user?->name ?: 'Usuario eliminado' }}
                            </div>
                        </td>

                        <td style="max-width: 360px;">
                            <div class="text-truncate">{{ $run->question }}</div>
                            <div class="text-secondary small">
                                {{ $run->conversation?->title ?: 'Sin conversación' }}
                            </div>
                        </td>

                        <td>
                            <span class="badge bg-blue-lt">{{ $run->scope_type }}</span>
                            @if($run->scope_id)
                                <div class="text-secondary small">ID {{ $run->scope_id }}</div>
                            @endif
                        </td>

                        <td>
                            {{ $run->model ?: '—' }}
                            <div class="text-secondary small">
                                {{ $run->requested_model_tier === 'pro'
                                    ? 'Avanzado'
                                    : 'Rápido'
                                }}
                                ·
                                {{ number_format(
                                    $run->quota_units ?: 1
                                ) }}
                                créditos
                            </div>
                        </td>
                        <td>{{ number_format($run->total_tokens) }}</td>
                        <td>USD {{ number_format((float) $run->estimated_cost_usd, 6) }}</td>
                        <td>
                            {{ $run->duration_ms
                                ? number_format($run->duration_ms / 1000, 2).' s'
                                : '—'
                            }}
                        </td>

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
                                Detalle
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-secondary py-5">
                            No hay ejecuciones para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($runs->hasPages())
        <div class="card-footer">{{ $runs->links() }}</div>
    @endif
</div>
@endsection
