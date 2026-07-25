<?php

namespace App\Services\Ai;

use RuntimeException;

class SchoolPassAiService
{
    public function __construct(
        private readonly DeepSeekClient $client
    ) {
    }

    public function analyze(
        array $bundle,
        string $mode,
        array $history = []
    ): array {
        $deepAnalysis = $mode === 'deep';

        $systemPrompt = <<<'PROMPT'
Eres PaseLista  IA, un analista administrativo de asistencia y control de acceso escolar.

REGLAS OBLIGATORIAS:
1. Usa exclusivamente los datos JSON entregados por PaseLista .
2. No inventes alumnos, cifras, causas, fechas ni relaciones.
3. No diagnostiques salud, conducta, discapacidad, situación familiar ni riesgo psicológico.
4. No recomiendes castigos, sanciones automáticas ni decisiones definitivas.
5. Distingue hechos, patrones, comparaciones y recomendaciones administrativas.
6. Cuando los datos sean insuficientes, dilo claramente.
7. ALU-* y ALUMNO-SELECCIONADO son referencias seudonimizadas.
8. No solicites ni reveles teléfonos, correos, domicilios, fotografías, QR, contraseñas ni datos de tutores.
9. La conversación previa sirve para comprender referencias como “ese alumno” o “el periodo anterior”, pero todas las cifras deben salir del contexto actual.
10. No menciones nombres de modelos, tokens, precios ni detalles internos del proveedor.
11. Responde en español.
12. Devuelve únicamente un objeto JSON válido, sin Markdown ni texto fuera del JSON.
13. No expongas razonamiento interno paso a paso. Resume únicamente la base verificable del análisis.

FORMATO JSON OBLIGATORIO:
{
  "answer": "respuesta ejecutiva clara de 1 a 4 párrafos",
  "facts": [
    {"label":"Indicador","value":"Valor","detail":"Explicación breve"}
  ],
  "patterns": ["Patrón sustentado por los datos"],
  "comparisons": ["Comparación verificable"],
  "findings": ["Hallazgo sustentado por los datos"],
  "analysis_basis": ["Base verificable utilizada para llegar a la conclusión"],
  "recommendations": ["Acción administrativa prudente y revisable"],
  "warnings": ["Limitación o advertencia"],
  "data_quality": {
    "level": "good|fair|limited",
    "explanation": "explicación breve",
    "issues": ["Inconsistencia detectada"]
  },
  "confidence": {
    "level": "high|medium|low",
    "explanation": "explicación breve"
  },
  "evidence": [
    {"source":"PaseLista","period":"YYYY-MM-DD a YYYY-MM-DD","scope":"alcance"}
  ]
}
PROMPT;

        $userPayload = [
            'instruction' =>
                'Analiza la pregunta con el contexto estructurado. Devuelve JSON.',

            'conversation_history' => $history,

            'question' =>
                $bundle['redacted_question'],

            'context' =>
                $bundle['context'],
        ];

        $response = $this->client->analyze(
            messages: [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode(
                        $userPayload,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                    ),
                ],
            ],
            deepAnalysis: $deepAnalysis
        );

        $result = $this->normalizeResult(
            (array) $response['result'],
            $bundle['context']
        );

        $result = $this->replaceAliases(
            $result,
            $bundle['aliases']
        );

        $usage = (array) $response['usage'];

