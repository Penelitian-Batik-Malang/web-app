<?php
/**
 * =========================================================================
 * DeteksiJenisController — Deteksi Jenis Batik (Tulis / Cap)
 * =========================================================================
 *
 * Fitur ini mengklasifikasi apakah batik yang diunggah merupakan
 * Batik Tulis (handwritten) atau Batik Cap (stamped) menggunakan AI.
 *
 * @status  DONE
 * @menu    deteksi-jenis
 * @see     config/services.php → services.ml.endpoints.jenis
 *
 * Alur kerja:
 *   1. User membuka halaman /deteksi/jenis
 *   2. User mengunggah gambar via komponen <x-ml-detector>
 *   3. Frontend memanggil POST /api/detect/jenis (AJAX)
 *   4. Controller memanggil ML API endpoint /tulis/scan
 *   5. Response dinormalisasi: { label, confidence, description }
 *   6. Frontend menampilkan hasil di modal
 *
 * Perbedaan Tulis vs Cap:
 *   - Tulis: dibuat dengan canting manual, motif unik, nilai seni tinggi
 *   - Cap  : dibuat dengan stempel tembaga, motif seragam, produksi cepat
 *
 * @see resources/views/pages/features/deteksi-jenis.blade.php — View
 * @see resources/views/components/ml-detector.blade.php — Komponen UI
 * @see public/js/ml-detector.js — Logic JS modal deteksi
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class DeteksiJenisController extends BaseMLController
{
    /**
     * Tampilkan halaman deteksi jenis batik.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.deteksi-jenis');
    }

    /**
     * Proses deteksi jenis batik dari gambar yang diunggah.
     *
     * Menggunakan flow standar handleImageDetection() dari BaseMLController.
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan file 'image'
     * @return \Illuminate\Http\JsonResponse  { success, result: { label, confidence, description } }
     */
    public function detect(Request $request)
    {
        $path = $this->endpoints['jenis'] ?? '/tulis/scan';
        return $this->handleImageDetection($request, $path);
    }
}
