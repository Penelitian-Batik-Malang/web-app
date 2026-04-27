# Panduan Deployment — S3 Object Storage IDCloudHost

> Dokumentasi ini menjelaskan cara setup S3 Object Storage IDCloudHost
> di environment production/staging.

---

## 1. Cara Kerja (Bukan Download!)

Integrasi S3 ini **TIDAK** men-download gambar ke server. Arsitekturnya:

```
Browser → GET /s3-image/{id} → Laravel (proxy) → S3 IDCloudHost → stream ke browser
```

- **Gambar tidak disimpan di server** — hanya di-stream melalui Laravel
- **Browser cache 24 jam** — setelah request pertama, browser pakai cache
- **Database hanya simpan metadata** — path/key S3, bukan file gambar itu sendiri
- **Sync command hanya buat record DB** — tidak download file apapun

---

## 2. Prasyarat Deployment

### 2.1 Package PHP

Pastikan package S3 sudah terinstall:

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### 2.2 PHP Extensions

Pastikan extension berikut aktif di server production:
- `ext-curl` (untuk HTTP request ke S3)
- `ext-openssl` (untuk HMAC signing)
- `ext-mbstring` (standar Laravel)

---

## 3. Konfigurasi Environment

### 3.1 File `.env` di Server Production

Tambahkan variabel berikut ke `.env` di server deployment:

```env
# ── IDCloudHost S3 Object Storage ─────────────────────
IDC_S3_KEY=DYGFOH2Y00PFLT4XLQ3P
IDC_S3_SECRET=5LPJmiIfaLtQkx9GjKX1BmvopoYFNdqviJ0MToX2
IDC_S3_REGION=us-east-1
IDC_S3_ENDPOINT=https://is3.cloudhost.id
IDC_S3_BATIK_BUCKET=batik-signature-gdrive
```

> ⚠️ **PENTING**: Jangan commit `.env` ke repository! Gunakan `.env.example` untuk template.

### 3.2 Config Cache

Setelah set `.env`, clear dan re-cache config:

```bash
php artisan config:clear
php artisan config:cache
```

---

## 4. Database Setup

### 4.1 Jalankan Migration

```bash
php artisan migrate
```

Migration akan menambah kolom `storage_disk` dan `s3_key` ke tabel `batik_images`.

### 4.2 Sync Data dari S3

```bash
# Preview dulu (tidak ada perubahan DB)
php artisan batik:sync-s3 --dry-run

# Jika OK, execute
php artisan batik:sync-s3
```

### 4.3 Re-sync (jika ada gambar baru di S3)

```bash
# Hanya sync folder baru (skip yang sudah ada)
php artisan batik:sync-s3

# Force re-sync semua (hapus & buat ulang record S3)
php artisan batik:sync-s3 --force
```

---

## 5. Verifikasi

### 5.1 Cek Koneksi S3

```bash
# Pastikan bisa list folder
php artisan batik:sync-s3 --dry-run
```

Jika error "Gagal terhubung ke S3", cek:
- Credentials di `.env` benar
- Server bisa akses `https://is3.cloudhost.id` (firewall/network)
- Extension `ext-curl` aktif

### 5.2 Cek Database

```bash
php artisan tinker
> App\Models\BatikImage::where('storage_disk', 's3-batik')->count();
# Harus: 1213 (atau lebih jika ada gambar baru)
```

### 5.3 Cek Gambar di Browser

Buka `/galeri` — thumbnail harus tampil dari S3 proxy.

---

## 6. Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|-------|
| Gambar tidak muncul | Config cache stale | `php artisan config:clear` |
| "Gagal terhubung ke S3" | Firewall block HTTPS | Buka akses ke `is3.cloudhost.id:443` |
| Gambar lambat | Pertama kali load (no cache) | Normal, cache 24 jam setelahnya |
| 404 pada `/s3-image/{id}` | Gambar dihapus dari S3 | Re-sync: `php artisan batik:sync-s3 --force` |
| Sync hanya 0 folder | Bucket kosong/salah nama | Cek `IDC_S3_BATIK_BUCKET` di `.env` |

---

## 7. Arsitektur Multi-Bucket (Masa Depan)

Untuk fitur lain yang pakai S3 bucket berbeda:

### 7.1 Tambah ke `.env`

```env
IDC_S3_FEATURES_BUCKET=nama-bucket-baru
```

### 7.2 Uncomment di `config/filesystems.php`

```php
// Uncomment block 's3-features' dan set bucket env
's3-features' => [
    'driver' => 's3',
    'key'    => env('IDC_S3_KEY'),     // Credentials sama
    'secret' => env('IDC_S3_SECRET'),
    'region' => env('IDC_S3_REGION', 'us-east-1'),
    'bucket' => env('IDC_S3_FEATURES_BUCKET'),
    'endpoint' => env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id'),
    'use_path_style_endpoint' => true,
    'throw' => false,
],
```

### 7.3 Gunakan di Controller

```php
Storage::disk('s3-features')->files('folder-name');
Storage::disk('s3-features')->get('path/to/file.jpg');
```

---

## 8. Checklist Deployment

- [ ] `.env` di server sudah diisi credentials IDCloudHost
- [ ] `composer install` sudah dijalankan (flysystem-aws-s3-v3 terinstall)
- [ ] `php artisan migrate` sudah dijalankan
- [ ] `php artisan config:cache` sudah dijalankan
- [ ] `php artisan batik:sync-s3` sudah dijalankan
- [ ] Buka `/galeri` di browser → gambar tampil ✓
- [ ] Buka `/galeri/{id}` detail → semua variasi tampil ✓
