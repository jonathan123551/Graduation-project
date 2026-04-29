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
    | OpenAI-API-compatible, free with generous limits, and has no
    | billing setup. Override `AI_BASE_URL` / `AI_MODEL` / `AI_API_KEY`
    | to swap providers (Gemini, OpenAI, etc.). For backward compatibility
    | we also accept the legacy `GROQ_API_KEY` / `GEMINI_API_KEY` env
    | vars as the key.
    */
    'ai' => [
        'key'      => env('AI_API_KEY', env('GROQ_API_KEY', env('GEMINI_API_KEY'))),
        'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model'    => env('AI_MODEL', 'llama-3.1-8b-instant'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase (Phone OTP via Firebase Phone Auth)
    |--------------------------------------------------------------------------
    |
    | Only the project_id is needed server-side — we verify Firebase-issued
    | ID tokens using Google's public JWKS (no service-account key needed).
    | Override FIREBASE_PROJECT_ID in production if you switch projects.
    */
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'ideavest-otp'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mindee (OCR / KYC ID-card verification)
    |--------------------------------------------------------------------------
    |
    | Used server-side to extract the national-ID number from uploaded
    | ID-card photos and validate it against the number the user typed.
    |
    | Override MINDEE_API_KEY / MINDEE_MODEL_ID in production. Default
    | values are scoped to the graduation-project test account and
    | should be rotated before real users land.
    */
    'mindee' => [
        'api_key'        => env('MINDEE_API_KEY', 'md_5598TiCfWU3QQI6dixEMNNlMhGmyhiLm6wedg_c7-no'),
        'model_id'       => env('MINDEE_MODEL_ID', '23dcda1f-bd27-4333-a5b9-a3f6e02a4509'),
        'min_confidence' => (float) env('MINDEE_MIN_CONFIDENCE', 0.8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paymob (Egyptian online payments / escrow)
    |--------------------------------------------------------------------------
    |
    | auto_capture=false on the payment_key call creates an authorize-only
    | transaction (funds held but not yet captured). After KYC clears we
    | call /acceptance/capture; on KYC failure we call /acceptance/void_refund/void.
    |
    | integration_id + iframe_id come from the Paymob dashboard. Defaults
    | below are the values the graduation-project dev account provided
    | and should be rotated before production via PAYMOB_* env vars.
    */
    'paymob' => [
        'api_key'        => env('PAYMOB_API_KEY', 'ZXlKaGJHY2lPaUpJVXpVeE1pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SmpiR0Z6Y3lJNklrMWxjbU5vWVc1MElpd2ljSEp2Wm1sc1pWOXdheUk2TVRFMU5EWTROeXdpYm1GdFpTSTZJbWx1YVhScFlXd2lmUS41ZUZfMnNIRzhNMHJQZjRHS2RGc1Fsa1pfWWtqcUVEMExkMXl6TkdXSUsxcmREMm9DeUJlU29vc3BTaF9WenM4VHh0UzAwRjlSdkJnMVBuRk1ibG16Zw=='),
        'integration_id' => (int) env('PAYMOB_INTEGRATION_ID', 5623390),
        'iframe_id'      => (int) env('PAYMOB_IFRAME_ID', 0),
        'hmac_secret'    => env('PAYMOB_HMAC_SECRET'),
    ],

];
