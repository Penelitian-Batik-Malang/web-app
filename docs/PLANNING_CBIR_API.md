# CBIR Batik API — Planning Document
> Instruksi lengkap untuk implementasi FastAPI CBIR + Blending.
> Paste dokumen ini sebagai context ke AI coding assistant (Vibe Code / Cursor / Copilot).

---

## Context & Stack

- **Project**: CBIR (Content-Based Image Retrieval) Batik menggunakan Fashionpedia + KMeans CIELAB
- **Backend**: FastAPI (Python 3.7, TensorFlow 1.15)
- **Environment**: venv sudah ada di `D:\laragon\www\fashionpedia\venv`
- **Metode jarak**: Euclidean + Hungarian algorithm (satu-satunya metode yang dipakai)
- **Kluster**: 3 (fixed, sesuai NPZ yang sudah pre-computed)
- **Top-k**: 5, 10, 15

### Install tambahan
```powershell
cd D:\laragon\www\fashionpedia
venv\Scripts\activate
pip install fastapi uvicorn python-multipart pillow scipy scikit-learn
```

---

## Struktur Folder (Final, Tidak Ambigu)

```
D:\laragon\www\
│
├── fashionpedia\                        ← project ML + API
│   ├── venv\                            ← sudah ada
│   ├── tpu\                             ← repo tensorflow/tpu sudah ada + sudah di-patch
│   ├── checkpoints\
│   │   └── fashionpedia-r50-fpn\        ← sudah ada
│   ├── data\
│   │   └── batik_skenario_3_warna.npz   ← PRE-COMPUTED fitur warna batik (1244 item, k=3)
│   └── api\                             ← folder API FastAPI
│       ├── __init__.py
│       ├── main.py                      ← sudah ada, perlu update
│       ├── inference.py                 ← sudah ada
│       ├── blending.py                  ← sudah ada
│       ├── cbir.py                      ← BUAT BARU
│       ├── utils.py                     ← sudah ada
│       └── sessions\                    ← auto-created
│           └── {session_id}\
│               ├── fashion.jpg
│               ├── result.npy
│               ├── current.jpg
│               ├── inference.html
│               ├── meta.json
│               └── batik_upload.jpg
│
└── Data_Untuk_Warna_Dominan\            ← folder gambar batik (DI LUAR fashionpedia)
    ├── abu-abu\
    ├── biru\
    ├── coklat\
    ├── hijau\
    ├── hitam\
    ├── merah\
    └── ungu\
```

> **Penting**: Folder batik `Data_Untuk_Warna_Dominan` berada di `D:\laragon\www\`,
> **bukan** di dalam folder `fashionpedia`. Ini satu-satunya lokasi gambar batik.

---

## Data NPZ Batik (sudah tersedia)

File: `fashionpedia/data/batik_skenario_3_warna.npz`

```
Keys     : filename, label, fitur_warna
Total    : 1244 item batik
Kluster  : 3 (fixed)
Shape    : fitur_warna (1244, 3, 3) — 1244 batik, 3 centroid, 3 nilai LAB per centroid
Labels   : abu-abu, biru, coklat, hijau, hitam, merah, ungu
```

> **Penting**: Field `filename` di NPZ berisi path Colab
> (`/content/drive/MyDrive/...`). Saat load di API lokal,
> path harus di-remap ke path lokal Windows.

### Cara remap path saat load NPZ

```python
import numpy as np
import os
from pathlib import Path

BATIK_ROOT_LOCAL = r"D:\laragon\www\Data_Untuk_Warna_Dominan"
BATIK_ROOT_COLAB = "/content/drive/MyDrive/Data Penelitian Batik 2025/Data_Untuk_Warna_Dominan"

def load_batik_database(npz_path: Path) -> dict:
    data = np.load(str(npz_path), allow_pickle=True)
    filenames   = data['filename']    # shape (1244,)
    labels      = data['label']       # shape (1244,)
    fitur_warna = data['fitur_warna'] # shape (1244, 3, 3)

    # Remap path dari Colab ke lokal Windows
    filenames_local = []
    for f in filenames:
        f_local = f.replace(BATIK_ROOT_COLAB, BATIK_ROOT_LOCAL)
        f_local = f_local.replace('/', os.sep)
        filenames_local.append(f_local)

    return {
        "filenames"  : filenames_local,
        "labels"     : labels.tolist(),
        "fitur_warna": fitur_warna,
    }
