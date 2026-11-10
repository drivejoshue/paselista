
<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <title>Reporte individual de calificaciones</title>

    <style>
        @include('academic.pdf.partials.styles')
    </style>
</head>

<body>

    {{-- ENCABEZADO INSTITUCIONAL --}}
    @include('academic.pdf.partials.header')

    {{-- TÍTULO DEL REPORTE --}}
    <h1>Reporte individual de calificaciones</h1>

    <p class="muted">
        Resultados académicos publicados y autorizados
        para consulta por la institución.
    </p>

    {{-- INFORMACIÓN DEL ALUMNO --}}
    <table class="identity">
        <tbody>
            <tr>
                <td style="width:55%;">
                    <span class="label">Alumno</span>
                    <span class="value">
                        {{ $student }}
                    </span>
                </td>

                <td>
                    <span class="label">Matrícula</span>
                    <span class="value">
                        {{ $student_code }}
                    </span>
                </td>
            </tr>

            <tr>
                <td>
                    <span class="label">Grupo</span>
                    <span class="value">
                        {{ $group ?? 'No disponible' }}
                    </span>
                </td>

                <td>
                    <span class="label">Ciclo escolar</span>
                    <span class="value">
                        {{ $cycle ?? 'No disponible' }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- CALIFICACIONES PUBLICADAS --}}
    @forelse($periods as $period)

        <div class="page-section">

            <h2>
                {{ $period['name'] }}
            </h2>

            <table class="grid-table">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Calificación</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($period['grades'] as $grade)

                        <tr>
                            <td>
                                {{ $grade['subject'] }}
                            </td>

                            <td class="{{
                                is_numeric($grade['value']) &&
                                (float) $grade['value'] < 6
                                    ? 'low'
                                    : 'grade'
                            }}">
                                {{ $grade['value'] }}
                            </td>
                        </tr>

                    @endforeach
                </tbody>
            </table>

            {{-- PROMEDIO DEL PERIODO --}}
            @if($period['average'] !== null)

                <div class="average">
                    Promedio informativo de materias
                    publicadas:
                    <strong>
                        {{ $period['average'] }}
                    </strong>
                </div>

            @else

                <p class="muted">
                    Promedio no disponible
                    para este periodo.
                </p>

            @endif

        </div>

    @empty

        <div class="notice">
            No hay calificaciones publicadas
            para este alumno en el ciclo escolar activo.
        </div>

    @endforelse

    {{-- LEYENDA INSTITUCIONAL --}}
    <div class="notice">
        <strong>AVISO IMPORTANTE</strong>

        <p>
            Este reporte contiene exclusivamente las
            calificaciones publicadas por la Dirección
            de la institución al momento de su emisión.
        </p>

        <p>
            Documento de carácter informativo.
            No constituye una boleta oficial certificada,
            ni sustituye documentos emitidos por la
            autoridad educativa competente.
        </p>
    </div>

    {{-- PIE DEL DOCUMENTO: SIEMPRE DESPUÉS DEL CONTENIDO --}}
    @include('academic.pdf.partials.footer')

</body>
</html>
