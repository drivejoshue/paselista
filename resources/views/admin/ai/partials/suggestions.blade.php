<div
    class="accordion mt-4 text-start"
    id="ai-suggestion-accordion"
>
    @foreach([
        [
            'key' => 'daily',
            'title' => 'Operación diaria',
            'icon' => 'ti-calendar-check',
            'open' => true,

            'questions' => [
                'Resume lo ocurrido hoy y señala qué requiere revisión.',

                '¿Qué grupos tuvieron menor asistencia o puntualidad hoy?',

                '¿Hay alumnos con entrada registrada y sin salida?',

                '¿Qué accesos fueron denegados o registrados como duplicados?',

                '¿Qué dispositivos están fuera de línea o sin actividad reciente?',

                '¿Cuántos registros manuales se realizaron y en qué grupos?',
            ],
        ],

        [
            'key' => 'student',
            'title' => 'Análisis de alumnos',
            'icon' => 'ti-user-search',
            'open' => false,

            'questions' => [
                'Dame el resumen de asistencia y accesos de un alumno específico.',

                '¿Cuáles son sus patrones de puntualidad durante el periodo?',

                '¿Tiene ausencias consecutivas o retardos recurrentes?',

                'Compara sus últimos 30 días con los 30 días anteriores.',

                '¿Registra salidas anticipadas o entradas sin salida?',

                '¿Su asistencia está mejorando, estable o empeorando?',
            ],
        ],

        [
            'key' => 'group',
            'title' => 'Grupos y niveles',
            'icon' => 'ti-users-group',
            'open' => false,

            'questions' => [
                'Resume el desempeño del grupo seleccionado.',

                '¿Qué días concentran más retardos en este grupo?',

                '¿Qué alumnos explican la mayor parte de las ausencias?',

                'Compara este grupo con otros del mismo nivel.',

                '¿La puntualidad del grupo está mejorando o empeorando?',

                '¿Qué anomalías del grupo requieren revisión administrativa?',
            ],
        ],

        [
            'key' => 'school',
            'title' => 'Dirección escolar',
            'icon' => 'ti-building-community',
            'open' => false,

            'questions' => [
                'Genera un resumen ejecutivo de la escuela durante el periodo.',

                'Compara la asistencia entre niveles y planteles.',

                '¿Qué cambió respecto al periodo anterior?',

                '¿Dónde se concentra la pérdida de puntualidad?',

                '¿Qué grupos requieren revisión prioritaria?',

                'Evalúa la calidad de los datos y señala inconsistencias.',
            ],
        ],

        [
            'key' => 'security',
            'title' => 'Seguridad y accesos',
            'icon' => 'ti-shield-check',
            'open' => false,

            'questions' => [
                '¿Existen diferencias anormales entre entradas y salidas?',

                '¿Cuántas salidas se realizaron sin tutor asociado?',

                '¿Hubo intentos de salida cuando el tutor era requerido?',

                '¿Qué dispositivos generan más duplicados o registros manuales?',

                '¿Qué accesos denegados necesitan revisión?',

                'Resume las anomalías de acceso del periodo.',
            ],
        ],
    ] as $category)
        @php
            $isOpen = (bool) (
                $category['open']
                ?? false
            );
        @endphp

        <div class="accordion-item">
            <h2
                class="accordion-header"
                id="heading-{{ $category['key'] }}"
            >
                <button
                    type="button"
                    class="accordion-button {{
                        $isOpen
                            ? ''
                            : 'collapsed'
                    }}"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{ $category['key'] }}"
                    aria-expanded="{{
                        $isOpen
                            ? 'true'
                            : 'false'
                    }}"
                    aria-controls="collapse-{{ $category['key'] }}"
                >
                    <i
                        class="ti {{ $category['icon'] }} me-2"
                    ></i>

                    {{ $category['title'] }}
                </button>
            </h2>

            <div
                id="collapse-{{ $category['key'] }}"
                class="accordion-collapse collapse {{
                    $isOpen
                        ? 'show'
                        : ''
                }}"
                aria-labelledby="heading-{{ $category['key'] }}"
                data-bs-parent="#ai-suggestion-accordion"
            >
                <div class="accordion-body">
                    <div class="row g-2">
                        @foreach(
                            $category['questions']
                            as $question
                        )
                            <div class="col-12 col-lg-6">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary
                                           w-100 h-100 text-start
                                           align-items-start
                                           sp-ai-question-button
                                           ai-suggestion"
                                    data-question="{{ $question }}"
                                >
                                    <i
                                        class="ti ti-arrow-up-right
                                               me-2 mt-1
                                               text-primary"
                                    ></i>

                                    <span>
                                        {{ $question }}
                                    </span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>