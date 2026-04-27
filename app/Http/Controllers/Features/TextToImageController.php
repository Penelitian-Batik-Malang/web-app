<?php
/**
 * =========================================================================
 * TextToImageController — Text to Image Batik (Generatif AI)
 * =========================================================================
 *
 * Fitur ini memungkinkan user menghasilkan motif batik Malang baru
 * dari deskripsi teks menggunakan model AI generatif. User menulis
 * prompt deskriptif, dan AI menghasilkan gambar motif batik sesuai.
 *
 * @status  TODO — Menunggu model AI generatif tersedia
 * @menu    text-to-image
 * @see     config/services.php → services.ml.endpoints.text_to_image
 *
 * Alur kerja:
 *   1. User menulis deskripsi motif batik yang diinginkan
 *   2. Frontend mengirim prompt ke POST /api/text-to-image
 *   3. Controller meneruskan ke ML API endpoint /generate/text2img
 *   4. ML API men-generate gambar motif batik baru
 *   5. Controller mengembalikan gambar hasil ke frontend
 *
 * API Endpoint yang dibutuhkan:
 *   POST {ML_BASE_URL}/generate/text2img
 *     Input : prompt (string deskripsi motif batik)
 *     Output: { success, image_url } atau { success, image_base64 }
 *
 * Contoh prompt:
 *   - "batik malang dengan motif bunga dan warna biru langit"
 *   - "motif batik geometris dengan sentuhan emas dan merah"
 *
 * Catatan:
 *   Model AI generatif biasanya membutuhkan waktu 10-30 detik.
 *   Pastikan timeout yang cukup dan loading indicator di frontend.
 *
 * @see resources/views/pages/features/text-to-image.blade.php — View
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class TextToImageController extends BaseMLController
{
    /**
     * Tampilkan halaman text to image batik.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.text-to-image');
    }

    /**
     * Generate gambar batik dari deskripsi teks.
     *
     * Menerima prompt teks dari frontend dan menghasilkan
     * gambar motif batik baru menggunakan model AI generatif.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @todo Implementasi setelah model AI generatif tersedia
     */
    public function generate(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Text to Image Batik belum diimplementasi.',
        ], 501);
    }
}
