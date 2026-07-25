<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <title>{{ $run->conversation?->title ?? 'PaseLista IA' }}</title>

    <style>
        @page { margin: 30px 34px 48px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }
        .print-toolbar {
            margin-bottom: 18px;
            padding: 10px;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            text-align: right;
        }
        .print-toolbar button {
            padding: 8px 12px;
            border: 0;
            border-radius: 5px;
            background: {{ $primaryColor }};
            color: #fff;
            cursor: pointer;
        }
        .header {
            width: 100%;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 3px solid {{ $primaryColor }};
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 58px; height: 58px; object-fit: contain; }
        .brand {
            color: {{ $secondaryColor }};
            font-size: 18px;
            font-weight: 700;
        }
        .subtitle {
            color: {{ $primaryColor }};
            font-size: 12px;
            font-weight: 700;
        }
        .meta { margin-top: 4px; color: #64748b; font-size: 8px; }
        .question {
            margin: 0 0 14px;
            padding: 12px;
            border: 1px solid #dbe3ef;
            border-radius: 6px;
            background: #f8fafc;
        }
        .question-label {
            color: #64748b;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .question-text { margin-top: 4px; font-size: 11px; font-weight: 700; }
        .section { margin-top: 16px; page-break-inside: avoid; }
        .section-title {
            margin: 0 0 7px;
            color: {{ $secondaryColor }};
            font-size: 12px;
            font-weight: 700;
        }
        .answer { font-size: 10px; white-space: pre-line; }
        .facts {
            width: 100%;
            margin-top: 12px;
            border-collapse: separate;
            border-spacing: 5px;
        }
        .fact {
            width: 25%;
            padding: 8px;
            border: 1px solid #dbe3ef;
            border-radius: 5px;
            vertical-align: top;
        }
        .fact-label { color: #64748b; font-size: 7px; }
        .fact-value {
            margin-top: 3px;
            color: {{ $secondaryColor }};
            font-size: 13px;
            font-weight: 700;
        }
        .fact-detail { margin-top: 3px; color: #64748b; font-size: 7px; }
        ul { margin: 0; padding-left: 18px; }
        li { margin-bottom: 5px; }
        .method {
            padding: 10px;
            border-left: 3px solid {{ $primaryColor }};
            background: #f8fafc;
        }
        .chart-block {
            margin: 0 0 16px;
            page-break-inside: avoid;
        }
        .chart-heading {
            margin-bottom: 5px;
            color: {{ $secondaryColor }};
            font-size: 10px;
            font-weight: 700;
        }
        .chart-description {
            margin-bottom: 7px;
            color: #64748b;
            font-size: 7px;
        }
        .chart-image {
            display: block;
            width: 100%;
            max-height: 330px;
            margin: 0 auto 8px;
        }
        .chart-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
            font-size: 7px;
        }
        .chart-table th,
        .chart-table td {
            padding: 3px 4px;
            border: 1px solid #dbe3ef;
            text-align: right;
        }
        .chart-table th:first-child,
        .chart-table td:first-child {
            text-align: left;
        }
        .chart-table th {
            color: {{ $secondaryColor }};
            background: #eef3f8;
            font-weight: 700;
        }
        .footer {
            position: fixed;
            right: 0;
            bottom: -30px;
            left: 0;
            padding-top: 8px;
            border-top: 1px solid #dbe3ef;
            color: #64748b;
            font-size: 7px;
            text-align: center;
        }
        @media print { .print-toolbar { display: none; } }
    </style>
</head>
<body>
    @if($printMode)
        <div class="print-toolbar">
            <button type="button" onclick="window.print()">
                Imprimir documento
            </button>
        </div>
    @endif

    <header class="header">
        <table class="header-table">
            <tr>
                <td style="width: 70px;">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="" class="logo">
                    @else
                        <div style="width:58px;height:58px;border-radius:10px;background:{{ $primaryColor }};color:#fff;font-size:24px;font-weight:700;line-height:58px;text-align:center;">
                            SP
                        </div>
                    @endif
                </td>
                <td>
                    <div class="brand">{{ $school->name }}</div>
                    <div class="subtitle">Informe de PaseLista IA</div>
                    <div class="meta">
                        Periodo:
                        {{ \Illuminate\Support\Carbon::parse($run->period_from)->format('d/m/Y') }}
                        a
                        {{ \Illuminate\Support\Carbon::parse($run->period_to)->format('d/m/Y') }}
                        · Generado por: {{ $run->user?->name ?? 'Usuario' }}
                        · Folio IA: {{ $run->id }}
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <section class="question">
        <div class="question-label">Consulta</div>
        <div class="question-text">{{ $run->question }}</div>
    </section>

    <section class="section">
        <h2 class="section-title">Resumen ejecutivo</h2>
        <div class="answer">{{ $result['answer'] ?? 'Sin respuesta disponible.' }}</div>
    </section>

    @if(!empty($result['facts']))
        <table class="facts">
            @foreach(collect($result['facts'])->chunk(4) as $chunk)
                <tr>
                    @foreach($chunk as $fact)
                        <td class="fact">
                            <div class="fact-label">{{ $fact['label'] ?? 'Indicador' }}</div>
                            <div class="fact-value">{{ $fact['value'] ?? '—' }}</div>
                            @if(!empty($fact['detail']))
                                <div class="fact-detail">{{ $fact['detail'] }}</div>
                            @endif
                        </td>
                    @endforeach
                    @for($i = $chunk->count(); $i < 4; $i++)
                        <td class="fact"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endif

    @if(!empty($result['charts']))
        <section class="section">
            <h2 class="section-title">
                Visualizaciones
            </h2>

            @foreach(
                $result['charts']
                as $chartIndex => $chart
            )
                @php
                    $chartImage =
                        $chartImages[
                            $chartIndex
                        ]
                        ?? null;
                @endphp

                <div class="chart-block">
                    <div class="chart-heading">
                        {{ $chart['title'] ?? 'Gráfica' }}
                    </div>

                    @if(!empty($chart['description']))
                        <div class="chart-description">
                            {{ $chart['description'] }}
                        </div>
                    @endif

                    @if($chartImage)
                        <img
                            src="{{ $chartImage }}"
                            alt="{{ $chart['title'] ?? 'Gráfica' }}"
                            class="chart-image"
                        >
                    @endif

                    <table class="chart-table">
                        <thead>
                            <tr>
                                <th>
                                    Categoría
                                </th>

                                @foreach(
                                    $chart['series']
                                    ?? []
                                    as $series
                                )
                                    <th>
                                        {{
                                            $series['label']
                                            ?? $series['key']
                                        }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach(
                                $chart['data']
                                ?? []
                                as $row
                            )
                                <tr>
                                    <td>
                                        {{
                                            $row[
                                                $chart['x_key']
                                            ]
                                            ?? '—'
                                        }}
                                    </td>

                                    @foreach(
                                        $chart['series']
                                        ?? []
                                        as $series
                                    )
                                        <td>
                                            {{
                                                $row[
                                                    $series['key']
                                                ]
                                                ?? 0
                                            }}{{
                                                $series['suffix']
                                                ?? ''
                                            }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </section>
    @endif

    @foreach([
        'patterns' => 'Patrones',
        'comparisons' => 'Comparaciones',
        'findings' => 'Hallazgos',
        'recommendations' => 'Recomendaciones administrativas',
        'warnings' => 'Advertencias',
        'analysis_basis' => 'Base verificable',
    ] as $field => $title)
        @if(!empty($result[$field]))
            <section class="section">
                <h2 class="section-title">{{ $title }}</h2>
                <ul>
                    @foreach($result[$field] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach

    @if(!empty($result['methodology']) || !empty($result['data_quality']) || !empty($result['confidence']))
        <section class="section method">
            <h2 class="section-title">Metodología y calidad</h2>

            @if(!empty($result['methodology']))
                <div>
                    Alumnos considerados:
                    <strong>{{ $result['methodology']['students_considered'] ?? 0 }}</strong>
                    · Jornadas-alumno esperadas:
                    <strong>{{ $result['methodology']['expected_student_days'] ?? 0 }}</strong>
                    · Días del periodo:
                    <strong>{{ $result['methodology']['period_days'] ?? 0 }}</strong>
                </div>
            @endif

            @if(!empty($result['data_quality']))
                <div style="margin-top: 6px;">
                    <strong>Calidad de datos:</strong>
                    {{ $result['data_quality']['explanation'] ?? 'Sin observaciones.' }}
                </div>
            @endif

            @if(!empty($result['confidence']))
                <div style="margin-top: 6px;">
                    <strong>Confianza del análisis:</strong>
                    {{ $result['confidence']['explanation'] ?? 'No especificada.' }}
                </div>
            @endif
        </section>
    @endif

    <footer class="footer">
        Generado por PaseLista IA · Documento de apoyo administrativo ·
        {{ $generatedAt->format('d/m/Y H:i') }} · Folio {{ $run->id }}
    </footer>

    @unless($printMode)
        <script type="text/php">
            if (isset($pdf)) {
                $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                $pdf->page_text(
                    500,
                    760,
                    "Página {PAGE_NUM} de {PAGE_COUNT}",
                    $font,
                    7,
                    array(0.39, 0.45, 0.55)
                );
            }
        </script>
    @endunless
</body>
</html>
