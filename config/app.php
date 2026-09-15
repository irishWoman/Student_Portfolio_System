<?php

return [
    'name' => env('APP_NAME', 'CpE Student Development Portfolio'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_PH'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [],
    'maintenance' => ['driver' => 'file'],

    // Branding surfaced in the header and stamped onto exported portfolios.
    'institution' => [
        'name' => env('INSTITUTION_NAME', 'University of Saint Louis Tuguegarao'),
        'unit' => env('INSTITUTION_UNIT', 'School of Architecture, Computing and Engineering'),
        'department' => env('INSTITUTION_DEPARTMENT', 'Computer Engineering Department'),
        'program' => env('INSTITUTION_PROGRAM', 'BS Computer Engineering'),
    ],
];
