# Dokumentasi Implementasi Berdasarkan `blueprint.txt`

Dokumen ini menjadi acuan teknis lanjutan implementasi aplikasi, terutama menu yang melibatkan model ML dan status progres terhadap isi `blueprint.txt`.

---

## 1) Ringkasan Arsitektur Saat Ini

- Aplikasi menggunakan Laravel (web routes + Blade + JS frontend).
- Integrasi ML diposisikan sebagai:
  - **Frontend UI** (popup/modal + upload/camera + result display),
  - **Backend proxy/controller** yang memanggil API ML eksternal,
  - **Konfigurasi endpoint terpusat** di `config/services.php`.

### File kunci yang sudah jadi template/pola reusable

- Konfigurasi endpoint ML:
  - `config/services.php` (`services.ml.base_url`, `services.ml.endpoints.`*)
- Integrasi API deteksi (backend):
  - `app/Http/Controllers/MLController.php`
- Komponen popup ML reusable (UI):
  - `resources/views/components/ml-detector.blade.php`
- Logic JS reusable untuk modal ML:
  - `public/js/ml-detector.js`
- Routing fitur deteksi saat ini:
  - `routes/web.php`
- Monitoring health model:
  - `app/Http/Controllers/Admin/MonitorAiController.php`
  - `resources/views/admin/monitor-ai.blade.php`

---

## 2) Standar Integrasi API ML (Wajib Dipakai)

## 2.1 Konfigurasi endpoint (jangan hardcode URL panjang)

Seluruh endpoint ML harus didefinisikan di:

- `config/services.php`:
  - `services.ml.base_url`
  - `services.ml.endpoints.<fitur>`

Contoh endpoint yang sudah ada:

- `motif` -> `/motif/scan`
- `jenis` -> `/tulis/scan`
- `health` -> `/health`

Tambahkan endpoint baru untuk fitur berikutnya di lokasi yang sama.

## 2.2 Kontrak response internal (normalisasi)

Backend sebaiknya menormalisasi response API eksternal agar frontend konsisten.

Pola minimum untuk klasifikasi image->text:

```json
{
  "success": true,
  "result": {
    "label": "Balai Kota",
    "confidence": 0.9994,
    "description": "-"
  }
}
```

Pola error:

```json
{
  "success": false,
  "message": "Pesan error"
}
```

Dengan pola ini, komponen JS/Blade tidak perlu tahu format mentah dari masing-masing API ML.

## 2.3 Pola implementasi backend fitur ML baru

Saat menambah menu ML baru:

1. Tambah endpoint config di `services.ml.endpoints`.
2. Tambah method di controller (atau service) yang:
  - validasi input,
  - panggil API eksternal,
  - normalisasi output ke format internal.
3. Tambah route web/API internal di `routes/web.php`.
4. Hubungkan halaman Blade ke route internal.

---

## 3) Standar Desain UI Untuk Menu ML

Gunakan pola komponen yang sama agar desain konsisten:

- Komponen utama: `x-ml-detector` di `resources/views/components/ml-detector.blade.php`
- Dukungan saat ini:
  - input `image` / `text`
  - output `text` / `image`
  - upload file + camera + webcam
  - live inference overlay pada webcam
  - tombol scan terintegrasi

### Checklist desain halaman fitur ML

- Hero section (judul, deskripsi singkat fitur).
- CTA section yang memanggil komponen `x-ml-detector`.
- Info edukasi singkat (cara kerja / tips input).
- Warna, spacing, radius, icon mengikuti gaya halaman `deteksi-motif` dan `deteksi-jenis`.

Referensi halaman yang sudah jadi:

- `resources/views/pages/deteksi-motif.blade.php`
- `resources/views/pages/deteksi-jenis.blade.php`

---

## 4) Peta File Untuk Menu ML Yang Belum Dikerjakan

Bagian ini menjadi template implementasi berulang untuk setiap menu ML:

## 4.1 Frontend

- Halaman fitur:
  - `resources/views/pages/<nama-fitur>.blade.php`
- Gunakan komponen:
  - `resources/views/components/ml-detector.blade.php`
- Script shared:
  - `public/js/ml-detector.js`

## 4.2 Backend

- Route:
  - `routes/web.php`
- Controller method:
  - disarankan di `app/Http/Controllers/MLController.php` (atau service terpisah jika method bertambah banyak)
- Middleware akses:
  - `menu.access` (auth+role ketat), atau
  - `menu.access_or_guest` (guest boleh, user login tetap difilter menu flagging)

## 4.3 Admin/monitoring (jika perlu)