```

---

## Label Configuration (Final)

```python
# Untuk KMeans (ekstraksi warna fashion) — union semua upper body
UPPER_BODY_IDS = {1, 2, 3, 4, 5, 6, 10, 11, 12, 13}

# Untuk blending per-part
PART_IDS_BLENDING = {28, 29, 30, 31, 32, 33, 34}
# hood, collar, lapel, epaulette, sleeve, pocket, neckline

LABEL_MAP = {
    1:'shirt, blouse', 2:'top, t-shirt, sweatshirt', 3:'sweater',
    4:'cardigan', 5:'jacket', 6:'vest', 10:'coat', 11:'dress',
    12:'jumpsuit', 13:'cape',
    28:'hood', 29:'collar', 30:'lapel', 31:'epaulette',
    32:'sleeve', 33:'pocket', 34:'neckline',
}
```

---

## Perbedaan Mask untuk KMeans vs Blending

| | KMeans (CBIR) | Blending |
|--|--|--|
| Mask yang dipakai | `mask_union` — union OR semua upper body **langsung** | `mask_body_clean` — union upper body **minus** piksel part |
| Part mask | Tidak dipakai | Dipakai, blend di atas body |
| Tujuan | Ekstraksi warna dominan pakaian | Tempel motif batik tanpa tumpang tindih |

```python
# KMeans → pakai mask_union langsung (tidak dikurangi part)
pixels = konversi_CIELAB(fashion_rgb, mask_union)
centroids_query = ekstrak_warna_dominan(pixels, kluster=3)

# Blending → body harus exclude piksel part agar tidak tumpang tindih
mask_body_clean = mask_union.copy()
for label, items in masks_parts.items():
    for item in items:
        mask_body_clean = np.logical_and(
            mask_body_clean,
            np.logical_not(item['mask'])
        ).astype(np.uint8)
```

---

## Fungsi Core

### konversi_CIELAB
```python
import cv2
import numpy as np

def konversi_CIELAB(image: np.ndarray, mask: np.ndarray) -> np.ndarray:
    """
    image: np.array (H,W,3) RGB
    mask : np.array (H,W) uint8 nilai 0/1
    """
    pixels = image[mask == 1]
    pixels = pixels.astype(np.float32) / 255.0
    pixels = cv2.cvtColor(pixels.reshape(-1, 1, 3), cv2.COLOR_RGB2LAB)
    return pixels.reshape(-1, 3)
```

### ekstrak_warna_dominan
```python
from sklearn.cluster import KMeans

def ekstrak_warna_dominan(pixels: np.ndarray, kluster: int = 3) -> np.ndarray:
    if len(pixels) < kluster:
        return np.zeros((kluster, 3), dtype=np.float32)
    kmeans = KMeans(n_clusters=kluster, random_state=42, n_init=10)
    kmeans.fit(pixels)
    return kmeans.cluster_centers_.astype(np.float32)
```

### euclidean_hungarian (satu-satunya metode jarak)
```python
from scipy.optimize import linear_sum_assignment

def euclidean_hungarian(query_centroids: np.ndarray, db_centroids: np.ndarray) -> float:
    """
    query_centroids : np.array (3, 3) — centroid query fashion
    db_centroids    : np.array (3, 3) — centroid batik dari NPZ
    """
    C = len(query_centroids)
    cost_matrix = np.zeros((C, C))
    for i in range(C):
        for j in range(C):
            cost_matrix[i][j] = float(np.linalg.norm(query_centroids[i] - db_centroids[j]))
    row_idx, col_idx = linear_sum_assignment(cost_matrix)
    return float(cost_matrix[row_idx, col_idx].sum() / C)
```

### multiply_blend (Final — pakai resize ke ukuran bounding box mask)
```python
import cv2
import numpy as np

