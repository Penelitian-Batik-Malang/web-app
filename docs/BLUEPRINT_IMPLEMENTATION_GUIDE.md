# Dokumentasi Implementasi Berdasarkan `blueprint.txt`

Dokumen ini menjadi acuan teknis lanjutan implementasi aplikasi, terutama menu yang melibatkan model ML dan status progres terhadap isi `blueprint.txt`.

> **Terakhir diperbarui**: 2026-04-27

---

## 1) Ringkasan Arsitektur

- Aplikasi menggunakan Laravel (web routes + Blade + JS frontend).
- Integrasi ML diposisikan sebagai:
  - **Frontend UI** (Blade views + modular JS),
  - **Backend controller** yang memanggil API ML eksternal via HTTP,
  - **Konfigurasi endpoint terpusat** di `config/services.php`.

### Hierarki Controller ML

```
BaseMLController (abstract) ← config, URL builder, image detection, sample fashion
├── DeteksiMotifController     [DONE]  — Deteksi motif batik
├── DeteksiJenisController     [DONE]  — Deteksi jenis batik (tulis/cap)
├── PencarianBatikController   [TODO]  — Pencarian batik serupa (CBIR)
├── PencarianWarnaController   [TODO]  — Pencarian by warna dominan
├── PewarnaanPaletController   [TODO]  — Pewarnaan by palet warna
├── PewarnaanPromptController  [TODO]  — Pewarnaan by prompt teks
├── TerapkanBatikController    [DONE]  — Terapkan motif ke pakaian
├── RekomendasiBatikController [DONE]  — Rekomendasi batik by fashion
├── TextToImageController      [TODO]  — Generate motif dari teks
└── SharedMLController                 — Session Fashionpedia (inference/reset)
```

### File Kunci

| Kategori | File | Deskripsi |
|----------|------|-----------|
| Config | `config/services.php` | Semua endpoint ML API |
| Routes | `routes/features.php` | Semua route fitur ML |
| Base | `app/Http/Controllers/Features/BaseMLController.php` | Base controller ML |
| UI Deteksi | `resources/views/components/ml-detector.blade.php` | Komponen reusable |
| JS Deteksi | `public/js/ml-detector.js` | Logic modal deteksi |
| JS BatikApp | `public/js/batik-app/*.js` | Modul JS modular (12 file) |
| Views | `resources/views/pages/features/*.blade.php` | Halaman fitur |
| Shared Views | `resources/views/pages/features/shared/*.blade.php` | Layout & partial shared |
| Docs ML API | `docs/ML_API_STRUCTURE_PLAN.md` | Arsitektur API ML |
| Docs JS | `docs/JS_MODULES_GUIDE.md` | Panduan modul JS |

---

## 2) Standar Integrasi API ML

### 2.1 Konfigurasi Endpoint

Seluruh endpoint ML didefinisikan di `config/services.php → services.ml`:

```php
'ml' => [
    'base_url' => env('ML_API_BASE_URL'),
    'endpoints' => [
        'motif'           => '/motif/scan',
        'jenis'           => '/tulis/scan',
        'search_batik'    => '/cbir/search',
        'search_warna'    => '/color/search',
        'pewarnaan_palet' => '/recolor/palette',
        'pewarnaan_prompt'=> '/recolor/prompt',
        'text_to_image'   => '/generate/text2img',
        'inference'       => '/inference',
        'blend'           => '/blend',
        'blend_cbir'      => '/blend-from-cbir',
        'reset'           => '/reset',
        'session'         => '/session',
        'health'          => '/health',
    ],
],
```

### 2.2 Kontrak Response (Normalisasi)

Klasifikasi (image → text):
```json
{ "success": true, "result": { "label": "...", "confidence": 0.99, "description": "..." } }
```

Image result (image → image):
```json
{ "success": true, "image_b64": "<base64>" }
```

Search result (image → grid):
```json
{ "success": true, "results": [{ "name": "...", "image_url": "...", "similarity_score": 0.89 }] }
```

Error:
```json
{ "success": false, "message": "Pesan error" }
```

### 2.3 Pola Implementasi Fitur ML Baru

1. Tambah endpoint di `config/services.php`
2. Buat controller extends `BaseMLController`
3. Tambah route di `routes/features.php`
4. Buat view di `resources/views/pages/features/`
5. Gunakan `$this->mlUrl('key')` untuk URL, `$this->handleImageDetection()` untuk deteksi standar

---

## 3) Standar Desain UI

### Komponen Reusable

