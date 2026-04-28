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
 *
 * Alur kerja:
 *   1. User membuka halaman /deteksi/motif
 *   2. Halaman fetch GET /api/detect/motif/labels → daftar motif dinamis
 *   3. User mengunggah gambar via komponen <x-ml-detector>
 *   4. Frontend memanggil POST /api/detect/motif (AJAX)
 *   5. Controller memanggil Batik Service POST /detection/motif
 *   6. Response dinormalisasi: { label, confidence, description }
 *   7. Frontend menampilkan hasil di modal
 *
 * Endpoints Batik Service (port 8001):
 *   POST /detection/motif          → klasifikasi motif
 *   GET  /detection/motif/labels   → daftar label yang didukung
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
     * Memanggil Batik Service POST /detection/motif.
     * Field yang dikirim: `file` (multipart/form-data).
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan file 'image'
     * @return \Illuminate\Http\JsonResponse  { success, result: { label, confidence, description } }
     */
    public function detect(Request $request)
    {
        return $this->handleImageDetection($request, '/detection/motif');
    }

    /**
     * Ambil daftar label motif yang didukung oleh model.
     *
     * Memanggil Batik Service GET /detection/motif/labels.
     * Digunakan oleh view untuk menampilkan grid motif secara dinamis.
     *
     * @return \Illuminate\Http\JsonResponse  Array label, misal: ["Kawung", "Parang", ...]
     */
    public function labels()
    {
        return $this->labelsFromBatikService('/detection/motif/labels');
    }
}
