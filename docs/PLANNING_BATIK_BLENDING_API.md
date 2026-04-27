# Batik Blending Web App — Planning Document
> Dokumen ini adalah instruksi lengkap untuk implementasi FastAPI + Web berdasarkan hasil diskusi.
> Paste dokumen ini sebagai context ke AI coding assistant (Vibe Code / Cursor / Copilot).

---

## Context & Stack

- **Project**: Batik Blending Web App menggunakan Fashionpedia Instance Segmentation
- **Backend**: FastAPI (Python 3.7, TensorFlow 1.15)
- **Frontend**: Bebas (React / Vue / Vanilla JS)
- **ML Model**: Fashionpedia ResNet-50 FPN (Attribute Mask R-CNN)
- **Environment**: venv sudah ada di `D:\laragon\www\fashionpedia\venv`

### Install tambahan yang dibutuhkan
```powershell
cd D:\laragon\www\fashionpedia
venv\Scripts\activate
pip install fastapi uvicorn python-multipart pillow
```

---

## Struktur Folder

```
D:\laragon\www\fashionpedia\
├── venv\                        ← sudah ada, pakai ini
├── tpu\                         ← repo tensorflow/tpu, sudah ada
├── checkpoints\
│   └── fashionpedia-r50-fpn\   ← sudah ada
├── api\                         ← BUAT BARU
│   ├── main.py                  ← FastAPI entry point
│   ├── inference.py             ← wrapper inference Fashionpedia
│   ├── blending.py              ← fungsi multiply blending
│   └── sessions\                ← folder session per user (auto-created)
│       └── {session_id}\
│           ├── fashion.jpg      ← foto asli
│           ├── result.npy       ← output inference
│           └── current.jpg      ← foto hasil blend terkini
└── web\                         ← BUAT BARU (frontend)
    ├── index.html
    ├── app.js
    └── style.css
```

---

## Label Configuration (Final)

```python
UPPER_BODY_IDS = {1, 2, 3, 4, 5, 6, 10, 11, 12, 13}
PART_IDS_BLENDING = {28, 29, 30, 31, 32, 33, 34}

LABEL_MAP = {
    1:  'shirt, blouse',
    2:  'top, t-shirt, sweatshirt',
    3:  'sweater',
    4:  'cardigan',
    5:  'jacket',
    6:  'vest',
    10: 'coat',
    11: 'dress',
    12: 'jumpsuit',
    13: 'cape',
    28: 'hood',
    29: 'collar',
    30: 'lapel',
    31: 'epaulette',
    32: 'sleeve',
    33: 'pocket',
    34: 'neckline',
}

# Warna overlay per part untuk visualisasi di web (RGBA)
PART_COLORS = {
    'body'     : [128, 128, 128, 128],
    'sleeve'   : [255,  80,  80, 128],
    'collar'   : [ 80, 160, 255, 128],
    'lapel'    : [ 80, 200,  80, 128],
    'hood'     : [255, 180,  50, 128],
    'pocket'   : [180,  80, 255, 128],
    'neckline' : [255, 255,  80, 128],
    'epaulette': [ 80, 220, 220, 128],
}
```

---

## API Endpoints

### `POST /inference`

**Input**: `multipart/form-data { image: file }`

**Proses**:
1. Simpan image ke `sessions/{uuid}/fashion.jpg`
2. Copy ke `sessions/{uuid}/current.jpg`
3. Jalankan inference Fashionpedia via **subprocess** (wajib, bukan import langsung)
4. Output inference disimpan ke `sessions/{uuid}/result.npy`
5. Load `result.npy`, decode semua mask, hitung bbox per part
6. Encode mask per part sebagai base64 PNG RGBA untuk overlay di web

**Response**:
```json
{
  "session_id": "uuid-string",
  "image_size": { "w": 275, "h": 183 },
  "parts": {
    "body": {
      "bbox": { "x": 100, "y": 59, "w": 103, "h": 110 },
      "mask_b64": "base64 PNG RGBA mask image",
      "area": 6735
    },
    "sleeve": [
      {
        "index": 0,
        "bbox": { "x": 101, "y": 69, "w": 23, "h": 51 },
        "mask_b64": "base64 PNG RGBA mask image",
        "area": 862,
        "score": 0.997
      },
      {
        "index": 1,
        "bbox": { "x": 175, "y": 66, "w": 30, "h": 105 },
        "mask_b64": "base64 PNG RGBA mask image",
        "area": 1763,
        "score": 0.986
      }
    ],
    "collar": [ ... ],
    "lapel":  [ ... ]
  }
}
```

