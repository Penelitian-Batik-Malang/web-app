<?php
/**
 * =========================================================================
 * PewarnaanPromptController — Pewarnaan Batik by Prompt Teks
 * =========================================================================
 *
 * Fitur ini memungkinkan user memberikan instruksi teks (prompt) untuk
 * mewarnai ulang motif batik secara AI. Gambar batik dan prompt teks
 * dikirim ke API ML, yang menginterpretasi instruksi dan menghasilkan
 * gambar batik dengan warna baru sesuai deskripsi.
 *
 * @status  TODO — Menunggu endpoint API ML tersedia
 * @menu    pewarnaan-prompt
 * @see     config/services.php → services.ml.endpoints.pewarnaan_prompt
 *
 * Alur kerja:
 *   1. User mengunggah gambar batik
 *   2. User menulis instruksi teks (misal: "ubah ke warna biru langit")
 *   3. Frontend mengirim gambar + prompt ke controller ini
 *   4. Controller meneruskan ke API ML endpoint /recolor/prompt
 *   5. API ML mengembalikan gambar hasil recoloring
 *   6. Controller mengembalikan gambar ke frontend
 *
 * API Endpoint yang dibutuhkan:
 *   POST {ML_BASE_URL}/recolor/prompt
 *     Input : image (multipart), prompt (string instruksi teks)
 *     Output: { success, result_image_url } atau binary image
 *
 * Langkah implementasi:
 *   1. Pastikan endpoint terdaftar di config/services.php (sudah)
 *   2. Implementasi method process() di bawah
 *   3. Desain UI di resources/views/pages/features/pewarnaan-prompt.blade.php
 *   4. Aktifkan route POST di routes/features.php
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PewarnaanPromptController extends BaseMLController
{
    /**
     * Tampilkan halaman pewarnaan by prompt teks.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.pewarnaan-prompt');
    }

    /**
     * Proses pewarnaan ulang batik dari instruksi teks/prompt AI.
     *
     * Menerima gambar batik dan prompt teks dari frontend,
     * meneruskan ke API ML, dan mengembalikan hasilnya.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @todo Implementasi setelah endpoint API ML /recolor/prompt tersedia
     */
    public function process(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pewarnaan by Prompt belum diimplementasi.',
        ], 501);
    }
}
