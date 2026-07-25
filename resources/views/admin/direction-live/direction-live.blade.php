@extends('layouts.direction-live')

@section('title', 'Pantalla en vivo')

@php
    $logoUrl = $school->logo_path
        ? asset('storage/'.$school->logo_path)
        : null;
@endphp

@push('styles')
<style>
    :root {
        --dl-bg: #0b1220;
        --dl-panel: #121d2f;
        --dl-panel-2: #17253a;
        --dl-border: rgba(148, 163, 184, .15);
        --dl-text: #f8fafc;
        --dl-muted: #94a3b8;
        --dl-primary: #3b82f6;
        --dl-success: #22c55e;
        --dl-warning: #f59e0b;
        --dl-danger: #ef4444;
        --dl-cyan: #22d3ee;
    }

    .dl-shell {
        min-height: 100vh;
        padding: 10px;
        color: var(--dl-text);
        background:
            radial-gradient(circle at 90% 0%, rgba(59,130,246,.12), transparent 28%),
            var(--dl-bg);
    }

    .dl-panel {
        background: linear-gradient(180deg, var(--dl-panel-2), var(--dl-panel));
        border: 1px solid var(--dl-border);
        border-radius: 11px;
        box-shadow: 0 8px 28px rgba(0,0,0,.16);
    }

    .dl-header {
        min-height: 66px;
        padding: 9px 12px;
        display: grid;
        grid-template-columns: minmax(260px, 1fr) auto;
        align-items: center;
        gap: 12px;
    }

    .dl-brand {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dl-logo,
    .dl-logo-fallback {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 9px;
    }

    .dl-logo {
        object-fit: cover;
        background: #fff;
    }

    .dl-logo-fallback {
        display: grid;
        place-items: center;
        background: rgba(59,130,246,.18);
        color: #93c5fd;
        font-size: 1.2rem;
    }

    .dl-kicker {
        color: var(--dl-muted);
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .dl-school-name {
        margin: 1px 0 0;
        overflow: hidden;
        color: var(--dl-text);
        font-size: clamp(1rem, 1.45vw, 1.4rem);
        font-weight: 700;
        line-height: 1.1;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dl-subtitle {
        margin-top: 2px;
        color: var(--dl-muted);
        font-size: .68rem;
    }

    .dl-header-right {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .dl-clock {
        min-width: 150px;
        text-align: right;
    }

    .dl-clock-time {
        font-variant-numeric: tabular-nums;
        font-size: clamp(1.45rem, 2.2vw, 2.1rem);
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1;
    }

    .dl-clock-date {
        margin-top: 3px;
        color: var(--dl-muted);
        font-size: .66rem;
    }

    .dl-toolbar {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .dl-status,
    .dl-button {
        height: 30px;
        border: 1px solid var(--dl-border);
        border-radius: 7px;
        background: rgba(15,23,42,.55);
        color: #e2e8f0;
        font-size: .67rem;
    }

    .dl-status {
        padding: 0 9px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .dl-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--dl-warning);
    }

    .dl-status.is-online .dl-status-dot {
        background: var(--dl-success);
        box-shadow: 0 0 8px rgba(34,197,94,.8);
    }

    .dl-status.is-offline .dl-status-dot {
        background: var(--dl-danger);
    }

    .dl-button {
        padding: 0 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .dl-button:hover {
        color: #fff;
        border-color: rgba(96,165,250,.38);
        background: rgba(59,130,246,.14);
    }

    .dl-filters {
        margin-top: 8px;
        padding: 8px 9px;
    }

    .dl-filter-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(150px, 1fr)) auto;
        gap: 7px;
        align-items: end;
    }

    .dl-field label {
        display: block;
        margin: 0 0 3px;
        color: var(--dl-muted);
        font-size: .6rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .dl-field .form-select {
        min-height: 31px;
        padding-top: 4px;
        padding-bottom: 4px;
        border-color: var(--dl-border);
        background-color: rgba(15,23,42,.62);
        color: #e2e8f0;
        font-size: .7rem;
    }

    .dl-apply {
        min-width: 104px;
        height: 31px;
        border: 0;
        border-radius: 7px;
        background: var(--dl-primary);
        color: #fff;
        font-size: .7rem;
        font-weight: 700;
    }

    .dl-alert {
        display: none;
        margin-top: 8px;
        padding: 7px 10px;
        border: 1px solid rgba(245,158,11,.28);
        border-radius: 8px;
        background: rgba(245,158,11,.13);
        color: #fde68a;
        font-size: .7rem;
    }

    .dl-kpis {
        margin-top: 8px;
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 7px;
    }

    .dl-kpi {
        min-height: 82px;
        padding: 9px 10px;
        position: relative;
        overflow: hidden;
    }

    .dl-kpi::after {
        content: '';
        position: absolute;
        right: -24px;
        bottom: -38px;
        width: 82px;
        height: 82px;
        border-radius: 50%;
        background: rgba(255,255,255,.03);
    }

    .dl-kpi-head {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .dl-kpi-label {
        color: #cbd5e1;
        font-size: .65rem;
        font-weight: 700;
    }

    .dl-kpi-icon {
        font-size: 1rem;
    }

    .dl-kpi-value {
        position: relative;
        z-index: 1;
        margin-top: 6px;
        font-variant-numeric: tabular-nums;
        font-size: clamp(1.5rem, 2.2vw, 2.15rem);
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: .95;
    }

    .dl-kpi-detail {
        position: relative;
        z-index: 1;
        margin-top: 6px;
        overflow: hidden;
        color: var(--dl-muted);
        font-size: .6rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dl-main-grid {
        height: calc(100vh - 66px - 47px - 98px - 42px);
        min-height: 360px;
        margin-top: 8px;
        display: grid;
        grid-template-columns: minmax(0, 1.22fr) minmax(340px, .78fr);
        gap: 8px;
    }

    .dl-section {
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .dl-section-head {
        min-height: 46px;
        padding: 8px 11px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border-bottom: 1px solid var(--dl-border);
    }

    .dl-section-title {
        margin: 0;
        font-size: .86rem;
        font-weight: 700;
    }

    .dl-section-subtitle,
    .dl-section-count {
        color: var(--dl-muted);
        font-size: .62rem;
    }

    .dl-groups,
    .dl-activity {
        min-height: 0;
        overflow: auto;
    }

    .dl-groups {
        padding: 5px 7px;
    }

    .dl-group-row {
        min-height: 52px;
        padding: 6px 7px;
        display: grid;
        grid-template-columns: minmax(140px, 1.35fr) 54px repeat(4, 52px);
        align-items: center;
        gap: 6px;
        border-bottom: 1px solid rgba(148,163,184,.1);
    }

    .dl-group-name {
        overflow: hidden;
        font-size: .72rem;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dl-group-meta,
    .dl-activity-meta {
        overflow: hidden;
        color: var(--dl-muted);
        font-size: .57rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dl-rate {
        font-variant-numeric: tabular-nums;
        font-size: .82rem;
        font-weight: 800;
        text-align: right;
    }

    .dl-mini {
        padding: 4px 3px;
        border-radius: 6px;
        background: rgba(15,23,42,.5);
        text-align: center;
    }

    .dl-mini strong {
        display: block;
        font-size: .72rem;
        line-height: 1;
    }

    .dl-mini span {
        display: block;
        margin-top: 2px;
        color: var(--dl-muted);
        font-size: .49rem;
    }

    .dl-activity-row {
        min-height: 54px;
        padding: 7px 9px;
        display: grid;
        grid-template-columns: 34px minmax(0,1fr) 54px;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid rgba(148,163,184,.1);
    }

    .dl-avatar,
    .dl-avatar-fallback {
        width: 32px;
        height: 32px;
        border-radius: 8px;
    }

    .dl-avatar {
        object-fit: cover;
    }

    .dl-avatar-fallback {
        display: grid;
        place-items: center;
        background: rgba(59,130,246,.16);
        color: #bfdbfe;
        font-size: .7rem;
        font-weight: 800;
    }

    .dl-person {
        overflow: hidden;
        font-size: .68rem;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dl-event-badge {
        display: inline-flex;
        margin-top: 3px;
        padding: 2px 5px;
        border-radius: 999px;
        font-size: .5rem;
        font-weight: 700;
    }

    .dl-event-ok {
        background: rgba(34,197,94,.14);
        color: #bbf7d0;
    }

    .dl-event-warn {
        background: rgba(245,158,11,.15);
        color: #fde68a;
    }

    .dl-event-error {
        background: rgba(239,68,68,.15);
        color: #fecaca;
    }

    .dl-event-time {
        font-variant-numeric: tabular-nums;
        font-size: .7rem;
        font-weight: 800;
        text-align: right;
    }

    .dl-empty {
        padding: 30px 12px;
        color: var(--dl-muted);
        font-size: .7rem;
        text-align: center;
    }

    .dl-blue { color: #60a5fa; }
    .dl-green { color: var(--dl-success); }
    .dl-yellow { color: var(--dl-warning); }
    .dl-red { color: var(--dl-danger); }
    .dl-cyan { color: var(--dl-cyan); }

    @media (max-width: 1300px) {
        .dl-kpis {
            grid-template-columns: repeat(3, minmax(0,1fr));
        }

        .dl-main-grid {
            height: auto;
            min-height: 430px;
        }
    }

    @media (max-width: 900px) {
        .dl-header {
            grid-template-columns: 1fr;
        }

        .dl-header-right {
            justify-content: space-between;
        }

        .dl-clock {
            text-align: left;
        }

        .dl-filter-grid {
            grid-template-columns: 1fr 1fr;
        }

        .dl-main-grid {
            grid-template-columns: 1fr;
        }

        .dl-section {
            max-height: 480px;
        }
    }

    @media (max-width: 600px) {
        .dl-kpis {
            grid-template-columns: repeat(2, minmax(0,1fr));
        }

        .dl-filter-grid {
            grid-template-columns: 1fr;
        }

        .dl-header-right {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div
    id="direction-live-root"
    class="dl-shell"
    data-endpoint="{{ route('admin.direction-live.data') }}"
    data-timezone="{{ $school->timezone ?: config('app.timezone') }}"
>
    <header class="dl-panel dl-header">
        <div class="dl-brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $school->name }}" class="dl-logo">
            @else
                <div class="dl-logo-fallback">
                    <i class="ti ti-school"></i>
                </div>
            @endif

            <div class="min-w-0">
                <div class="dl-kicker">PaseLista · Dirección</div>
                <h1 class="dl-school-name">{{ $school->name }}</h1>
                <div class="dl-subtitle">Monitor operativo en tiempo real</div>
            </div>
        </div>

        <div class="dl-header-right">
            <div class="dl-clock">
                <div id="live-clock-time" class="dl-clock-time">--:--:--</div>
                <div id="live-clock-date" class="dl-clock-date">Cargando fecha…</div>
            </div>

            <div class="dl-toolbar">
                <div id="live-connection" class="dl-status">
                    <span class="dl-status-dot"></span>
                    <span id="live-connection-label">Conectando…</span>
                </div>

                <button type="button" id="live-refresh" class="dl-button" title="Actualizar">
                    <i class="ti ti-refresh"></i>
                </button>

                <button type="button" id="live-fullscreen" class="dl-button">
                    <i class="ti ti-maximize"></i>
                    <span class="d-none d-lg-inline">Pantalla completa</span>
                </button>

                <a href="{{ route('admin.dashboard') }}" class="dl-button">
                    <i class="ti ti-arrow-left"></i>
                    <span class="d-none d-lg-inline">Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <div id="live-cycle-alert" class="dl-alert"></div>

    <section class="dl-panel dl-filters">
        <form method="GET" action="{{ route('admin.direction-live.index') }}" class="dl-filter-grid">
            <div class="dl-field">
                <label for="campus_id">Plantel</label>
                <select id="campus_id" name="campus_id" class="form-select">
                    <option value="">Todos los planteles</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" @selected((string)$filters['campus_id'] === (string)$campus->id)>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dl-field">
                <label for="level_id">Nivel</label>
                <select id="level_id" name="level_id" class="form-select">
                    <option value="">Todos los niveles</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}" @selected((string)$filters['level_id'] === (string)$level->id)>
                            {{ $level->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dl-field">
                <label for="group_id">Grupo</label>
                <select id="group_id" name="group_id" class="form-select">
                    <option value="">Todos los grupos</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected((string)$filters['group_id'] === (string)$group->id)>
                            {{ $group->campus_name }} · {{ $group->level_name }} · {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button class="dl-apply">
                <i class="ti ti-filter me-1"></i>
                Aplicar
            </button>
        </form>
    </section>

    <section class="dl-kpis">
        @foreach([
            ['total', 'Inscritos', 'ti-users', 'dl-blue'],
            ['present', 'Presentes', 'ti-user-check', 'dl-green'],
            ['on_time', 'Puntuales', 'ti-clock-check', 'dl-green'],
            ['late_total', 'Retardos', 'ti-clock-exclamation', 'dl-yellow'],
            ['absent', 'Ausentes', 'ti-user-x', 'dl-red'],
            ['attendance_rate', 'Asistencia', 'ti-chart-donut', 'dl-cyan'],
        ] as [$key, $label, $icon, $color])
            <article class="dl-panel dl-kpi">
                <div class="dl-kpi-head">
                    <div class="dl-kpi-label">{{ $label }}</div>
                    <i class="ti {{ $icon }} dl-kpi-icon {{ $color }}"></i>
                </div>

                <div id="live-stat-{{ $key }}" class="dl-kpi-value">—</div>
                <div id="live-stat-detail-{{ $key }}" class="dl-kpi-detail">Cargando…</div>
            </article>
        @endforeach
    </section>

    <section class="dl-main-grid">
        <article class="dl-panel dl-section">
            <header class="dl-section-head">
                <div>
                    <h2 class="dl-section-title">Resumen por grupos</h2>
                    <div class="dl-section-subtitle">Presentes, retardos, ausencias y salidas</div>
                </div>
                <div id="live-group-count" class="dl-section-count">—</div>
            </header>

            <div id="live-groups" class="dl-groups">
                <div class="dl-empty">Cargando grupos…</div>
            </div>
        </article>

        <article class="dl-panel dl-section">
            <header class="dl-section-head">
                <div>
                    <h2 class="dl-section-title">Actividad reciente</h2>
                    <div class="dl-section-subtitle">Entradas, salidas e incidencias</div>
                </div>
                <div id="live-last-update" class="dl-section-count">—</div>
            </header>

            <div id="live-activity" class="dl-activity">
                <div class="dl-empty">Cargando actividad…</div>
            </div>
        </article>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('direction-live-root');

    if (!root) {
        return;
    }

    const endpoint = new URL(root.dataset.endpoint, window.location.origin);
    const query = new URLSearchParams(window.location.search);

    for (const [key, value] of query.entries()) {
        endpoint.searchParams.set(key, value);
    }

    let timezone = root.dataset.timezone;
    let loading = false;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const statusLabel = (status, decision) => {
        const labels = {
            on_time: 'Puntual',
            late: 'Retardo',
            very_late: 'Muy tarde',
            early_exit: 'Salida anticipada',
            normal_exit: 'Salida normal',
            duplicate: 'Duplicado',
            guardian_required: 'Tutor requerido',
            student_not_enrolled: 'Sin inscripción',
            cycle_not_started: 'Ciclo no iniciado',
            manual: 'Manual',
        };

        return labels[status]
            ?? (decision === 'denied'
                ? 'Denegado'
                : String(status ?? 'Evento').replaceAll('_', ' '));
    };

    const sourceLabel = (source) => {
        const labels = {
            qr: 'QR alumno',
            guardian_qr: 'QR tutor',
            manual: 'Manual',
            kiosk: 'Kiosco',
            nfc: 'NFC',
        };

        return labels[source]
            ?? String(source ?? 'Acceso').replaceAll('_', ' ');
    };

    const eventClass = (item) => {
        if (item.decision === 'denied' || item.event_status === 'guardian_required') {
            return 'dl-event-error';
        }

        if (['late', 'very_late', 'early_exit', 'duplicate'].includes(item.event_status)) {
            return 'dl-event-warn';
        }

        return 'dl-event-ok';
    };

    const setConnection = (state, label) => {
        const element = document.getElementById('live-connection');
        element.classList.remove('is-online', 'is-offline');

        if (state) {
            element.classList.add(state);
        }

        document.getElementById('live-connection-label').textContent = label;
    };

    const setStat = (key, value, detail) => {
        document.getElementById(`live-stat-${key}`).textContent = value;
        document.getElementById(`live-stat-detail-${key}`).textContent = detail;
    };

    const renderSummary = (summary) => {
        const lateTotal = Number(summary.late ?? 0) + Number(summary.very_late ?? 0);

        setStat('total', summary.total, `${summary.pending} pendientes`);
        setStat('present', summary.present, `${summary.exited} con salida`);
        setStat('on_time', summary.on_time, 'Dentro del horario');
        setStat('late_total', lateTotal, `${summary.late} retardo · ${summary.very_late} muy tarde`);
        setStat('absent', summary.absent, `${summary.no_class} sin clase`);
        setStat(
            'attendance_rate',
            `${Number(summary.attendance_rate).toFixed(1)}%`,
            `${summary.online_devices}/${summary.active_devices} dispositivos en línea`
        );
    };

    const renderGroups = (groups) => {
        const container = document.getElementById('live-groups');
        document.getElementById('live-group-count').textContent = `${groups.length} grupo(s)`;

        if (!groups.length) {
            container.innerHTML = '<div class="dl-empty">No hay grupos para mostrar.</div>';
            return;
        }

        container.innerHTML = groups.map((group) => {
            const rate = Number(group.attendance_rate ?? 0);
            const rateClass = rate >= 80
                ? 'dl-green'
                : rate >= 60
                    ? 'dl-yellow'
                    : 'dl-red';

            return `
                <div class="dl-group-row">
                    <div>
                        <div class="dl-group-name">${escapeHtml(group.name)}</div>
                        <div class="dl-group-meta">
                            ${escapeHtml(group.level)} · ${escapeHtml(group.campus)}
                        </div>
                    </div>

                    <div class="dl-rate ${rateClass}">${rate.toFixed(0)}%</div>

                    <div class="dl-mini">
                        <strong>${group.present}/${group.total}</strong>
                        <span>Presentes</span>
                    </div>

                    <div class="dl-mini">
                        <strong>${group.late + group.very_late}</strong>
                        <span>Retardos</span>
                    </div>

                    <div class="dl-mini">
                        <strong>${group.absent}</strong>
                        <span>Ausentes</span>
                    </div>

                    <div class="dl-mini">
                        <strong>${group.exited}</strong>
                        <span>Salidas</span>
                    </div>
                </div>
            `;
        }).join('');
    };

    const renderActivity = (activity) => {
        const container = document.getElementById('live-activity');

        if (!activity.length) {
            container.innerHTML = '<div class="dl-empty">No hay movimientos registrados hoy.</div>';
            return;
        }

        container.innerHTML = activity.map((item) => {
            const initial = escapeHtml(item.student_name.charAt(0).toUpperCase());

            const avatar = item.photo_url
                ? `<img src="${escapeHtml(item.photo_url)}" alt="" class="dl-avatar">`
                : `<div class="dl-avatar-fallback">${initial}</div>`;

            const guardian = item.guardian_name
                ? ` · Tutor: ${escapeHtml(item.guardian_name)}`
                : '';

            return `
                <div class="dl-activity-row">
                    ${avatar}

                    <div class="min-w-0">
                        <div class="dl-person">${escapeHtml(item.student_name)}</div>
                        <div class="dl-activity-meta">
                            ${escapeHtml(item.student_code)} · ${escapeHtml(item.group_name)}
                        </div>
                        <div class="dl-activity-meta">
                            ${escapeHtml(sourceLabel(item.source))}
                            · ${escapeHtml(item.device_name)}
                            ${guardian}
                        </div>
                        <span class="dl-event-badge ${eventClass(item)}">
                            ${escapeHtml(statusLabel(item.event_status, item.decision))}
                        </span>
                    </div>

                    <div class="dl-event-time">${escapeHtml(item.time)}</div>
                </div>
            `;
        }).join('');
    };

    const renderCycle = (cycle) => {
        const alert = document.getElementById('live-cycle-alert');
        let message = '';

        if (!cycle.exists) {
            message = 'No existe un ciclo escolar activo.';
        } else if (!cycle.inside_cycle) {
            message = 'La fecha actual está fuera de la vigencia del ciclo.';
        } else if (cycle.no_class_day) {
            message = cycle.calendar_title
                ? `Hoy está marcado como: ${cycle.calendar_title}.`
                : 'Hoy está marcado como día sin clase.';
        }

        alert.textContent = message;
        alert.style.display = message ? 'block' : 'none';
    };

    const updateClock = () => {
        const now = new Date();

        document.getElementById('live-clock-time').textContent =
            new Intl.DateTimeFormat('es-MX', {
                timeZone: timezone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            }).format(now);

        const date = new Intl.DateTimeFormat('es-MX', {
            timeZone: timezone,
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(now);

        document.getElementById('live-clock-date').textContent =
            date.charAt(0).toUpperCase() + date.slice(1);
    };

    const loadData = async () => {
        if (loading) {
            return;
        }

        loading = true;
        setConnection('', 'Actualizando…');

        try {
            const response = await fetch(endpoint.toString(), {
                headers: {
                    Accept: 'application/json',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            timezone = data.school.timezone ?? timezone;

            renderSummary(data.summary);
            renderGroups(data.groups);
            renderActivity(data.activity);
            renderCycle(data.cycle);

            document.getElementById('live-last-update').textContent =
                `Actualizado ${data.clock.time}`;

            setConnection('is-online', 'En línea · 15 s');
        } catch (error) {
            console.error('No se pudo actualizar la pantalla.', error);
            setConnection('is-offline', 'Sin conexión');
        } finally {
            loading = false;
        }
    };

    document.getElementById('live-refresh').addEventListener('click', loadData);

    document.getElementById('live-fullscreen').addEventListener('click', async () => {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen();
        } else {
            await document.exitFullscreen();
        }
    });

    updateClock();
    window.setInterval(updateClock, 1000);

    loadData();
    window.setInterval(loadData, 15000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadData();
        }
    });
})();
</script>
@endpush
