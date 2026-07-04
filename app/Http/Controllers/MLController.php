<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        /**
     * Endpoint: POST /api/colorize/palet
     * Input: batik_image (base64), color_image (base64), palette (optional, pre-extracted), skip_extract (boolean)
     * Process: 
     *   1. Gunakan palette yang sudah di-extract jika ada, atau extract dari color_image
     *   2. Recolor batik image dengan palette
     * Output: { success, result_image_url, result_image_path, processing_time_ms }
     */
    public function colorizePalet(Request $request)
    {
        $request->validate([
            'batik_image' => 'required|string', // base64
            'color_image' => 'required|string', // base64
            'palette' => 'sometimes|array',     // pre-extracted palette (optional)
            'skip_extract' => 'sometimes|boolean', // flag untuk skip extract
        ]);

        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Model AI belum terhubung. Endpoint ML_API_BASE_URL belum dikonfigurasi.',
            ], 503);
        }

        try {
            // Get batik image content dari base64
            $batikImageBase64 = $request->input('batik_image');
            $batikImageContent = $this->base64ToImageFile($batikImageBase64);
            
            // Get color image content dari base64
            $colorImageBase64 = $request->input('color_image');
            $colorImageContent = $this->base64ToImageFile($colorImageBase64);

            // Step 1: Get palette - dari request atau extract dari color_image
            $palettes = $request->input('palette', []);
            $skipExtract = $request->input('skip_extract', false);
            
            if (empty($palettes) || !$skipExtract) {
                // Extract palette dari color_image jika tidak disediakan atau skip_extract false
                $paletteResponse = Http::timeout(30)
                    ->attach('image', $colorImageContent, 'color_image.jpg')
                    ->attach('method', 'kmeans')
                    ->attach('n_colors', '6')
                    ->post($this->baseUrl . '/' . ltrim($this->endpoints['palette_extract'] ?? '/palette/extract', '/'));

                if (!$paletteResponse->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal extract palette dari gambar warna.',
                        'debug' => $paletteResponse->body(),
                    ], 400);
                }

                $paletteData = $paletteResponse->json();
                $palettes = $paletteData['palettes']['kmeans'] ?? [];
            }

            if (empty($palettes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengekstrak warna dari gambar. Pastikan gambar cukup jelas.',
                ], 400);
            }

            // Step 2: Recolor batik dengan palette
            $paletteJson = json_encode($palettes);
            
            $recolorResponse = Http::timeout(60)
                ->attach('image', $batikImageContent, 'batik.jpg')
                ->attach('palette', $paletteJson)
                ->attach('white_threshold', '150')
                ->post($this->baseUrl . '/' . ltrim($this->endpoints['recolor'] ?? '/recolor', '/'));

            if (!$recolorResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal melakukan recoloring pada batik.',
                    'debug' => $recolorResponse->body(),
                ], 400);
            }

            $result = $recolorResponse->json();
            
            // Construct full image URL
            $resultImageUrl = $result['result_image_url'] ?? null;
            
            // Normalize backslashes to forward slashes (Windows path fix)
            if ($resultImageUrl) {
                $resultImageUrl = str_replace('\\', '/', $resultImageUrl);
            }
            
            if ($resultImageUrl) {
                // If it's already a full URL, use as-is
                if (filter_var($resultImageUrl, FILTER_VALIDATE_URL)) {
                    // Already full URL
                } else {
                    // It's a relative path, prepend base URL
                    $baseUrl = rtrim($this->baseUrl, '/');
                    // Handle different path formats
                    if (strpos($resultImageUrl, '/uploads') === 0) {
                        $resultImageUrl = $baseUrl . $resultImageUrl;
                    } else {
                        // Assume it's relative to uploads
                        $resultImageUrl = $baseUrl . '/uploads/' . ltrim($resultImageUrl, '/');
                    }
                }
            }

            return response()->json([
                'success' => true,
                'result' => [
                    'result_image_url' => $resultImageUrl,
                    'result_image_path' => $result['result_image_path'] ?? null,
                    'processing_time_ms' => $result['processing_time_ms'] ?? 0,
                    'palette_used' => $palettes,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Colorize Palet Error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pewarnaan. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Convert base64 string to image file content
     */
    private function base64ToImageFile(string $base64String)
    {
        // Handle data URL format: data:image/jpeg;base64,xxx
        if (strpos($base64String, 'data:image') === 0) {
            $base64String = substr($base64String, strpos($base64String, ',') + 1);
        }
        
        return base64_decode($base64String);
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
}
