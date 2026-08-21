<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Membresía anual Family
    |--------------------------------------------------------------------------
    */

    'annual_price' => (float) env(
        'FAMILY_MEMBERSHIP_ANNUAL_PRICE',
        149
    ),

    'currency' => env(
        'FAMILY_MEMBERSHIP_CURRENCY',
        'MXN'
    ),

    'default_sponsorship_quota' => (int) env(
        'FAMILY_SPONSORSHIP_DEFAULT_QUOTA',
        20
    ),

    /*
    |--------------------------------------------------------------------------
    | Google Play Billing
    |--------------------------------------------------------------------------
    |
    | PaseLista Family utiliza una suscripción anual prepago. El backend
    | verifica la compra directamente con Google Play Android Developer API.
    |
    */

    'google_play' => [
        'enabled' => filter_var(
            env('FAMILY_GOOGLE_PLAY_ENABLED', false),
            FILTER_VALIDATE_BOOL
        ),

        'package_name' => env(
            'FAMILY_GOOGLE_PLAY_PACKAGE_NAME',
            'com.schoolpass.family'
        ),

        'product_id' => env(
            'FAMILY_GOOGLE_PLAY_PRODUCT_ID',
            'paselista_family_access'
        ),

        'base_plan_id' => env(
            'FAMILY_GOOGLE_PLAY_BASE_PLAN_ID',
            'annual-prepaid-mx'
        ),

        /*
         * Archivo JSON de la cuenta de servicio.
         * Debe permanecer fuera de public/ y fuera del repositorio Git.
         */
        'service_account_json' => env(
            'FAMILY_GOOGLE_PLAY_SERVICE_ACCOUNT_JSON',
            storage_path(
                'app/private/google-play-service-account.json'
            )
        ),

        'oauth_scope' => (
            'https://www.googleapis.com/auth/androidpublisher'
        ),

        'oauth_token_uri' => (
            'https://oauth2.googleapis.com/token'
        ),

        'publisher_base_url' => (
            'https://androidpublisher.googleapis.com/'
            .'androidpublisher/v3'
        ),

        'timeout_seconds' => (int) env(
            'FAMILY_GOOGLE_PLAY_TIMEOUT_SECONDS',
            20
        ),

        'token_cache_seconds' => (int) env(
            'FAMILY_GOOGLE_PLAY_TOKEN_CACHE_SECONDS',
            3300
        ),

        /*
         * Debe permanecer activo en producción. El identificador se genera
         * en el backend y Android lo enviará mediante setObfuscatedAccountId.
         */
        'require_obfuscated_account_id' => filter_var(
            env(
                'FAMILY_GOOGLE_PLAY_REQUIRE_OBFUSCATED_ACCOUNT_ID',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),
    ],
];