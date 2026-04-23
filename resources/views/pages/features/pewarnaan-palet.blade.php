{{--
=========================================================================
TEMPLATE — Pewarnaan Batik by Palet Warna
=========================================================================
Status     : TODO
Menu       : pewarnaan-palet
Controller : App\Http\Controllers\Features\PewarnaaanPaletController
Route show : GET  /pewarnaan-palet          (routes/features.php)
Route API  : POST /api/pewarnaan/palet      (routes/features.php — aktifkan TODO)

Panduan desain UI:
  - Upload gambar batik + UI color picker untuk memilih palet (2–5 warna)
  - Tampil hasil gambar batik dengan warna yang sudah diganti
  - Sertakan preview before/after

Langkah implementasi:
  1. Desain UI di file ini (butuh color picker JS, misal Pickr atau vanilla input[type=color])
  2. Implementasi method process() di PewarnaaanPaletController
  3. Tambah endpoint: config/services.php → services.ml.endpoints.pewarnaan_palet
  4. Aktifkan route POST di routes/features.php
=========================================================================
--}}
@extends('layouts.layout')

@section('title', 'Pewarnaan by Palet Warna')

@section('content')
<div class="max-w-4xl mx-auto space-y-12">

    <div class="text-center space-y-4 py-24">
        <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-tools"></i> Segera Hadir
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Pewarnaan by <span class="text-primary">Palet Warna</span>
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Ubah warna kain batik menggunakan palet warna pilihan Anda.
            Fitur ini sedang dalam pengembangan.
        </p>
        <a href="{{ route('fitur') }}" class="inline-flex items-center gap-2 mt-4 text-sm text-gray-400 hover:text-white transition-colors">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Fitur
        </a>
    </div>

</div>
@endsection
