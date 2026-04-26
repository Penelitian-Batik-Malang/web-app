# Panduan Integrasi Pewarnaan dengan Backend API

## Ringkasan Perubahan

Fitur pewarnaan batik telah diintegrasikan dengan backend API Anda. Berikut adalah dokumentasi lengkap tentang implementasi dan konfigurasi yang diperlukan.

---

## 🔧 Konfigurasi yang Diperlukan

### 1. Update File `.env`

Tambahkan konfigurasi backend API ke file `.env`:

```env
# Backend API Configuration
ML_API_BASE_URL=http://localhost:5000/api
ML_API_PALETTE_EXTRACT_PATH=/palette/extract
ML_API_RECOLOR_PATH=/recolor
```

**Penjelasan:**

- `ML_API_BASE_URL`: Base URL backend API Anda (sesuaikan dengan URL backend yang sedang berjalan)
- `ML_API_PALETTE_EXTRACT_PATH`: Endpoint untuk extract color palette (default: `/palette/extract`)
- `ML_API_RECOLOR_PATH`: Endpoint untuk recolor gambar (default: `/recolor`)

### 2. Konfigurasi di `config/services.php`

Sudah terupdate otomatis dengan mendukung endpoint:

- `palette_extract`: Extract warna dari gambar
- `recolor`: Recolor batik dengan palette

---

## 🎯 Alur Kerja Pewarnaan

### Flow Diagram

```
User Input
    ↓
┌────────────────────────────┐
│ 1. Upload 2 Gambar:        │
│    - Gambar Batik Sumber   │
│    - Gambar Warna (Pallet) │
└────────────┬───────────────┘
             ↓
┌─────────────────────────────┐
│ 2. Preview (proses-gambar)  │
│    - Tampil Batik Original  │
│    - Tampil Pallet Warna    │
└────────────┬────────────────┘
             ↓
┌─────────────────────────────┐
│ 3. User Klik "Proses"       │
│    - AJAX ke /api/colorize  │
└────────────┬────────────────┘
             ↓
┌──────────────────────────────────┐
│ 4. Backend Process:              │
│    a. Extract Palette dari img   │
│    b. Call /api/palette/extract  │
│    c. Recolor batik              │
│    d. Call /api/recolor          │
│    e. Return hasil ke frontend   │
└────────────┬─────────────────────┘
             ↓
┌─────────────────────────────┐
│ 5. Display Hasil:           │
│    - Gambar Hasil Colorize  │
│    - Processing Time        │
│    - Palette Colors Used    │
└─────────────────────────────┘
```

---

## 📝 Perubahan File

### 1. `app/Http/Controllers/MLController.php`

**Method Baru:**

- `colorizePalet()`: Handle pewarnaan dengan flow lengkap
- `base64ToImageFile()`: Helper untuk convert base64 ke image

**Logic:**

1. Validasi input (batik_image, color_image - kedua-duanya base64)
2. Extract palette dari color_image yang di-upload
3. Recolor batik image dengan palette yang di-extract
4. Return hasil (URL gambar, processing time, palette used)

### 2. `config/services.php`

**Tambahan Endpoint:**

```php
'palette_extract' => env('ML_API_PALETTE_EXTRACT_PATH', '/palette/extract'),
'recolor' => env('ML_API_RECOLOR_PATH', '/recolor'),
```

### 3. `resources/views/pages/pewarnaan-pallet-warna.blade.php`

**Perubahan Utama:**

- Section 1: Form upload gambar batik sumber (max 10MB) dengan drag & drop
- Section 2: Form upload gambar warna/pallet (max 1MB) dengan drag & drop
- Kedua file di-convert ke base64 sebelum dikirim
- Form validation sebelum submit

**Form Fields:**

- `batik_source` (file input) → convert ke `batik_image` (base64)
- `color_image` (file input) → convert ke `color_image` (base64)

### 4. `resources/views/pages/pewarnaanPalletNet/proses-gambar.blade.php`

**Perubahan Utama:**

- Tampil batikImage dari base64 (input user, bukan dari database)
- Add button "Proses Gambar" untuk trigger API call
- Add loading indicator saat processing
- Add result container untuk tampil hasil
- Add error handler dengan pesan informatif
- Add JavaScript untuk handle AJAX request dengan 2 gambar

**JavaScript Functions:**

- `handleColorize()`: Main function untuk trigger API call dengan batik_image + color_image
- Display hasil (gambar, processing time, palette colors)

### 5. `routes/web.php`

**Route Perubahan:**

```php
Route::post('/pewarnaan/palet/proses', function ($request) {
    // Validasi batik_image dan color_image (bukan batik_id)
    // Pass keduanya ke view proses-gambar
})->name('pewarnaan.palet.proses');
```

Route API:

```php
Route::post('/api/colorize/palet', [App\Http\Controllers\MLController::class, 'colorizePalet'])
    ->name('api.colorize.palet');
```

---

## 🧪 Testing

### 1. Setup Backend API

Pastikan backend API running di port yang dikonfigurasi:

```bash
# Backend harus running dan accessible
http://localhost:5000/api/health
```

### 2. Test Step-by-Step

#### Step 1: Kunjungi Halaman Pewarnaan

```
http://localhost:8000/pewarnaan/palet
```

#### Step 2: Pilih Batik & Upload Gambar Warna