def multiply_blend(mask: np.ndarray, fashion_rgb: np.ndarray, batik_rgb: np.ndarray) -> np.ndarray:
    """
    Multiply blending batik ke area fashion sesuai mask.

    Args:
        mask        : np.array (H,W) uint8 nilai 0/1
        fashion_rgb : np.array (H,W,3) RGB — current.jpg
        batik_rgb   : np.array (H,W,3) RGB — gambar batik

    Catatan penting:
        - Batik di-RESIZE ke ukuran bounding box mask (FINAL, bukan tile+crop)
        - shading_map di-clip 0.0-2.0 agar tidak overexpose
        - Blend hanya pada piksel mask == 1, bukan seluruh bbox
    """
    if mask.dtype != np.uint8:
        mask = (mask > 0).astype(np.uint8)
    mask_bool = mask > 0
    if not mask_bool.any():
        return fashion_rgb.copy()

    y_indices, x_indices = np.where(mask_bool)
    y_min, y_max = y_indices.min(), y_indices.max()
    x_min, x_max = x_indices.min(), x_indices.max()
    roi = fashion_rgb[y_min:y_max + 1, x_min:x_max + 1]
    mask_crop = mask_bool[y_min:y_max + 1, x_min:x_max + 1].astype(np.uint8)

    bbox_h, bbox_w = mask_crop.shape

    # Resize batik ke ukuran bounding box (FINAL)
    batik_fitted = cv2.resize(batik_rgb, (bbox_w, bbox_h), interpolation=cv2.INTER_LANCZOS4)

    fashion_gray_crop = cv2.cvtColor(roi, cv2.COLOR_RGB2GRAY)
    rata_pencahayaan = np.mean(fashion_gray_crop[mask_crop == 1])
    shading_map = fashion_gray_crop / (rata_pencahayaan + 1e-6)
    shading_map = np.clip(shading_map, 0.0, 2.0)

    batik_float = batik_fitted.astype(float)
    for i in range(3):
        batik_float[:, :, i] *= shading_map

    batik_final = np.clip(batik_float, 0, 255).astype(np.uint8)

    result = fashion_rgb.copy()
    roi_result = result[y_min:y_max + 1, x_min:x_max + 1]
    roi_result[mask_crop == 1] = batik_final[mask_crop == 1]
    result[y_min:y_max + 1, x_min:x_max + 1] = roi_result
    return result