> Hanya part yang terdeteksi dengan score >= 0.3 yang dimasukkan ke response.

---

### `POST /blend`

**Input**: `multipart/form-data`:
```
session_id     : str
part           : str   ← "body" / "sleeve" / "collar" / dll
instance_index : int   ← index jika ada lebih dari 1 (misal 2 sleeve), default 0
batik          : file  ← gambar batik sudah di-crop dari canvas web
```

**Proses**:
1. Load `sessions/{session_id}/current.jpg`
2. Load `sessions/{session_id}/result.npy`
3. Decode mask untuk `part` + `instance_index` yang diminta
4. Resize mask ke ukuran `current.jpg`
5. Jalankan multiply blending (tile+crop, **bukan resize batik**)
6. Simpan hasil ke `current.jpg` (overwrite)
7. Return gambar hasil sebagai base64

**Response**:
```json
{
  "image_b64": "base64 JPEG hasil blend"
}
```

---

### `POST /reset`

**Input**: `application/json { "session_id": "uuid-string" }`

**Proses**: Copy `fashion.jpg` ke `current.jpg` (hapus semua blend)

**Response**:
```json
{
  "image_b64": "base64 JPEG foto asli"
}
```

---

### `GET /session/{session_id}`

**Response**:
```json
{
  "session_id": "uuid-string",
  "current_image_b64": "base64 JPEG current.jpg",
  "parts_detected": ["body", "sleeve", "collar"],
  "parts_blended": ["sleeve"]
}
```

---

## Blending Logic (Final — Tidak Ada Resize)

```python
import cv2
import numpy as np

def multiply(mask, fashion_rgb, batik_rgb):
    """
    Multiply blending batik ke area fashion sesuai mask.
    
    Args:
        mask        : np.array (H,W) uint8 nilai 0/1
        fashion_rgb : np.array (H,W,3) RGB — current.jpg
        batik_rgb   : np.array (Hb,Wb,3) RGB — hasil crop canvas web
    
    Returns:
        result : np.array (H,W,3) RGB hasil blending
    
    Catatan:
        - Tidak ada resize batik → motif tidak menyempit
        - Batik di-tile jika lebih kecil dari bbox, lalu di-crop
        - Blend hanya pada piksel mask == 1, bukan seluruh bbox
    """
    y_indices, x_indices = np.where(mask == 1)
    if len(y_indices) == 0 or len(x_indices) == 0:
        return fashion_rgb

    y_min, y_max = y_indices.min(), y_indices.max()
    x_min, x_max = x_indices.min(), x_indices.max()
    bbox_h = y_max - y_min + 1
    bbox_w = x_max - x_min + 1

    mask_crop    = mask[y_min:y_max+1, x_min:x_max+1]
    fashion_crop = fashion_rgb[y_min:y_max+1, x_min:x_max+1]

    # Tile + crop batik (tidak resize → motif tidak menyempit)
    batik_h, batik_w = batik_rgb.shape[:2]
    tile_y = (bbox_h // batik_h) + 2
    tile_x = (bbox_w // batik_w) + 2
    batik_tiled  = np.tile(batik_rgb, (tile_y, tile_x, 1))
    batik_fitted = batik_tiled[:bbox_h, :bbox_w]

    # Shading map dari pencahayaan fashion asli
    fashion_gray_crop = cv2.cvtColor(fashion_crop, cv2.COLOR_RGB2GRAY)
    rata_pencahayaan  = np.mean(fashion_gray_crop[mask_crop == 1])
    shading_map       = fashion_gray_crop / (rata_pencahayaan + 1e-6)

    batik_float = batik_fitted.astype(float)
    for i in range(3):
        batik_float[:, :, i] *= shading_map
    batik_final = np.clip(batik_float, 0, 255).astype(np.uint8)

    result = fashion_rgb.copy()
    roi = result[y_min:y_max+1, x_min:x_max+1]
    roi[mask_crop == 1] = batik_final[mask_crop == 1]
    return result
```

---

## Catatan Penting untuk Implementasi

### 1. Inference WAJIB via Subprocess
Inference Fashionpedia tidak bisa di-import langsung ke FastAPI karena butuh:
- Working directory: `tpu/models/official/detection/`
- PYTHONPATH khusus
- TF 1.15 yang sudah di-patch

