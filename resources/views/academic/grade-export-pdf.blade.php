<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">

    <style>
        @include('academic.pdf.partials.styles')
    </style>
</head>

<body>
    @include('academic.pdf.partials.header')
    @include('academic.pdf.partials.footer')

    @if($mode === 'summary')
        <h1>
            Concentrado de capturas y publicaciones
        </h1>

        <p class="muted">
            Ciclo: {{ $cycle }}
            · Registros: {{ count($rows) }}
            · Reporte interno de Dirección.
        </p>

        <table class="grid-table">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Materia</th>
                    <th>Docente(s)</th>
                    <th>Periodo</th>
                    <th>Avance</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['group'] }}</td>
                        <td>{{ $row['subject'] }}</td>
                        <td>{{ $row['teacher'] }}</td>
                        <td>{{ $row['period'] }}</td>
                        <td>{{ $row['progress'] }}</td>

                        <td>
                            {{
                                [
                                    'draft' => 'Borrador',
                                    'submitted' => 'Por validar',
                                    'published' => 'Publicada',
                                ][$row['status']]
                                ?? $row['status']
                            }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="notice">
            Uso interno administrativo.

            El listado puede incluir capturas
            aún no publicadas y no debe entregarse
            como boleta al alumnado.
        </div>

    @else
        <h1>
            Relación de calificaciones por grupo
        </h1>

        <table class="identity">
            <tr>
                <td>
                    <span class="label">Grupo</span>
                    <span class="value">
                        {{ $book->group_name }}
                    </span>
                </td>

                <td>
                    <span class="label">Materia</span>
                    <span class="value">
                        {{ $book->subject_name }}
                    </span>
                </td>
            </tr>

            <tr>
                <td>
                    <span class="label">
                        Ciclo / periodo
                    </span>

                    <span class="value">
                        {{ $cycle }}
                        · {{ $book->period_name }}
                    </span>
                </td>

                <td>
                    <span class="label">Estado</span>

                    <span class="value">
                        {{
                            [
                                'draft' => 'Borrador',
                                'submitted' => 'Por validar',
                                'published' => 'Publicada',
                            ][$book->status]
                            ?? $book->status
                        }}
                    </span>
                </td>
            </tr>
        </table>

        <p class="muted">
            Docente(s): {{ $teachers }}
            · Alumnos: {{ count($students) }}
        </p>

        <table class="grid-table">
            <thead>
                <tr>
                    <th style="width:35px;">#</th>
                    <th>Matrícula</th>
                    <th>Alumno</th>
                    <th>Calificación</th>
                </tr>
            </thead>

            <tbody>
                @foreach($students as $i => $student)
                    <tr>
                        <td>{{ $i + 1 }}</td>

                        <td>
                            {{ $student['code'] }}
                        </td>

                        <td>
                            {{ $student['name'] }}
                        </td>

                        <td>
                            {{ $student['grade'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($book->status !== 'published')
            <div class="notice">
                <strong>
                    USO INTERNO · NO PUBLICADO.
                </strong>

                Esta captura todavía no tiene
                carácter de calificación publicada.
            </div>
        @else
            <div class="notice">
                Captura publicada por Dirección.
                Documento de control escolar,
                no equivalente a una boleta oficial.
            </div>
        @endif
    @endif
</body>
</html>
