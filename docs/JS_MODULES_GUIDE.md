# Panduan Modul JavaScript — BatikApp

> Dokumentasi untuk developer yang bekerja pada frontend fitur
> Terapkan Batik dan Rekomendasi Batik.

---

## 1. Gambaran Umum

Fitur Terapkan Batik dan Rekomendasi Batik menggunakan arsitektur
**modular JavaScript** (tanpa bundler). Setiap modul bertugas mengelola
satu aspek spesifik dari aplikasi.

Semua modul berada di `public/js/batik-app/` dan di-load oleh
Blade template `shared/scripts.blade.php`.

---

## 2. Arsitektur

```
┌────────────────────────────────────────────────────────────┐
│                    Blade Template                          │
│  batik-app.blade.php → window.BatikAppConfig (routes, mode)│
│  scripts.blade.php   → <script src="..."> loader          │
└──────────────────────┬─────────────────────────────────────┘
                       │
    ┌──────────────────┼──────────────────┐
    │           public/js/batik-app/      │
    │                                     │
    │  ┌─────────────┐ ┌──────────────┐   │
    │  │ constants.js │ │ state.js     │   │  ← Foundation
    │  └─────────────┘ └──────────────┘   │
    │  ┌──────────────────────────────┐   │
    │  │ helpers.js                   │   │  ← Utilities
    │  └──────────────────────────────┘   │
    │  ┌──────────┐ ┌────────────────┐    │
    │  │ fashion- │ │ inference.js   │    │  ← Input
    │  │ upload.js│ │                │    │
    │  └──────────┘ └────────────────┘    │
    │  ┌──────────┐ ┌────────────────┐    │
    │  │ canvas.js│ │ parts-list.js  │    │  ← Display
    │  └──────────┘ └────────────────┘    │
    │  ┌──────────┐ ┌────────────────┐    │
    │  │ batik-   │ │ blend.js       │    │  ← Interaction
    │  │ panel.js │ │                │    │
    │  └──────────┘ └────────────────┘    │
    │  ┌──────────┐ ┌────────────────┐    │
    │  │ workspace│ │ webcam.js      │    │  ← Controls
    │  │-controls │ │                │    │
    │  └──────────┘ └────────────────┘    │
    │  ┌──────────────────────────────┐   │
    │  │ main.js (orchestrator)       │   │  ← Init
    │  └──────────────────────────────┘   │
    └─────────────────────────────────────┘
```

---

## 3. Daftar Modul

### 3.1 Foundation (Harus di-load pertama)

| File | Namespace | Deskripsi |
|------|-----------|-----------|
| `constants.js` | `BatikApp.PART_COLORS`, `BatikApp.PART_LABELS` | Warna overlay dan label terjemahan untuk setiap bagian pakaian |
| `state.js` | `BatikApp.state` | Objek state global (bukan reactive, update manual) |

### 3.2 Utilities

| File | Namespace | Deskripsi |
|------|-----------|-----------|
| `helpers.js` | `BatikApp.Helpers` | CSRF token, color conversion, safe JSON parsing, image loading, phase management |

### 3.3 Modul Fitur

| File | Namespace | Deskripsi |
|------|-----------|-----------|
| `fashion-upload.js` | `BatikApp.FashionUpload` | Upload gambar fashion (file picker, kamera, sample) |
| `inference.js` | `BatikApp.Inference` | Kirim gambar ke ML API, parse response, init workspace |
| `canvas.js` | `BatikApp.Canvas` | Render fashion canvas dengan mask overlay + hover effects |
| `parts-list.js` | `BatikApp.PartsList` | Sidebar daftar bagian pakaian terdeteksi |
| `batik-panel.js` | `BatikApp.BatikPanel` | Panel overlay untuk pilih & atur posisi motif batik |
| `blend.js` | `BatikApp.Blend` | Crop motif dari canvas, kirim ke ML API blend endpoint |
| `workspace-controls.js` | `BatikApp.WorkspaceControls` | Tombol reset, finish, back, save |
| `webcam.js` | `BatikApp.Webcam` | Akses kamera device untuk capture gambar |

### 3.4 Orchestrator

