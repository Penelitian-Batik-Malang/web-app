<?php
/**
 * =========================================================================
 * DeteksiMotifController — Deteksi Motif Batik Malang
 * =========================================================================
 *
 * Fitur ini memungkinkan user mengunggah foto kain batik dan
 * mendapatkan identifikasi motif secara otomatis menggunakan AI.
 *
 * @status  DONE
 * @menu    deteksi-motif
 * @see     config/services.php → services.ml.endpoints.motif
 *
 * Alur kerja:
 *   1. User membuka halaman /deteksi/motif
 *   2. User mengunggah gambar via komponen <x-ml-detector>
 *   3. Frontend memanggil POST /api/detect/motif (AJAX)
 *   4. Controller memanggil ML API endpoint /motif/scan
 *   5. Response dinormalisasi: { label, confidence, description }
 *   6. Frontend menampilkan hasil di modal
 *
 * Motif yang didukung:
 *   Sido Mukti, Parang, Kawung, Banji, Ceplok, Truntum,
 *   Sekar Jagad, Balai Kota (bertambah seiring model update)
 *
 * @see resources/views/pages/features/deteksi-motif.blade.php — View
 * @see resources/views/components/ml-detector.blade.php — Komponen UI
 * @see public/js/ml-detector.js — Logic JS modal deteksi
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class DeteksiMotifController extends BaseMLController
{
    /**
     * Tampilkan halaman deteksi motif batik.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.deteksi-motif');
    }

    /**
     * Proses deteksi motif batik dari gambar yang diunggah.
     *
     * Menggunakan flow standar handleImageDetection() dari BaseMLController
     * yang menangani validasi, API call, dan normalisasi response.
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan file 'image'
     * @return \Illuminate\Http\JsonResponse  { success, result: { label, confidence, description } }
     */
    public function detect(Request $request)
    {
        $path = $this->endpoints['motif'] ?? '/motif/scan';
        return $this->handleImageDetection($request, $path);
    }
}
