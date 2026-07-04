<?php
/**
 * =========================================================================
 * PewarnaanPaletController — Pewarnaan Batik by Palet Warna (PalletNet)
 * =========================================================================
 *
 * Fitur ini memungkinkan user mengubah warna kain batik menggunakan
 * palet warna pilihan. Alur lengkap:
 *
 * 1. User mengunggah gambar batik
 * 2. User mengunggah gambar warna referensi
 * 3. Sistem ekstrak palette dari gambar warna (3 metode: kmeans, histogram, median_cut)
 * 4. User dapat memodifikasi warna di color picker
 * 5. Sistem process pewarnaan untuk semua 3 metode secara paralel
 * 6. Hasil ditampilkan di halaman output
 *
 * @menu    pewarnaan-palet
 * @see     config/services.php → services.ml.base_url, endpoints
 *
 * API Endpoints yang digunakan:
 *   POST {ML_BASE_URL}/palette/extract
 *     Input : image, method='all', n_colors=6
 *     Output: { palettes: { kmeans: [...], histogram: [...], median_cut: [...] } }
 *   
 *   POST {ML_BASE_URL}/recolor
 *     Input : image, palette (JSON), white_threshold=150
 *     Output: { result_image_url, result_image_path, processing_time_ms }
 *
 * @status  IMPLEMENTED ✓
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Batik;

class PewarnaanPaletController extends BaseMLController
{
    /**
     * Tampilkan halaman awal pewarnaan dengan pilihan batik
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $batiks = Batik::where('is_active', true)
            ->with('mainImage')
            ->get();

        return view('pages.features.pewarnaan-palet.index', compact('batiks'));
    }

    /**
     * POST /pewarnaan/palet/proses
     * Proses gambar batik dan pilihan warna (auto-extract, upload image, atau manual color)
     * 
     * Input:
     * - batik_image: base64 encoded gambar batik (required)
     * - color_source_type: 'auto-extract', 'upload' atau 'manual' (required)
     * - color_image: base64 encoded gambar warna (required jika color_source_type='upload')
     * - manual_color: hex color code (required jika color_source_type='manual')
     *
    * @return \Illuminate\View\View|RedirectResponse
     */
    public function processPalette(Request $request)
    {
        try {
            // Validasi umum
            $request->validate([
                'batik_image' => 'required|string',
                'color_source_type' => 'required|in:auto-extract,upload,manual',
            ]);

            $batikImage = $request->input('batik_image');
            $colorSourceType = $request->input('color_source_type');

            if (empty($batikImage)) {
                return redirect()->route('pewarnaan.palet')
                    ->withErrors(['error' => 'Gambar batik sumber tidak ditemukan.']);
            }

            $palettes = [];
            $colorImage = null;
            $manualColor = null;
            $manualColors = [];
            $isAutoExtract = false;

            // Handle berdasarkan tipe sumber warna
            if ($colorSourceType === 'auto-extract') {
                // FITUR BARU: Ekstrak warna otomatis dari batik image
                $palettes = $this->extractPalettes($batikImage);
                $isAutoExtract = true;

                Log::info('Processing palette from batik image (auto-extract)', [
                    'has_batik_image' => !empty($batikImage),
                    'kmeans_count' => count($palettes['kmeans'] ?? []),
                    'histogram_count' => count($palettes['histogram'] ?? []),
                    'median_count' => count($palettes['median_cut'] ?? []),
                ]);

            } else if ($colorSourceType === 'upload') {
                // Validasi color_image harus ada untuk tipe upload
                $request->validate([
                    'color_image' => 'required|string',
                ]);

                $colorImage = $request->input('color_image');
                
                if (empty($colorImage)) {
                    return redirect()->route('pewarnaan.palet')
                        ->withErrors(['error' => 'Gambar warna referensi belum diunggah.']);
                }

                // Extract palette dari color_image menggunakan semua 3 metode
                $palettes = $this->extractPalettes($colorImage);

                Log::info('Processing palette from image', [
                    'has_batik_image' => !empty($batikImage),
                    'has_color_image' => !empty($colorImage),
                    'kmeans_count' => count($palettes['kmeans'] ?? []),
                    'histogram_count' => count($palettes['histogram'] ?? []),
                    'median_count' => count($palettes['median_cut'] ?? []),
                ]);

            } else if ($colorSourceType === 'manual') {
                // Validasi manual_color harus ada untuk tipe manual
                $request->validate([
                    'manual_color' => 'required|string',
                ]);

                $manualColorJson = $request->input('manual_color');

                if (empty($manualColorJson)) {
                    return redirect()->route('pewarnaan.palet')
                        ->withErrors(['error' => 'Warna tidak dipilih.']);
                }

                // Parse JSON array dari manual_color
                $manualColors = json_decode($manualColorJson, true);
                
                if (!is_array($manualColors) || empty($manualColors)) {
                    return redirect()->route('pewarnaan.palet')
                        ->withErrors(['error' => 'Format warna tidak valid.']);
                }

                // Buat palette dari manual colors (sama untuk semua 3 metode)
                $palettes = [
                    'kmeans' => $manualColors,
                    'histogram' => $manualColors,
                    'median_cut' => $manualColors,
                ];

                Log::info('Processing palette from manual color picker', [
                    'has_batik_image' => !empty($batikImage),
                    'manual_colors' => $manualColors,
                ]);
            }

            // Pass data ke view
            return view('pages.features.pewarnaan-palet.proses', [
                'batikImage' => $batikImage,
                'colorImage' => $colorImage,
                'colorSourceType' => $colorSourceType,
                'manualColor' => $manualColor,
                'manualColors' => $manualColors,
                'palettesKmeans' => $palettes['kmeans'] ?? [],
                'palettesHistogram' => $palettes['histogram'] ?? [],
                'paletteMedianCut' => $palettes['median_cut'] ?? [],
                'isAutoExtract' => $isAutoExtract,
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
     * - skip_extract: boolean untuk skip ekstraksi dan langsung pakai palette
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function colorize(Request $request)
    {
        try {
            $request->validate([
                'batik_image' => 'required|string',
                'palette' => 'required|array',
                'method' => 'sometimes|string',
            ]);

            if (!$this->isMLAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model AI belum terhubung. ML API Base URL belum dikonfigurasi.',
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
            $batikImageContent = $this->base64ToImageFile($batikImageBase64);

            // Konversi palette dari HEX ke HEX format string untuk API
            // Backend expects: ["#FF0000", "#00FF00", ...] NOT RGB objects!
            $paletteHex = $this->convertHexToHexForApi($palette);
            $paletteJson = json_encode($paletteHex);

            Log::info('Sending recolor request', [
                'base_url' => $this->mlUrl,
                'image_size' => strlen($batikImageContent),
                'palette_hex' => $paletteJson,
                'method' => $method,
            ]);

            // Try dengan fallback endpoints jika primary gagal
            $startTime = microtime(true);
            $recolorResponse = $this->attemptRecolor($batikImageContent, $paletteHex, $this->mlUrl);
            $endTime = microtime(true);
            $processingTimeMs = round(($endTime - $startTime) * 1000);

            if (!$recolorResponse) {
                Log::error('All recolor endpoints failed', [
                    'base_url' => $this->mlUrl,
                    'palette' => $paletteJson,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal melakukan recoloring pada batik. Endpoint tidak tersedia atau error.',
                ], 503);
            }

            $result = $recolorResponse->json();
            $resultImageUrl = null;

            if (!is_array($result)) {
                $rawBody = trim((string) $recolorResponse->body());

                if ($rawBody !== '') {
                    if (str_starts_with($rawBody, 'data:image')) {
                        $resultImageUrl = $rawBody;
                    } elseif (!str_starts_with($rawBody, '<')) {
                        $resultImageUrl = 'data:image/jpeg;base64,' . $rawBody;
                    }
                }

                if (!$resultImageUrl) {
                    Log::error('Recolor API returned invalid JSON', [
                        'status' => $recolorResponse->status(),
                        'body' => substr($recolorResponse->body(), 0, 1000),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Response dari model AI tidak valid. Silakan coba lagi.',
                    ], 502);
                }

                $result = [
                    'success' => true,
                    'result_image_url' => $resultImageUrl,
                ];
            }

            if (isset($result['data']) && is_array($result['data'])) {
                $result = $result['data'];
            } elseif (isset($result['result']) && is_array($result['result'])) {
                $result = $result['result'];
            }

            Log::info('Recolor result parsed', [
                'result_keys' => array_keys($result),
                'success' => $result['success'] ?? false,
            ]);

            // Construct full image URL
            if (!$resultImageUrl && isset($result['image_b64'])) {
                $resultImageUrl = 'data:image/jpeg;base64,' . $result['image_b64'];
            } elseif (!$resultImageUrl && isset($result['result_image_b64'])) {
                $resultImageUrl = 'data:image/jpeg;base64,' . $result['result_image_b64'];
            } elseif (!$resultImageUrl && isset($result['output_image_b64'])) {
                $resultImageUrl = 'data:image/jpeg;base64,' . $result['output_image_b64'];
            } elseif (!$resultImageUrl && isset($result['result_image_url'])) {
                $resultImageUrl = $result['result_image_url'];
                if (!filter_var($resultImageUrl, FILTER_VALIDATE_URL)) {
                    // It's a relative path, prepend base URL
                    $baseUrl = rtrim($this->mlUrl, '/');
                    if (strpos($resultImageUrl, '/uploads') === 0) {
                        $resultImageUrl = $baseUrl . $resultImageUrl;
                    } else {
                        $resultImageUrl = $baseUrl . '/uploads/' . ltrim($resultImageUrl, '/');
                    }
                }
            }

            Log::info('Colorization successful', [
                'method' => $method,
                'result_url' => $resultImageUrl,
                'processing_time_ms' => $result['processing_time_ms'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'result' => [
                    'result_image_url' => $resultImageUrl,
                    'result_image_path' => $result['result_image_path'] ?? null,
                    'processing_time_ms' => $result['processing_time_ms'] ?? $processingTimeMs,
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
     *
    * @return \Illuminate\View\View|RedirectResponse
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

        // Transform data structure untuk view
        // Dari: {kmeans: {success, result: {result_image_url, processing_time_ms}}, ...}
        // Ke: {kmeans: {image_url, processing_time_ms}, ...}
        $transformedResults = [];
        foreach ($results as $method => $responseData) {
            if (is_array($responseData) && isset($responseData['result']) && $responseData['result']) {
                // Success case
                $transformedResults[$method] = [
                    'image_url' => $responseData['result']['result_image_url'] ?? null,
                    'processing_time_ms' => $responseData['result']['processing_time_ms'] ?? 0,
                    'palette_used' => $responseData['result']['palette_used'] ?? [],
                    'error' => null,
                ];
            } else {
                // Error case - result is null or not present
                $transformedResults[$method] = [
                    'image_url' => null,
                    'processing_time_ms' => 0,
                    'error' => $responseData['message'] ?? 'Unknown error',
                ];
            }
        }

        // Normalize all URLs (convert backslashes to forward slashes)
        foreach ($transformedResults as &$methodResult) {
            if (isset($methodResult['image_url']) && $methodResult['image_url']) {
                $methodResult['image_url'] = str_replace('\\', '/', $methodResult['image_url']);
            }
        }
        unset($methodResult);

        return view('pages.features.pewarnaan-palet.output', [
            'results' => $transformedResults,
            'batikImage' => $batikImage,
            'colorImage' => $colorImage
        ]);
    }

    /**
     * POST /api/save-results
     * Menyimpan hasil pewarnaan ke session
     * 
     * Input:
     * - batik_image: gambar batik awal
     * - color_image: gambar warna referensi
     * - results: array berisi hasil dari 3 metode pewarnaan
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveResults(Request $request)
    {
        try {
            $request->validate([
                'colorize_results' => 'required|array',
                'colorize_batik_image' => 'required|string',
            ]);

            $results = $request->input('colorize_results', []);
            $batikImage = $request->input('colorize_batik_image', '');
            $colorImage = $request->input('colorize_color_image', '');

            // Validasi hasil memiliki minimal satu metode
            if (empty($results)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada hasil pewarnaan untuk disimpan.',
                ], 400);
            }

            // Normalize backslashes in all image URLs
            foreach ($results as $method => &$methodResult) {
                if (isset($methodResult['image_url'])) {
                    $methodResult['image_url'] = str_replace('\\', '/', $methodResult['image_url']);
                }
            }
            unset($methodResult); // Break reference

            // Simpan ke session
            $request->session()->put('colorize_results', $results);
            $request->session()->put('colorize_batik_image', $batikImage);
            if ($colorImage) {
                $request->session()->put('colorize_color_image', $colorImage);
            }

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
     * Extract palette dari color image menggunakan semua 3 metode
     * (kmeans, histogram, median_cut)
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

        if (!$this->isMLAvailable()) {
            Log::warning('Fashion Service URL tidak dikonfigurasi, skipping palette extraction');
            return $palettes;
        }

        try {
            // Konversi base64 ke binary
            $colorImageContent = $this->base64ToImageFile($colorImageBase64);

            // Endpoint: POST /api/recolor/palette/extract
            $response = Http::timeout(60)
                ->withHeaders($this->getMLHeaders())
                ->attach('image', $colorImageContent, 'color_image.jpg')
                ->post($this->mlServiceUrl('/recolor/palette/extract'), [
                    'method' => 'all',
                    'n_colors' => 6
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['palette'])) {
                    // Convert RGB format to HEX format for frontend display
                    $palettes['kmeans'] = $this->convertRgbToHex($data['data']['palette']['kmeans'] ?? []);
                    $palettes['histogram'] = $this->convertRgbToHex($data['data']['palette']['histogram'] ?? []);
                    $palettes['median_cut'] = $this->convertRgbToHex($data['data']['palette']['median_cut'] ?? []);
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
    private function base64ToImageFile(string $base64String)
    {
        // Handle data URL format
        if (strpos($base64String, 'data:image') === 0) {
            $base64String = substr($base64String, strpos($base64String, ',') + 1);
        }

        return base64_decode($base64String, true);
    }

    /**
     * Convert RGB color array [{'r': 255, 'g': 0, 'b': 0}, ...] ke HEX format ['#FF0000', ...]
     * Untuk display di frontend
     * 
     * @param array $rgbColors Array of RGB color objects
     * @return array Array of HEX color strings
     */
    private function convertRgbToHex(array $rgbColors): array
    {
        return array_map(function ($color) {
            if (is_array($color)) {
                $r = intval($color['r'] ?? 0);
                $g = intval($color['g'] ?? 0);
                $b = intval($color['b'] ?? 0);
            } else {
                // Jika sudah string hex, return as is
                return $color;
            }
            
            return sprintf('#%02X%02X%02X', $r, $g, $b);
        }, $rgbColors);
    }

    /**
     * Convert HEX color array ['#FF0000', ...] ke HEX format untuk API
     * Backend expects HEX strings, NOT RGB objects!
     * 
     * @param array $hexColors Array of HEX color strings
     * @return array Array of HEX color strings untuk API
     */
    private function convertHexToHexForApi(array $hexColors): array
    {
        return array_map(function ($color) {
            // If already HEX string, validate and return
            if (is_string($color)) {
                // Ensure it starts with #
                if (strpos($color, '#') !== 0) {
                    $color = '#' . $color;
                }
                return strtoupper($color);
            }
            
            // If RGB object, convert to HEX
            if (is_array($color) && isset($color['r'])) {
                $r = str_pad(dechex($color['r']), 2, '0', STR_PAD_LEFT);
                $g = str_pad(dechex($color['g']), 2, '0', STR_PAD_LEFT);
                $b = str_pad(dechex($color['b']), 2, '0', STR_PAD_LEFT);
                return '#' . strtoupper($r . $g . $b);
            }
            
            return $color;
        }, $hexColors);
    }

    /**
     * Try recolor ke backend API
     * Backend endpoint: POST http://localhost:5000/api/recolor
     * 
     * @param string $imageContent Binary image content
     * @param array $paletteHex HEX palette array like ["#FF0000", ...]
     * @param string $baseUrl Base ML API URL
     * @return mixed Response dari API atau false jika gagal
     */
    private function attemptRecolor(string $imageContent, array $paletteHex, string $baseUrl)
    {
        $fullUrl = $this->mlServiceUrl('/recolor/palette');
        $paletteJson = json_encode($paletteHex);

        try {
            Log::info('Attempting recolor', [
                'endpoint' => $fullUrl,
                'image_size' => strlen($imageContent),
                'palette_hex' => $paletteJson,
            ]);
            
            $response = Http::timeout(120)
                ->attach('image', $imageContent, 'batik.jpg')
                ->post($fullUrl, [
                    'colors' => $paletteJson,
                    'palette' => $paletteJson,
                    'white_threshold' => 150,
                ]);

            Log::info('Recolor response received', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                Log::info('Recolor successful', ['endpoint' => $fullUrl]);
                return $response;
            } else {
                // Log error response untuk debugging
                Log::warning('Recolor returned error status', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                // Still return response jika ada error, biar handler catch
                return $response;
            }
        } catch (\Exception $e) {
            Log::error('Recolor request exception', [
                'error' => $e->getMessage(),
                'endpoint' => $fullUrl,
            ]);
            return false;
        }
    }
}