```

---

## API Endpoints

### `POST /inference` — update, tambah CBIR

**Input**: `multipart/form-data { image: file }`

**Proses**:
1. Generate `session_id` (UUID)
2. Simpan image ke `sessions/{session_id}/fashion.jpg` + `current.jpg`
3. Jalankan inference Fashionpedia via **subprocess**
4. Load `result.npy`, decode semua mask
5. Bangun `mask_union` dari semua upper body (untuk KMeans)
6. Jalankan KMeans pada piksel `mask_union` → `centroids_query` (k=3, CIELAB)
7. Jalankan CBIR retrieval top-5, top-10, top-15 terhadap NPZ batik
8. Encode mask per part sebagai base64 PNG RGBA

**Response**:
```json
{
  "session_id": "uuid-string",
  "image_size": { "w": 640, "h": 480 },
  "parts": {
    "body": {
      "bbox": { "x": 100, "y": 59, "w": 103, "h": 110 },
      "mask_b64": "base64 PNG RGBA",
      "area": 6735
    },
    "sleeve": [
      {
        "index": 0,
        "bbox": { "x": 101, "y": 69, "w": 23, "h": 51 },
        "mask_b64": "base64 PNG RGBA",
        "area": 862,
        "score": 0.997
      }
    ],
    "collar": [],
    "lapel": []
  },
  "cbir": {
    "query_centroids": [[88.6, -3.1, 4.4], [38.1, 4.8, -1.9], [73.8, -2.9, 3.2]],
    "top_5": [
      {
        "rank": 1,
        "filename": "D:\\laragon\\www\\Data_Untuk_Warna_Dominan\\abu-abu\\file.jpg",
        "label": "abu-abu",
        "jarak": 2.3456,
        "thumbnail_b64": "base64 JPEG thumbnail 64x64"
      }
    ],
    "top_10": ["..."],
    "top_15": ["..."]
  }
}
```

---

### `POST /blend` — tidak berubah

Blend batik dari **upload file bebas** (untuk menu pilih batik bebas).

**Input**: `multipart/form-data`
```
session_id     : str
part           : str   ← "body" / "sleeve" / "collar" / dll
instance_index : int   ← default 0
batik          : file  ← upload file gambar batik bebas
```

**Response**: `{ "image_b64": "base64 JPEG hasil blend" }`

---

### `POST /blend-from-cbir` — BUAT BARU

Blend batik dari **hasil rekomendasi CBIR** (untuk menu terapkan dari rekomendasi).
Batik diambil langsung dari path lokal server, tidak perlu upload ulang.

**Input**: `multipart/form-data`
```
session_id     : str
part           : str   ← "body" / "sleeve" / "collar" / dll
instance_index : int   ← default 0
batik_filename : str   ← nilai field "filename" dari response /inference cbir.top_k[n]
```

> `batik_filename` adalah path lokal Windows dari response `/inference`
> field `cbir.top_5[n].filename` atau `top_10` atau `top_15`.

**Proses**:
1. Validasi `session_id`
2. Security check: `batik_filename` harus berawal dari `D:\laragon\www\Data_Untuk_Warna_Dominan`
3. Load gambar batik dari `batik_filename`
4. Decode mask sesuai `part` + `instance_index` dari `result.npy`
5. Jalankan `multiply_blend` (logika sama persis dengan `/blend`)
6. Overwrite `current.jpg`, update meta

**Response**: `{ "image_b64": "base64 JPEG hasil blend" }`

---

### `POST /reset` — tidak berubah

**Input**: `application/json { "session_id": "uuid" }`
**Response**: `{ "image_b64": "base64 JPEG foto asli" }`

---

### `GET /session/{session_id}` — tidak berubah

**Response**:
```json
{
  "session_id": "uuid",
  "current_image_b64": "base64 JPEG",
  "parts_detected": ["body", "sleeve", "collar"],
  "parts_blended": ["sleeve"]
}
```

---

## Perbedaan `/blend` vs `/blend-from-cbir`

| | `/blend` | `/blend-from-cbir` |
|--|--|--|
| Sumber batik | Upload file dari user (bebas) | Path lokal dari hasil CBIR |
| Input batik | `batik: UploadFile` | `batik_filename: str` |
| Kapan dipakai | Menu "pilih batik bebas" | Menu "terapkan dari rekomendasi CBIR" |
| Logic blending | Sama persis | Sama persis |

---

## File yang Perlu Dibuat/Diupdate

| File | Status | Action |
|------|--------|--------|
| `api/cbir.py` | Belum ada | **BUAT BARU** |
| `api/main.py` | Sudah ada | **UPDATE**: tambah CBIR di `/inference`, tambah endpoint `/blend-from-cbir`, update startup |
| `api/blending.py` | Sudah ada | Tidak perlu diubah |
| `api/inference.py` | Sudah ada | Tidak perlu diubah |
| `api/utils.py` | Sudah ada | Tidak perlu diubah |

---

## Isi `api/cbir.py` (lengkap)

```python
import base64
import io
import os
from pathlib import Path

import cv2
import numpy as np
from PIL import Image
from scipy.optimize import linear_sum_assignment
from sklearn.cluster import KMeans

BATIK_ROOT_LOCAL = r"D:\laragon\www\Data_Untuk_Warna_Dominan"
BATIK_ROOT_COLAB = "/content/drive/MyDrive/Data Penelitian Batik 2025/Data_Untuk_Warna_Dominan"


def load_batik_database(npz_path: Path) -> dict:
    data = np.load(str(npz_path), allow_pickle=True)
    filenames   = data['filename']
    labels      = data['label']
    fitur_warna = data['fitur_warna']  # (1244, 3, 3)

    filenames_local = []
    for f in filenames:
        f_local = f.replace(BATIK_ROOT_COLAB, BATIK_ROOT_LOCAL)
        f_local = f_local.replace('/', os.sep)
        filenames_local.append(f_local)

    return {
        "filenames"  : filenames_local,
        "labels"     : labels.tolist(),
        "fitur_warna": fitur_warna,
    }


