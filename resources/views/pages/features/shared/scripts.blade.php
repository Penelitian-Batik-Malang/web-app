{{--
=========================================================================
SHARED: Scripts Loader — Load Modular BatikApp JS
=========================================================================

File ini sebelumnya berisi 900+ baris JavaScript monolitik.
Sekarang berfungsi sebagai loader yang memuat modul-modul JS
terpisah dari public/js/batik-app/ dalam urutan yang benar.

STRUKTUR MODUL (public/js/batik-app/):
  ┌──────────────────────────┬──────────────────────────────────────┐
  │ File                     │ Tanggung Jawab                       │
  ├──────────────────────────┼──────────────────────────────────────┤
  │ constants.js             │ Warna & label bagian pakaian         │
  │ state.js                 │ State management global              │
  │ helpers.js               │ CSRF, color, JSON, image, phase      │
  │ fashion-upload.js        │ Upload gambar fashion                │
  │ canvas.js                │ Fashion canvas rendering             │
  │ parts-list.js            │ Sidebar daftar bagian pakaian        │
  │ batik-panel.js           │ Panel pilih & atur motif batik       │
  │ blend.js                 │ Blend API call                       │
  │ workspace-controls.js    │ Reset, finish, back, save            │
  │ webcam.js                │ Akses kamera device                  │
  │ inference.js             │ Analisis fashion → deteksi bagian    │
  │ main.js                  │ Orchestrator (init semua modul)      │
  └──────────────────────────┴──────────────────────────────────────┘

CATATAN URUTAN:
  - main.js HARUS di-load terakhir (orchestrator)
  - constants.js dan state.js HARUS sebelum modul lain
  - helpers.js HARUS sebelum modul yang menggunakannya

@see docs/JS_MODULES_GUIDE.md — Dokumentasi lengkap modul JS
@see public/js/batik-app/ — Source code modul
=========================================================================
--}}

@push('scripts')
{{-- Modul BatikApp — dimuat dalam urutan dependensi yang benar --}}
<script src="{{ asset('js/batik-app/constants.js') }}"></script>
<script src="{{ asset('js/batik-app/state.js') }}"></script>
<script src="{{ asset('js/batik-app/helpers.js') }}"></script>
<script src="{{ asset('js/batik-app/fashion-upload.js') }}"></script>
<script src="{{ asset('js/batik-app/canvas.js') }}"></script>
<script src="{{ asset('js/batik-app/parts-list.js') }}"></script>
<script src="{{ asset('js/batik-app/batik-panel.js') }}"></script>
<script src="{{ asset('js/batik-app/blend.js') }}"></script>
<script src="{{ asset('js/batik-app/workspace-controls.js') }}"></script>
<script src="{{ asset('js/batik-app/webcam.js') }}"></script>
<script src="{{ asset('js/batik-app/inference.js') }}"></script>
<script src="{{ asset('js/batik-app/main.js') }}"></script>
@endpush
