<?php
/**
 * =========================================================================
 * PewarnaanPromptController — Pewarnaan Batik by Prompt Teks
 * =========================================================================
 *
 * Fitur ini memungkinkan user memberikan instruksi teks (prompt) untuk
 * mewarnai ulang motif batik secara AI. Gambar batik dan prompt teks
 * dikirim ke API ML, yang menginterpretasi instruksi dan menghasilkan
 * gambar batik dengan warna baru sesuai deskripsi.
 *
 * @status  TODO — Menunggu endpoint API ML tersedia
 * @menu    pewarnaan-prompt
 * @see     config/services.php → services.ml.endpoints.pewarnaan_prompt
 *
 * Alur kerja:
 *   1. User mengunggah gambar batik
 *   2. User menulis instruksi teks (misal: "ubah ke warna biru langit")
 *   3. Frontend mengirim gambar + prompt ke controller ini
 *   4. Controller meneruskan ke API ML endpoint /recolor/prompt
 *   5. API ML mengembalikan gambar hasil recoloring
 *   6. Controller mengembalikan gambar ke frontend
 *
 * API Endpoint yang dibutuhkan:
 *   POST {ML_BASE_URL}/recolor/prompt
 *     Input : image (multipart), prompt (string instruksi teks)
 *     Output: { success, result_image_url } atau binary image
 *
 * Langkah implementasi:
 *   1. Pastikan endpoint terdaftar di config/services.php (sudah)
 *   2. Implementasi method process() di bawah
 *   3. Desain UI di resources/views/pages/features/pewarnaan-prompt.blade.php
 *   4. Aktifkan route POST di routes/features.php
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class PewarnaanPromptController extends BaseMLController
{
    /**
     * Override mlServiceUrl to use the new colorizer python backend (port 8000)
     */
    protected function mlServiceUrl(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        if (!str_starts_with($cleanPath, 'api/')) {
            $cleanPath = 'api/' . $cleanPath;
        }

        $colorizerUrl = config('services.ml.colorizer_url', 'http://127.0.0.1:8000');
        return rtrim($colorizerUrl, '/') . '/' . $cleanPath;
    }

    /**
     * Tampilkan halaman pewarnaan by prompt teks.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('pages.features.pewarnaan-prompt');
    }

    /**
     * Ambil daftar template prompt dari ML.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function templates()
    {
        if (!$this->isMLAvailable()) {
            return response()->json(['templates' => []]);
        }

        try {
            $endpointPath = config('services.ml.endpoints.pewarnaan_templates', '/templates');
            $url  = $this->mlServiceUrl($endpointPath);

            $response = \Illuminate\Support\Facades\Http::timeout(60)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return response()->json(['templates' => []]);
        } catch (\Exception $e) {
            return response()->json(['templates' => []]);
        }
    }

    /**
     * Proses pewarnaan ulang batik dari instruksi teks/prompt AI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        $request->validate([
            'image'         => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'prompt_mode'   => 'nullable|string',
            'template_id'   => 'nullable|integer',
            'custom_prompt' => 'nullable|string',
            'neg_prompt'    => 'nullable|string',
            'steps'         => 'nullable|integer',
            'cfg_scale'     => 'nullable|numeric',
            'color_scale'   => 'nullable|numeric',
        ]);

        if (!$this->isMLAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Model AI belum terhubung. Konfigurasi ML_URL di .env.',
            ], 503);
        }

        try {
            $endpointPath = config('services.ml.endpoints.pewarnaan_prompt', '/colorize');
            $url  = $this->mlServiceUrl($endpointPath);
            $file = $request->file('image');

            if (empty($this->apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi API Key ML tidak ditemukan.',
                ], 503);
            }

            $response = \Illuminate\Support\Facades\Http::timeout(120)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->attach(
                    'image', 
                    file_get_contents($file->getRealPath()), 
                    $file->getClientOriginalName(),
                    ['Content-Type' => $file->getClientMimeType()]
                )
                ->post($url, [
                    'prompt_mode'   => $request->input('prompt_mode', 'custom'),
                    'template_id'   => $request->input('template_id', 1),
                    'custom_prompt' => $request->input('custom_prompt', ''),
                    'neg_prompt'    => $request->input('neg_prompt', ''),
                    'steps'         => $request->input('steps', 50),
                    'cfg_scale'     => $request->input('cfg_scale', 12.0),
                    'color_scale'   => $request->input('color_scale', 0.8),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $outputB64 = $data['output_image_b64'] ?? null;
                if ($outputB64) {
                    $data['result_image_url'] = 'data:image/jpeg;base64,' . $outputB64;
                }
                
                return response()->json($data);
            }

            \Illuminate\Support\Facades\Log::error('ML API Prompt Colorize returned non-success', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Model AI tidak memberikan respons yang valid.',
            ], $response->status());

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ML API Prompt Colorize Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server Model AI. Periksa koneksi atau endpoint.',
            ], 500);
        }
    }
}
