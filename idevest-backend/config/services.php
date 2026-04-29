<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI provider (OpenAI-compatible chat completions)
    |--------------------------------------------------------------------------
    |
    | Defaults target Groq (https://console.groq.com/) which is fully
    | OpenAI-API-compatible. Override `AI_BASE_URL` / `AI_MODEL` / `AI_API_KEY`
    | to swap providers. For backward compatibility we also accept
    | `GROQ_API_KEY` as the key env var.
    */
    'ai' => [
        'key'      => env('AI_API_KEY', env('GROQ_API_KEY')),
        'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model'    => env('AI_MODEL', 'llama-3.1-8b-instant'),
    ],

];