- **Deteksi**: Gunakan `<x-ml-detector>` untuk fitur input image → output text
- **Fashionpedia**: Gunakan `shared/batik-app.blade.php` layout untuk workflow multi-phase
- **Batik Panel**: Gunakan `shared/batik-panel.blade.php` untuk panel pilih motif

### Checklist Halaman Fitur ML

- [ ] Hero section (judul, badge status, deskripsi)
- [ ] CTA section dengan trigger komponen
- [ ] Info edukasi / cara kerja (3-column grid)
- [ ] Responsive design
- [ ] Warna, spacing, icon mengikuti `deteksi-motif.blade.php`

---

## 4) JavaScript — Modular Architecture

Frontend fitur Terapkan/Rekomendasi Batik menggunakan **12 modul JS** di `public/js/batik-app/`:

| Modul | Tanggung Jawab |
|-------|---------------|
| `constants.js` | Warna & label bagian pakaian |
| `state.js` | State management |
| `helpers.js` | Utility functions |
| `fashion-upload.js` | Upload gambar fashion |
| `inference.js` | Analisis fashion → ML API |
| `canvas.js` | Fashion canvas rendering |
| `parts-list.js` | Sidebar bagian pakaian |
| `batik-panel.js` | Panel pilih & atur motif |
| `blend.js` | Blend API call |
| `workspace-controls.js` | Reset, finish, back, save |
| `webcam.js` | Akses kamera |
| `main.js` | Orchestrator |

Lihat `docs/JS_MODULES_GUIDE.md` untuk dokumentasi lengkap.

---

## 5) Progress terhadap `blueprint.txt`

| Area Blueprint | Status | Catatan |
|---------------|--------|---------|
| Galeri Batik (lihat/detail) | ✅ DONE | `/galeri`, `/galeri/{batik}` |
| Like gambar + rekomendasi | 🔄 PARTIAL | Like DONE; recommend stub siap integrasi ML |
| Deteksi Motif Batik | ✅ DONE | Halaman + popup + API ML |
| Deteksi Jenis Batik | ✅ DONE | Halaman + popup + API ML |
| Pencarian Batik (similar) | 🔄 PARTIAL | Controller + view stub, API ML belum |
| Pencarian by Warna Dominan | 🔄 PARTIAL | Controller + view stub, API ML belum |
| Rekomendasi by Fashion | ✅ DONE | CBIR + workspace + blend |
| Pewarnaan by Palet Warna | 🔄 PARTIAL | Controller + view stub, API ML belum |
| Pewarnaan by Prompt | 🔄 PARTIAL | Controller + view stub, API ML belum |
| Terapkan Batik | ✅ DONE | Full workflow: inference → blend |
| Text to Image Batik | 🔄 PARTIAL | Controller + view stub, API ML belum |
| Login email/password | ✅ DONE | |
| Login Google | ✅ DONE | |
| Register | ✅ DONE | |
| Remember Me | ✅ DONE | |
| Lupa Password | 🔄 TODO | Belum ada flow reset password |
| Profil user | ✅ DONE | |
| Admin Dashboard | ✅ DONE | |
| Kelola User | ✅ DONE | |
| Kelola Role + flagging | ✅ DONE | |
| Kelola Galeri Batik | ✅ DONE | |
| Kelola Konten Landing | ✅ DONE | |
| Monitor Model AI | ✅ DONE | Health table + auto-refresh |

---

## 6) Rencana Eksekusi Menu ML yang Belum

Untuk fitur yang masih TODO/PARTIAL, ikuti pola:

1. **Koordinasi dengan tim ML** — pastikan endpoint tersedia (lihat `docs/ML_API_STRUCTURE_PLAN.md`)
2. **Implementasi backend** — uncomment/implement method di controller yang sudah ada
3. **Desain UI** — ikuti pola `deteksi-motif.blade.php` untuk fitur deteksi, atau buat custom untuk fitur search/grid
4. **Aktifkan route** — uncomment route POST di `routes/features.php`
5. **Testing** — test endpoint secara isolated, lalu test full flow

---

## 7) Referensi Dokumentasi

| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Blueprint | `blueprint.txt` | Spesifikasi kebutuhan aplikasi |
| ML API Plan | `docs/ML_API_STRUCTURE_PLAN.md` | Arsitektur API ML yang direkomendasikan |
| JS Modules | `docs/JS_MODULES_GUIDE.md` | Panduan modul JavaScript frontend |
| Blending API | `docs/PLANNING_BATIK_BLENDING_API.md` | Detail teknis blending API |
| CBIR API | `docs/PLANNING_CBIR_API.md` | Detail teknis CBIR API |
