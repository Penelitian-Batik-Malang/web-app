<?php
/**
 * =========================================================================
 * SharedMLController — Shared Fashion Service Session Management
 * =========================================================================
 *
 * Controller ini mengelola session Fashion Service (port 8002) yang
 * digunakan BERSAMA oleh:
 *   1. Terapkan Batik (TerapkanBatikController)
 *   2. Rekomendasi Batik (RekomendasiBatikController)
 *
 * Endpoint Fashion Service yang digunakan:
 *   POST /fashion/segment          → inference + CBIR warna
 *   POST /fashion/reset-session    → reset ke gambar original
 *   GET  /fashion/session/{id}     → status session
 *
 * @see TerapkanBatikController     — Fitur terapkan batik ke pakaian
 * @see RekomendasiBatikController  — Fitur rekomendasi batik dari CBIR
 * @see BaseMLController            — Parent class dengan utilities
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SharedMLController extends BaseMLController
{
    /**
     * Deteksi bagian pakaian dari citra fashion via Fashionpedia.
     *
     * POST /api/inference → Fashion Service POST /fashion/segment
     *
     * Response berisi:
     *   - session_id  : UUID session
     *   - parts       : Bagian pakaian terdeteksi (bbox + mask b64)
     *   - cbir        : Rekomendasi CBIR warna (top_5, top_10, top_15)
     *   - image_size  : Dimensi gambar
     *
     * @param  Request  $request  Request dengan file 'image'
     * @return \Illuminate\Http\JsonResponse
     */
    public function inference(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isFashionAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Fashion Service belum terhubung. Konfigurasi ML_FASHION_URL di .env.',
            ], 503);
        }

        $url = $this->fashionServiceUrl('/fashion/segment');

        try {
            $http = $this->attachFile(
                Http::timeout(600)
                    ->accept('application/json')
                    ->withHeaders(['X-API-Key' => env('ML_API_KEY', '')]),
                'image',
                $request->file('image')
            );
            $response = $http->post($url);

            if ($response->successful()) {
                $raw = $response->json();
                $data = isset($raw['data']) && isset($raw['status']) ? $raw['data'] : $raw;
                $data = $this->enrichCbirItems($data);
                return response()->json($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendeteksi bagian fashion (HTTP ' . $response->status() . ').',
                'detail'  => $response->body(),
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashionpedia Inference Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Inference error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tambahkan image_url dan galeri_url ke setiap item dalam CBIR response.
     *
     * Fashion Service mengembalikan thumbnail_b64 = "" (kosong) dan filename = URL S3.
     * Method ini mengisi image_url dari filename agar JS bisa menampilkan gambar,
     * dan galeri_url dari lookup label ke database batik.
     *
     * @param  array  $data  Response JSON dari Fashion Service
     * @return array
     */
    private function enrichCbirItems(array $data): array
    {
        if (empty($data['cbir'])) {
            return $data;
        }

        // Proxy fashion_url jika ada agar tidak 403
        if (!empty($data['fashion_url'])) {
            $s3Base = 'https://is3.cloudhost.id/color-dominant-batik/';
            if (strpos($data['fashion_url'], $s3Base) === 0) {
                $path = substr($data['fashion_url'], strlen($s3Base));
                $data['fashion_url'] = route('storage.cbir.proxy', ['path' => $path]);
            }
        }

        // Kumpulkan unique labels untuk bulk lookup
        $uniqueLabels = [];
        foreach (['top_5', 'top_10', 'top_15'] as $tier) {
            foreach ($data['cbir'][$tier] ?? [] as $item) {
                if (!empty($item['label'])) {
                    $uniqueLabels[$item['label']] = null;
                }
            }
        }

        // Lookup galeri URL per label (sekali per label unik)
        $galeriUrls = [];
        foreach (array_keys($uniqueLabels) as $label) {
            $galeriUrls[$label] = $this->findGaleriUrlByLabel($label);
        }

        // Enrichment: gunakan proxy route /storage/cbir/ agar tidak 403 (bucket private)
        foreach (['top_5', 'top_10', 'top_15'] as $tier) {
            if (!isset($data['cbir'][$tier])) continue;
            $data['cbir'][$tier] = array_map(function ($item) use ($galeriUrls) {
                // Konversi URL S3 full ke internal proxy route
                // misal: https://is3.cloudhost.id/color-dominant-batik/hijau/img.jpg
                // jadi:  /storage/cbir/hijau/img.jpg
                if (!empty($item['filename'])) {
                    $s3Base = 'https://is3.cloudhost.id/color-dominant-batik/';
                    if (strpos($item['filename'], $s3Base) === 0) {
                        $path = substr($item['filename'], strlen($s3Base));
                        $item['image_url'] = route('storage.cbir.proxy', ['path' => $path]);
                    } else {
                        $item['image_url'] = $item['filename'];
                    }
                }
                
                // galeri_url: dari lookup label ke DB
                $item['galeri_url'] = $galeriUrls[$item['label'] ?? ''] ?? null;
                return $item;
            }, $data['cbir'][$tier]);
        }

        return $data;
    }

    /**
     * Reset session Fashion Service ke gambar original.
     *
     * POST /api/reset → Fashion Service POST /fashion/reset-session
     *
     * @param  Request  $request  Request dengan 'session_id'
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        if (!$this->isFashionAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->fashionServiceUrl('/fashion/reset-session');

        try {
            $response = Http::timeout(30)
                ->asForm()
                ->withHeaders(['X-API-Key' => env('ML_API_KEY', '')])
                ->post($url, ['session_id' => $request->input('session_id')]);

            if ($response->successful()) {
                $raw = $response->json();
                $data = isset($raw['data']) && isset($raw['status']) ? $raw['data'] : $raw;
                return response()->json($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset gambar (HTTP ' . $response->status() . ').',
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashion Reset Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi Fashion Service.',
            ], 500);
        }
    }

    /**
     * Ambil info session Fashion Service yang aktif.
     *
     * GET /api/session/{sessionId} → Fashion Service GET /fashion/session/{id}
     *
     * @param  string  $sessionId  UUID session dari inference
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSession(string $sessionId)
    {
        if (!$this->isFashionAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->fashionServiceUrl('/fashion/session/' . $sessionId);

        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => env('ML_API_KEY', '')])
                ->get($url);

            if ($response->successful()) {
                $raw = $response->json();
                $data = isset($raw['data']) && isset($raw['status']) ? $raw['data'] : $raw;
                return response()->json($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Session tidak ditemukan.',
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashion Session Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi Fashion Service.',
            ], 500);
        }
    }

    /**
     * Proxy gambar batik dari S3 (atau URL eksternal lain) untuk menghindari CORS.
     * Mengambil parameter 'u' (base64 encoded URL).
     *
     * GET /img?u={BASE64_URL}
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function proxyBatikImage(Request $request)
    {
        $encoded = $request->query('u');
        if (!$encoded) {
            return response()->json(['error' => 'Missing URL parameter'], 400);
        }

        try {
            // Decode url-safe base64
            $url = base64_decode(strtr($encoded, '-_', '+/'));
            if (!$url) {
                return response()->json(['error' => 'Invalid encoding'], 400);
            }

            // Cache key based on URL hash
            $cacheKey = 'img_proxy_' . md5($url);
            
            // Check cache (1 day)
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
                return response($cached['content'])
                    ->header('Content-Type', $cached['type'])
                    ->header('Cache-Control', 'public, max-age=86400')
                    ->header('Access-Control-Allow-Origin', '*');
            }

            // Fetch image via HTTP (bypass SSL verification for local dev stability)
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ])
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Proxy Image Failed: ' . $url . ' (HTTP ' . $response->status() . ')');
                return response()->json(['error' => 'Target URL failed'], $response->status());
            }

            $contentType = $response->header('Content-Type') ?: 'image/jpeg';
            $content     = $response->body();

            // Store in cache
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'content' => $content,
                'type'    => $contentType
            ], 86400);

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=86400')
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Throwable $e) {
            Log::error('Proxy Image Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
