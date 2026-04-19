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

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth/google/callback'),
    ],

    'ml' => [
        // Base URL layanan ML (tanpa trailing slash).
        // Default diarahkan ke endpoint produksi yang kamu sebutkan.
        'base_url' => env('ML_API_BASE_URL', 'http://127.0.0.1:5000'),
        'endpoints' => [
            // Path endpoint scan motif batik.
            'motif' => env('ML_API_MOTIF_PATH', '/motif/scan'),
            // Path endpoint scan jenis batik (tulis/cap).
            'jenis' => env('ML_API_JENIS_PATH', '/tulis/scan'),
            // Path endpoint deteksi mask fashion.
            'fashion_mask' => env('ML_API_FASHION_MASK_PATH', '/fashion-mask'),
            // Path endpoint terapkan batik pada citra fashion.
            'apply_batik' => env('ML_API_APPLY_BATIK_PATH', '/apply-batik'),
            // Path endpoint health model AI.
            'health' => env('ML_API_HEALTH_PATH', '/health'),
            // Fashionpedia API endpoints
            'inference' => env('ML_API_INFERENCE_PATH', '/inference'),
            'blend' => env('ML_API_BLEND_PATH', '/blend'),
            'reset' => env('ML_API_RESET_PATH', '/reset'),
            'session' => env('ML_API_SESSION_PATH', '/session'),
        ],
    ],

];
