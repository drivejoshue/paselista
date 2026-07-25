@extends('layouts.sysadmin')

@section('title', 'Auditoría IA #'.$run->id)
@section('page_title', 'Auditoría IA #'.$run->id)

@section('content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">{{ $run->school?->name ?: 'Escuela eliminada' }}</div>
            <h2 class="page-title">Ejecución #{{ $run->id }}</h2>
            <div class="text-secondary mt-1">
                Trazabilidad técnica y operativa de SchoolPass IA.
            </div>
        </div>

        <div class="col-auto ms-auto">
            <a
                href="{{ route('sysadmin.ai.audit.index', ['school_id' => $run->school_id]) }}"
                class="btn btn-outline-secondary"
            >
                <i class="ti ti-arrow-left me-1"></i>
                Auditoría
            </a>
        </div>
    </div>
</div>

<div class="alert alert-info">
    Esta pantalla muestra etapas públicas verificables, métricas, contexto
    seudonimizado y respuesta estructurada. No expone razonamiento interno
    privado del proveedor.
</div>

<div class="row row-cards mb-3">
    @foreach([
        ['Estado', $run->status],
        ['Modelo', $run->model ?: '—'],
        [
            'Nivel solicitado',
            $run->requested_model_tier === 'pro'
                ? 'Avanzado'
                : 'Rápido',
        ],
        [
            'Créditos',
            number_format(
                $run->quota_units
                ?: 1
            ),
        ],
        ['Tokens', number_format($run->total_tokens)],
        ['Costo', 'USD '.number_format((float) $run->estimated_cost_usd, 6)],
        ['Duración', $run->duration_ms ? number_format($run->duration_ms / 1000, 2).' s' : '—'],
        ['Prompt', $run->prompt_version ?: '—'],
    ] as [$label, $value])
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ $label }}</div>
                    <div class="h3 mt-2 mb-0">{{ $value }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row row-cards">
    <div class="col-xl-7">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Solicitud</h3>
            </div>

            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Escuela</dt>
                    <dd class="col-sm-8">{{ $run->school?->name ?: 'Escuela eliminada' }}</dd>

                    <dt class="col-sm-4">Usuario</dt>
                    <dd class="col-sm-8">
                        {{ $run->user?->name ?: 'Usuario eliminado' }}
                        @if($run->user?->email)
                            <div class="text-secondary small">
                                {{ $run->user->email }} · {{ $run->user->role }}
                            </div>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Conversación</dt>
                    <dd class="col-sm-8">{{ $run->conversation?->title ?: 'Sin conversación' }}</dd>

                    <dt class="col-sm-4">Alcance</dt>
                    <dd class="col-sm-8">
                        {{ $run->scope_type }}
                        @if($run->scope_id)
                            · ID {{ $run->scope_id }}
                        @endif
                    </dd>

                    <dt class="col-sm-4">Periodo</dt>
                    <dd class="col-sm-8">
                        {{ $run->period_from->format('d/m/Y') }}
                        –
                        {{ $run->period_to->format('d/m/Y') }}
                    </dd>

                    <dt class="col-sm-4">Contexto generado</dt>
                    <dd class="col-sm-8">
                        {{ $run->context_generated_at
                            ? $run->context_generated_at->format('d/m/Y H:i:s')
                            : '—'
                        }}
                    </dd>

                    <dt class="col-sm-4">Hash</dt>
                    <dd class="col-sm-8"><code>{{ $run->context_hash ?: '—' }}</code></dd>
                </dl>

                <hr>

                <div class="mb-3">
                    <div class="fw-semibold mb-1">Pregunta original</div>
                    <div class="border rounded p-3">{{ $run->question }}</div>
                </div>

                <div>
                    <div class="fw-semibold mb-1">Pregunta seudonimizada</div>
                    <div class="border rounded p-3 bg-light-lt">
                        {{ $run->redacted_question ?: 'No registrada' }}
                    </div>
                </div>
            </div>
        </div>

        @if($run->status === 'error')
            <div class="card mb-3 border-danger">
                <div class="card-header">
                    <h3 class="card-title text-danger">Error</h3>
                </div>
                <div class="card-body">
                    <pre class="mb-0 text-wrap">{{ $run->error_message }}</pre>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Respuesta estructurada</h3>
            </div>

            <div class="card-body">
                @if(!empty($result['answer']))
                    <h3>Resumen ejecutivo</h3>
                    <div style="white-space: pre-line;">{{ $result['answer'] }}</div>
                @else
                    <div class="text-secondary">La ejecución no contiene una respuesta final.</div>
                @endif

                @if(!empty($result['facts']))
                    <div class="row g-2 mt-3">
                        @foreach($result['facts'] as $fact)
                            <div class="col-sm-6 col-xl-3">
                                <div class="card card-sm h-100">
                                    <div class="card-body">
                                        <div class="text-secondary small">{{ $fact['label'] ?? 'Indicador' }}</div>
                                        <div class="h3 mb-1">{{ $fact['value'] ?? '—' }}</div>
                                        @if(!empty($fact['detail']))
                                            <div class="text-secondary small">{{ $fact['detail'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @foreach([
                    'patterns' => 'Patrones',
                    'comparisons' => 'Comparaciones',
                    'findings' => 'Hallazgos',
                    'recommendations' => 'Recomendaciones',
                    'warnings' => 'Advertencias',
                ] as $key => $title)
                    @if(!empty($result[$key]))
                        <h3 class="mt-4">{{ $title }}</h3>
                        <ul>
                            @foreach($result[$key] as $item)
                                <li class="mb-2">{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach

                @if(!empty($result['charts']))
                    <div class="alert alert-info mt-4">
                        Esta respuesta contiene {{ count($result['charts']) }} gráfica(s).
                    </div>
                @endif

                <details class="mt-4">
                    <summary class="fw-semibold">JSON completo</summary>
                    <pre class="mt-3 p-3 bg-dark text-white rounded" style="white-space: pre-wrap;">{{ json_encode(
                        $result,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ) }}</pre>
                </details>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Consumo técnico</h3>
            </div>

            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-7">Proveedor</dt>
                    <dd class="col-5 text-end">{{ $run->provider }}</dd>
                    <dt class="col-7">Entrada</dt>
                    <dd class="col-5 text-end">{{ number_format($run->input_tokens) }}</dd>
                    <dt class="col-7">Entrada en caché</dt>
                    <dd class="col-5 text-end">{{ number_format($run->cached_input_tokens) }}</dd>
                    <dt class="col-7">Salida</dt>
                    <dd class="col-5 text-end">{{ number_format($run->output_tokens) }}</dd>
                    <dt class="col-7">Total</dt>
                    <dd class="col-5 text-end">{{ number_format($run->total_tokens) }}</dd>
                    <dt class="col-7">Razonamiento solicitado</dt>
                    <dd class="col-5 text-end">{{ $run->thinking_enabled ? 'Sí' : 'No' }}</dd>
                    <dt class="col-7">Creada</dt>
                    <dd class="col-5 text-end">{{ $run->created_at->format('d/m/Y H:i:s') }}</dd>
                    <dt class="col-7">Completada</dt>
                    <dd class="col-5 text-end">
                        {{ $run->completed_at ? $run->completed_at->format('d/m/Y H:i:s') : '—' }}
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Etapas operativas</h3>
                    <div class="text-secondary">Traza pública del procesamiento.</div>
                </div>
            </div>

            <div class="list-group list-group-flush">
                @forelse($run->events as $event)
                    <div class="list-group-item">
                        <div class="d-flex gap-3">
                            <span class="avatar avatar-sm {{ $event->status === 'completed' ? 'bg-success-lt' : ($event->status === 'failed' ? 'bg-danger-lt' : 'bg-warning-lt') }}">
                                <i class="ti {{ $event->status === 'completed' ? 'ti-check' : ($event->status === 'failed' ? 'ti-x' : 'ti-loader') }}"></i>
                            </span>

                            <div class="flex-fill">
                                <div class="fw-semibold">{{ $event->label }}</div>

                                @if($event->public_detail)
                                    <div class="text-secondary small mt-1">{{ $event->public_detail }}</div>
                                @endif

                                <div class="text-secondary small mt-1">
                                    {{ $event->started_at ? $event->started_at->format('H:i:s') : '—' }}
                                    –
                                    {{ $event->completed_at ? $event->completed_at->format('H:i:s') : '—' }}
                                </div>

                                @if(!empty($event->metadata_json))
                                    <details class="mt-2">
                                        <summary class="small">Metadata</summary>
                                        <pre class="small mt-2 mb-0">{{ json_encode(
                                            $event->metadata_json,
                                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                        ) }}</pre>
                                    </details>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-secondary">Sin etapas registradas.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
