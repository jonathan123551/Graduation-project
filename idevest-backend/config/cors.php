<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * Explicit origins the API trusts. FRONTEND_URL can override / extend this
     * list at runtime (comma-separated) without requiring a redeploy.
     */
    'allowed_origins' => array_values(array_filter(array_merge(
        [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:8080',
            'https://idevest.up.railway.app',
            'https://idevest-frontend.up.railway.app',
        ],
        array_map('trim', explode(',', (string) env('FRONTEND_URL', '')))
    ))),

    /*
     * Allow any *.up.railway.app preview / branch deployment to talk to the
     * API. Regex must match the full Origin header.
     */
    'allowed_origins_patterns' => [
        '#^https://[a-z0-9-]+\.up\.railway\.app$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
