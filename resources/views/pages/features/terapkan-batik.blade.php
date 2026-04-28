{{--
=========================================================================
Terapkan Batik — Terapkan Motif Batik ke Citra Fashion
=========================================================================

Halaman ini memungkinkan user menerapkan motif batik dari galeri ke
bagian pakaian pada citra fashion secara interaktif.

Extends: shared/batik-app.blade.php (layout bertahap: upload → workspace → result)

Flow:
  1. Upload gambar fashion (phase-upload)
  2. AI menganalisis bagian pakaian (phase-loading)
  3. Workspace: klik bagian → pilih batik → blend (phase-workspace)
  4. Lihat hasil akhir (phase-result)

@see TerapkanBatikController        — Backend controller
@see shared/batik-panel.blade.php   — Panel pilih batik (shared)
@see shared/scripts.blade.php       — JS logic utama
=========================================================================
--}}

@extends('pages.features.shared.batik-app', [
    'title' => 'Terapkan Batik',
    'description' => 'Unggah foto fashion, analisis bagian pakaian secara otomatis, lalu terapkan motif batik ke setiap bagian.',
    'mode' => 'terapkan'
])

@section('custom_panel')
    @include('pages.features.shared.batik-panel', ['mode' => 'terapkan', 'batikSamples' => $batikSamples])
@endsection
