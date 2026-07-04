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
        // ── Base URL murni tanpa path /api ─────────────────────────────
        //   Lokal      : RETRIEVAL_API_BASE_URL=http://127.0.0.1:8001
        //   Production : RETRIEVAL_API_BASE_URL=http://127.0.0.1:8001  (atau via SSH tunnel)
        //
        // Path /api ditambahkan otomatis oleh endpoint mapping di bawah.
        'url'     => rtrim(env('RETRIEVAL_API_BASE_URL', 'http://127.0.0.1:8001'), '/'),
        'colorizer_url' => rtrim(env('COLORIZER_API_BASE_URL', 'http://127.0.0.1:8000'), '/'),
        'api_key' => env('RETRIEVAL_API_KEY', ''),

        // ── Endpoint FastAPI terpusat (single source of truth) ──────────
        // Semua path sudah menyertakan prefix /api sesuai FastAPI router.
        // Lihat: model-ml/app/routes/api.py  (prefix="/api")
        //        model-ml/app/controllers/detection.py (prefix="/detection")
        //        model-ml/app/controllers/fashion.py   (prefix="/fashion")
        //        model-ml/app/controllers/search.py    (prefix="/search")
        'endpoints' => [
            // Detection controller  → router prefix /api/detection
            'motif'           => '/api/detection/motif',
            'jenis'           => '/api/detection/type',

            // Fashion controller   → router prefix /api/fashion
            'fashion_segment' => '/api/fashion/segment',
            'fashion_blend'   => '/api/fashion/blend-manual',
            'fashion_cbir'    => '/api/fashion/blend-cbir',
            'fashion_reset'   => '/api/fashion/reset-session',
            'fashion_session' => '/api/fashion/session',

            // Search controller    → router prefix /api/search
            'search_general'  => '/api/search/general',

            // Color FAISS controller → no sub-prefix, at /api root
            'color_palette'   => '/api/color-palette-faiss',
            'color_recommend' => '/api/get-recommendation-faiss',

            // Pewarnaan by Prompt (FastAPI /api/colorize)
            'pewarnaan_prompt' => '/api/colorize',
            'pewarnaan_templates' => '/api/templates',
        ],

        // ── S3 Object Storage ──────────────────────────────────────────
        's3_batik_base' => env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id') . '/' . env('IDC_S3_BATIK_BUCKET', 'batik-signature-gdrive'),
        's3_cbir_base'  => env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id') . '/color-dominant-batik',
    ],

];
