<?php

return [
    'brand' => env('SCHOOLPASS_PUBLIC_BRAND', 'SchoolPass'),

    /*
    |--------------------------------------------------------------------------
    | Identidad legal pública
    |--------------------------------------------------------------------------
    |
    | La legislación mexicana exige que el aviso identifique al responsable y
    | señale un domicilio. Configura aquí la persona moral, persona física con
    | actividad empresarial o identidad jurídica que realmente opere el servicio.
    |
    */
    'legal_name' => env(
        'SCHOOLPASS_PUBLIC_LEGAL_NAME',
        '[CONFIGURAR IDENTIDAD LEGAL DEL OPERADOR]'
    ),

    'legal_address' => env(
        'SCHOOLPASS_PUBLIC_LEGAL_ADDRESS',
        '[CONFIGURAR DOMICILIO COMERCIAL O DE NOTIFICACIONES]'
    ),

    'privacy_email' => env(
        'SCHOOLPASS_PUBLIC_PRIVACY_EMAIL',
        'privacidad@schoolpass.mx'
    ),

    'support_email' => env(
        'SCHOOLPASS_PUBLIC_SUPPORT_EMAIL',
        'soporte@schoolpass.mx'
    ),

    'commercial_email' => env(
        'SCHOOLPASS_PUBLIC_COMMERCIAL_EMAIL',
        'contacto@schoolpass.mx'
    ),

    'phone' => env('SCHOOLPASS_PUBLIC_PHONE'),
    'website_url' => env('SCHOOLPASS_PUBLIC_WEBSITE_URL', config('app.url')),
    'privacy_version' => env('SCHOOLPASS_PRIVACY_VERSION', '1.0'),
    'privacy_updated_at' => env('SCHOOLPASS_PRIVACY_UPDATED_AT', '18 de julio de 2026'),

    /*
     | Puede ser una URL completa. Si queda vacío, se utiliza la ruta login.
     */
    'login_url' => env('SCHOOLPASS_PUBLIC_LOGIN_URL'),

    /*
     | Dirección a la que se enviará una notificación de nuevas solicitudes.
     | La solicitud siempre queda registrada en privacy_requests.
     */
    'privacy_notification_email' => env(
        'SCHOOLPASS_PRIVACY_NOTIFICATION_EMAIL',
        env('SCHOOLPASS_PUBLIC_PRIVACY_EMAIL', 'privacidad@schoolpass.mx')
    ),
];
