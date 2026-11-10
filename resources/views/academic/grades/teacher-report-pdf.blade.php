<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de calificaciones</title>

    <style>
        @page {
            margin: 30px 32px 58px;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #172033;
            font-size: 9px;
            margin: 0;
        }

        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .school {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }

        .school-meta {
            font-size: 8px;
            color: #64748b;
            margin-top: 5px;
            line-height: 1.6;
        }

        .report-title {
            font-size: 13px;
            color: #1d4ed8;
            margin: 15px 0 6px;
        }

        .metadata {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }

        .metadata td {
            width: 50%;
            padding: 5px 7px;
            border: 1px solid #dbe3ef;
            font-size: 8px;
        }

        .label {
            color: #64748b;
            font-size: 7px;
        }

        .status {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            margin-bottom: 14px;
        }

        .status strong {
            color: #0f172a;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .summary td {
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            text-align: center;
            padding: 9px;
            width: 25%;
        }

        .summary .number {
            font-size: 15px;
            font-weight: bold;
            margin-top: 3px;
        }

        table.grades {
            width: 100%;
            border-collapse: collapse;
        }

        table.grades thead {
            display: table-header-group;
        }

        table.grades th {
            background: #1e3a5f;
            color: white;
            font-size: 8px;
            text-align: left;
            padding: 9px;
        }

        table.grades td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 9px;
            font-size: 8px;
        }

        table.grades tr:nth-child(even) {
            background: #f8fafc;
        }

        .grade {
            font-weight: bold;
            text-align: center;
        }

        .low {
            color: #dc2626;
        }

        .pending {
            color: #b45309;
        }

        .note {
            margin-top: 16px;
            font-size: 8px;
            color: #64748b;
            line-height: 1.6;
        }

        .footer {
            position: fixed;
            bottom: -38px;
            left: 0;
            right: 0;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            text-align: center;
            color: #64748b;
            font-size: 7px;
        }
    </style>
</head>

<body>
    @php
        $statusLabel = match ($book->status) {
            'submitted' => 'ENVIADO A DIRECCIÓN',
            'published' => 'PUBLICADO',
            default => 'BORRADOR',
        };
    @endphp

    <div class="header">
        <h1 class="school">{{ $school->name }}</h1>

        <div class="school-meta">
            @if($school->legal_name)
                {{ $school->legal_name }}
                <br>
            @endif

            @if($school->address)
                {{ $school->address }}
                <br>
            @endif

            @if($school->contact_phone)
                Tel. {{ $school->contact_phone }}
            @endif

            @if($school->contact_email)
                · {{ $school->contact_email }}
            @endif
        </div>

        <h2 class="report-title">
            Reporte de captura de calificaciones
        </h2>
    </div>

    <table class="metadata">
        <tr>
            <td>
                <div class="label">CICLO ESCOLAR</div>
                <strong>{{ $cycle->name }}</strong>
            </td>
            <td>
                <div class="label">GRUPO</div>
                <strong>{{ $assignment->group_name }}</strong>
            </td>
        </tr>

        <tr>
            <td>
                <div class="label">MATERIA</div>
                <strong>{{ $assignment->subject_name }}</strong>
            </td>
            <td>
                <div class="label">PERIODO</div>
                <strong>{{ $period->name }}</strong>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <div class="label">DOCENTE RESPONSABLE</div>
                <strong>{{ $teacherName }}</strong>
            </td>
        </tr>
    </table>

    <div class="status">
        <strong>ESTADO: {{ $statusLabel }}</strong>

        @if($book->status === 'draft')
            <div>
                Captura en proceso. Documento de trabajo
                sin validación administrativa.
            </div>
        @elseif($book->status === 'submitted')
            <div>
                Captura enviada. Pendiente de validación
                y publicación por Dirección.
            </div>
        @else
            <div>
                Calificaciones publicadas por Dirección.
            </div>
        @endif
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">ALUMNOS</div>
                <div class="number">{{ $total }}</div>
            </td>
            <td>
                <div class="label">CAPTURADAS</div>
                <div class="number">{{ $recorded }}</div>
            </td>
            <td>
                <div class="label">PENDIENTES</div>
                <div class="number">{{ $pending }}</div>
            </td>
            <td>
                <div class="label">MENORES A 6</div>
                <div class="number" style="color:#dc2626">
                    {{ $low }}
                </div>
            </td>
        </tr>
    </table>

    <table class="grades">
        <thead>
            <tr>
                <th style="width:7%">#</th>
                <th style="width:25%">Matrícula</th>
                <th>Alumno</th>
                <th style="width:17%;text-align:center">
                    Calificación
                </th>
            </tr>
        </thead>

        <tbody>
            @foreach($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->student_code }}</td>
                    <td>{{ $row->name }}</td>

                    <td class="grade">
                        @if($row->numeric_value === null)
                            <span class="pending">PENDIENTE</span>
                        @else
                            <span class="{{ (float)$row->numeric_value < 6 ? 'low' : '' }}">
                                {{ number_format(
                                    (float) $row->numeric_value,
                                    1,
                                    '.',
                                    ''
                                ) }}
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="note">
        Escala numérica: 0.0 a 10.0.
        Las calificaciones menores a 6.0 se muestran en rojo.
        Las celdas pendientes no representan calificación cero.
        Este reporte corresponde al estado de la captura
        al momento de su generación.
    </div>

    <div class="footer">
        Generado con SchoolCore Academic · Plataforma SchoolCore
        <br>
        {{ $generatedAt }}
        · Libro académico #{{ $book->id }}
        · {{ $statusLabel }}
    </div>
</body>
</html>
