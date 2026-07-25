@extends('layouts.app')

@section('title', 'Resultado IA | PaseLista')
@section('section-label', 'Dirección')
@section('page-title', 'Resultado de PaseLista IA')

@section('topbar-actions')
    <a href="{{ route('admin.ai.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>
        Volver
    </a>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ $run->question }}</h3>
                <div class="text-secondary">
                    {{ \Illuminate\Support\Carbon::parse($run->period_from)->format('d/m/Y') }}
                    –
                    {{ \Illuminate\Support\Carbon::parse($run->period_to)->format('d/m/Y') }}
                    · {{ $run->model ?? 'Sin modelo' }}
                    · {{ $run->user_name ?? 'Usuario eliminado' }}
                </div>
            </div>
            <div class="card-actions">
                <span class="badge bg-{{ $run->status === 'success' ? 'success' : ($run->status === 'error' ? 'danger' : 'warning') }}-lt">
                    {{ ucfirst($run->status) }}
                </span>
            </div>
        </div>

        @if($run->status === 'error')
            <div class="card-body">
                <div class="alert alert-danger mb-0">{{ $run->error_message }}</div>
            </div>
        @elseif(!$result)
            <div class="card-body text-center text-secondary py-5">
                El análisis todavía no tiene respuesta.
            </div>
        @else
            <div class="card-body">
                <div class="d-flex gap-3">
                    <span class="avatar bg-blue-lt"><i class="ti ti-brain"></i></span>
                    <div class="flex-fill">
                        <h3>Resumen ejecutivo</h3>
                        <div style="white-space: pre-line;">{{ $result['answer'] }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($result)
        @if(!empty($result['facts']))
            <div class="row row-cards mb-3">
                @foreach($result['facts'] as $fact)
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-sm h-100">
                            <div class="card-body">
                                <div class="text-secondary">{{ $fact['label'] ?? 'Indicador' }}</div>
                                <div class="h2 mb-1">{{ $fact['value'] ?? '—' }}</div>
                                @if(!empty($fact['detail']))
                                    <div class="text-secondary small">{{ $fact['detail'] }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="row row-cards">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Hallazgos</h3></div>
                    <div class="list-group list-group-flush">
                        @forelse($result['findings'] ?? [] as $finding)
                            <div class="list-group-item d-flex gap-2">
                                <i class="ti ti-chart-dots text-primary mt-1"></i>
                                <div>{{ $finding }}</div>
                            </div>
                        @empty
                            <div class="list-group-item text-secondary">Sin hallazgos adicionales.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Recomendaciones administrativas</h3></div>
                    <div class="list-group list-group-flush">
                        @forelse($result['recommendations'] ?? [] as $recommendation)
                            <div class="list-group-item d-flex gap-2">
                                <i class="ti ti-checkbox text-success mt-1"></i>
                                <div>{{ $recommendation }}</div>
                            </div>
                        @empty
                            <div class="list-group-item text-secondary">Sin recomendaciones adicionales.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($result['warnings']))
            <div class="alert alert-warning mt-3">
                <strong><i class="ti ti-alert-triangle me-1"></i> Advertencias</strong>
                <ul class="mb-0 mt-2">
                    @foreach($result['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($result['evidence']))
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Evidencia utilizada</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Fuente</th><th>Periodo</th><th>Alcance</th></tr></thead>
                        <tbody>
                            @foreach($result['evidence'] as $evidence)
                                <tr>
                                    <td>{{ $evidence['source'] ?? 'PaseLista' }}</td>
                                    <td>{{ $evidence['period'] ?? '—' }}</td>
                                    <td>{{ $evidence['scope'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card mt-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-3"><div class="text-secondary small">Entrada</div><div class="fw-bold">{{ number_format($run->input_tokens) }} tokens</div></div>
                    <div class="col-sm-3"><div class="text-secondary small">Salida</div><div class="fw-bold">{{ number_format($run->output_tokens) }} tokens</div></div>
                    <div class="col-sm-3"><div class="text-secondary small">Duración</div><div class="fw-bold">{{ number_format(($run->duration_ms ?? 0) / 1000, 2) }} s</div></div>
                    <div class="col-sm-3"><div class="text-secondary small">Costo estimado</div><div class="fw-bold">USD {{ number_format($run->estimated_cost_usd, 6) }}</div></div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            Esta respuesta es apoyo administrativo. Revísala antes de tomar decisiones.
        </div>
    @endif
@endsection