```python
import subprocess
import sys
import os

def run_inference(image_path: str, output_npy: str, output_html: str):
    PROJECT_DIR   = r"D:\laragon\www\fashionpedia"
    DETECTION_DIR = os.path.join(PROJECT_DIR, "tpu", "models", "official", "detection")
    TPU_MODELS    = os.path.join(PROJECT_DIR, "tpu", "models")
    EFFICIENTNET  = os.path.join(TPU_MODELS, "official", "efficientnet")
    HYPERPARAMS   = os.path.join(PROJECT_DIR, "tpu", "models", "hyperparameters")
    CHECKPOINT    = os.path.join(PROJECT_DIR, "checkpoints", "fashionpedia-r50-fpn", "model.ckpt")
    LABEL_MAP     = os.path.join(DETECTION_DIR, "projects", "fashionpedia", "dataset", "fashionpedia_label_map.csv")
    CONFIG_FILE   = os.path.join(DETECTION_DIR, "projects", "fashionpedia", "configs", "yaml", "r50fpn_amrcnn.yaml")
    INFERENCE_PY  = os.path.join(DETECTION_DIR, "inference_fashionpedia.py")

    env = os.environ.copy()
    env["PYTHONPATH"] = ";".join([DETECTION_DIR, TPU_MODELS, EFFICIENTNET, HYPERPARAMS])

    cmd = [
        sys.executable, INFERENCE_PY,
        "--model=attribute_mask_rcnn",
        "--image_size=640",
        f"--checkpoint_path={CHECKPOINT}",
        f"--label_map_file={LABEL_MAP}",
        f"--config_file={CONFIG_FILE}",
        f"--image_file_pattern={image_path}",
        f"--output_html={output_html}",
        "--max_boxes_to_draw=15",
        "--min_score_threshold=0.05",
        f"--output_file={output_npy}",
    ]

    result = subprocess.run(
        cmd,
        cwd=DETECTION_DIR,
        env=env,
        capture_output=True,
        text=True,
        timeout=300
    )
    return result.returncode == 0
```

### 2. Body Mask Harus Exclude Piksel Part
Saat blend body, piksel yang sudah jadi milik part (sleeve/collar/dll)
harus di-exclude dari mask body agar tidak tumpang tindih:

```python
mask_body_clean = mask_body.copy()
for label, items in masks_parts.items():
    for item in items:
        mask_body_clean = np.logical_and(
            mask_body_clean,
            np.logical_not(item['mask'])
        ).astype(np.uint8)
```

### 3. Mask Overlay di Web
- Kirim mask sebagai base64 PNG RGBA
- Warna per part sesuai `PART_COLORS` di atas
- Alpha 128 (50% transparan) untuk overlay
- Frontend overlay mask di atas foto menggunakan HTML Canvas

### 4. Session Cleanup
Tambahkan cleanup otomatis session yang lebih dari 2 jam agar storage tidak penuh:

```python
import time
import shutil

def cleanup_old_sessions(sessions_dir: str, max_age_hours: int = 2):
    now = time.time()
    for session_id in os.listdir(sessions_dir):
        session_path = os.path.join(sessions_dir, session_id)
        if os.path.isdir(session_path):
            age = now - os.path.getmtime(session_path)
            if age > max_age_hours * 3600:
                shutil.rmtree(session_path)
```

### 5. CORS
Aktifkan CORS di FastAPI untuk akses dari frontend:

```python
from fastapi.middleware.cors import CORSMiddleware

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)
```

---

## Frontend Behavior

### Alur User
```
1. Upload foto fashion
   → Tampilkan foto + overlay mask per part (semi-transparan, berwarna)

2. Hover part → highlight overlay part tersebut

3. Klik part → panel muncul:
   - Nama part (sleeve, collar, dll)
   - Info bounding box (ukuran referensi untuk canvas batik)
   - Pilih batik dari database / upload
   - Canvas batik dengan ukuran referensi bbox
   - Kontrol: Zoom, Pan, Rotate area batik
   - Tombol Apply

4. Klik Apply
   → Kirim POST /blend dengan batik hasil crop canvas
   → Loading indicator
   → Foto update langsung (live) — overlay part berubah

5. Lanjut ke part lain atau klik Reset untuk mulai ulang

6. Bisa apply ulang part yang sudah di-blend (overwrite)
```

### Canvas Batik
- Tampilkan batik penuh di canvas
- Kotak putih menunjukkan area crop yang akan dikirim ke API
- Ukuran kotak default = ukuran bbox part dari response `/inference`
- User bisa zoom, pan, rotate untuk atur motif
- Saat Apply → crop area dalam kotak putih → kirim ke `/blend`

---

## Cara Jalankan API

```powershell
cd D:\laragon\www\fashionpedia
venv\Scripts\activate
uvicorn api.main:app --reload --host 0.0.0.0 --port 8000
```

API tersedia di: `http://localhost:8000`
Docs otomatis di: `http://localhost:8000/docs`
