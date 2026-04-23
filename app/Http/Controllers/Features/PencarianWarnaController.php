<?php
/**
 * =========================================================================
 * TEMPLATE — Pencarian Batik by Warna Dominan
 * =========================================================================
 * @status  TODO
 * @menu    pencarian-warna
 *
 * API Endpoint yang dibutuhkan:
 *   POST /api/search/warna
 *     Input : image (multipart/form-data)
 *     Output: { success, dominant_colors: [...], results: [{ name, image_url }] }
 *
 * Langkah implementasi:
 *   1. Tambah endpoint di config/services.php:
 *        'search_warna' => env('ML_ENDPOINT_SEARCH_WARNA', '/color/search'),
 *   2. Implementasi method search() di controller ini
 *   3. Desain halaman view resources/views/pages/features/pencarian-warna.blade.php
 *   4. Aktifkan route POST di routes/features.php (lihat komentar TODO di sana)
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PencarianWarnaController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.pencarian-warna');
    }

    /** TODO: Implementasi pencarian batik berdasarkan warna dominan. */
    public function search(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pencarian by Warna Dominan belum diimplementasi.',
        ], 501);
    }
}
