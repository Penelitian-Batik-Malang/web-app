<?php
/**
 * =========================================================================
 * PencarianWarnaController — Pencarian Batik by Warna Dominan
 * =========================================================================
 *
 * Fitur ini memungkinkan user mencari batik berdasarkan warna dominan
 * pada kain. User mengunggah gambar, sistem mengekstrak warna dominan,
 * dan mencocokkannya dengan database batik.
 *
 * @status  TODO — Menunggu endpoint API ML tersedia
 * @menu    pencarian-warna
 * @see     config/services.php → services.ml.endpoints.search_warna
 *
 * Alur kerja:
 *   1. User mengunggah gambar batik atau kain
 *   2. Frontend mengirim gambar ke POST /api/search/warna
 *   3. Controller meneruskan ke ML API endpoint /color/search
 *   4. ML API mengekstrak warna dominan + mencocokkan database
 *   5. Controller mengembalikan: warna dominan + daftar batik cocok
 *
 * API Endpoint yang dibutuhkan:
 *   POST {ML_BASE_URL}/color/search
 *     Input : image (multipart/form-data)
 *     Output: { success, dominant_colors: [...], results: [{ name, image_url }] }
 *
 * @see resources/views/pages/features/pencarian-warna.blade.php — View
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PencarianWarnaController extends BaseMLController
{
    /**
     * Tampilkan halaman pencarian by warna dominan.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.pencarian-warna');
    }

    /**
     * Cari batik berdasarkan warna dominan dari gambar.
     *
     * Menerima gambar dan mengembalikan warna dominan yang terdeteksi
     * beserta daftar batik yang cocok berdasarkan palet warna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @todo Implementasi setelah endpoint API ML /color/search tersedia
     */
    public function search(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pencarian by Warna Dominan belum diimplementasi.',
        ], 501);
    }
}
