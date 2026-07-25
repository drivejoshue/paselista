<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,
                 initial-scale=1,
                 viewport-fit=cover"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield(
            'title',
            'Pantalla en vivo'
        )
        · PaseLista
    </title>

    @include(
        'partials.theme-bootstrap'
    )

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <style>
        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            overflow-x: hidden;
        }

        html[data-bs-theme="dark"],
        html[data-bs-theme="dark"] body {
            background: #0b1220;
        }

        html[data-bs-theme="light"],
        html[data-bs-theme="light"] body {
            background: #eef3f8;
        }

        /*
        |--------------------------------------------------------------------------
        | Botón flotante de tema
        |--------------------------------------------------------------------------
        */

        .sp-live-theme-control {
            position: fixed;
            right: 14px;
            bottom: 14px;
            z-index: 2140;

            display: flex;
            align-items: center;
            justify-content: center;

            width: 44px;
            height: 44px;

            border:
                1px solid rgba(
                    148,
                    163,
                    184,
                    .25
                );

            border-radius: 12px;

            box-shadow:
                0 10px 30px rgba(
                    0,
                    0,
                    0,
                    .18
                );

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        html[data-bs-theme="dark"]
        .sp-live-theme-control {
            background:
                rgba(
                    17,
                    28,
                    46,
                    .92
                );
        }

        html[data-bs-theme="light"]
        .sp-live-theme-control {
            background:
                rgba(
                    255,
                    255,
                    255,
                    .94
                );
        }

        .sp-live-theme-control
        .sp-theme-toggle {
            width: 100%;
            height: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 0;
            border-radius: inherit;

            color: inherit;
            cursor: pointer;
        }

        .sp-live-theme-control
        .sp-theme-toggle:hover {
            background:
                rgba(
                    59,
                    130,
                    246,
                    .12
                );
        }

        .sp-theme-toggle-icons {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .sp-theme-icon {
            font-size: 1.35rem;
        }

        html[data-bs-theme="light"]
        .sp-theme-icon-sun {
            display: none;
        }

        html[data-bs-theme="dark"]
        .sp-theme-icon-moon {
            display: none;
        }
    </style>

    {{--
        Aquí entra el CSS de la vista Live.
    --}}
    @stack('styles')

    <style>
        /*
        |--------------------------------------------------------------------------
        | Paleta clara para Pantalla en vivo
        |--------------------------------------------------------------------------
        |
        | Se coloca después del stack para sobrescribir las variables oscuras
        | declaradas dentro de direction-live.blade.php.
        */

        html[data-bs-theme="light"] {
            --dl-bg: #eef3f8;
            --dl-panel: #ffffff;
            --dl-panel-2: #f8fafc;
            --dl-border: rgba(
                100,
                116,
                139,
                .18
            );
            --dl-text: #0f172a;
            --dl-muted: #64748b;
            --dl-primary: #2563eb;
            --dl-success: #16a34a;
            --dl-warning: #d97706;
            --dl-danger: #dc2626;
            --dl-cyan: #0891b2;
            --dl-purple: #7c3aed;
        }

        html[data-bs-theme="light"]
        .dl-shell {
            color: var(--dl-text);

            background:
                radial-gradient(
                    circle at 90% 0%,
                    rgba(
                        37,
                        99,
                        235,
                        .10
                    ),
                    transparent 32%
                ),
                var(--dl-bg);
        }

        html[data-bs-theme="light"]
        .dl-panel {
            background:
                linear-gradient(
                    180deg,
                    #ffffff,
                    #f8fafc
                );

            border-color:
                var(--dl-border);

            box-shadow:
                0 8px 25px rgba(
                    15,
                    23,
                    42,
                    .07
                );
        }

        html[data-bs-theme="light"]
        .dl-school-name,
        html[data-bs-theme="light"]
        .dl-clock-time,
        html[data-bs-theme="light"]
        .dl-section-title,
        html[data-bs-theme="light"]
        .dl-group-name,
        html[data-bs-theme="light"]
        .dl-person,
        html[data-bs-theme="light"]
        .dl-event-time,
        html[data-bs-theme="light"]
        .dl-kpi-value {
            color: var(--dl-text);
        }

        html[data-bs-theme="light"]
        .dl-kicker,
        html[data-bs-theme="light"]
        .dl-school-subtitle,
        html[data-bs-theme="light"]
        .dl-clock-date,
        html[data-bs-theme="light"]
        .dl-kpi-detail,
        html[data-bs-theme="light"]
        .dl-section-subtitle,
        html[data-bs-theme="light"]
        .dl-section-count,
        html[data-bs-theme="light"]
        .dl-group-meta,
        html[data-bs-theme="light"]
        .dl-activity-meta,
        html[data-bs-theme="light"]
        .dl-empty {
            color: var(--dl-muted);
        }

        html[data-bs-theme="light"]
        .dl-status {
            background:
                rgba(
                    241,
                    245,
                    249,
                    .92
                );

            border-color:
                var(--dl-border);

            color: #334155;
        }

        html[data-bs-theme="light"]
        .dl-button {
            background:
                rgba(
                    255,
                    255,
                    255,
                    .90
                );

            border-color:
                var(--dl-border);

            color: #334155;
        }

        html[data-bs-theme="light"]
        .dl-button:hover {
            background:
                rgba(
                    37,
                    99,
                    235,
                    .10
                );

            border-color:
                rgba(
                    37,
                    99,
                    235,
                    .30
                );

            color: #1d4ed8;
        }

        html[data-bs-theme="light"]
        .dl-field .form-select {
            background-color: #ffffff;
            border-color:
                var(--dl-border);
            color: #0f172a;
        }

        html[data-bs-theme="light"]
        .dl-mini {
            background:
                rgba(
                    241,
                    245,
                    249,
                    .90
                );
        }

        html[data-bs-theme="light"]
        .dl-kpi::after {
            background:
                rgba(
                    15,
                    23,
                    42,
                    .025
                );
        }

        html[data-bs-theme="light"]
        .dl-group-row,
        html[data-bs-theme="light"]
        .dl-activity-row {
            border-bottom-color:
                rgba(
                    100,
                    116,
                    139,
                    .15
                );
        }

        html[data-bs-theme="light"]
        .dl-avatar-fallback,
        html[data-bs-theme="light"]
        .dl-logo-fallback {
            background:
                rgba(
                    37,
                    99,
                    235,
                    .11
                );

            color: #1d4ed8;
        }

        html[data-bs-theme="light"]
        .dl-event-ok {
            background:
                rgba(
                    22,
                    163,
                    74,
                    .11
                );

            color: #166534;
        }

        html[data-bs-theme="light"]
        .dl-event-warn {
            background:
                rgba(
                    217,
                    119,
                    6,
                    .12
                );

            color: #92400e;
        }

        html[data-bs-theme="light"]
        .dl-event-error {
            background:
                rgba(
                    220,
                    38,
                    38,
                    .11
                );

            color: #991b1b;
        }

        html[data-bs-theme="light"]
        .dl-alert {
            background:
                rgba(
                    245,
                    158,
                    11,
                    .12
                );

            border-color:
                rgba(
                    217,
                    119,
                    6,
                    .25
                );

            color: #92400e;
        }
    </style>
</head>

<body>
    @yield('content')

    <div
        class="sp-live-theme-control d-print-none"
    >
        @include(
            'partials.theme-toggle'
        )
    </div>

    @include(
        'layouts.partials.theme-controller'
    )

    @stack('scripts')
</body>
</html>