        return [
            'result' => $result,
            'model' => $response['model'],
            'thinking_enabled' =>
                $response['thinking_enabled'],
            'usage' => $usage,
            'estimated_cost_usd' =>
                $this->client->estimatedCostUsd(
                    $response['model'],
                    $usage
                ),
        ];
    }

    private function normalizeResult(
        array $result,
        array $context
    ): array {
        $answer = trim(
            (string) ($result['answer'] ?? '')
        );

        if ($answer === '') {
            throw new RuntimeException(
                'La IA no devolvió una respuesta ejecutiva.'
            );
        }

        return [
            'answer' => mb_substr(
                $answer,
                0,
                10000
            ),

            'facts' => $this->normalizeFacts(
                $result['facts'] ?? []
            ),

            'patterns' => $this->normalizeStrings(
                $result['patterns'] ?? []
            ),

            'comparisons' => $this->normalizeStrings(
                $result['comparisons'] ?? []
            ),

            'findings' => $this->normalizeStrings(
                $result['findings'] ?? []
            ),

            'analysis_basis' =>
                $this->normalizeStrings(
                    $result['analysis_basis'] ?? []
                ),

            'recommendations' =>
                $this->normalizeStrings(
                    $result['recommendations'] ?? []
                ),

            'warnings' => $this->normalizeStrings(
                $result['warnings'] ?? []
            ),

            'methodology' =>
                $this->methodologyFromContext(
                    $context
                ),

            'data_quality' =>
                $this->normalizeDataQuality(
                    $result['data_quality'] ?? []
                ),

            'confidence' =>
                $this->normalizeConfidence(
                    $result['confidence'] ?? []
                ),

            'evidence' =>
                $this->normalizeEvidence(
                    $result['evidence'] ?? [],
                    $context
                ),
        ];
    }

    private function methodologyFromContext(
        array $context
    ): array {
        return [
            'students_considered' => (int) data_get(
                $context,
                'summary.students',
                0
            ),

            'expected_student_days' => (int) data_get(
                $context,
                'summary.expected_student_days',
                0
            ),

            'closed_expected_student_days' => (int) data_get(
                $context,
                'summary.closed_expected_student_days',
                0
            ),

            'period_days' => (int) data_get(
                $context,
                'period.days_in_calendar',
                0
            ),

            'criteria' => [
                'Inscripciones vigentes',
                'Horarios activos por grupo',
                'Calendario escolar y días sin clase',
                'Asistencia diaria consolidada',
                'Registros de entrada y salida',
            ],
        ];
    }

    private function normalizeFacts(
        mixed $facts
    ): array {
        if (! is_array($facts)) {
            return [];
        }

        return collect($facts)
            ->take(20)
            ->filter(
                fn ($item): bool => is_array($item)
            )
            ->map(
                fn (array $item): array => [
                    'label' => mb_substr(
                        trim(
                            (string) (
                                $item['label']
                                ?? 'Indicador'
                            )
                        ),
                        0,
                        160
                    ),
                    'value' => mb_substr(
                        trim(
                            (string) (
                                $item['value']
                                ?? '—'
                            )
                        ),
                        0,
                        160
                    ),
                    'detail' => mb_substr(
                        trim(
                            (string) (
                                $item['detail']
                                ?? ''
                            )
                        ),
                        0,
                        700
                    ),
                ]
            )
            ->values()
            ->all();
    }

    private function normalizeDataQuality(
        mixed $value
    ): array {
        $value = is_array($value)
            ? $value
            : [];

        $level = (string) (
            $value['level']
            ?? 'fair'
        );

        if (! in_array(
            $level,
            ['good', 'fair', 'limited'],
            true
        )) {
            $level = 'fair';
        }

        return [
            'level' => $level,
            'explanation' => mb_substr(
                trim(
                    (string) (
                        $value['explanation']
                        ?? 'La calidad depende de la integridad de los registros escolares.'
                    )
                ),
                0,
                1000
            ),
            'issues' => $this->normalizeStrings(
                $value['issues'] ?? []
            ),
        ];
    }

    private function normalizeConfidence(
        mixed $value
    ): array {
        $value = is_array($value)
            ? $value
            : [];

        $level = (string) (
            $value['level']
            ?? 'medium'
        );

        if (! in_array(
            $level,
            ['high', 'medium', 'low'],
            true
        )) {
            $level = 'medium';
        }

        return [
            'level' => $level,
            'explanation' => mb_substr(
                trim(
                    (string) (
                        $value['explanation']
                        ?? 'La confianza depende del volumen y consistencia de los datos disponibles.'
                    )
                ),
                0,
                1000
            ),
        ];
    }

    private function normalizeEvidence(
        mixed $items,
        array $context
    ): array {
        $normalized = [];

        if (is_array($items)) {
            $normalized = collect($items)
                ->take(10)
                ->filter(
                    fn ($item): bool => is_array($item)
                )
                ->map(
                    fn (array $item): array => [
                        'source' => mb_substr(
                            trim(
                                (string) (
                                    $item['source']
                                    ?? 'SchoolPass'
                                )
                            ),
                            0,
                            120
                        ),
                        'period' => mb_substr(
                            trim(
                                (string) (
                                    $item['period']
                                    ?? ''
                                )
                            ),
                            0,
                            120
                        ),
                        'scope' => mb_substr(
                            trim(
                                (string) (
                                    $item['scope']
                                    ?? ''
                                )
                            ),
                            0,
                            220
                        ),
                    ]
                )
                ->values()
                ->all();
        }

        if ($normalized === []) {
            $normalized[] = [
                'source' => 'PaseLista',
                'period' => data_get(
                    $context,
                    'period.from'
                )
                    .' a '
                    .data_get(
                        $context,
                        'period.to'
                    ),
                'scope' => (string) data_get(
                    $context,
                    'scope.label',
                    'Escuela'
                ),
            ];
        }

        return $normalized;
    }

    private function normalizeStrings(
        mixed $items
    ): array {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(
                fn ($item): bool => is_scalar($item)
            )
            ->map(
                fn ($item): string => mb_substr(
                    trim((string) $item),
                    0,
                    1200
                )
            )
            ->filter()
            ->take(24)
            ->values()
            ->all();
    }

    private function replaceAliases(
        mixed $value,
        array $aliases
    ): mixed {
        if (is_array($value)) {
            return collect($value)
                ->map(
                    fn ($item) =>
                        $this->replaceAliases(
                            $item,
                            $aliases
                        )
                )
                ->all();
        }

        if (! is_string($value)) {
            return $value;
        }

        uksort(
            $aliases,
            fn (string $a, string $b): int =>
                strlen($b)
                <=> strlen($a)
        );

        return str_replace(
            array_keys($aliases),
            array_values($aliases),
            $value
        );
    }
}
