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
        // ── Batik Service (Python 3.9, PyTorch) — Port 8001 ──────────
        // Endpoint: /detection/motif, /detection/type, /search/general
        'batik_url'  => env('ML_BATIK_URL', 'http://127.0.0.1:8001'),

        // ── Fashion Service (Python 3.7, TF 1.15) — Port 8002 ────────
        // Endpoint: /fashion/segment, /fashion/blend-manual, /fashion/blend-cbir, etc.
        'fashion_url' => env('ML_FASHION_URL', 'http://127.0.0.1:8002'),

        // ── S3 Object Storage ─────────────────────────────────────────
        // Base URL bucket batik-signature-gdrive (galeri utama)
        's3_batik_base'   => env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id') . '/' . env('IDC_S3_BATIK_BUCKET', 'batik-signature-gdrive'),
        // Base URL bucket color-dominant-batik (hasil CBIR warna fashion)
        's3_cbir_base'    => env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id') . '/color-dominant-batik',
    ],

    'retrieval' => [
        // Base URL service retrieval warna (FastAPI).
        'base_url' => env('RETRIEVAL_API_BASE_URL', env('ML_API_BASE_URL', 'http://127.0.0.1:8001')),
        'api_key' => env('RETRIEVAL_API_KEY', ''),
    ],

];
