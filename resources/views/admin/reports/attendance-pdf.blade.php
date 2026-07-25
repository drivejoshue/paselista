<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <title>
        Asistencia diaria
    </title>

    <style>
        @page {
            margin: 18px 20px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #172033;
            font-family:
                DejaVu Sans,
                sans-serif;
            font-size: 8px;
        }

        .header {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
        }

        .school-name {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
            font-weight: 700;
        }

        .report-title {
            margin: 3px 0 0;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .meta {
            margin-top: 4px;
            color: #64748b;
            font-size: 7.5px;
        }

        .cycle {
            margin-top: 3px;
            color: #334155;
            font-size: 7.5px;
        }

        .summary {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: separate;
            border-spacing: 4px 0;
        }

        .summary td {
            width: 14.28%;
            padding: 6px;
            border: 1px solid #dbe3ef;
            border-radius: 4px;
            background: #f8fafc;
            text-align: center;
        }

        .summary-label {
            color: #64748b;
            font-size: 6.5px;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 2px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .main-table th {
            padding: 5px 3px;
            border: 1px solid #cbd5e1;
            background: #1e3a5f;
            color: #ffffff;
            font-size: 6.4px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }

        .main-table td {
            padding: 4px 3px;
            border: 1px solid #dbe3ef;
            font-size: 6.5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .main-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .student {
            font-weight: 700;
        }

        .muted {
            color: #64748b;
            font-size: 5.8px;
        }

        .status {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            background: #e2e8f0;
            color: #334155;
            font-size: 5.7px;
            font-weight: 700;
        }

        .status-on_time {
            background: #dcfce7;
            color: #166534;
        }

        .status-late,
        .status-very_late {
            background: #fef3c7;
            color: #92400e;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-pending {
            background: #dbeafe;
            color: #1e40af;
        }

        .footer {
            margin-top: 8px;
            color: #64748b;
            font-size: 6.5px;
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .empty {
            padding: 22px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $formatTime = function ($value) {
            if (! $value) {
                return '—';
            }

            return \Illuminate\Support\Carbon::parse(
                $value
            )->format('H:i');
        };

        $humanize = function ($value) {
            if (! $value) {
                return '—';
            }

            return str($value)
                ->replace('_', ' ')
                ->title();
        };
    @endphp

    <div class="header">
        <h1 class="school-name">
            {{ $school->name ?? 'PaseLista' }}
        </h1>

        <div class="report-title">
            Reporte diario de asistencia
        </div>

        <div class="meta">
            {{ $filterDescription }}
        </div>

        @if($activeCycle)
            <div class="cycle">
                Ciclo:
                <strong>
                    {{ $activeCycle->name }}
                </strong>

                ·

                {{ \Illuminate\Support\Carbon::parse(
                    $activeCycle->starts_on
                )->format('d/m/Y') }}

                al

                {{ \Illuminate\Support\Carbon::parse(
                    $activeCycle->ends_on
                )->format('d/m/Y') }}
            </div>
        @endif
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">
                    Considerados
                </div>

                <div class="summary-value">
                    {{ $summary['total'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Presentes
                </div>

                <div class="summary-value">
                    {{ $summary['present'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Puntuales
                </div>

                <div class="summary-value">
                    {{ $summary['on_time'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Retardos
                </div>

                <div class="summary-value">
                    {{
                        $summary['late']
                        + $summary['very_late']
                    }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Ausentes
                </div>

                <div class="summary-value">
                    {{ $summary['absent'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Con salida
                </div>

                <div class="summary-value">
                    {{ $summary['exited'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Asistencia
                </div>

                <div class="summary-value">
                    {{
                        number_format(
                            $summary['attendance_rate'],
                            1
                        )
                    }}%
                </div>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 8%;">
                    Alumno
                </th>

                <th style="width: 8%;">
                    Plantel / grupo
                </th>

                <th style="width: 6%;">
                    Estado
                </th>

                <th style="width: 5%;">
                    Entrada
                </th>

                <th style="width: 10%;">
                    Tutor que entregó
                </th>

                <th style="width: 8%;">
                    Origen entrada
                </th>

                <th style="width: 5%;">
                    Salida
                </th>

                <th style="width: 10%;">
                    Tutor que recogió
                </th>

                <th style="width: 8%;">
                    Origen salida
                </th>

                <th style="width: 12%;">
                    Operador / dispositivo
                </th>

                <th style="width: 12%;">
                    Observaciones
                </th>
            </tr>
        </thead>

        <tbody>
            @forelse($rows as $row)
                @php
                    $entryObservation =
                        $row->entry_notes
                        ?: $row->entry_reason;

                    $exitObservation =
                        $row->exit_notes
                        ?: $row->exit_reason;

                    $entrySource =
                        $sourceLabels[
                            $row->entry_source
                        ]
                        ?? $humanize(
                            $row->entry_source
                        );

                    $entryReader =
                        $readerLabels[
                            $row->entry_reader_type
                        ]
                        ?? $humanize(
                            $row->entry_reader_type
                        );

                    $exitSource =
                        $sourceLabels[
                            $row->exit_source
                        ]
                        ?? $humanize(
                            $row->exit_source
                        );

                    $exitReader =
                        $readerLabels[
                            $row->exit_reader_type
                        ]
                        ?? $humanize(
                            $row->exit_reader_type
                        );
                @endphp

                <tr>
                    <td>
                        <div class="student">
                            {{ $row->first_name }}
                            {{ $row->last_name }}
                        </div>

                        <div class="muted">
                            {{ $row->student_code }}
                        </div>
                    </td>

                    <td>
                        <strong>
                            {{ $row->group_name }}
                        </strong>

                        <div class="muted">
                            {{ $row->level_name ?? 'Sin nivel' }}
                            ·
                            {{ $row->campus_name }}
                        </div>
                    </td>

                    <td class="center">
                        <span
                            class="status status-{{ $row->final_status }}"
                        >
                            {{
                                $statusLabels[
                                    $row->final_status
                                ]
                                ?? $humanize(
                                    $row->final_status
                                )
                            }}
                        </span>

                        @if($row->minutes_late > 0)
                            <div class="muted">
                                {{ $row->minutes_late }}
                                min tarde
                            </div>
                        @endif

                        @if($row->is_early_exit)
                            <div class="muted">
                                Salida anticipada
                            </div>
                        @endif
                    </td>

                    <td class="center nowrap">
                        {{ $formatTime($row->entry_at) }}

                        @if($row->scheduled_entry_time)
                            <div class="muted">
                                H:
                                {{
                                    $formatTime(
                                        $row->scheduled_entry_time
                                    )
                                }}
                            </div>
                        @endif
                    </td>

                    <td>
                        {{
                            $row->entry_guardian_name
                            ?? (
                                $row->entry_at
                                    ? 'Sin tutor asociado'
                                    : '—'
                            )
                        }}

                        @if($row->entry_performed_for)
                            <div class="muted">
                                Para:
                                {{
                                    $humanize(
                                        $row->entry_performed_for
                                    )
                                }}
                            </div>
                        @endif
                    </td>

                    <td>
                        @if($row->entry_at)
                            {{ $entrySource }}

                            <div class="muted">
                                {{ $entryReader }}
                            </div>
                        @else
                            —
                        @endif
                    </td>

                    <td class="center nowrap">
                        {{ $formatTime($row->exit_at) }}

                        @if($row->scheduled_exit_time)
                            <div class="muted">
                                H:
                                {{
                                    $formatTime(
                                        $row->scheduled_exit_time
                                    )
                                }}
                            </div>
                        @endif
                    </td>

                    <td>
                        {{
                            $row->exit_guardian_name
                            ?? (
                                $row->exit_at
                                    ? 'Sin tutor asociado'
                                    : '—'
                            )
                        }}

                        @if($row->exit_performed_for)
                            <div class="muted">
                                Para:
                                {{
                                    $humanize(
                                        $row->exit_performed_for
                                    )
                                }}
                            </div>
                        @endif
                    </td>

                    <td>
                        @if($row->exit_at)
                            {{ $exitSource }}

                            <div class="muted">
                                {{ $exitReader }}
                            </div>
                        @else
                            —
                        @endif
                    </td>

                    <td>
                        @if($row->entry_at)
                            <strong>Entrada:</strong>

                            {{
                                $row->entry_user_name
                                ?? 'Sin operador'
                            }}

                            <div class="muted">
                                {{
                                    $row->entry_device_name
                                    ?? 'Sin dispositivo'
                                }}
                            </div>
                        @endif

                        @if($row->exit_at)
                            <div style="margin-top: 3px;">
                                <strong>Salida:</strong>

                                {{
                                    $row->exit_user_name
                                    ?? 'Sin operador'
                                }}

                                <div class="muted">
                                    {{
                                        $row->exit_device_name
                                        ?? 'Sin dispositivo'
                                    }}
                                </div>
                            </div>
                        @endif

                        @if(
                            ! $row->entry_at
                            && ! $row->exit_at
                        )
                            —
                        @endif
                    </td>

                    <td>
                        @if($entryObservation)
                            <strong>Entrada:</strong>
                            {{ $entryObservation }}
                        @endif

                        @if($exitObservation)
                            <div style="margin-top: 3px;">
                                <strong>Salida:</strong>
                                {{ $exitObservation }}
                            </div>
                        @endif

                        @if(
                            ! $entryObservation
                            && ! $exitObservation
                        )
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="11"
                        class="empty"
                    >
                        No hay alumnos con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        PaseLista · Generado el
        {{ $generatedAt->format('d/m/Y H:i') }}
        · {{ $displayedTotal }} registro(s)
    </div>
</body>
</html>