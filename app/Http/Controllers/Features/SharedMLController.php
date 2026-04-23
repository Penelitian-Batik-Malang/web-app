<?php

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SharedMLController extends BaseMLController
{
    /**
     * POST /api/inference — Detect fashion parts via Fashionpedia.
     * Dipakai bersama oleh fitur terapkan-batik dan rekomendasi-batik.
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
     * POST /api/reset — Reset session ke gambar original.
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
     * GET /api/session/{sessionId} — Ambil info session aktif.
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

    /**
     * GET /sample-fashion/{filename} — Serve sample fashion image.
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
}
