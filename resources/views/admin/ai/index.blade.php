@extends('layouts.app')

@section('title', 'PaseLista IA')
@section('section-label', 'Dirección')
@section('page-title', 'PaseLista IA')

@push('styles')
    @include('admin.ai.partials.styles')
@endpush

@section('content')
    @php
        $usedCredits = (int) (
            (
                $usage->credits
                ?? $usage->runs
                ?? 0
            )
            + (
                $usage->pending_credits
                ?? 0
            )
        );

        $creditLimit = max(
            1,
            (int) $settings->monthly_query_limit
        );

        $fastUnits = max(
            1,
            (int) config(
                'schoolpass_ai.quota.fast_units',
                1
            )
        );

        $proUnits = max(
            1,
            (int) config(
                'schoolpass_ai.quota.pro_units',
                6
            )
        );

        $remainingCredits = max(
            0,
            $creditLimit - $usedCredits
        );

        $conversationGroups = $recentConversations->groupBy(function ($conversation) {
            $date = $conversation->last_message_at ?? $conversation->created_at;

            if ($date->isToday()) {
                return 'Hoy';
            }

            if ($date->isYesterday()) {
                return 'Ayer';
            }

            if ($date->greaterThanOrEqualTo(now()->subDays(7))) {
                return 'Últimos 7 días';
            }

            return 'Anteriores';
        });

        $scopeLabels = [
            'school' => 'Toda la escuela',
            'group' => 'Grupo',
            'student' => 'Alumno',
        ];
    @endphp

    @if(!$globalEnabled)
        <div class="alert alert-danger">
            <i class="ti ti-brain-off me-2"></i>
            PaseLista IA está desactivado en <code>.env</code>.
        </div>
    @elseif(!$apiKeyConfigured)
        <div class="alert alert-danger">
            <i class="ti ti-key-off me-2"></i>
            Falta configurar <code>DEEPSEEK_API_KEY</code>.
        </div>
    @elseif(!$settings->enabled)
        <div class="alert alert-warning">
            <i class="ti ti-lock me-2"></i>
            La IA está desactivada para esta escuela.
        </div>
    @endif

    @if(!$activeCycle)
        <div class="alert alert-warning">
            <i class="ti ti-calendar-off me-2"></i>
            No existe un ciclo activo. La IA necesita ciclo, inscripciones y horarios.
        </div>
    @endif

    <div
        id="sp-ai-root"
        class="card overflow-hidden"
        data-analyze-url="{{ route('admin.ai.analyze') }}"
        data-status-url-template="{{ route('admin.ai.runs.status', ['run' => '__RUN__']) }}"
        data-charts-url-template="{{ route('admin.ai.runs.charts.store', ['run' => '__RUN__']) }}"
        data-print-url-template="{{ route('admin.ai.runs.print', ['run' => '__RUN__']) }}"
        data-pdf-url-template="{{ route('admin.ai.runs.pdf', ['run' => '__RUN__']) }}"
        data-index-url="{{ route('admin.ai.index') }}"
        data-csrf="{{ csrf_token() }}"
        data-processing-run="{{ $processingRun?->id }}"
        data-processing-tier="{{ $processingRun?->requested_model_tier ?: 'fast' }}"
        data-used-credits="{{ $usedCredits }}"
        data-credit-limit="{{ $creditLimit }}"
        data-fast-units="{{ $fastUnits }}"
        data-pro-units="{{ $proUnits }}"
        data-pro-allowed="{{ $settings->allow_pro ? '1' : '0' }}"
    >
        <div class="d-flex sp-ai-layout">
            <aside class="sp-ai-history border-end d-none d-lg-flex flex-column">
                <div class="p-3 border-bottom">
                    <a href="{{ route('admin.ai.index') }}" class="btn btn-primary w-100">
                        <i class="ti ti-plus me-1"></i>
                        Nueva conversación
                    </a>

                    <div class="input-icon mt-2">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input
                            id="ai-conversation-search"
                            class="form-control form-control-sm"
                            placeholder="Buscar conversaciones"
                        >
                    </div>
                </div>

                <div class="list-group list-group-flush sp-ai-scroll">
                    @forelse($conversationGroups as $groupLabel => $conversations)
                        <div
                            class="px-3 pt-3 pb-1 text-uppercase text-secondary fw-bold"
                            style="font-size: .67rem; letter-spacing: .06em;"
                        >
                            {{ $groupLabel }}
                        </div>

                        @foreach($conversations as $conversation)
                            <div
                                class="list-group-item p-1 border-0"
                                data-conversation-item
                                data-title="{{ mb_strtolower($conversation->title) }}"
                            >
                                <div
                                    class="d-flex align-items-center rounded {{
                                        $activeConversation?->id === $conversation->id
                                            ? 'bg-primary-lt'
                                            : ''
                                    }}"
                                >
                                    <a
                                        href="{{ route('admin.ai.index', ['conversation' => $conversation->id]) }}"
                                        class="flex-fill min-w-0 p-2 text-reset text-decoration-none"
                                    >
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ti ti-message-circle text-secondary"></i>
                                            <div class="min-w-0">
                                                <div class="fw-semibold sp-ai-conversation-title">
                                                    {{ $conversation->title }}
                                                </div>
                                                <div class="text-secondary small">
                                                    {{ $scopeLabels[$conversation->scope_type] ?? 'Consulta' }}
                                                </div>
                                            </div>
                                        </div>
                                    </a>

                                    <div class="dropdown me-1">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-ghost-secondary btn-icon"
                                            data-bs-toggle="dropdown"
                                            aria-label="Opciones"
                                        >
                                            <i class="ti ti-dots"></i>
                                        </button>

                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button
                                                type="button"
                                                class="dropdown-item ai-rename-conversation"
                                                data-title="{{ $conversation->title }}"
                                                data-url="{{ route('admin.ai.conversations.rename', $conversation->id) }}"
                                            >
                                                <i class="ti ti-pencil me-2"></i>
                                                Renombrar
                                            </button>

                                            <button
                                                type="button"
                                                class="dropdown-item ai-archive-conversation"
                                                data-url="{{ route('admin.ai.conversations.archive', $conversation->id) }}"
                                            >
                                                <i class="ti ti-archive me-2"></i>
                                                Archivar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @empty
                        <div class="text-center text-secondary small py-5 px-3">
                            Todavía no hay conversaciones.
                        </div>
                    @endforelse
                </div>
            </aside>

            <section class="flex-fill min-w-0 d-flex flex-column">
                <header class="card-header border-bottom">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-icon d-lg-none"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#ai-mobile-history"
                        >
                            <i class="ti ti-menu-2"></i>
                        </button>

                        <div class="min-w-0">
                            <h3 id="ai-chat-title" class="card-title text-truncate mb-0">
                                {{ $activeConversation?->title ?? 'Nueva conversación' }}
                            </h3>
                            <div class="text-secondary small">
                                <span id="ai-scope-label">
                                    {{ $scopeLabels[$initialScopeType] ?? 'Toda la escuela' }}
                                </span>
                                ·
                                <span id="ai-period-label">
                                    {{ \Illuminate\Support\Carbon::parse($defaultFrom)->format('d/m/Y') }}
                                    –
                                    {{ \Illuminate\Support\Carbon::parse($defaultTo)->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions d-flex align-items-center gap-2">
                        <span
                            class="badge bg-blue-lt"
                            title="Créditos mensuales de PaseLista IA"
                        >
                            <i class="ti ti-sparkles me-1"></i>
                            <span id="ai-quota-used">{{ $usedCredits }}</span>
                            de
                            <span id="ai-quota-limit">{{ $creditLimit }}</span>
                            créditos
                        </span>

                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            data-bs-toggle="collapse"
                            data-bs-target="#ai-context-panel"
                        >
                            <i class="ti ti-adjustments-horizontal me-1"></i>
                            Contexto
                        </button>
                    </div>
                </header>

                <div id="ai-context-panel" class="collapse border-bottom">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label mb-1">Alcance</label>
                                <select id="ai-scope-type" class="form-select form-select-sm">
                                    @if($settings->allow_school_analysis)
                                        <option value="school" @selected($initialScopeType === 'school')>
                                            Toda la escuela
                                        </option>
                                    @endif
                                    @if($settings->allow_group_analysis)
                                        <option value="group" @selected($initialScopeType === 'group')>
                                            Grupo
                                        </option>
                                    @endif
                                    @if($settings->allow_student_analysis)
                                        <option value="student" @selected($initialScopeType === 'student')>
                                            Alumno
                                        </option>
                                    @endif
                                </select>
                            </div>

                            <div id="ai-group-field" class="col-md-5" hidden>
                                <label class="form-label mb-1">Grupo</label>
                                <select id="ai-group-select" class="form-select form-select-sm">
                                    <option value="">Selecciona un grupo</option>
                                    @foreach($groups as $group)
                                        <option
                                            value="{{ $group->id }}"
                                            @selected(
                                                $initialScopeType === 'group'
                                                && (string) $initialScopeId === (string) $group->id
                                            )
                                        >
                                            {{ $group->campus_name }} · {{ $group->level_name }} · {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="ai-student-field" class="col-md-5" hidden>
                                <label class="form-label mb-1">Alumno</label>
                                <select id="ai-student-select" class="form-select form-select-sm">
                                    <option value="">Selecciona un alumno</option>
                                    @foreach($students as $student)
                                        <option
                                            value="{{ $student->id }}"
                                            @selected(
                                                $initialScopeType === 'student'
                                                && (string) $initialScopeId === (string) $student->id
                                            )
                                        >
                                            {{ $student->last_name }}, {{ $student->first_name }}
                                            · {{ $student->student_code }} · {{ $student->group_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label mb-1">Desde</label>
                                <input
                                    type="date"
                                    id="ai-period-from"
                                    class="form-control form-control-sm"
                                    value="{{ $defaultFrom }}"
                                >
                            </div>

                            <div class="col-md-2">
                                <label class="form-label mb-1">Hasta</label>
                                <input
                                    type="date"
                                    id="ai-period-to"
                                    class="form-control form-control-sm"
                                    value="{{ $defaultTo }}"
                                    max="{{ now()->toDateString() }}"
                                >
                            </div>
                        </div>

                        <div class="form-hint mt-2">
                            También puedes escribir el nombre completo o matrícula de un alumno.
                        </div>
                    </div>
                </div>

                <div id="ai-messages" class="card-body sp-ai-scroll flex-fill">
                    @if($activeMessages->isEmpty())
                        <div id="ai-welcome" class="mx-auto py-4 text-center" style="max-width: 860px;">
                            <span class="avatar avatar-xl bg-blue-lt mb-3">
                                <i class="ti ti-brain fs-1"></i>
                            </span>
                            <h2>¿Qué necesitas revisar?</h2>
                            <p class="text-secondary">
                                Consulta asistencia, puntualidad, accesos, grupos, alumnos y dispositivos.
                            </p>

                            @include('admin.ai.partials.suggestions')
                        </div>
                    @else
                        @foreach($activeMessages as $message)
                            @include('admin.ai.partials.message', ['message' => $message])
                        @endforeach
                    @endif
                </div>

                <footer class="card-footer sp-ai-composer">
                    <form id="ai-chat-form">
                        <input
                            type="hidden"
                            id="ai-conversation-id"
                            value="{{ $activeConversation?->id }}"
                        >
                        <input
                            type="hidden"
                            id="ai-scope-id"
                            value="{{ $initialScopeId }}"
                        >

                        <div class="input-group">
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-icon"
                                data-bs-toggle="collapse"
                                data-bs-target="#ai-context-panel"
                                title="Cambiar contexto"
                            >
                                <i class="ti ti-plus"></i>
                            </button>

                            <textarea
                                id="ai-question"
                                class="form-control"
                                rows="1"
                                maxlength="{{ config('schoolpass_ai.limits.max_question_chars', 1800) }}"
                                placeholder="Pregunta a PaseLista IA…"
                                required
                            >{{ $initialQuestion }}</textarea>

                            <button
                                type="submit"
                                id="ai-send-button"
                                class="btn btn-primary btn-icon"
                                @disabled(
                                    !$globalEnabled
                                    || !$apiKeyConfigured
                                    || !$settings->enabled
                                    || !$activeCycle
                                    || $remainingCredits < $fastUnits
                                )
                            >
                                <i class="ti ti-arrow-up"></i>
                            </button>
                        </div>

                        <div
                            class="d-flex flex-wrap
                                   align-items-center
                                   justify-content-between
                                   gap-2 mt-2"
                        >
                            @if($settings->allow_pro)
                                <label
                                    class="form-check form-switch mb-0"
                                    title="El modo avanzado consume más créditos y puede tardar más."
                                >
                                    <input
                                        type="checkbox"
                                        id="ai-pro-mode"
                                        class="form-check-input"
                                        @disabled(
                                            $remainingCredits
                                            < $proUnits
                                        )
                                    >

                                    <span class="form-check-label">
                                        <span class="fw-semibold">
                                            Análisis avanzado
                                        </span>

                                        <span
                                            id="ai-pro-mode-hint"
                                            class="text-secondary small ms-1"
                                        >
                                            {{ $proUnits }} créditos · hasta 90 s
                                        </span>
                                    </span>
                                </label>
                            @else
                                <span></span>
                            @endif

                            <div class="text-secondary small">
                                PaseLista IA puede equivocarse.
                                Verifica los datos antes de tomar decisiones.
                            </div>
                        </div>
                    </form>
                </footer>
            </section>
        </div>
    </div>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="ai-mobile-history">
        <div class="offcanvas-header">
            <h3 class="offcanvas-title">Conversaciones</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body p-2">
            <a href="{{ route('admin.ai.index') }}" class="btn btn-primary w-100 mb-2">
                <i class="ti ti-plus me-1"></i>
                Nueva conversación
            </a>

            <div class="list-group list-group-flush">
                @foreach($recentConversations as $conversation)
                    <a
                        href="{{ route('admin.ai.index', ['conversation' => $conversation->id]) }}"
                        class="list-group-item list-group-item-action rounded mb-1 {{
                            $activeConversation?->id === $conversation->id
                                ? 'active'
                                : ''
                        }}"
                    >
                        <div class="fw-semibold text-truncate">
                            {{ $conversation->title }}
                        </div>
                        <div class="small opacity-75">
                            {{ $scopeLabels[$conversation->scope_type] ?? 'Consulta' }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.ai.partials.script')
@endpush
