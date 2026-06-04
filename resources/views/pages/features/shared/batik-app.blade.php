{{--
=========================================================================
SHARED: Batik App Layout — Layout Bertahap untuk Fitur Fashionpedia
=========================================================================

Layout utama yang digunakan oleh:
  - terapkan-batik.blade.php  (mode = 'terapkan')
  - rekomendasi-batik.blade.php (mode = 'rekomendasi')

Phase/Tahap UI:
  1. phase-upload     → Upload gambar fashion
  2. phase-loading    → Loading saat inference ML API
  3. phase-cbir       → Hasil CBIR (hanya mode rekomendasi)
  4. phase-workspace  → Canvas interaktif + sidebar parts
  5. phase-result     → Perbandingan before/after

Sections yang bisa di-yield oleh child view:
  - phase_cbir      → Konten CBIR result (rekomendasi)
  - custom_panel    → Batik panel (terapkan/rekomendasi)
  - custom_scripts  → Script tambahan khusus mode

Variabel yang dibutuhkan:
  - $title       : Judul halaman
  - $description : Deskripsi singkat
  - $mode        : 'terapkan' | 'rekomendasi'

@see TerapkanBatikController      → Backend terapkan
@see RekomendasiBatikController   → Backend rekomendasi
@see shared/scripts.blade.php     → Loader modul JS
=========================================================================
--}}

@extends('layouts.layout')
@section('title', $title)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" id="batik-app">

    <div class="text-center">
        <h1 class="text-4xl font-playfair font-bold text-white">{{ $title }}</h1>
        <p class="text-gray-400 mt-2 max-w-2xl mx-auto text-sm">
            {{ $description }}
        </p>
    </div>

    {{-- PHASE 1: Upload --}}
    @include('pages.features.shared.phase-upload')

    {{-- PHASE: Loading --}}
    @include('pages.features.shared.phase-loading')

    {{-- PHASE: CBIR Recommendation Result (only used in rekomendasi-batik mode) --}}
    @yield('phase_cbir')

    {{-- PHASE 3: Workspace --}}
    @include('pages.features.shared.phase-workspace')

    {{-- PHASE 4: Result --}}
    @include('pages.features.shared.phase-result')

    {{-- CUSTOM PANEL (Terapkan vs Rekomendasi) --}}
    @yield('custom_panel')

    {{-- WEBCAM MODAL --}}
    @include('pages.features.shared.webcam-modal')

</div>
@endsection


@push('scripts')
{{--
    Konfigurasi BatikApp — diset di sini karena memerlukan Blade variables.
    Modul JS membaca dari window.BatikAppConfig alih-alih inline Blade.
--}}
<script>
    window.BatikAppConfig = {
        /** @type {boolean} Apakah mode rekomendasi (CBIR phase dulu) atau terapkan (langsung workspace) */
        isRekomendasiMode: {{ $mode === 'rekomendasi' ? 'true' : 'false' }},
        /** @type {string} Route untuk inference (deteksi bagian pakaian) */
        apiInferenceRoute: "{{ route('api.inference') }}",
        /** @type {string} Route untuk reset session ke gambar original */
        apiResetRoute: "{{ route('api.reset') }}",
        /** @type {string} Route untuk blend motif batik ke segmen */
        apiBlendRoute: "{{ route('api.blend') }}",
        /** @type {string} API Key untuk by-pass / direct proxy Nginx */
        apiKey: "{{ config('services.ml.api_key', env('RETRIEVAL_API_KEY')) }}",
    };
</script>
@include('pages.features.shared.scripts')
@yield('custom_scripts')
@endpush
