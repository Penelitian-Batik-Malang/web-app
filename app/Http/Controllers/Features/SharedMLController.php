<?php
/**
 * =========================================================================
 * SharedMLController — Shared Fashionpedia Session Management
 * =========================================================================
 *
 * Controller ini mengelola session Fashionpedia API yang digunakan
 * BERSAMA oleh dua fitur:
 *   1. Terapkan Batik (TerapkanBatikController)
 *   2. Rekomendasi Batik (RekomendasiBatikController)
 *
 * Kedua fitur tersebut menggunakan alur yang sama:
 *   Upload fashion → Inference (deteksi bagian pakaian) → Workspace
 *
 * TANGGUNG JAWAB:
 *   - inference()   : Deteksi bagian pakaian via Fashionpedia API
 *   - reset()       : Reset session ke gambar original
 *   - getSession()  : Ambil info session yang aktif
 *
 * CATATAN:
 *   - serveSampleFashion() sekarang ada di BaseMLController
 *   - Method di sini tidak spesifik ke mode terapkan/rekomendasi
 *   - Session ID dikelola oleh ML API, bukan Laravel
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
     * POST /api/inference
     *
     * Menerima gambar fashion dari frontend, mengirim ke ML API
     * endpoint /inference, dan mengembalikan:
     *   - session_id : ID unik session untuk blend selanjutnya
     *   - parts      : Daftar bagian pakaian terdeteksi (bbox + mask)
     *   - cbir       : Data rekomendasi CBIR (khusus mode rekomendasi)
     *   - image_size : Dimensi gambar yang diproses
     *
     * Dipakai bersama oleh:
     *   - Terapkan Batik  → setelah inference, langsung ke workspace
     *   - Rekomendasi     → setelah inference, tampilkan CBIR dulu
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan file 'image'
     * @return \Illuminate\Http\JsonResponse
     */
    public function inference(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isMLAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Fashionpedia API belum terhubung.',
            ], 503);
        }

        $url = $this->mlUrl('inference', '/inference');

        try {
            $http = $this->attachFile(
                Http::timeout(120)->accept('application/json'),
                'image',
                $request->file('image')
            );
            $response = $http->post($url);

            if ($response->successful()) {
                return response()->json($response->json());
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
     * Reset session Fashionpedia ke gambar original.
     *
     * POST /api/reset
     *
     * Mengembalikan semua blend yang sudah diterapkan ke gambar asli.
     * Digunakan ketika user ingin mulai ulang tanpa upload ulang.
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan 'session_id'
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        if (!$this->isMLAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->mlUrl('reset', '/reset');

        try {
            $response = Http::timeout(30)
                ->asJson()
                ->post($url, ['session_id' => $request->input('session_id')]);

            if ($response->successful()) {
                return response()->json($response->json());
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
     * Ambil info session Fashionpedia yang aktif.
     *
     * GET /api/session/{sessionId}
     *
     * Mengambil state session saat ini dari ML API, termasuk
     * gambar terkini dan daftar blend yang sudah diterapkan.
     *
     * @param  string  $sessionId  UUID session dari inference
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSession(string $sessionId)
    {
        if (!$this->isMLAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->mlUrl('session', '/session') . '/' . $sessionId;

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
            Log::error('Fashionpedia Session Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi Fashionpedia API.',
            ], 500);
        }
    }
}
