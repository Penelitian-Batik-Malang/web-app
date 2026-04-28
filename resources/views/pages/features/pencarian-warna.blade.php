{{--
=========================================================================
TEMPLATE — Pencarian Batik by Warna Dominan
=========================================================================
Status     : TODO
Menu       : pencarian-warna
Controller : App\Http\Controllers\Features\PencarianWarnaController
Route show : GET  /pencarian-warna       (routes/features.php)
Route API  : POST /api/search/warna      (routes/features.php — aktifkan TODO)

Panduan desain UI:
  - Upload gambar fashion/batik → ekstrak warna dominan → tampil hasil batik senada
  - Tampilkan palet warna yang terdeteksi sebagai swatch berwarna
  - Grid hasil gambar batik yang cocok dengan warna dominan

Langkah implementasi:
  1. Desain UI di file ini
  2. Implementasi method search() di PencarianWarnaController
  3. Tambah endpoint: config/services.php → services.ml.endpoints.search_warna
  4. Aktifkan route POST di routes/features.php
=========================================================================
--}}
@extends('layouts.layout')

@section('title', 'Pencarian by Warna Dominan')

@section('content')
<div class="max-w-4xl mx-auto space-y-12">

    <div class="text-center space-y-4 py-24">
        <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-tools"></i> Segera Hadir
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Pencarian by <span class="text-primary">Warna Dominan</span>
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Temukan batik berdasarkan warna dominan pada kain.
            Fitur ini sedang dalam pengembangan.
        </p>
        <a href="{{ route('fitur') }}" class="inline-flex items-center gap-2 mt-4 text-sm text-gray-400 hover:text-white transition-colors">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Fitur
        </a>
    </div>

</div>
@endsection
