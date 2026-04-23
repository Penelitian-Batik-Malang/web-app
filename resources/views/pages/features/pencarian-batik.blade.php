{{--
=========================================================================
TEMPLATE — Pencarian Batik (CBIR - Content-Based Image Retrieval)
=========================================================================
Status     : TODO
Menu       : pencarian-batik
Controller : App\Http\Controllers\Features\PencarianBatikController
Route show : GET  /pencarian-batik       (routes/features.php)
Route API  : POST /api/search/batik      (routes/features.php — aktifkan TODO)

Panduan desain UI:
  - Gunakan pola halaman deteksi-motif.blade.php sebagai referensi layout
  - Output berupa grid gambar batik serupa (bukan teks klasifikasi)
  - Sertakan: hero section, upload gambar, grid hasil, tombol kembali

Langkah implementasi:
  1. Desain UI di file ini
  2. Implementasi method search() di PencarianBatikController
  3. Tambah endpoint: config/services.php → services.ml.endpoints.search_batik
  4. Aktifkan route POST di routes/features.php
=========================================================================
--}}
@extends('layouts.layout')

@section('title', 'Pencarian Batik')

@section('content')
<div class="max-w-4xl mx-auto space-y-12">

    <div class="text-center space-y-4 py-24">
        <div class="inline-flex items-center gap-2 bg-orange-500/10 border border-orange-500/30 text-orange-400 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-tools"></i> Segera Hadir
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Pencarian <span class="text-primary">Batik</span>
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Cari batik serupa menggunakan fitur visual global dari gambar yang Anda unggah.
            Fitur ini sedang dalam pengembangan.
        </p>
        <a href="{{ route('fitur') }}" class="inline-flex items-center gap-2 mt-4 text-sm text-gray-400 hover:text-white transition-colors">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Fitur
        </a>
    </div>

</div>
@endsection
