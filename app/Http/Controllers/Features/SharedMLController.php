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
                Http::timeout(600)->accept('application/json'),
                'image',
                $request->file('image')
            );
            $response = $http->post($url);

            if ($response->successful()) {
                return response()->json($response->json());
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
                ->asJson()
                ->post($url, ['session_id' => $request->input('session_id')]);

            if ($response->successful()) {
                return response()->json($response->json());
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
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                return response()->json($response->json());
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
}
