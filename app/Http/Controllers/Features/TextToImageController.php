<?php
/**
 * =========================================================================
 * TEMPLATE — Text to Image Batik (Generatif AI)
 * =========================================================================
 * @status  TODO
 * @menu    text-to-image
 *
 * API Endpoint yang dibutuhkan:
 *   POST /api/text-to-image
 *     Input : prompt (string deskripsi motif batik yang ingin di-generate)
 *     Output: { success, image_url } atau { success, image_base64 }
 *
 * Langkah implementasi:
 *   1. Tambah endpoint di config/services.php:
 *        'text_to_image' => env('ML_ENDPOINT_TEXT_TO_IMAGE', '/generate/text2img'),
 *   2. Implementasi method generate() di controller ini
 *   3. Desain halaman view resources/views/pages/features/text-to-image.blade.php
 *      (UI: textarea prompt + tombol generate + tampil hasil gambar)
 *   4. Aktifkan route POST di routes/features.php (lihat komentar TODO di sana)
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class TextToImageController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.text-to-image');
    }

    /** TODO: Implementasi generate gambar batik dari deskripsi teks. */
    public function generate(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Text to Image Batik belum diimplementasi.',
        ], 501);
    }
}
