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
 * TANGGUNG JAWAB:
 *   1. Membaca konfigurasi ML API dari config/services.php
 *   2. Menyediakan helper untuk membuat URL endpoint ML
 *   3. Menyediakan method reusable untuk image detection flow
 *   4. Menyediakan utility untuk file handling dan response
 *   5. Menyediakan akses ke sample fashion images
 *
 * CARA MENAMBAH FITUR ML BARU:
 *   1. Buat controller baru yang extends BaseMLController
 *   2. Daftarkan endpoint di config/services.php → services.ml.endpoints
 *   3. Gunakan $this->mlUrl('key', '/default') untuk URL
 *   4. Gunakan $this->handleImageDetection() untuk flow deteksi standar
 *   5. Daftarkan route di routes/features.php
 *
 * HIERARKI CONTROLLER:
 *   BaseMLController (abstract)
 *   ├── DeteksiMotifController
 *   ├── DeteksiJenisController
 *   ├── PencarianBatikController
 *   ├── PencarianWarnaController
 *   ├── PewarnaanPaletController
 *   ├── PewarnaanPromptController
 *   ├── TerapkanBatikController
 *   ├── RekomendasiBatikController
 *   ├── TextToImageController
 *   └── SharedMLController
 *
 * @see config/services.php               — Konfigurasi endpoint
 * @see routes/features.php               — Route definitions
 * @see docs/ML_API_STRUCTURE_PLAN.md     — Arsitektur API ML
 * @see docs/BLUEPRINT_IMPLEMENTATION_GUIDE.md — Progress implementasi
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
     * Base URL dari ML API server.
     * Diambil dari config('services.ml.base_url').
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Array endpoint paths yang tersedia.
     * Diambil dari config('services.ml.endpoints').
     *
     * @var array<string, string>
     */
    protected array $endpoints;

    /**
     * Inisialisasi konfigurasi ML API.
     *
     * Membaca base URL dan daftar endpoint dari config/services.php.
     * Jika ML_API_BASE_URL tidak diset, fitur ML tetap bisa berjalan
     * dalam mode stub (mengembalikan pesan "belum terhubung").
     */
    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.base_url', env('ML_API_BASE_URL', '')), '/');
        $this->endpoints = (array) config('services.ml.endpoints', []);
    }

    // ─── Status & URL Helpers ─────────────────────────────────────────

    /**
     * Cek apakah ML API sudah dikonfigurasi.
     *
     * @return bool true jika base URL tidak kosong
     */
    protected function isMLAvailable(): bool
    {
        return !empty($this->baseUrl);
    }

    /**
     * Bangun URL lengkap untuk endpoint ML tertentu.
     *
     * Mencari key di config/services.php → services.ml.endpoints,
     * fallback ke $default jika key tidak ditemukan.
     *
     * @param  string  $endpointKey  Key endpoint (misal: 'motif', 'blend')
     * @param  string  $default      Path default jika key tidak ada
     * @return string  URL lengkap (misal: http://127.0.0.1:5000/motif/scan)
     *
     * @example $this->mlUrl('motif', '/motif/scan')
     */
    protected function mlUrl(string $endpointKey, string $default): string
    {
        $path = $this->endpoints[$endpointKey] ?? $default;
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    // ─── Standard Responses ───────────────────────────────────────────

    /**
     * Response standar ketika ML API belum dikonfigurasi (503).
     *
     * Digunakan oleh semua controller sebagai guard awal:
     *   if (!$this->isMLAvailable()) return $this->notConfiguredResponse();
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notConfiguredResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Model AI belum terhubung. Endpoint ML_API_BASE_URL belum dikonfigurasi.',
        ], 503);
    }

    // ─── Image Detection Flow ─────────────────────────────────────────

    /**
     * Flow standar untuk deteksi gambar (upload → call ML API → normalize response).
     *
     * Digunakan oleh fitur yang pola interaksinya:
     *   1. User upload gambar batik
     *   2. Gambar dikirim ke ML API
     *   3. ML API mengembalikan label + confidence + description
     *
     * Response dinormalisasi ke format internal:
     *   { success: true, result: { label, confidence, description } }
     *
     * Fitur yang menggunakan method ini:
     *   - DeteksiMotifController::detect()
     *   - DeteksiJenisController::detect()
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan file 'image'
     * @param  string  $mlPath  Path endpoint ML API (misal: /motif/scan)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleImageDetection(Request $request, string $mlPath)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isMLAvailable()) {
            return response()->json([
                'success' => false,
                'stub'    => true,
                'message' => 'Model AI belum terhubung. Endpoint ML_API_BASE_URL belum dikonfigurasi.',
                'result'  => null,
            ], 503);
        }

        try {
            $url = $this->baseUrl . '/' . ltrim($mlPath, '/');
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($request->file('image')->getRealPath()), $request->file('image')->getClientOriginalName())
                ->post($url);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'result'  => [
                        'label'       => $data['label'] ?? $data['class'] ?? $data['result'] ?? 'Tidak Diketahui',
                        'confidence'  => $data['confidence'] ?? $data['probability'] ?? $data['score'] ?? 0,
                        'description' => $data['description'] ?? $data['desc'] ?? $data['message'] ?? '-',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Model AI tidak memberikan respons yang valid.',
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('ML API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server Model AI. Periksa koneksi atau endpoint.',
            ], 500);
        }
    }

    // ─── File Handling Utilities ───────────────────────────────────────

    /**
     * Attach file ke HTTP request sebagai multipart.
     *
     * Helper untuk mengirim file ke ML API menggunakan Laravel HTTP Client.
     *
     * @param  mixed  $http  Instance PendingRequest Laravel HTTP
     * @param  string  $name  Nama field multipart (misal: 'image', 'batik')
     * @param  \Illuminate\Http\UploadedFile  $file  File yang diupload
     * @return mixed  PendingRequest dengan file ter-attach
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
     *
     * Digunakan ketika endpoint menerima gambar dari file upload ATAU URL.
     * Prioritas: file upload → fetch dari URL → null.
     *
     * @param  \Illuminate\Http\UploadedFile|null  $file  File upload (nullable)
     * @param  string|null  $url  URL gambar (nullable)
     * @return array{body: string, content_type: string}|null  Binary + MIME type
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
     *
     * Membaca file gambar dari direktori `sample_fashion/` di root project
     * dan menghasilkan URL publik untuk masing-masing file.
     *
     * Digunakan oleh:
     *   - TerapkanBatikController::show()
     *   - RekomendasiBatikController::show()
     *
     * @return string[]  Array URL gambar sample fashion
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
     *
     * Route: GET /sample-fashion/{filename}
     * Melayani file gambar dari direktori `sample_fashion/` dengan
     * validasi keamanan path traversal.
     *
     * @param  string  $filename  Nama file gambar
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function serveSampleFashion(string $filename)
    {
        $dir  = base_path('sample_fashion');
        $path = realpath($dir . DIRECTORY_SEPARATOR . $filename);

        // Validasi: pastikan path tidak keluar dari direktori sample_fashion
        if (!$path || !str_starts_with($path, realpath($dir))) {
            abort(404);
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        return response()->file($path, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
