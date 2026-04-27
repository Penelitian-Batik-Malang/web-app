<?php
/**
 * =========================================================================
 * TerapkanBatikController — Terapkan Motif Batik ke Citra Fashion
 * =========================================================================
 *
 * Fitur ini memungkinkan user menerapkan motif batik dari galeri ke
 * bagian pakaian pada citra fashion. Menggunakan Fashionpedia untuk
 * segmentasi pakaian dan blending engine untuk menerapkan motif.
 *
 * @status  DONE
 * @menu    terapkan-batik
 *
 * Alur kerja lengkap:
 *   1. Upload    → User unggah/pilih gambar fashion
 *   2. Inference → SharedMLController::inference() mendeteksi bagian pakaian
 *   3. Workspace → User melihat canvas dengan overlay bagian terdeteksi
 *   4. Panel     → User pilih bagian, pilih motif batik, atur posisi
 *   5. Blend     → Controller mengirim crop batik + session ke ML API
 *   6. Result    → Gambar fashion dengan motif batik yang sudah diterapkan
 *
 * Session lifecycle:
 *   - Session dibuat oleh ML API saat inference (SharedMLController)
 *   - Session menyimpan gambar original + state blend terkini
 *   - Reset mengembalikan ke gambar original (SharedMLController::reset())
 *   - Blend mengubah state gambar di server (method blend() di sini)
 *
 * Endpoints yang digunakan:
 *   - inference    : POST /api/inference     (SharedMLController)
 *   - blend        : POST /api/blend         (method blend() di sini)
 *   - reset        : POST /api/reset         (SharedMLController)
 *   - session      : GET  /api/session/{id}  (SharedMLController)
 *   - detect_mask  : POST /api/detect/mask   (legacy, method detectMask())
 *   - apply_batik  : POST /api/apply-batik   (legacy, method applyBatik())
 *
 * @see SharedMLController                — Shared session management
 * @see RekomendasiBatikController        — Fitur serupa dengan CBIR
 * @see config/services.php               — Endpoint configuration
 * @see resources/views/pages/features/terapkan-batik.blade.php  — View
 * @see resources/views/pages/features/shared/scripts.blade.php  — JS logic
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use App\Models\Batik;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    public function detectMask(Request $request)
    {
        $request->validate([
            'fashion_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'fashion_url'   => 'nullable|url',
        ]);

        if (!$this->isMLAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Model AI belum terhubung. Endpoint ML_API_BASE_URL belum dikonfigurasi.',
            ], 503);
        }

        $url = $this->mlUrl('fashion_mask', '/fashion-mask');

        try {
            $http = Http::timeout(60)->accept('application/json');

            if ($request->hasFile('fashion_image')) {
                $http = $this->attachFile($http, 'fashion_image', $request->file('fashion_image'));
            } elseif ($request->filled('fashion_url')) {
                $http = $http->asMultipart()->attach('fashion_url', $request->string('fashion_url')->toString());
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'fashion_image atau fashion_url harus dikirim untuk deteksi mask.',
                ], 422);
            }

            $response = $http->post($url);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success'      => true,
                    'mask_base64'  => $data['mask_base64'] ?? $data['mask'] ?? null,
                    'mask_url'     => $data['mask_url'] ?? null,
                    'meta'         => $data,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Model AI tidak memberikan respons mask yang valid.',
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('ML API Mask Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server Model AI untuk mask.',
            ], 500);
        }
    }

    public function applyBatik(Request $request)
    {
        $request->validate([
            'fashion_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'batik_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'fashion_url'   => 'nullable|url',
            'batik_url'     => 'nullable|url',
            'mask_target'   => 'nullable|string|in:full,upper_clothes,dress,sleeves',
            'mask_image'    => 'nullable|image|mimes:png,jpeg,jpg|max:10240',
        ]);

        $hasFashion = $request->hasFile('fashion_image') || filled($request->input('fashion_url'));
        $hasBatik   = $request->hasFile('batik_image') || filled($request->input('batik_url'));

        if (!$hasFashion || !$hasBatik) {
            return response()->json([
                'success' => false,
                'message' => 'Input fashion dan batik wajib diisi.',
            ], 422);
        }

        if ($this->isMLAvailable()) {
            $url = $this->mlUrl('apply_batik', '/apply-batik');

            try {
                $http = Http::timeout(60)->accept('image/*,application/json');

                if ($request->hasFile('fashion_image')) {
                    $http = $this->attachFile($http, 'fashion_image', $request->file('fashion_image'));
                } elseif ($request->filled('fashion_url')) {
                    $http = $http->asMultipart()->attach('fashion_url', $request->string('fashion_url')->toString());
                }

                if ($request->hasFile('batik_image')) {
                    $http = $this->attachFile($http, 'batik_image', $request->file('batik_image'));
                } elseif ($request->filled('batik_url')) {
                    $http = $http->asMultipart()->attach('batik_url', $request->string('batik_url')->toString());
                }

                if ($request->hasFile('mask_image')) {
                    $http = $this->attachFile($http, 'mask_image', $request->file('mask_image'));
                }

                if ($request->filled('mask_target')) {
                    $http = $http->asMultipart()->attach('mask_target', $request->input('mask_target'));
                }

                $response = $http->post($url);

                if ($response->successful()) {
                    $contentType = $response->header('Content-Type', '');
                    if (str_contains($contentType, 'image/')) {
                        return response($response->body(), 200)->header('Content-Type', $contentType);
                    }

                    $json = $response->json();
                    if (is_array($json)) {
                        if (!empty($json['image_base64'])) {
                            $bin = base64_decode($json['image_base64'], true);
                            if ($bin !== false) {
                                return response($bin, 200)->header('Content-Type', 'image/png');
                            }
                        }
                        if (!empty($json['image_url'])) {
                            $img = Http::timeout(30)->get($json['image_url']);
                            if ($img->successful()) {
                                return response($img->body(), 200)->header('Content-Type', $img->header('Content-Type', 'image/png'));
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Apply batik endpoint fallback: ' . $e->getMessage());
            }
        }

        // Fallback: kembalikan image fashion agar alur UI tetap berjalan.
        $fallback = $this->resolveInputImageBinary(
            $request->file('fashion_image'),
            $request->input('fashion_url')
        );

        if ($fallback) {
            return response($fallback['body'], 200)->header('Content-Type', $fallback['content_type']);
        }

        return response()->json([
            'success' => false,
            'message' => 'Belum bisa menghasilkan gambar. API model belum tersedia.',
        ], 503);
    }

    public function blend(Request $request)
    {
        $request->validate([
            'session_id'     => 'required|string',
            'part'           => 'required|string|in:shirt,t-shirt,sweater,cardigan,jacket,vest,dress,jumpsuit,suit,coat,sleeve,collar,lapel,hood,pocket,neckline,epaulette',
            'instance_index' => 'nullable|integer|min:0',
            'batik'          => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isMLAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->mlUrl('blend', '/blend');

        try {
            $batikFile     = $request->file('batik');
            $batikRealPath = $batikFile->getRealPath();

            if (!$batikRealPath || !file_exists($batikRealPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File batik tidak valid atau tidak dapat dibaca. Coba unggah ulang.',
                ], 422);
            }

            $batikContents = file_get_contents($batikRealPath);
            if ($batikContents === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca file batik. Coba unggah ulang.',
                ], 422);
            }

            // Pakai GuzzleHttp langsung — menghindari bug Laravel HTTP wrapper pada beberapa versi.
            $guzzle     = new GuzzleClient(['timeout' => 60]);
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
                'message' => 'API error ' . $statusCode . ': ' . $body,
            ], $statusCode);

        } catch (\Throwable $e) {
            Log::error('Fashionpedia Blend Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Blend error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
