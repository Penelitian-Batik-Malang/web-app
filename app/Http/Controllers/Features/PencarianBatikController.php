<?php
/**
 * =========================================================================
 * PencarianBatikController — Pencarian Batik Serupa (CBIR)
 * =========================================================================
 *
 * Fitur ini memungkinkan user mencari batik yang serupa secara visual
 * menggunakan Content-Based Image Retrieval (CBIR). User mengunggah
 * gambar batik, dan sistem menemukan batik-batik serupa dari database.
 *
 * @status  TODO — Menunggu endpoint API ML tersedia
 * @menu    pencarian-batik
 * @see     config/services.php → services.ml.endpoints.search_batik
 *
 * Alur kerja:
 *   1. User mengunggah gambar batik referensi
 *   2. Frontend mengirim gambar ke POST /api/search/batik
 *   3. Controller meneruskan ke ML API endpoint /cbir/search
 *   4. ML API mengembalikan daftar batik serupa (ranked by similarity)
 *   5. Controller mengembalikan grid gambar serupa ke frontend
 *
 * API Endpoint yang dibutuhkan:
 *   POST {ML_BASE_URL}/cbir/search
 *     Input : image (multipart/form-data)
 *     Output: { success, results: [{ name, image_url, similarity_score }] }
 *
 * Catatan output:
 *   Berbeda dengan fitur deteksi (output teks), fitur ini menampilkan
 *   GRID GAMBAR batik serupa. Gunakan outputType="gallery" atau custom
 *   section pada view, bukan komponen <x-ml-detector> standar.
 *
 * Langkah implementasi:
 *   1. Pastikan endpoint terdaftar di config/services.php (sudah)
 *   2. Implementasi method search() di bawah
 *   3. Desain UI grid hasil di pencarian-batik.blade.php
 *   4. Aktifkan route POST di routes/features.php
 *
 * @see resources/views/pages/features/pencarian-batik.blade.php — View
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PencarianBatikController extends BaseMLController
{
    /**
     * Tampilkan halaman pencarian batik serupa.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.pencarian-batik');
    }

    /**
     * Cari batik serupa menggunakan CBIR.
     *
     * Menerima gambar batik dari frontend dan mengembalikan
     * daftar batik serupa berdasarkan fitur visual (warna, tekstur, pola).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @todo Implementasi setelah endpoint API ML /cbir/search tersedia
     */
    public function search(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pencarian Batik belum diimplementasi.',
        ], 501);
    }
}
