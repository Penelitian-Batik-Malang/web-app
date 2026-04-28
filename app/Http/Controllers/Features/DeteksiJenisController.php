<?php
/**
 * =========================================================================
 * DeteksiJenisController — Deteksi Jenis Batik (Tulis / Cap)
 * =========================================================================
 *
 * @status  DONE
 * @menu    deteksi-jenis
 *
 * Endpoints Batik Service (port 8001):
 *   POST /detection/type          → klasifikasi jenis (tulis/cap)
 *   GET  /detection/type/labels   → daftar label yang didukung
 *
 * @see resources/views/pages/features/deteksi-jenis.blade.php — View
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class DeteksiJenisController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.deteksi-jenis');
    }

    /**
     * Deteksi jenis batik (tulis / cap).
     * Memanggil Batik Service POST /detection/type.
     */
    public function detect(Request $request)
    {
        return $this->handleImageDetection($request, '/detection/type');
    }

    /**
     * Daftar label jenis yang didukung (GET /detection/type/labels).
     */
    public function labels()
    {
        return $this->labelsFromBatikService('/detection/type/labels');
    }
}
