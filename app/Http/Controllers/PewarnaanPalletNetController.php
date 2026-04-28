<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Batik;

class PewarnaanPalletNetController extends Controller
{
    /**
     * Base URL untuk ML API
     */
    private string $baseUrl;
    private array $endpoints;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.base_url', env('ML_API_BASE_URL', '')), '/');
        $this->endpoints = (array) config('services.ml.endpoints', []);
    }

    /**
     * GET /pewarnaan/palet
     * Menampilkan halaman awal pewarnaan dengan pilihan batik
     */
    public function showPalet()
    {
        $batiks = Batik::where('is_active', true)
            ->with('mainImage')
            ->get();

        return view('pages.pewarnaan-pallet-warna', compact('batiks'));
    }

    /**
     * POST /pewarnaan/palet/proses
     * Memproses gambar batik dan gambar warna, kemudian ekstrak palette
     * 
     * Input:
     * - batik_image: base64 encoded gambar batik
     * - color_image: base64 encoded gambar warna referensi
     */
    public function processPalette(Request $request)
    {
        try {
            $request->validate([
                'batik_image' => 'required|string',
                'color_image' => 'required|string',
            ]);

            $batikImage = $request->input('batik_image');
            $colorImage = $request->input('color_image');

            if (empty($batikImage)) {
                return redirect()->route('pewarnaan.palet')
                    ->withErrors(['error' => 'Gambar batik sumber tidak ditemukan.']);
            }

            if (empty($colorImage)) {
                return redirect()->route('pewarnaan.palet')
                    ->withErrors(['error' => 'Gambar warna referensi belum diunggah.']);
            }

            // Extract palette dari color_image menggunakan semua 3 metode
            $palettes = $this->extractPalettes($colorImage);

            // Pass data ke view
            return view('pages.pewarnaanPalletNet.proses-gambar', [
                'batikImage' => $batikImage,
                'colorImage' => $colorImage,
                'palettesKmeans' => $palettes['kmeans'] ?? [],
                'palettesHistogram' => $palettes['histogram'] ?? [],
                'paletteMedianCut' => $palettes['median_cut'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('Pewarnaan Process Palette Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()->route('pewarnaan.palet')
                ->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /api/colorize/palet
     * Melakukan colorization pada batik dengan palette yang dipilih user
     * 
     * Input:
     * - batik_image: base64 encoded gambar batik
     * - color_image: base64 encoded gambar warna referensi
     * - palette: array warna yang dipilih/dimodifikasi user
     * - method: metode ekstraksi palette (kmeans, histogram, median)
     * - skip_extract: boolean untuk skip ekstraksi dan langsung pakai palette dari request
     */
    public function colorize(Request $request)
    {
        try {
            $request->validate([
                'batik_image' => 'required|string',
                'color_image' => 'required|string',
                'palette' => 'required|array',
                'method' => 'sometimes|string',
                'skip_extract' => 'sometimes|boolean',
            ]);

            if (empty($this->baseUrl)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model AI belum terhubung. Endpoint ML_API_BASE_URL belum dikonfigurasi.',
                ], 503);
            }

            $batikImageBase64 = $request->input('batik_image');
            $palette = $request->input('palette', []);
            $method = $request->input('method', 'kmeans');

            // Validasi palette
            if (empty($palette)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Palette warna tidak lengkap. Silakan upload ulang gambar warna Anda.',
                ], 400);
            }

            // Konversi base64 ke binary
            $batikImageContent = $this->convertBase64ToImageFile($batikImageBase64);

            // Step 1: Lakukan recoloring dengan palette yang dikirim
            $paletteJson = json_encode($palette);

            $recolorResponse = Http::timeout(60)
                ->attach('image', $batikImageContent, 'batik.jpg')
                ->attach('palette', $paletteJson)
                ->attach('white_threshold', '150')
                ->post($this->baseUrl . '/' . ltrim($this->endpoints['recolor'] ?? '/recolor', '/'));

            if (!$recolorResponse->successful()) {
                Log::warning('Recolor API Error', [
                    'status' => $recolorResponse->status(),
                    'body' => $recolorResponse->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal melakukan recoloring pada batik.',
                ], 400);
            }

            $result = $recolorResponse->json();

            // Construct full image URL
            $resultImageUrl = $result['result_image_url'] ?? null;
            if ($resultImageUrl && !filter_var($resultImageUrl, FILTER_VALIDATE_URL)) {
                // It's a relative path, prepend base URL
                $baseUrl = rtrim($this->baseUrl, '/');
                if (strpos($resultImageUrl, '/uploads') === 0) {
                    $resultImageUrl = $baseUrl . $resultImageUrl;
                } else {
                    $resultImageUrl = $baseUrl . '/uploads/' . ltrim($resultImageUrl, '/');
                }
            }

            Log::info('Colorization successful', [
                'method' => $method,
                'result_url' => $resultImageUrl,
            ]);

            return response()->json([
                'success' => true,
                'result' => [
                    'result_image_url' => $resultImageUrl,
                    'result_image_path' => $result['result_image_path'] ?? null,
                    'processing_time_ms' => $result['processing_time_ms'] ?? 0,
                    'palette_used' => $palette,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Colorize Error: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pewarnaan. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /pewarnaan/output-gambar
     * Menampilkan hasil pewarnaan dari session
     */
    public function showOutput(Request $request)
    {
        $results = $request->session()->get('colorize_results', []);
        $batikImage = $request->session()->get('colorize_batik_image', '');
        $colorImage = $request->session()->get('colorize_color_image', '');

        if (empty($results)) {
            return redirect()->route('pewarnaan.palet')
                ->withErrors(['error' => 'Tidak ada hasil pewarnaan. Silakan proses ulang.']);
        }

        return view('pages.pewarnaanPalletNet.output-gambar', compact('results', 'batikImage', 'colorImage'));
    }

    /**
     * POST /api/save-results
     * Menyimpan hasil pewarnaan ke session
     * 
     * Input:
     * - batik_image: gambar batik awal
     * - color_image: gambar warna referensi
     * - results: array berisi hasil dari 3 metode pewarnaan
     */
    public function saveResults(Request $request)
    {
        try {
            $request->validate([
                'results' => 'required|array',
                'batik_image' => 'required|string',
                'color_image' => 'required|string',
            ]);

            $results = $request->input('results', []);
            $batikImage = $request->input('batik_image', '');
            $colorImage = $request->input('color_image', '');

            // Validasi hasil memiliki minimal satu metode
            if (empty($results)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada hasil pewarnaan untuk disimpan.',
                ], 400);
            }

            // Simpan ke session
            $request->session()->put('colorize_results', $results);
            $request->session()->put('colorize_batik_image', $batikImage);
            $request->session()->put('colorize_color_image', $colorImage);

            Log::info('Colorization results saved to session');

            return response()->json([
                'success' => true,
                'message' => 'Hasil pewarnaan berhasil disimpan',
            ]);

        } catch (\Exception $e) {
            Log::error('Save Results Error: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ===== HELPER METHODS =====
     */

    /**
     * Extract palette dari color image menggunakan semua 3 metode (kmeans, histogram, median_cut)
     * 
     * @param string $colorImageBase64 Base64 encoded color image
     * @return array Array berisi palettes dari 3 metode
     */
    private function extractPalettes(string $colorImageBase64): array
    {
        $palettes = [
            'kmeans' => [],
            'histogram' => [],
            'median_cut' => [],
        ];

        if (empty($this->baseUrl)) {
            Log::warning('ML API Base URL tidak dikonfigurasi, skipping palette extraction');
            return $palettes;
        }

        try {
            // Konversi base64 ke binary
            $colorImageContent = $this->convertBase64ToImageFile($colorImageBase64);

            // Call API extract palette dengan method "all" untuk mendapatkan 3 metode sekaligus
            $response = Http::timeout(30)
                ->attach('image', $colorImageContent, 'color_image.jpg')
                ->attach('method', 'all')
                ->attach('n_colors', '6')
                ->post($this->baseUrl . '/palette/extract');

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['palettes'])) {
                    $palettes['kmeans'] = $data['palettes']['kmeans'] ?? [];
                    $palettes['histogram'] = $data['palettes']['histogram'] ?? [];
                    $palettes['median_cut'] = $data['palettes']['median_cut'] ?? [];
                }

                Log::info('Palettes extracted successfully', [
                    'kmeans_count' => count($palettes['kmeans']),
                    'histogram_count' => count($palettes['histogram']),
                    'median_cut_count' => count($palettes['median_cut']),
                ]);
            } else {
                Log::warning('Palette extraction failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Palette extraction error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return $palettes;
    }

    /**
     * Convert base64 string ke image file content (binary)
     * Handle format data URL (data:image/jpeg;base64,xxx) dan plain base64
     * 
     * @param string $base64String Base64 encoded string
     * @return string|false Binary content atau false jika gagal
     */
    private function convertBase64ToImageFile(string $base64String)
    {
        // Handle data URL format
        if (strpos($base64String, 'data:image') === 0) {
            $base64String = substr($base64String, strpos($base64String, ',') + 1);
        }

        return base64_decode($base64String, true);
    }
}
