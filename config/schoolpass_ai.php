<?php

return [
    'enabled' => filter_var(
        env('SCHOOLPASS_AI_ENABLED', false),
        FILTER_VALIDATE_BOOL
    ),

    'provider' => 'deepseek',

    'prompt_version' => env(
        'SCHOOLPASS_AI_PROMPT_VERSION',
        'v2.1-chat'
    ),

    'queue' => [
        'connection' => env(
            'SCHOOLPASS_AI_QUEUE_CONNECTION',
            'database'
        ),

        'name' => env(
            'SCHOOLPASS_AI_QUEUE',
            'default'
        ),
    ],

    'deepseek' => [
        'base_url' => rtrim(
            (string) env(
                'DEEPSEEK_BASE_URL',
                'https://api.deepseek.com'
            ),
            '/'
        ),

        'api_key' => env('DEEPSEEK_API_KEY'),

        'fast_model' => env(
            'DEEPSEEK_MODEL_FAST',
            'deepseek-v4-flash'
        ),

        'pro_model' => env(
            'DEEPSEEK_MODEL_PRO',
            'deepseek-v4-pro'
        ),

        'connect_timeout_seconds' => (int) env(
            'DEEPSEEK_CONNECT_TIMEOUT',
            10
        ),

        'timeout_seconds' => (int) env(
            'DEEPSEEK_TIMEOUT',
            90
        ),

        'max_output_tokens' => (int) env(
            'DEEPSEEK_MAX_OUTPUT_TOKENS',
            3200
        ),
    ],

    'defaults' => [
        'school_enabled' => filter_var(
            env(
                'SCHOOLPASS_AI_DEFAULT_SCHOOL_ENABLED',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),

        'default_model' => env(
            'SCHOOLPASS_AI_DEFAULT_MODEL',
            'fast'
        ),

        'allow_pro' => filter_var(
            env(
                'SCHOOLPASS_AI_ALLOW_PRO',
                false
            ),
            FILTER_VALIDATE_BOOL
        ),

        /*
         * El nombre de la columna se conserva por compatibilidad.
         * Su valor representa créditos mensuales, no cantidad de filas.
         */
        'monthly_query_limit' => (int) env(
            'SCHOOLPASS_AI_MONTHLY_QUERY_LIMIT',
            300
        ),

        'max_range_days' => (int) env(
            'SCHOOLPASS_AI_MAX_RANGE_DAYS',
            120
        ),

        'allow_school_analysis' => true,
        'allow_group_analysis' => true,
        'allow_student_analysis' => true,
    ],

    'quota' => [
        'fast_units' => (int) env(
            'SCHOOLPASS_AI_FAST_UNITS',
            1
        ),

        'pro_units' => (int) env(
            'SCHOOLPASS_AI_PRO_UNITS',
            6
        ),
    ],

    'limits' => [
        'max_question_chars' => 1800,
        'max_context_students' => 40,
        'recent_runs' => 20,
        'recent_conversations' => 80,
        'conversation_title_chars' => 72,
        'history_messages' => 8,
        'history_message_chars' => 2200,
    ],

    /*
     * Se conserva solo para auditoría técnica de Sysadmin.
     * Nunca se muestra en el panel de dirección.
     */
    'pricing' => [
        'deepseek-v4-flash' => [
            'input_cache_hit' => 0.0028,
            'input_cache_miss' => 0.14,
            'output' => 0.28,
        ],

        'deepseek-v4-pro' => [
            'input_cache_hit' => 0.003625,
            'input_cache_miss' => 0.435,
            'output' => 0.87,
        ],
    ],
];
