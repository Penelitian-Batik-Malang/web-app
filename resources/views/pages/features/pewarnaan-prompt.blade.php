{{--
=========================================================================
TEMPLATE — Pewarnaan Batik by Prompt Teks
=========================================================================
Status     : TODO
Menu       : pewarnaan-prompt
Controller : App\Http\Controllers\Features\PewarnaaanPromptController
Route show : GET  /pewarnaan-prompt         (routes/features.php)
Route API  : POST /api/pewarnaan/prompt     (routes/features.php — aktifkan TODO)

Panduan desain UI:
  - Upload gambar batik + textarea untuk instruksi teks (misal: "warnai dengan merah marun dan emas")
  - Tampil hasil gambar batik yang sudah diwarnai ulang oleh AI
  - Sertakan contoh prompt untuk panduan pengguna

Langkah implementasi:
  1. Desain UI di file ini
  2. Implementasi method process() di PewarnaaanPromptController
  3. Tambah endpoint: config/services.php → services.ml.endpoints.pewarnaan_prompt
  4. Aktifkan route POST di routes/features.php
=========================================================================
--}}
@extends('layouts.layout')

@section('title', 'Pewarnaan by Prompt')

@section('content')
<div class="max-w-4xl mx-auto space-y-12">

    <div class="text-center space-y-4 py-24">
        <div class="inline-flex items-center gap-2 bg-gray-500/10 border border-gray-500/30 text-gray-400 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-tools"></i> Segera Hadir
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Pewarnaan by <span class="text-primary">Prompt</span>
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Beri instruksi teks untuk mewarnai ulang motif batik secara AI.
            Fitur ini sedang dalam pengembangan.
        </p>
        <a href="{{ route('fitur') }}" class="inline-flex items-center gap-2 mt-4 text-sm text-gray-400 hover:text-white transition-colors">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Fitur
        </a>
    </div>

</div>
@endsection
