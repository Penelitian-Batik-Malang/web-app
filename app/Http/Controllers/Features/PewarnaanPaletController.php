<?php
/**
 * =========================================================================
 * PewarnaanPaletController — Pewarnaan Batik by Palet Warna
 * =========================================================================
 *
 * Fitur ini memungkinkan user mengubah warna kain batik menggunakan
 * palet warna pilihan. Gambar batik dan array warna hex dikirim ke
 * API ML, yang mengembalikan gambar batik dengan warna yang telah diubah.
 *
 * @status  TODO — Menunggu endpoint API ML tersedia
 * @menu    pewarnaan-palet
 * @see     config/services.php → services.ml.endpoints.pewarnaan_palet
 *
 * Alur kerja:
 *   1. User mengunggah gambar batik
 *   2. User memilih palet warna (color picker)
 *   3. Frontend mengirim gambar + array warna ke controller ini
 *   4. Controller meneruskan ke API ML endpoint /recolor/palette
 *   5. API ML mengembalikan gambar hasil recoloring
 *   6. Controller mengembalikan gambar ke frontend
 *
 * API Endpoint yang dibutuhkan:
 *   POST {ML_BASE_URL}/recolor/palette
 *     Input : image (multipart), colors (array hex ["#FF5733","#C70039"])
 *     Output: { success, result_image_url } atau binary image
 *
 * Langkah implementasi:
 *   1. Pastikan endpoint terdaftar di config/services.php (sudah)
 *   2. Implementasi method process() di bawah
 *   3. Desain UI di resources/views/pages/features/pewarnaan-palet.blade.php
 *   4. Aktifkan route POST di routes/features.php
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PewarnaanPaletController extends BaseMLController
{
    /**
     * Tampilkan halaman pewarnaan by palet warna.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.pewarnaan-palet');
    }

    /**
     * Proses pewarnaan ulang batik menggunakan palet warna pilihan.
     *
     * Menerima gambar batik dan array warna hex dari frontend,
     * meneruskan ke API ML, dan mengembalikan hasilnya.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @todo Implementasi setelah endpoint API ML /recolor/palette tersedia
     */
    public function process(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur Pewarnaan by Palet Warna belum diimplementasi.',
        ], 501);
    }
}
