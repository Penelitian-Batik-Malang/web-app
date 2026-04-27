# Arsitektur API ML — Galeri Digital Batik Malang

> Dokumen ini mendefinisikan standar arsitektur yang direkomendasikan untuk ML API (Python/Flask)
> agar inline dan konsisten dengan arsitektur web app (Laravel).
> Dibuat untuk memudahkan koordinasi antar developer web dan ML.

---

## 1. Ringkasan

Web app Laravel berfungsi sebagai **frontend + proxy** yang memanggil ML API (Python).
Semua endpoint ML dikonfigurasi di `config/services.php` dan dipanggil melalui
`BaseMLController` menggunakan Laravel HTTP Client.

```
┌─────────────┐     HTTP/JSON      ┌──────────────┐
│  Laravel     │ ──────────────────▶│  ML API      │
│  (Frontend)  │◀────────────────── │  (Python)    │
│              │     Response       │              │
│  Controller  │                    │  Flask/Fast  │
│  + Blade     │                    │  API + Model │
└─────────────┘                    └──────────────┘
```

---

## 2. Peta Endpoint (Kontrak API)

### 2.1 Deteksi & Analisis

| Endpoint | Method | Input | Output | Status |
|----------|--------|-------|--------|--------|
| `/motif/scan` | POST | `image` (multipart) | `{ label, confidence, description }` | ✅ DONE |
| `/tulis/scan` | POST | `image` (multipart) | `{ label, confidence, description }` | ✅ DONE |

### 2.2 Pencarian Batik

| Endpoint | Method | Input | Output | Status |
|----------|--------|-------|--------|--------|
| `/cbir/search` | POST | `image` (multipart) | `{ results: [{ name, image_url, similarity_score }] }` | 🔄 TODO |
| `/color/search` | POST | `image` (multipart) | `{ dominant_colors: [...], results: [{ name, image_url }] }` | 🔄 TODO |

### 2.3 Kreasi & Generasi

| Endpoint | Method | Input | Output | Status |
|----------|--------|-------|--------|--------|
| `/recolor/palette` | POST | `image` (multipart), `colors` (JSON array hex) | `{ result_image_b64 }` | 🔄 TODO |
| `/recolor/prompt` | POST | `image` (multipart), `prompt` (string) | `{ result_image_b64 }` | 🔄 TODO |
| `/generate/text2img` | POST | `prompt` (string) | `{ image_b64 }` | 🔄 TODO |

### 2.4 Terapkan Batik (Fashionpedia)

| Endpoint | Method | Input | Output | Status |
|----------|--------|-------|--------|--------|
| `/inference` | POST | `image` (multipart) | `{ session_id, parts, cbir, image_size }` | ✅ DONE |
| `/blend` | POST | `session_id`, `part`, `instance_index`, `batik` (multipart) | `{ image_b64 }` | ✅ DONE |
| `/blend-from-cbir` | POST | `session_id`, `part`, `instance_index`, `batik_filename` | `{ image_b64 }` | ✅ DONE |
| `/reset` | POST | `session_id` (JSON) | `{ image_b64 }` | ✅ DONE |
| `/session/{id}` | GET | — | `{ session_id, image_b64, parts }` | ✅ DONE |

### 2.5 Utilitas

| Endpoint | Method | Input | Output | Status |
|----------|--------|-------|--------|--------|
| `/health` | GET | — | `{ status, models: { name, loaded, version } }` | ✅ DONE |

---

## 3. Standar Response

### 3.1 Response Sukses (Klasifikasi)

```json
{
  "label": "Balai Kota",
  "confidence": 0.9994,
  "description": "Motif yang menggambarkan landmark Balai Kota Malang."
}
```

### 3.2 Response Sukses (Image Result)

```json
{
  "success": true,
  "image_b64": "<base64-encoded-image>",
  "message": "Berhasil"
}
```

### 3.3 Response Sukses (Search/Grid)

```json
{
  "success": true,
  "results": [
    {
      "name": "Batik Parang Malang",
      "image_url": "/batik/images/parang-malang.webp",
      "similarity_score": 0.89
    }
  ]
}
```

### 3.4 Response Error

```json
{
  "success": false,
  "message": "Pesan error yang deskriptif"
}
```

---

## 4. Struktur Folder ML API yang Direkomendasikan

