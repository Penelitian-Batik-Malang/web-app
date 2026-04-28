{{--
=========================================================================
TEMPLATE — Text to Image Batik (Generatif AI)
=========================================================================
Status     : TODO
Menu       : text-to-image
Controller : App\Http\Controllers\Features\TextToImageController
Route show : GET  /text-to-image          (routes/features.php)
Route API  : POST /api/text-to-image      (routes/features.php — aktifkan TODO)

Panduan desain UI:
  - Textarea untuk deskripsi motif batik yang ingin di-generate
  - Contoh prompt untuk panduan pengguna (misal: "batik malang dengan motif bunga dan warna biru")
  - Tombol generate + loading indicator (model AI generatif bisa lambat 10–30 detik)
  - Tampil hasil gambar yang di-generate

Langkah implementasi:
  1. Desain UI di file ini
  2. Implementasi method generate() di TextToImageController
  3. Tambah endpoint: config/services.php → services.ml.endpoints.text_to_image
  4. Aktifkan route POST di routes/features.php
=========================================================================
--}}
@extends('layouts.layout')

@section('title', 'Text to Image Batik')

@section('content')
<div class="max-w-4xl mx-auto space-y-12">

    <div class="text-center space-y-4 py-24">
        <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-lightning-charge-fill"></i> AI Generatif &nbsp;·&nbsp; Segera Hadir
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Text to <span class="text-primary">Image</span> Batik
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Generate motif batik Malang baru dari deskripsi teks menggunakan model AI generatif.
            Fitur ini sedang dalam pengembangan.
        </p>
        <a href="{{ route('fitur') }}" class="inline-flex items-center gap-2 mt-4 text-sm text-gray-400 hover:text-white transition-colors">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Fitur
        </a>
    </div>

</div>
@endsection
