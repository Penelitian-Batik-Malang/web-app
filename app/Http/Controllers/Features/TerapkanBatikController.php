<?php
/**
 * =========================================================================
 * TerapkanBatikController — Terapkan Motif Batik ke Citra Fashion
 * =========================================================================
 *
 * Endpoint Fashion Service (port 8002):
 *   POST /fashion/blend-manual  → blend batik dari file upload
 *
 * @see SharedMLController  — inference, reset, getSession
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use App\Models\Batik;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TerapkanBatikController extends BaseMLController
{
    public function show()
    {
        $fashionSamples = $this->getSampleFashionUrls();

        $batikSamples = Batik::query()
            ->where('is_active', true)
            ->with('mainImage')
            ->latest()
            ->limit(12)
            ->get()
            ->map(function ($batik) {
                return [
                    'name'        => $batik->name,
                    'description' => $batik->description,
                    'image_url'   => optional($batik->mainImage)->full_url,
                ];
            })
            ->filter(fn ($item) => !empty($item['image_url']))
            ->values();

        return view('pages.features.terapkan-batik', [
            'fashionSamples' => $fashionSamples,
            'batikSamples'   => $batikSamples,
        ]);
    }

    /**
     * Blend batik dari file upload ke segmen pakaian.
     *
     * POST /api/blend → Fashion Service POST /fashion/blend-manual
     *
     * Input (multipart/form-data):
     *   - session_id     : UUID dari /fashion/segment
     *   - part           : nama segmen pakaian (shirt, sleeve, dll.)
     *   - instance_index : index instance (default 0)
     *   - batik          : file gambar batik (UploadFile)
     *
     * Response: { image_b64: "base64 JPEG" }
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function blend(Request $request)
    {
        $request->validate([
            'session_id'     => 'required|string',
            'part'           => 'required|string|in:shirt,t-shirt,sweater,cardigan,jacket,vest,dress,jumpsuit,suit,coat,sleeve,collar,lapel,hood,pocket,neckline,epaulette',
            'instance_index' => 'nullable|integer|min:0',
            'batik'          => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isFashionAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->fashionServiceUrl('/fashion/blend-manual');

        try {
            $batikFile     = $request->file('batik');
            $batikRealPath = $batikFile->getRealPath();

            if (!$batikRealPath || !file_exists($batikRealPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File batik tidak valid. Coba unggah ulang.',
                ], 422);
            }

            $batikContents = file_get_contents($batikRealPath);
            if ($batikContents === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca file batik. Coba unggah ulang.',
                ], 422);
            }

            // Pakai GuzzleHttp langsung — lebih reliable untuk multipart besar
            $guzzle     = new GuzzleClient(['timeout' => 120]);
            $guzzleResp = $guzzle->post($url, [
                'http_errors' => false,
                'headers'     => ['Accept' => 'application/json'],
                'multipart'   => [
                    ['name' => 'session_id',     'contents' => (string) $request->input('session_id')],
                    ['name' => 'part',           'contents' => (string) $request->input('part')],
                    ['name' => 'instance_index', 'contents' => (string) ((int) $request->input('instance_index', 0))],
                    [
                        'name'     => 'batik',
                        'contents' => $batikContents,
                        'filename' => $batikFile->getClientOriginalName(),
                        'headers'  => ['Content-Type' => $batikFile->getMimeType() ?: 'image/jpeg'],
                    ],
                ],
            ]);

            $statusCode = $guzzleResp->getStatusCode();
            $body       = (string) $guzzleResp->getBody();
            $data       = json_decode($body, true);

            if ($statusCode >= 200 && $statusCode < 300) {
                return response()->json($data ?? []);
            }

            return response()->json([
                'success' => false,
                'message' => 'Fashion Service error ' . $statusCode . ': ' . $body,
            ], $statusCode);

        } catch (\Throwable $e) {
            Log::error('Fashion Blend Manual Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Blend error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
