<?php
/**
 * =========================================================================
 * RekomendasiBatikController — Rekomendasi Batik by Fashion (CBIR)
 * =========================================================================
 *
 * Fitur ini menganalisis warna dominan pakaian pada citra fashion
 * dan merekomendasikan motif batik yang palet warnanya senada,
 * menggunakan Content-Based Image Retrieval (CBIR) dengan ruang
 * warna CIELAB + K-Means clustering.
 *
 * @status  DONE
 * @menu    rekomendasi-batik
 *
 * Alur kerja lengkap:
 *   1. Upload    → User unggah/pilih gambar fashion
 *   2. Inference → SharedMLController::inference() → deteksi pakaian + CBIR
 *   3. CBIR      → Tampilkan fase rekomendasi (warna dominan + grid batik)
 *   4. Apply     → User bisa lanjut ke workspace untuk terapkan rekomendasi
 *   5. Panel     → User pilih batik rekomendasi, atur posisi, blend
 *   6. Result    → Gambar fashion dengan batik rekomendasi yang diterapkan
 *
 * Perbedaan dengan TerapkanBatik:
 *   - Terapkan  : User langsung ke workspace, pilih batik manual dari galeri
 *   - Rekomendasi: User dapat rekomendasi CBIR dulu, baru ke workspace
 *
 * Endpoints yang digunakan:
 *   - inference      : POST /api/inference      (SharedMLController)
 *   - blend_cbir     : POST /api/blend-from-cbir (method blendFromCbir())
 *   - blend          : POST /api/blend           (TerapkanBatikController)
 *   - reset          : POST /api/reset           (SharedMLController)
 *
 * @see SharedMLController                 — Shared session management
 * @see TerapkanBatikController            — Fitur serupa tanpa CBIR
 * @see config/services.php                — Endpoint configuration
 * @see resources/views/pages/features/rekomendasi-batik.blade.php — View
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RekomendasiBatikController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.rekomendasi-batik', [
            'fashionSamples' => $this->getSampleFashionUrls(),
        ]);
    }

    public function blendFromCbir(Request $request)
    {
        $request->validate([
            'session_id'     => 'required|string',
            'part'           => 'required|string',
            'instance_index' => 'required|integer',
            'batik_filename' => 'required|string',
        ]);

        if (!$this->isMLAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->mlUrl('blend_cbir', '/blend-from-cbir');

        try {
            $response = Http::timeout(60)
                ->asMultipart()
                ->post($url, [
                    'session_id'     => $request->input('session_id'),
                    'part'           => $request->input('part'),
                    'instance_index' => $request->input('instance_index'),
                    'batik_filename' => $request->input('batik_filename'),
                ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'message' => 'API error ' . $response->status(),
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashionpedia Blend CBIR Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Blend CBIR error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