- Upload gambar batik sumber (format: JPG, PNG, max 10MB)
    - Section 1 dengan drag & drop support
    - Preview akan muncul setelah upload
- Upload gambar warna/pallet (format: JPG, PNG, max 1MB)
    - Section 2 dengan drag & drop support
    - Preview akan muncul setelah upload

#### Step 3: Lihat Preview

- Klik "Proses Gambar"
- Halaman menampilkan preview batik original + pallet warna

#### Step 4: Proses Pewarnaan

- Klik tombol "Proses Gambar" di halaman preview
- Tunggu loading selesai (spinner akan muncul)
- Hasil pewarnaan akan ditampilkan di section "Hasil Pewarnaan"
- Lihat processing time dan palette colors yang digunakan

### 3. Testing dengan cURL

Jika ingin test API directly:

```bash
# 1. Extract Palette
curl -X POST http://localhost:5000/api/palette/extract \
  -F "image=@/path/to/color_image.jpg" \
  -F "method=kmeans" \
  -F "n_colors=6"

# Response:
# {
#   "success": true,
#   "palettes": {
#     "kmeans": [{"r": 255, "g": 0, "b": 0}, ...]
#   }
# }

# 2. Recolor
curl -X POST http://localhost:5000/api/recolor \
  -F "image=@/path/to/batik.jpg" \
  -F 'palette=[{"r": 255, "g": 0, "b": 0}]' \
  -F "white_threshold=150"

# Response:
# {
#   "success": true,
#   "result_image_url": "/uploads/results/result_20260422_143025.jpg",
#   "processing_time_ms": 523.45
# }
```

---

## 📱 Troubleshooting

### Error: "Model AI belum terhubung"

**Penyebab:** `ML_API_BASE_URL` tidak dikonfigurasi atau backend tidak running

**Solusi:**

1. Check file `.env`, pastikan `ML_API_BASE_URL` sudah ada dan benar
2. Pastikan backend API running
3. Test connectivity: `curl http://localhost:5000/api/health`

### Error: "Gagal extract palette dari gambar warna"

**Penyebab:**

- Gambar warna tidak memiliki warna yang jelas
- Format gambar tidak didukung
- File terlalu besar

**Solusi:**

1. Upload gambar dengan warna yang lebih jelas
2. Gunakan format JPG atau PNG
3. Pastikan file size < 1MB

### Error: "Gagal melakukan recoloring pada batik"

**Penyebab:**

- Backend timeout
- Batik tidak memiliki gambar
- Error di backend API

**Solusi:**

1. Check backend logs
2. Pastikan batik memiliki main image
3. Increase timeout di MLController jika diperlukan

### Gambar Hasil Tidak Muncul

**Penyebab:**

- URL gambar hasil salah
- CORS issue
- Backend tidak serve static files

**Solusi:**

1. Check browser console untuk network error
2. Pastikan backend serve uploads folder
3. Check `ML_API_BASE_URL` di config

---

## 🔐 Security Notes

1. **File Upload Validation:**
    - Max file size: 1MB untuk color image
    - Accepted formats: JPG, JPEG, PNG, WebP
    - Validation di frontend dan backend

2. **Base64 Handling:**
    - Color image di-kirim sebagai base64 string
    - Size bisa lebih besar dari binary, pastikan `post_max_size` cukup

3. **CSRF Protection:**
    - Token CSRF di-include otomatis dari meta tag
    - Endpoint `/api/colorize/palet` dilindungi CSRF

---

## 🚀 Deployment Production

### 1. Backend API Configuration

Update `.env` dengan production URL:

```env
ML_API_BASE_URL=https://your-backend-domain.com/api
```

### 2. Nginx Configuration (Optional)

Jika backend di domain berbeda, configure CORS:

```nginx
# Backend nginx config
add_header 'Access-Control-Allow-Origin' '*';
add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
```

### 3. Upload Folder Permission

Pastikan folder `uploads/` di backend punya write permission:

```bash
chmod -R 755 uploads/
```

---

## 📚 API Reference

### Endpoint: POST /api/colorize/palet

**Request:**

```json
{
    "batik_image": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
    "color_image": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Response (Success):**

```json
{
    "success": true,
    "result": {
        "result_image_url": "/uploads/results/result_20260422_143025_abc12345.jpg",
        "result_image_path": "results/result_20260422_143025_abc12345.jpg",
        "processing_time_ms": 523.45,
        "palette_used": [
            { "r": 255, "g": 0, "b": 0 },
            { "r": 0, "g": 255, "b": 0 }
        ]
    }
}
```

**Response (Error):**

```json
{
    "success": false,
    "message": "Error description here"
}
```

---

## 📋 Checklist Setup

- [ ] Update `.env` dengan `ML_API_BASE_URL`
- [ ] Backend API running dan accessible
- [ ] Test connectivity ke backend API
- [ ] Kunjungi `/pewarnaan/palet` dan test full flow
- [ ] Check browser console untuk error
- [ ] Check Laravel logs: `storage/logs/laravel.log`
- [ ] Check backend API logs

---

## 📞 Support

Jika ada error atau tidak jalan:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console (F12 → Console)
3. Check backend API logs
4. Verify `.env` configuration
5. Test backend API dengan cURL (lihat Testing section)

---

**Updated:** April 22, 2026
**Version:** 1.0.0
