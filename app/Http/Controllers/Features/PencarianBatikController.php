<?php
/**
 * =========================================================================
 * TEMPLATE — Pencarian Batik (CBIR - Content-Based Image Retrieval)
 * =========================================================================
 * @status  TODO
 * @menu    pencarian-batik
 *
 * API Endpoint yang dibutuhkan:
 *   POST /api/search/batik
 *     Input : image (multipart/form-data)
 *     Output: { success, results: [{ name, image_url, similarity_score }] }
 *
 * Langkah implementasi:
 *   1. Tambah endpoint di config/services.php:
 *        'search_batik' => env('ML_ENDPOINT_SEARCH_BATIK', '/cbir/search'),
 *   2. Implementasi method search() di controller ini
 *   3. Desain halaman view resources/views/pages/features/pencarian-batik.blade.php
 *      (output berupa grid gambar serupa, bukan teks klasifikasi)
 *   4. Aktifkan route POST di routes/features.php (lihat komentar TODO di sana)
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PencarianBatikController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.pencarian-batik');
    }

    /** TODO: Implementasi pencarian batik serupa menggunakan CBIR. */
    public function search(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pencarian Batik belum diimplementasi.',
        ], 501);
    }
}
