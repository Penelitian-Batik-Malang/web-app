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

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
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
            $guzzle     = new GuzzleClient(['timeout' => 120]);
            $guzzleResp = $guzzle->post($url, [
                'http_errors' => false,
                'headers'     => [
                    'Accept'    => 'application/json',
                    'X-API-Key' => $this->apiKey
                ],
                'multipart'   => [
                    ['name' => 'session_id',     'contents' => (string) $request->input('session_id')],
                    ['name' => 'part',           'contents' => (string) $request->input('part')],
                    ['name' => 'instance_index', 'contents' => (string) ((int) $request->input('instance_index', 0))],
                    ['name' => 'batik_filename', 'contents' => (string) $request->input('batik_filename')],
                ],
            ]);

            $statusCode = $guzzleResp->getStatusCode();
            $body       = (string) $guzzleResp->getBody();
            $raw       = json_decode($body, true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $data = isset($raw['data']) && isset($raw['status']) ? $raw['data'] : $raw;
                return response()->json($data ?? []);
            }

            return response()->json([
                'success' => false,
                'message' => 'Fashion Service error ' . $statusCode . ': ' . $body,
            ], $statusCode);

        } catch (\Throwable $e) {
            Log::error('Fashion Blend CBIR Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Blend CBIR error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
