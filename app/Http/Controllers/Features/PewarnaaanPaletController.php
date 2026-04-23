<?php
/**
 * =========================================================================
 * TEMPLATE — Pewarnaan Batik by Palet Warna
 * =========================================================================
 * @status  TODO
 * @menu    pewarnaan-palet
 *
 * API Endpoint yang dibutuhkan:
 *   POST /api/pewarnaan/palet
 *     Input : image (multipart/form-data), colors (array hex, misal ["#FF5733","#C70039"])
 *     Output: { success, result_image_url } atau binary image
 *
 * Langkah implementasi:
 *   1. Tambah endpoint di config/services.php:
 *        'pewarnaan_palet' => env('ML_ENDPOINT_PEWARNAAN_PALET', '/recolor/palette'),
 *   2. Implementasi method process() di controller ini
 *   3. Desain halaman view resources/views/pages/features/pewarnaan-palet.blade.php
 *      (UI perlu color picker untuk memilih palet warna)
 *   4. Aktifkan route POST di routes/features.php (lihat komentar TODO di sana)
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PewarnaaanPaletController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.pewarnaan-palet');
    }

    /** TODO: Implementasi pewarnaan ulang batik menggunakan palet warna pilihan. */
    public function process(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pewarnaan by Palet Warna belum diimplementasi.',
        ], 501);
    }
}
