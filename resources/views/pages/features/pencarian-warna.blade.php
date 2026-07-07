{{--
=========================================================================
TEMPLATE — Pencarian Batik by Warna Dominan
=========================================================================
Status     : Implemented (UI + FE only)
Menu       : pencarian-warna
Controller : App\Http\Controllers\Features\PencarianWarnaController
Route show : GET  /pencarian-warna       (routes/features.php)
Route API  : POST /api/search/color-palette
             POST /api/search/color-recommendation

Panduan desain UI:
  - Upload gambar fashion/batik → ekstrak warna dominan → tampil hasil batik senada
  - Tampilkan palet warna yang terdeteksi sebagai swatch berwarna
  - Grid hasil gambar batik yang cocok dengan warna dominan

Langkah implementasi:
    1. Pastikan endpoint color FAISS siap di ML service
    2. (Opsional) Proxy endpoint melalui Laravel jika dibutuhkan
=========================================================================
--}}
@extends('layouts.layout')

@section('title', 'Pencarian by Warna Dominan')

@section('content')
<div class="max-w-5xl mx-auto space-y-10">

    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/30 text-primary text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2 cs-fade-up">
            <i class="bi bi-palette"></i> Dominant Color Search
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight cs-fade-up">
            Pencarian by <span class="text-primary">Warna Dominan</span>
        </h1>
        <p class="text-gray-400 max-w-2xl mx-auto leading-relaxed cs-fade-up">
            Unggah foto batik, pilih jumlah palet warna, lalu temukan batik dengan dominasi warna paling serasi.
        </p>
    </div>

    @php
        $paletteEndpoint = \Illuminate\Support\Facades\Route::has('api.search.color-palette')
            ? route('api.search.color-palette')
            : url('/api/search/color-palette');

        $recommendationEndpoint = \Illuminate\Support\Facades\Route::has('api.search.color-recommendation')
            ? route('api.search.color-recommendation')
            : url('/api/search/color-recommendation');
    @endphp

    <div class="bg-gradient-to-br from-gray-900 via-amber-950/20 to-gray-900 border border-gray-800 rounded-3xl p-8 shadow-2xl cs-fade-up">
        <div
            id="color-search-page"
            data-palette-endpoint="{{ $paletteEndpoint }}"
            data-recommendation-endpoint="{{ $recommendationEndpoint }}"
            class="space-y-6"
        >
            <div id="color-search-page-alert" class="hidden rounded-xl border px-4 py-3 text-sm">
                <div class="flex items-start gap-2">
                    <i id="color-search-page-alert-icon" class="bi bi-info-circle-fill mt-0.5"></i>
                    <p id="color-search-page-alert-message" class="leading-relaxed"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-white">Unggah Gambar Batik</h2>
                    <div
                        id="color-search-page-dropzone"
                        class="group min-h-[240px] cursor-pointer rounded-2xl border-2 border-dashed border-amber-700/60 bg-gray-900/40 p-5 transition-all hover:border-primary/60 hover:bg-primary/5"
                    >
                        <div id="color-search-page-upload-state" class="flex h-full flex-col items-center justify-center gap-4 text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-gray-700 bg-gray-800 transition-transform group-hover:scale-110">
                                <i class="bi bi-camera text-2xl text-gray-400"></i>
                            </div>
                            <div>
                                <p class="text-base font-semibold text-white">Klik atau seret gambar ke sini</p>
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP. Maks 20MB.</p>                            </div>
                        </div>

                        <img
                            id="color-search-page-preview"
                            src=""
                            alt="Preview Gambar Batik"
                            class="hidden h-[260px] w-full rounded-xl border border-gray-700 object-cover"
                        >
                    </div>

                    <input
                        id="color-search-page-file-input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                    >

                    <input
                        id="color-search-page-camera-input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        capture="environment"
                        class="sr-only"
                    >

                    <div class="mx-auto grid w-full max-w-md grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            id="color-search-page-camera-btn"
                            class="rounded-xl border border-amber-700/50 bg-amber-950/30 px-3 py-3 text-sm font-semibold text-amber-400 transition-colors hover:bg-amber-900/40"
                        >
                            <i class="bi bi-camera-fill mr-2"></i>Scan Langsung
                        </button>
                        <button
                            type="button"
                            id="color-search-page-upload-btn"
                            class="rounded-xl border border-gray-700 bg-gray-800/60 px-3 py-3 text-sm font-semibold text-gray-300 transition-colors hover:bg-gray-700"
                        >
                            <i class="bi bi-upload mr-2"></i>Unggah Galeri
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-bold text-white">Palet Warna Dominan</h2>
                        <span class="text-xs text-gray-500">Maksimal 5 Palet</span>
                    </div>

                    <div id="color-search-page-palette-empty" class="flex min-h-[260px] items-center justify-center rounded-2xl border border-gray-800 bg-gray-900/30 p-5 text-center text-sm text-gray-500">
                        Palet warna akan muncul setelah proses ekstraksi.
                    </div>
                    <div id="color-search-page-palette-list" class="hidden min-h-[260px] rounded-2xl border border-amber-700/60 bg-gray-900/30 p-3"></div>
                    <div id="color-search-page-refresh-wrap" class="hidden text-center">
                        <button
                            id="color-search-page-refresh-btn"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-amber-700/50 bg-amber-950/30 px-4 py-2 text-xs font-semibold text-amber-300 transition-colors hover:bg-amber-900/40"
                        >
                            <i class="bi bi-arrow-clockwise"></i>
                            Refresh Palet
                        </button>
                    </div>
                </div>
            </div>

            <div id="color-search-page-action-section" class="hidden border-t border-gray-800 pt-5">
                <p id="color-search-page-action-label" class="mb-4 text-center text-lg font-semibold text-white sm:text-xl">Lakukan Pencarian Sekarang?</p>
                <p id="color-search-page-action-note" class="mb-3 text-center text-xs text-gray-400"></p>
                <div class="mx-auto grid max-w-md grid-cols-1 gap-3 sm:grid-cols-2">
                    <button
                        id="color-search-page-scan-btn"
                        type="button"
                        class="rounded-xl bg-primary py-3 font-bold text-black transition-colors hover:bg-amber-600"
                    >
                        Pindai Gambar
                    </button>
                    <button
                        id="color-search-page-reset-btn"
                        type="button"
                        class="rounded-xl border border-gray-700 bg-gray-800 py-3 font-bold text-white transition-colors hover:bg-gray-700"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <div id="color-search-page-recommend-section" class="hidden border-t border-gray-800 pt-6">
                <div class="flex flex-col items-start justify-between gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <h3 class="text-lg font-bold text-white sm:text-xl lg:text-2xl">Rekomendasi Batik Warna Sesuai Untukmu</h3>
                    <span id="color-search-page-recommend-count" class="text-sm text-gray-400"></span>
                </div>

                <div id="color-search-page-recommend-list" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"></div>
            </div>
        </div>
    </div>

    @include('pages.features.shared.webcam-modal')

</div>
@endsection

@push('styles')
<style>
@keyframes cs-fade-up {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.cs-fade-up { animation: cs-fade-up 0.55s ease both; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/pencarian-warna.js') }}"></script>
@endpush
