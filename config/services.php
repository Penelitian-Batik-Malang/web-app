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
        // Base URL layanan ML (tanpa trailing slash).
        'base_url' => env('ML_API_BASE_URL', 'http://127.0.0.1:5000'),

        'endpoints' => [

            // ── Deteksi & Analisis ────────────────────────────────────
            // Scan motif batik (klasifikasi nama motif)
            'motif'           => env('ML_API_MOTIF_PATH', '/motif/scan'),
            // Scan jenis batik (tulis / cap)
            'jenis'           => env('ML_API_JENIS_PATH', '/tulis/scan'),

            // ── Pencarian Batik ───────────────────────────────────────
            // CBIR pencarian batik serupa berdasarkan gambar
            'search_batik'    => env('ML_API_SEARCH_BATIK_PATH', '/cbir/search'),
            // Pencarian batik berdasarkan warna dominan
            'search_warna'    => env('ML_API_SEARCH_WARNA_PATH', '/color/search'),

            // ── Kreasi & Generasi ─────────────────────────────────────
            // Pewarnaan ulang batik menggunakan palet warna
            'pewarnaan_palet' => env('ML_API_PEWARNAAN_PALET_PATH', '/recolor/palette'),
            // Pewarnaan ulang batik menggunakan prompt teks
            'pewarnaan_prompt'=> env('ML_API_PEWARNAAN_PROMPT_PATH', '/recolor/prompt'),
            // Generate motif batik dari deskripsi teks
            'text_to_image'   => env('ML_API_TEXT_TO_IMAGE_PATH', '/generate/text2img'),

            // ── Terapkan Batik & Rekomendasi (Fashionpedia) ───────────
            // Deteksi bagian pakaian dari citra fashion
            'inference'       => env('ML_API_INFERENCE_PATH', '/inference'),
            // Terapkan (blend) motif batik ke segmen pakaian
            'blend'           => env('ML_API_BLEND_PATH', '/blend'),
            // Blend menggunakan batik dari hasil CBIR (filename server-side)
            'blend_cbir'      => env('ML_API_BLEND_CBIR_PATH', '/blend-from-cbir'),
            // Reset session ke gambar original
            'reset'           => env('ML_API_RESET_PATH', '/reset'),
            // Ambil info session yang aktif
            'session'         => env('ML_API_SESSION_PATH', '/session'),

            // ── Utilitas ──────────────────────────────────────────────
            // Deteksi mask fashion (legacy, digantikan inference)
            'fashion_mask'    => env('ML_API_FASHION_MASK_PATH', '/fashion-mask'),
            // Terapkan batik langsung (legacy, digantikan blend)
            'apply_batik'     => env('ML_API_APPLY_BATIK_PATH', '/apply-batik'),
            // Health check model AI
            'health'          => env('ML_API_HEALTH_PATH', '/health'),
        ],
    ],

];
