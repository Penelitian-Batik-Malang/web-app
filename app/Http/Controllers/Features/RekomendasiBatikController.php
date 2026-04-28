<?php
/**
 * =========================================================================
 * RekomendasiBatikController — Rekomendasi Batik by Fashion (CBIR)
 * =========================================================================
 *
 * Endpoint Fashion Service (port 8002):
 *   POST /fashion/blend-cbir  → blend batik dari URL S3 rekomendasi
 *
 * Input blend-cbir:
 *   - session_id     : UUID dari /fashion/segment
 *   - part           : nama segmen pakaian
 *   - instance_index : index instance
 *   - batik_filename : URL lengkap S3 dari cbir.top_k[n].filename
 *
 * @see SharedMLController  — inference, reset, getSession
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

    /**
     * Blend batik dari URL S3 rekomendasi ke segmen pakaian.
     *
     * POST /api/blend-from-cbir → Fashion Service POST /fashion/blend-cbir
     *
     * batik_filename adalah URL lengkap S3 dari kolom filename di
     * cbir.top_k hasil /fashion/segment — Fashion Service akan
     * mengunduh gambar langsung dari URL tersebut.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse  { image_b64: "base64 JPEG" }
     */
    public function blendFromCbir(Request $request)
    {
        $request->validate([
            'session_id'     => 'required|string',
            'part'           => 'required|string',
            'instance_index' => 'required|integer',
            'batik_filename' => 'required|string',
        ]);

        if (!$this->isFashionAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->fashionServiceUrl('/fashion/blend-cbir');

        try {
            $response = Http::timeout(120)
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
                'message' => 'Fashion Service error ' . $response->status(),
                'detail'  => $response->body(),
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashion Blend CBIR Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Blend CBIR error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