def euclidean_hungarian(query: np.ndarray, db: np.ndarray) -> float:
    C = len(query)
    cost_matrix = np.zeros((C, C))
    for i in range(C):
        for j in range(C):
            cost_matrix[i][j] = float(np.linalg.norm(query[i] - db[j]))
    row_idx, col_idx = linear_sum_assignment(cost_matrix)
    return float(cost_matrix[row_idx, col_idx].sum() / C)


def retrieve_batik(query_centroids: np.ndarray, db: dict, top_k_list: list = [5, 10, 15]) -> dict:
    jarak_list = []
    for i in range(len(db["filenames"])):
        jarak = euclidean_hungarian(query_centroids, db["fitur_warna"][i])
        jarak_list.append({
            "filename": db["filenames"][i],
            "label"   : db["labels"][i],
            "jarak"   : jarak,
        })
    jarak_list.sort(key=lambda x: x["jarak"])

    max_k = max(top_k_list)
    top_results = jarak_list[:max_k]
    for item in top_results:
        item["thumbnail_b64"] = _make_thumbnail_b64(item["filename"])

    hasil = {}
    for k in top_k_list:
        hasil[f"top_{k}"] = [
            {"rank": i + 1, **r} for i, r in enumerate(top_results[:k])
        ]
    return hasil


def extract_query_centroids(fashion_rgb: np.ndarray, mask_union: np.ndarray, kluster: int = 3) -> np.ndarray:
    """Ekstrak centroid warna dari area mask_union untuk query CBIR."""
    pixels = fashion_rgb[mask_union == 1]
    if len(pixels) < kluster:
        return np.zeros((kluster, 3), dtype=np.float32)
    pixels = pixels.astype(np.float32) / 255.0
    pixels_lab = cv2.cvtColor(pixels.reshape(-1, 1, 3), cv2.COLOR_RGB2LAB).reshape(-1, 3)
    kmeans = KMeans(n_clusters=kluster, random_state=42, n_init=10)
    kmeans.fit(pixels_lab)
    return kmeans.cluster_centers_.astype(np.float32)


def _make_thumbnail_b64(image_path: str, size: tuple = (64, 64)) -> str:
    try:
        with Image.open(image_path) as img:
            img = img.convert("RGB")
            img.thumbnail(size, Image.LANCZOS)
            buf = io.BytesIO()
            img.save(buf, format="JPEG", quality=85)
            return base64.b64encode(buf.getvalue()).decode("utf-8")
    except Exception:
        return ""
```

---

## Catatan Penting

### 1. Database batik di-load SEKALI saat startup
```python
@app.on_event("startup")
def startup_event() -> None:
    global BATIK_DB
    cleanup_old_sessions(max_age_hours=2)
    npz_path = PROJECT_DIR / "data" / "batik_skenario_3_warna.npz"
    if npz_path.exists():
        BATIK_DB = load_batik_database(npz_path)
        print(f"Database batik loaded: {len(BATIK_DB['filenames'])} item")
    else:
        print(f"WARNING: NPZ tidak ditemukan di {npz_path}")
```

### 2. Security check `batik_filename`
```python
batik_path = Path(batik_filename)
try:
    batik_path.resolve().relative_to(Path(BATIK_ROOT_LOCAL).resolve())
except ValueError:
    raise HTTPException(status_code=403, detail="batik_filename tidak valid")
```

### 3. CBIR hanya jalan jika `mask_union` ditemukan
Jika tidak ada upper body terdeteksi, field `cbir` di response `/inference` dikembalikan kosong `{}`.

---

## Cara Jalankan

```powershell
cd D:\laragon\www\fashionpedia
venv\Scripts\activate
uvicorn api.main:app --reload --host 0.0.0.0 --port 8000
```

- API: `http://localhost:8000`
- Docs: `http://localhost:8000/docs`
