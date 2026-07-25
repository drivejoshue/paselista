@if($message->role === 'user')
    <div class="d-flex justify-content-end mb-4">
        <div class="bg-primary text-white rounded-3 p-3 sp-ai-user-bubble">
            {{ $message->content }}
        </div>
    </div>
@elseif($message->role === 'assistant')
    @php
        $structured = is_array($message->structured_json)
            ? $message->structured_json
            : [];

        $copyTarget = 'ai-message-content-'.$message->id;
    @endphp

    <div class="d-flex align-items-start gap-3 mb-4">
        <span class="avatar avatar-sm bg-blue-lt flex-shrink-0">
            <i class="ti ti-brain"></i>
        </span>

        <div class="sp-ai-assistant-content">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <div class="fw-bold">
                    PaseLista IA
                </div>

                @if(
                    ($structured['model_tier'] ?? null)
                    === 'pro'
                )
                    <span class="badge bg-purple-lt">
                        <i class="ti ti-sparkles me-1"></i>
                        Avanzado ·
                        {{ (int) (
                            $structured['quota_units']
                            ?? 6
                        ) }}
                        créditos
                    </span>
                @elseif(
                    ! empty(
                        $structured['model_tier']
                    )
                )
                    <span class="badge bg-blue-lt">
                        Rápido ·
                        {{ (int) (
                            $structured['quota_units']
                            ?? 1
                        ) }}
                        crédito
                    </span>
                @endif
            </div>

            <div id="{{ $copyTarget }}" class="sp-ai-answer-content">
                <div class="sp-ai-answer">
                    {{ $structured['answer'] ?? $message->content }}
                </div>

                @if(!empty($structured['facts']))
                    <div class="row g-2 mt-3">
                        @foreach($structured['facts'] as $fact)
                            <div class="col-sm-6 col-xl-3">
                                <div class="card card-sm h-100">
                                    <div class="card-body">
                                        <div class="text-secondary small">
                                            {{ $fact['label'] ?? 'Indicador' }}
                                        </div>
                                        <div class="h3 mb-1">
                                            {{ $fact['value'] ?? '—' }}
                                        </div>
                                        @if(!empty($fact['detail']))
                                            <div class="text-secondary small">
                                                {{ $fact['detail'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div
                    data-ai-charts-container
                    data-run-id="{{ $message->ai_run_id }}"
                >
                    @if(!empty($structured['charts']))
                        <div class="row g-3 mt-3">
                            @foreach($structured['charts'] as $chartIndex => $chart)
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <div>
                                                <h3 class="card-title">
                                                    {{ $chart['title'] ?? 'Gráfica' }}
                                                </h3>

                                                @if(!empty($chart['description']))
                                                    <div class="text-secondary small">
                                                        {{ $chart['description'] }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="card-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-ghost-secondary"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#chart-body-{{ $message->id }}-{{ $chartIndex }}"
                                                    aria-label="Contraer gráfica"
                                                >
                                                    <i class="ti ti-chevron-up"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            id="chart-body-{{ $message->id }}-{{ $chartIndex }}"
                                            class="collapse show"
                                        >
                                            <div class="card-body">
                                                <div
                                                    class="sp-ai-chart-host"
                                                    data-ai-chart='@json($chart)'
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @foreach([
                    'patterns' => ['Patrones', 'ti-chart-dots'],
                    'comparisons' => ['Comparaciones', 'ti-arrows-diff'],
                    'findings' => ['Hallazgos', 'ti-bulb'],
                    'recommendations' => ['Recomendaciones', 'ti-checkbox'],
                    'warnings' => ['Advertencias', 'ti-alert-triangle'],
                ] as $field => [$title, $icon])
                    @if(!empty($structured[$field]))
                        <div class="mt-4">
                            <div class="fw-bold mb-2">
                                <i class="ti {{ $icon }} me-1"></i>
                                {{ $title }}
                            </div>
                            <ul class="mb-0">
                                @foreach($structured[$field] as $item)
                                    <li class="mb-2">{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach

                @if(
                    !empty($structured['analysis_basis'])
                    || !empty($structured['methodology'])
                    || !empty($structured['data_quality'])
                    || !empty($structured['confidence'])
                )
                    <div class="accordion mt-4">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button collapsed py-2"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#analysis-method-{{ $message->id }}"
                                >
                                    <i class="ti ti-list-details me-2"></i>
                                    Cómo se obtuvo
                                </button>
                            </h2>

                            <div
                                id="analysis-method-{{ $message->id }}"
                                class="accordion-collapse collapse"
                            >
                                <div class="accordion-body">
                                    @if(!empty($structured['analysis_basis']))
                                        <ul>
                                            @foreach($structured['analysis_basis'] as $item)
                                                <li class="mb-2">{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if(!empty($structured['methodology']))
                                        <div class="text-secondary small">
                                            {{ $structured['methodology']['students_considered'] ?? 0 }} alumnos ·
                                            {{ $structured['methodology']['expected_student_days'] ?? 0 }} jornadas-alumno esperadas ·
                                            {{ $structured['methodology']['period_days'] ?? 0 }} días de periodo
                                        </div>
                                    @endif

                                    @if(!empty($structured['data_quality']))
                                        <div class="mt-3">
                                            <strong>Calidad de datos:</strong>
                                            {{ $structured['data_quality']['explanation'] ?? 'Sin observaciones.' }}
                                        </div>
                                    @endif

                                    @if(!empty($structured['confidence']))
                                        <div class="mt-2">
                                            <strong>Confianza:</strong>
                                            {{ $structured['confidence']['explanation'] ?? 'No especificada.' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="btn-list mt-3 pt-2 border-top">
                <button
                    type="button"
                    class="btn btn-sm btn-ghost-secondary"
                    data-ai-copy
                    data-copy-target="{{ $copyTarget }}"
                >
                    <i class="ti ti-copy me-1"></i>
                    Copiar
                </button>

                @if($message->ai_run_id)
                    @if(empty($structured['charts']))
                        <button
                            type="button"
                            class="btn btn-sm btn-ghost-secondary"
                            data-ai-generate-chart
                            data-run-id="{{ $message->ai_run_id }}"
                        >
                            <i class="ti ti-chart-bar me-1"></i>
                            Generar gráfica
                        </button>
                    @endif

                    <a
                        href="{{ route('admin.ai.runs.print', $message->ai_run_id) }}"
                        target="_blank"
                        rel="noopener"
                        class="btn btn-sm btn-ghost-secondary"
                    >
                        <i class="ti ti-printer me-1"></i>
                        Imprimir
                    </a>

                    <a
                        href="{{ route('admin.ai.runs.pdf', $message->ai_run_id) }}"
                        class="btn btn-sm btn-ghost-secondary"
                    >
                        <i class="ti ti-file-type-pdf me-1"></i>
                        PDF
                    </a>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="alert alert-warning mb-4">
        <i class="ti ti-alert-circle me-2"></i>
        {{ $message->content }}
    </div>
@endif
