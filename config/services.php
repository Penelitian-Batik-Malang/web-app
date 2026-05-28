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

    /*
    |--------------------------------------------------------------------------
    | ML API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi terpusat untuk seluruh endpoint Machine Learning API.
    | Semua fitur ML menggunakan config ini melalui BaseMLController.
    |
    | Cara kerja:
    |   - Controller memanggil: $this->mlUrl('endpoint_key', '/default-path')
    |   - Hasilnya: {base_url}/{endpoint_path}
    |
    | Untuk override di .env, gunakan key yang sesuai, contoh:
    |   ML_API_BASE_URL=https://api.example.com
    |   ML_API_MOTIF_PATH=/v2/motif/scan
    |
    | @see app/Http/Controllers/Features/BaseMLController.php
    | @see docs/ML_API_STRUCTURE_PLAN.md
    |
    */
    'ml' => [
        // ── Semua ML Service terpusat di SATU URL ─────────────────────
        // ML_URL harus berisi URL lengkap beserta port.
        // Contoh lokal      : ML_URL=http://127.0.0.1:8001
        // Contoh production : ML_URL=http://127.0.0.1:8001 (atau via SSH tunnel)
        'url' => rtrim(env('ML_URL', 'http://127.0.0.1:8001'), '/') . '/api',

        // ── S3 Object Storage ──────────────────────────────────
        's3_batik_base' => env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id') . '/' . env('IDC_S3_BATIK_BUCKET', 'batik-signature-gdrive'),
        's3_cbir_base'  => env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id') . '/color-dominant-batik',
    ],

    'retrieval' => [
        // Base URL service retrieval warna (FastAPI) — sama dengan ML_URL.
        'base_url' => rtrim(env('ML_URL', 'http://127.0.0.1:8001'), '/'),
        'api_key'  => env('ML_API_KEY', ''),
    ],

];