| File | Namespace | Deskripsi |
|------|-----------|-----------|
| `main.js` | — | Entry point yang menginisialisasi semua modul dalam urutan yang benar |

---

## 4. Pola Komunikasi Antar Modul

Modul berkomunikasi melalui:

1. **Shared state** — `window.BatikApp.state` (read/write langsung)
2. **Direct function calls** — `window.BatikApp.<Module>.<method>()`
3. **Config object** — `window.BatikAppConfig` (read-only, diset oleh Blade)

### Contoh: Alur Klik Bagian Pakaian

```
Canvas.click → findPartAt(x,y) → BatikPanel.open(part)
                                     ↓
                              set state.selectedPart
                              drawBatikCanvas()
                              show panel overlay
```

### Contoh: Alur Blend

```
Blend.applyBlendBtn.click
    ↓
getCroppedBlob()          ← crop dari batik canvas
    ↓
fetch(apiBlendRoute)      ← kirim ke ML API
    ↓
state.fashionImage = new  ← update gambar
state.blendedKeys.add()   ← tandai sudah blend
    ↓
Canvas.render()           ← re-render dengan centang
PartsList.render()        ← update sidebar
BatikPanel.close()        ← tutup panel
```

---

## 5. Konfigurasi (BatikAppConfig)

Diset oleh `batik-app.blade.php` sebelum modul JS di-load:

```javascript
window.BatikAppConfig = {
    isRekomendasiMode: true|false,     // Mode fitur
    apiInferenceRoute: "/api/inference", // URL inference
    apiResetRoute: "/api/reset",         // URL reset
    apiBlendRoute: "/api/blend",         // URL blend
};
```

---

## 6. Backward Compatibility

Untuk kompatibilitas dengan kode lama (terutama `rekomendasi-batik.blade.php`
custom scripts), fungsi-fungsi penting di-expose ke global scope:

| Global | Source | Dipakai oleh |
|--------|--------|-------------|
| `window.state` | `BatikApp.state` | Custom scripts |
| `window.PART_COLORS` | `BatikApp.PART_COLORS` | Custom scripts |
| `window.csrf` | `BatikApp.Helpers.csrf` | Custom scripts |
| `window.safeJson` | `BatikApp.Helpers.safeJson` | Custom scripts |
| `window.loadImage` | `BatikApp.Helpers.loadImage` | Custom scripts |
| `window.setPhase` | `BatikApp.Helpers.setPhase` | Custom scripts |
| `window.renderFashionCanvas` | `BatikApp.Canvas.render` | Custom scripts |
| `window.renderPartsList` | `BatikApp.PartsList.render` | Custom scripts |
| `window.closeBatikPanel` | `BatikApp.BatikPanel.close` | Custom scripts |
| `window.setBatikImage` | `BatikApp.BatikPanel.setBatikImage` | Custom scripts |
| `window.drawBatikCanvas` | `BatikApp.BatikPanel.draw` | Custom scripts |

> **Catatan**: Gunakan namespace `BatikApp.*` untuk kode baru.
> Global variables hanya untuk backward compatibility dan akan dihapus di masa depan.

---

## 7. Cara Menambah Modul Baru

1. Buat file `public/js/batik-app/<nama-modul>.js`
2. Gunakan pola:
   ```javascript
   window.BatikApp = window.BatikApp || {};
   window.BatikApp.NamaModul = {};
   window.BatikApp.NamaModul.init = function () {
       // Setup DOM refs, event listeners, expose functions
   };
   ```
3. Tambahkan `<script src>` di `shared/scripts.blade.php` (sebelum `main.js`)
4. Tambahkan nama modul ke array `modules` di `main.js`
5. Dokumentasikan di tabel modul di file ini

---

## 8. Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|-------|
| `BatikApp is undefined` | Script di-load sebelum constants.js | Periksa urutan `<script>` di scripts.blade.php |
| Canvas tidak render | `fashion-canvas` element tidak ada | Pastikan phase-workspace partial di-include |
| Blend 419 error | CSRF token expired | User perlu refresh halaman |
| Panel tidak buka | `openBatikPanelFunc` override aktif | Cek custom scripts di rekomendasi mode |
