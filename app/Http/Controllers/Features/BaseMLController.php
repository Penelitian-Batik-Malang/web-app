<?php
/**
 * =========================================================================
 * BaseMLController — Base Controller untuk Semua Fitur ML
 * =========================================================================
 *
 * Controller abstrak ini menyediakan infrastruktur dasar yang digunakan
 * oleh semua controller fitur ML. Setiap fitur ML (deteksi, pencarian,
 * pewarnaan, terapkan, dll.) meng-extend class ini.
 *
 * ARSITEKTUR MICROSERVICE:
 *   - Batik Service  (Port 8001) — deteksi motif, deteksi jenis, pencarian CBIR
 *   - Fashion Service (Port 8002) — segmentasi, blending, session management
 *
 * TANGGUNG JAWAB:
 *   1. Membaca konfigurasi ML API dari config/services.php
 *   2. Menyediakan helper URL untuk batik service & fashion service
 *   3. Menyediakan method reusable untuk image detection flow
 *   4. Menyediakan utility untuk file handling dan response
 *   5. Menyediakan akses ke sample fashion images
 *
 * @see config/services.php               — Konfigurasi endpoint
 * @see routes/features.php               — Route definitions
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseMLController extends Controller
{
    /**
     * Base URL Batik Service (port 8001).
     * @var string
     */
    protected string $batikUrl;

    /**
     * Base URL Fashion Service (port 8002).
     * @var string
     */
    protected string $fashionUrl;

    /**
     * Inisialisasi konfigurasi ML API (dual-service).
     */
    public function __construct()
    {
        $this->batikUrl   = rtrim((string) config('services.ml.batik_url',   'http://127.0.0.1:8001'), '/');
        $this->fashionUrl = rtrim((string) config('services.ml.fashion_url', 'http://127.0.0.1:5000'), '/');
    }

    /**
     * Proxy gambar dari S3 IDCloudHost via server-side — menghindari CORS/canvas-taint.
     *
     * GET /img?u=BASE64URL
     * Hanya memperbolehkan URL dari is3.cloudhost.id (whitelist).
     *
     * @return \Illuminate\Http\Response
     */
    public function proxyBatikImage(\Illuminate\Http\Request $request)
    {
        $encoded = (string) $request->query('u', '');
        if (empty($encoded)) abort(400, 'Missing url parameter');

        $url = base64_decode(strtr($encoded, '-_', '+/'), true);
        if (!$url || !str_starts_with($url, 'https://is3.cloudhost.id/')) {
            abort(403, 'URL not whitelisted');
        }

        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
            if (!$resp->successful()) abort($resp->status());

            return response($resp->body(), 200, [
                'Content-Type'                => $resp->header('Content-Type', 'image/jpeg'),
                'Cache-Control'               => 'public, max-age=86400',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Throwable $e) {
            abort(502, 'Proxy fetch failed: ' . $e->getMessage());
        }
    }


    // ─── Status & URL Helpers ─────────────────────────────────────────

    /**
     * Cek apakah Batik Service tersedia.
     */
    protected function isBatikAvailable(): bool
    {
        return !empty($this->batikUrl);
    }

    /**
     * Cek apakah Fashion Service tersedia.
     */
    protected function isFashionAvailable(): bool
    {
        return !empty($this->fashionUrl);
    }

    /**
     * Alias isMLAvailable → cek batik service (backward compat).
     */
    protected function isMLAvailable(): bool
    {
        return $this->isBatikAvailable();
    }

    /**
     * Bangun URL lengkap untuk Batik Service endpoint.
     *
     * @param  string  $path  Path endpoint (misal: '/detection/motif')
     * @return string  URL lengkap
     */
    protected function batikServiceUrl(string $path): string
    {
        return $this->batikUrl . '/' . ltrim($path, '/');
    }

    /**
     * Bangun URL lengkap untuk Fashion Service endpoint.
     *
     * @param  string  $path  Path endpoint (misal: '/fashion/segment')
     * @return string  URL lengkap
     */
    protected function fashionServiceUrl(string $path): string
    {
        return $this->fashionUrl . '/' . ltrim($path, '/');
    }

    // ─── Standard Responses ───────────────────────────────────────────

    /**
     * Response standar ketika ML API belum dikonfigurasi (503).
     */
    protected function notConfiguredResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Model AI belum terhubung. Konfigurasi ML_BATIK_URL / ML_FASHION_URL di .env.',
        ], 503);
    }

    // ─── Image Detection Flow ─────────────────────────────────────────

    /**
     * Flow standar untuk deteksi gambar di Batik Service.
     *
     * Field yang dikirim ke ML API adalah `file` (sesuai API docs).
     * Response dinormalisasi ke: { success, result: { label, confidence, description } }
     *
     * Digunakan oleh:
     *   - DeteksiMotifController::detect()   → POST /detection/motif
     *   - DeteksiJenisController::detect()   → POST /detection/type
     *
     * @param  Request  $request  Request dengan file 'image'
     * @param  string   $path     Path endpoint di Batik Service
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleImageDetection(Request $request, string $path)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isBatikAvailable()) {
            return response()->json([
                'success' => false,
                'stub'    => true,
                'message' => 'Model AI belum terhubung. Konfigurasi ML_BATIK_URL di .env.',
                'result'  => null,
            ], 503);
        }

        try {
            $url  = $this->batikServiceUrl($path);
            $file = $request->file('image');

            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($url);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'result'  => [
                        'label'       => $data['label']       ?? $data['class']  ?? $data['result'] ?? 'Tidak Diketahui',
                        'confidence'  => $data['confidence']  ?? $data['probability'] ?? $data['score'] ?? 0,
                        'description' => $data['description'] ?? $data['desc']   ?? $data['message'] ?? '-',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Model AI tidak memberikan respons yang valid.',
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('ML API Detection Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server Model AI. Periksa koneksi atau endpoint.',
            ], 500);
        }
    }

    /**
     * Ambil daftar label dari endpoint GET di Batik Service.
     *
     * Digunakan oleh:
     *   - DeteksiMotifController::labels()   → GET /detection/motif/labels
     *   - DeteksiJenisController::labels()   → GET /detection/type/labels
     *
     * @param  string  $path  Path endpoint (misal: '/detection/motif/labels')
     * @return \Illuminate\Http\JsonResponse
     */
    protected function labelsFromBatikService(string $path)
    {
        if (!$this->isBatikAvailable()) {
            return response()->json([]);
        }

        try {
            $response = Http::timeout(10)->get($this->batikServiceUrl($path));
            if ($response->successful()) {
                return response()->json($response->json());
            }
        } catch (\Exception $e) {
            Log::warning('ML labels fetch failed: ' . $e->getMessage());
        }

        return response()->json([]);
    }

    // ─── File Handling Utilities ───────────────────────────────────────

    /**
     * Attach file ke HTTP request sebagai multipart.
     */
    protected function attachFile($http, string $name, UploadedFile $file)
    {
        return $http->asMultipart()->attach(
            $name,
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        );
    }

    /**
     * Resolve input gambar menjadi binary dari file upload atau URL.
     */
    protected function resolveInputImageBinary(?UploadedFile $file, ?string $url): ?array
    {
        if ($file instanceof UploadedFile) {
            return [
                'body'         => file_get_contents($file->getRealPath()),
                'content_type' => $file->getMimeType() ?: 'image/jpeg',
            ];
        }

        if (!empty($url)) {
            $resp = Http::timeout(20)->get($url);
            if ($resp->successful()) {
                return [
                    'body'         => $resp->body(),
                    'content_type' => $resp->header('Content-Type', 'image/jpeg'),
                ];
            }
        }

        return null;
    }

    // ─── Sample Fashion Images ────────────────────────────────────────

    /**
     * Dapatkan daftar URL gambar fashion sample.
     */
    protected function getSampleFashionUrls(): array
    {
        $dir = base_path('sample_fashion');
        if (!is_dir($dir)) {
            return [];
        }

        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $files = array_values(array_filter(
            scandir($dir),
            fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $extensions)
        ));

        return array_map(
            fn ($f) => route('sample.fashion', ['filename' => $f]),
            $files
        );
    }

    /**
     * Serve file gambar sample fashion dari disk.
     */
    public function serveSampleFashion(string $filename)
    {
        $dir  = base_path('sample_fashion');
        $path = realpath($dir . DIRECTORY_SEPARATOR . $filename);

        if (!$path || !str_starts_with($path, realpath($dir))) {
            abort(404);
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        return response()->file($path, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }


    // ─── Gallery Lookup Helpers ───────────────────────────────────────


    /**
     * Temukan record Batik di database berdasarkan label dari ML API.
     *
     * Label ML API bisa berbeda format (underscore, dash, lowercase) dari nama di DB.
     * Gunakan pencarian fuzzy LIKE case-insensitive dengan fallback ke kata pertama.
     *
     * @param  string  $label  Label dari ML API (misal: "topeng_gandring_wirasena")
     * @return \App\Models\Batik|null
     */
    protected function findBatikByLabel(string $label): ?\App\Models\Batik
    {
        if (empty($label)) return null;

        $normalized = strtolower(str_replace(['_', '-'], ' ', $label));

        $batik = \App\Models\Batik::where('is_active', true)
            ->with('mainImage')
            ->whereRaw('LOWER(REPLACE(REPLACE(name, "_", " "), "-", " ")) LIKE ?', ["%{$normalized}%"])
            ->first();

        if (!$batik) {
            $firstWord = explode(' ', $normalized)[0];
            if (strlen($firstWord) >= 3) {
                $batik = \App\Models\Batik::where('is_active', true)
                    ->with('mainImage')
                    ->whereRaw('LOWER(name) LIKE ?', ["%{$firstWord}%"])
                    ->first();
            }
        }

        return $batik;
    }

    /**
     * Dapatkan URL galeri detail untuk label motif dari ML API.
     *
     * @param  string  $label
     * @return string|null
     */
    protected function findGaleriUrlByLabel(string $label): ?string
    {
        $batik = $this->findBatikByLabel($label);
        return $batik ? route('galeri.show', $batik->id) : null;
    }

    /**
     * Generate URL proxy untuk gambar eksternal (S3) agar bisa diakses same-origin.
     */
    protected function proxyUrl(string $url): string
    {
        if (empty($url)) return '';
        $encoded = strtr(base64_encode($url), '+/', '-_');
        return route('img.proxy', ['u' => $encoded]);
    }
}