```
ml-api/
├── main.py                    # Entry point Flask/FastAPI
├── config.py                  # Konfigurasi (model paths, thresholds)
├── requirements.txt           # Dependencies
│
├── routes/                    # Route handlers (thin, hanya routing)
│   ├── __init__.py
│   ├── detection.py           # /motif/scan, /tulis/scan
│   ├── search.py              # /cbir/search, /color/search
│   ├── creation.py            # /recolor/*, /generate/*
│   ├── fashionpedia.py        # /inference, /blend, /reset, /session
│   └── health.py              # /health
│
├── services/                  # Business logic (heavy lifting)
│   ├── __init__.py
│   ├── motif_classifier.py    # Load model, predict motif
│   ├── type_classifier.py     # Load model, predict tulis/cap
│   ├── cbir_engine.py         # CBIR search + color extraction
│   ├── color_search.py        # Pencarian by warna dominan
│   ├── recolor_engine.py      # Pewarnaan ulang (palet + prompt)
│   ├── text2img_engine.py     # Text-to-image generatif
│   ├── fashionpedia.py        # Segmentasi pakaian
│   └── blending.py            # Blend motif ke segmen
│
├── models/                    # File model (.h5, .pt, .onnx)
│   ├── motif_model/
│   ├── type_model/
│   ├── fashionpedia_model/
│   └── generative_model/
│
├── utils/                     # Shared utilities
│   ├── image_utils.py         # Resize, convert, encode/decode
│   ├── color_utils.py         # Color space conversion (RGB ↔ LAB)
│   └── session_manager.py     # In-memory session storage
│
├── data/                      # Static data
│   ├── batik_database/        # Gambar batik untuk CBIR index
│   ├── batik_features.npz     # Pre-computed CBIR features
│   └── motif_labels.json      # Label mapping
│
└── tests/                     # Unit tests
    ├── test_detection.py
    ├── test_search.py
    └── test_fashionpedia.py
```

---

## 5. Prinsip Arsitektur

### 5.1 Separation of Concerns

| Layer | Tanggung Jawab | Contoh |
|-------|---------------|--------|
| **Routes** | Parsing request, validasi input, return response | `routes/detection.py` |
| **Services** | Business logic, model inference, image processing | `services/motif_classifier.py` |
| **Utils** | Fungsi utility yang dipakai banyak service | `utils/image_utils.py` |
| **Models** | File model ML (read-only) | `models/motif_model/` |

### 5.2 Aturan Penting

1. **Route handler harus tipis** — hanya parsing input, panggil service, format output
2. **Jangan hardcode path** — gunakan `config.py` untuk semua path dan threshold
3. **Semua gambar output dalam `.webp`** — konsisten dengan web app
4. **Session management pakai in-memory dict** — cukup untuk single-server
5. **Error handling di route** — service boleh throw, route yang catch
6. **Health endpoint wajib** — monitor loading status semua model

### 5.3 Konvensi Penamaan

| Item | Konvensi | Contoh |
|------|----------|--------|
| Route file | `snake_case.py` | `detection.py` |
| Service class | `PascalCase` | `MotifClassifier` |
| Function | `snake_case` | `predict_motif()` |
| URL endpoint | `kebab-case` | `/cbir/search` |
| Config key | `UPPER_SNAKE` | `MOTIF_MODEL_PATH` |

---

## 6. Mapping Web → ML API

Tabel ini menunjukkan hubungan antara controller Laravel dan endpoint ML API:

| Laravel Controller | ML Route File | Endpoint | 
|-------------------|---------------|----------|
| `DeteksiMotifController` | `routes/detection.py` | `POST /motif/scan` |
| `DeteksiJenisController` | `routes/detection.py` | `POST /tulis/scan` |
| `PencarianBatikController` | `routes/search.py` | `POST /cbir/search` |
| `PencarianWarnaController` | `routes/search.py` | `POST /color/search` |
| `PewarnaanPaletController` | `routes/creation.py` | `POST /recolor/palette` |
| `PewarnaanPromptController` | `routes/creation.py` | `POST /recolor/prompt` |
| `TextToImageController` | `routes/creation.py` | `POST /generate/text2img` |
| `SharedMLController` | `routes/fashionpedia.py` | `POST /inference`, `/blend`, `/reset` |
| `TerapkanBatikController` | `routes/fashionpedia.py` | `POST /blend` |
| `RekomendasiBatikController` | `routes/fashionpedia.py` | `POST /blend-from-cbir` |
| `GalleryController::recommend` | `routes/search.py` | `POST /cbir/search` |
| `MonitorAiController` | `routes/health.py` | `GET /health` |

---

## 7. Checklist untuk Developer ML

Saat menambahkan endpoint baru:

- [ ] Buat route handler di `routes/<kategori>.py`
- [ ] Buat service logic di `services/<nama>_engine.py`
- [ ] Tambahkan ke `/health` response
- [ ] Informasikan tim web untuk:
  - [ ] Update `config/services.php` (sudah disiapkan, tinggal update path)
  - [ ] Implementasi method controller (sudah ada stub)
  - [ ] Aktifkan route POST di `routes/features.php`
- [ ] Tulis minimal 1 test di `tests/`
- [ ] Update tabel endpoint di dokumen ini
