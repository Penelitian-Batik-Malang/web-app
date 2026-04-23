<?php
/**
 * =========================================================================
 * TEMPLATE — Pewarnaan Batik by Prompt Teks
 * =========================================================================
 * @status  TODO
 * @menu    pewarnaan-prompt
 *
 * API Endpoint yang dibutuhkan:
 *   POST /api/pewarnaan/prompt
 *     Input : image (multipart/form-data), prompt (string instruksi teks)
 *     Output: { success, result_image_url } atau binary image
 *
 * Langkah implementasi:
 *   1. Tambah endpoint di config/services.php:
 *        'pewarnaan_prompt' => env('ML_ENDPOINT_PEWARNAAN_PROMPT', '/recolor/prompt'),
 *   2. Implementasi method process() di controller ini
 *   3. Desain halaman view resources/views/pages/features/pewarnaan-prompt.blade.php
 *      (UI: upload gambar + textarea prompt + tampil hasil)
 *   4. Aktifkan route POST di routes/features.php (lihat komentar TODO di sana)
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PewarnaaanPromptController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.pewarnaan-prompt');
    }

    /** TODO: Implementasi pewarnaan ulang batik dari instruksi teks/prompt. */
    public function process(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pewarnaan by Prompt belum diimplementasi.',
        ], 501);
    }
}
