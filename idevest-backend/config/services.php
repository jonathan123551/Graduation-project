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
    | Defaults target Google Gemini via its OpenAI-compatible endpoint
    | (https://ai.google.dev/gemini-api/docs/openai). Override
    | `AI_BASE_URL` / `AI_MODEL` / `AI_API_KEY` to swap providers (Groq,
    | OpenAI, etc.). For backward compatibility we also accept the
    | legacy `GROQ_API_KEY` / `GEMINI_API_KEY` env vars as the key.
    */
    'ai' => [
        'key'      => env('AI_API_KEY', env('GEMINI_API_KEY', env('GROQ_API_KEY'))),
        'base_url' => env('AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
        'model'    => env('AI_MODEL', 'gemini-2.0-flash'),
    ],

];