- Controller:
  - `app/Http/Controllers/Admin/MonitorAiController.php`
- View:
  - `resources/views/admin/monitor-ai.blade.php`

---

## 5) Progress terhadap `blueprint.txt`

Status dikelompokkan menjadi:

- `DONE`: sudah tersedia dan berjalan dasar.
- `PARTIAL`: sudah ada fondasi/template, tapi belum full sesuai target blueprint.
- `TODO`: belum diimplementasi.


| Area Blueprint                         | Status  | Catatan                                                                                   |
| -------------------------------------- | ------- | ----------------------------------------------------------------------------------------- |
| Galeri Batik (lihat gambar/detail)     | DONE    | Halaman galeri publik tersedia (`/galeri`, `/galeri/{batik}`)                             |
| Like gambar + rekomendasi setelah like | PARTIAL | Like sudah ada; rekomendasi route ada, validasi end-to-end tergantung API ML/gallery flow |
| Deteksi Motif Batik                    | DONE    | Halaman + popup + API internal + integrasi endpoint ML selesai                            |
| Deteksi Jenis Batik                    | DONE    | Halaman + popup + API internal + integrasi endpoint ML selesai                            |
| Pencarian Batik (similar image)        | TODO    | Belum ada halaman/controller/route khusus                                                 |
| Pencarian by Warna Dominan             | TODO    | Belum ada halaman/controller/route khusus                                                 |
| Rekomendasi by Fashion                 | TODO    | Belum ada halaman/controller/route khusus                                                 |
| Pewarnaan by Palet Warna               | TODO    | Belum ada halaman/controller/route khusus                                                 |
| Pewarnaan by Prompt                    | TODO    | Belum ada halaman/controller/route khusus                                                 |
| Terapkan Batik                         | TODO    | Belum ada halaman/controller/route khusus                                                 |
| Text to Image Batik                    | TODO    | Belum ada halaman/controller/route khusus                                                 |
| Login email/password                   | DONE    | Tersedia                                                                                  |
| Login Google                           | DONE    | Tersedia                                                                                  |
| Register                               | DONE    | Tersedia                                                                                  |
| Remember Me                            | DONE    | Tersedia di login flow                                                                    |
| Lupa Password                          | TODO    | Belum terlihat route/flow reset password                                                  |
| Profil user                            | DONE    | Halaman/profile update tersedia                                                           |
| Admin Dashboard                        | DONE    | Tersedia dan menu sesuai akses                                                            |
| Kelola User                            | DONE    | Resource admin tersedia                                                                   |
| Kelola Role + flagging menu            | DONE    | Resource admin tersedia, middleware menu.access sudah aktif                               |
| Kelola Galeri Batik                    | DONE    | Resource admin + upload images tersedia                                                   |
| Kelola Konten Global Landing           | DONE    | Admin landing content tersedia                                                            |
| Monitor Model AI (health table)        | DONE    | Implementasi + auto-refresh sudah ada                                                     |


---

## 6) Rencana Eksekusi Menu ML yang Belum (Direkomendasikan)

Urutan prioritas agar cepat deliver:

1. **Pencarian Batik (similar image)**
2. **Pencarian by Warna Dominan**
3. **Rekomendasi by Fashion**
4. **Pewarnaan by Prompt**
5. **Pewarnaan by Palet Warna**
6. **Terapkan Batik**
7. **Text-to-Image Batik**

Untuk tiap fitur, ulangi pola:

1. Tambah endpoint config di `services.ml.endpoints`.
2. Tambah route internal (`routes/web.php`).
3. Tambah method backend (normalisasi response).
4. Buat halaman `resources/views/pages/<fitur>.blade.php`.
5. Pakai `x-ml-detector` (atau extend komponen jika output grid/list khusus).
6. Tambah card/entry di `resources/views/pages/features.blade.php`.

---

## 7) Catatan Implementasi Penting

- Untuk fitur dengan output **list gambar** (mis. pencarian similar), komponen result saat ini perlu ditambah varian `outputType="gallery"` atau render custom section.
- Untuk fitur gabungan **input image + text** (contoh fashion/prompt), komponen `x-ml-detector` sudah punya fondasi, tapi butuh penambahan mode input campuran.
- Jika jumlah method di `MLController` makin banyak, refactor ke service layer disarankan:
  - contoh: `app/Services/MLGatewayService.php`
  - controller hanya fokus validasi request + return response.

---

## 8) Referensi Endpoint Health

- Health API model yang saat ini dipakai:
  - [https://galeridigital-batikmalang.id/api/health](https://galeridigital-batikmalang.id/api/health)

