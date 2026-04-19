<?php

namespace App\Http\Controllers;

use App\Models\Batik;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLController extends Controller
{
    /**
     * Base URL Model ML — ganti dengan endpoint nyata saat production.
     * Endpoint ini bisa diset via .env: ML_API_BASE_URL=http://...
     */
    private string $baseUrl;
    private array $endpoints;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.base_url', env('ML_API_BASE_URL', '')), '/');
        $this->endpoints = (array) config('services.ml.endpoints', []);
    }

    /**
     * Endpoint: POST /api/detect/motif
     * Input: Gambar (multipart/form-data)
     * Output: { label, confidence, description }
     */
    public function detectMotif(Request $request)
    {
        $path = $this->endpoints['motif'] ?? '/motif/scan';
        return $this->handleImageDetection($request, $path);
    }

    /**
     * Endpoint: POST /api/detect/jenis
     * Input: Gambar (multipart/form-data)
     * Output: { label, confidence, description }
     */
    public function detectJenis(Request $request)
    {
        $path = $this->endpoints['jenis'] ?? '/tulis/scan';
        return $this->handleImageDetection($request, $path);
    }

    public function detectMask(Request $request)
    {
        $request->validate([
            'fashion_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'fashion_url' => 'nullable|url',
        ]);

        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Model AI belum terhubung. Endpoint ML_API_BASE_URL belum dikonfigurasi.',
            ], 503);
        }

        $maskPath = $this->endpoints['fashion_mask'] ?? '/fashion-mask';
        $url = $this->baseUrl . '/' . ltrim($maskPath, '/');

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
                    'success' => true,
                    'mask_base64' => $data['mask_base64'] ?? $data['mask'] ?? null,
                    'mask_url' => $data['mask_url'] ?? null,
                    'meta' => $data,
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

    public function showApplyBatik()
    {
        $fashionSamples = [
            'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=480&q=80',
            'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=480&q=80',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=480&q=80',
            'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=480&q=80',
            'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=480&q=80',
            'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=480&q=80',
        ];

        $batikSamples = Batik::query()
            ->where('is_active', true)
            ->with('mainImage')
            ->latest()
            ->limit(12)
            ->get()
            ->map(function ($batik) {
                $imagePath = optional($batik->mainImage)->image_path;
                return [
                    'name' => $batik->name,
                    'description' => $batik->description,
                    'image_url' => $imagePath ? asset('storage/' . ltrim($imagePath, '/')) : null,
                ];
            })
            ->filter(fn ($item) => !empty($item['image_url']))
            ->values();

        return view('pages.terapkan-batik', [
            'fashionSamples' => $fashionSamples,
            'batikSamples' => $batikSamples,
        ]);
    }

    public function applyBatik(Request $request)
    {
        $request->validate([
            'fashion_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'batik_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'fashion_url' => 'nullable|url',
            'batik_url' => 'nullable|url',
            'mask_target' => 'nullable|string|in:full,upper_clothes,dress,sleeves',
            'mask_image' => 'nullable|image|mimes:png,jpeg,jpg|max:10240',
        ]);

        $hasFashion = $request->hasFile('fashion_image') || filled($request->input('fashion_url'));
        $hasBatik = $request->hasFile('batik_image') || filled($request->input('batik_url'));
        if (!$hasFashion || !$hasBatik) {
            return response()->json([
                'success' => false,
                'message' => 'Input fashion dan batik wajib diisi.',
            ], 422);
        }

        // Coba panggil endpoint ML jika tersedia.
        if (!empty($this->baseUrl)) {
            $applyPath = $this->endpoints['apply_batik'] ?? '/apply-batik';
            $url = $this->baseUrl . '/' . ltrim($applyPath, '/');

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

        // Fallback sementara: kembalikan image fashion agar alur UI tetap berjalan.
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

    /**
     * POST /api/inference - Detect fashion parts using Fashionpedia
     */
    public function inference(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Fashionpedia API belum terhubung.',
            ], 503);
        }

        $inferencePath = $this->endpoints['inference'] ?? '/inference';
        $url = $this->baseUrl . '/' . ltrim($inferencePath, '/');

        try {
            $http = Http::timeout(120)->accept('application/json');

            $http = $this->attachFile($http, 'image', $request->file('image'));

            $response = $http->post($url);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendeteksi bagian fashion.',
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
     * POST /api/blend - Apply batik to specific part
     */
    public function blend(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'part' => 'required|string|in:body,sleeve,collar,lapel,hood,pocket,neckline,epaulette',
            'instance_index' => 'nullable|integer|min:0',
            'batik' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Fashionpedia API belum terhubung.',
            ], 503);
        }

        $blendPath = $this->endpoints['blend'] ?? '/blend';
        $url = $this->baseUrl . '/' . ltrim($blendPath, '/');

        try {
            $batikFile = $request->file('batik');
            $batikRealPath = $batikFile->getRealPath();

            // Validasi bahwa file benar-benar ada sebelum dikirim ke Guzzle.
            // fopen() bisa gagal (return false) jika temp file sudah tidak ada,
            // dan Guzzle akan throw 'A contents key is required'.
            if (!$batikRealPath || !file_exists($batikRealPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File batik tidak valid atau tidak dapat dibaca. Coba unggah ulang.',
                ], 422);
            }

            // Baca isi file ke dalam string untuk menghindari masalah dengan fopen + temp files.
            $batikContents = file_get_contents($batikRealPath);
            if ($batikContents === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca file batik. Coba unggah ulang.',
                ], 422);
            }

            // Pakai GuzzleHttp langsung — menghindari bug Laravel HTTP wrapper
            // pada beberapa versi saat membangun multipart stream.
            $guzzle = new GuzzleClient(['timeout' => 60]);
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

    /**
     * POST /api/reset - Reset session to original image
     */
    public function reset(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Fashionpedia API belum terhubung.',
            ], 503);
        }

        $resetPath = $this->endpoints['reset'] ?? '/reset';
        $url = $this->baseUrl . '/' . ltrim($resetPath, '/');

        try {
            $response = Http::timeout(30)
                ->asJson()
                ->post($url, ['session_id' => $request->input('session_id')]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset gambar.',
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashionpedia Reset Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi Fashionpedia API.',
            ], 500);
        }
    }

    /**
     * GET /api/session/{session_id} - Get session info
     */
    public function getSession($sessionId)
    {
        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Fashionpedia API belum terhubung.',
            ], 503);
        }

        $sessionPath = $this->endpoints['session'] ?? '/session';
        $url = $this->baseUrl . '/' . ltrim($sessionPath, '/') . '/' . $sessionId;

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Session tidak ditemukan.',
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashionpedia Session Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi Fashionpedia API.',
            ], 500);
        }
    }

    /**
     * Shared handler untuk semua fitur image → text classification.
     * Ketika ML_API_BASE_URL tidak diset, kembalikan stub response.
     */
    private function handleImageDetection(Request $request, string $mlPath)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240'
        ]);

        // Jika URL ML belum dikonfigurasi, kembalikan stub informatif
        if (empty($this->baseUrl)) {
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
                        // Normalisasi response agar UI/JS konsisten.
                        'label'       => $data['label'] ?? $data['class'] ?? $data['result'] ?? 'Tidak Diketahui',
                        // API produksi: confidence biasanya number (0..1 atau 0..100). UI sudah handle >1 sebagai persen.
                        'confidence'  => $data['confidence'] ?? $data['probability'] ?? $data['score'] ?? 0,
                        // API produksi yang kamu tunjukkan belum tentu ada description.
                        'description' => $data['description'] ?? $data['desc'] ?? $data['message'] ?? '-',
                    ]
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

    private function attachFile($http, string $name, UploadedFile $file)
    {
        return $http->asMultipart()->attach(
            $name,
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        );
    }

    private function resolveInputImageBinary(?UploadedFile $file, ?string $url): ?array
    {
        if ($file instanceof UploadedFile) {
            return [
                'body' => file_get_contents($file->getRealPath()),
                'content_type' => $file->getMimeType() ?: 'image/jpeg',
            ];
        }

        if (!empty($url)) {
            $resp = Http::timeout(20)->get($url);
            if ($resp->successful()) {
                return [
                    'body' => $resp->body(),
                    'content_type' => $resp->header('Content-Type', 'image/jpeg'),
                ];
            }
        }

        return null;
    }
}
