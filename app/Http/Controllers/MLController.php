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
