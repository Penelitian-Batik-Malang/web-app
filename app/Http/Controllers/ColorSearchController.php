<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;

class ColorSearchController extends Controller
{
    private const PALETTE_PATH = '/api/color-palette-faiss';
    private const RECOMMENDATION_PATH = '/api/get-recommendation-faiss';

    private string $baseUrl;
    private string $apiKey;
    private string $hfToken;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.url', ''), '/');
        $this->apiKey = trim((string) config('services.ml.api_key', ''));
        $this->hfToken = trim((string) config('services.ml.hf_token', ''));
    }

    private function getMLHeaders(): array
    {
        $headers = [];
        if (!empty($this->apiKey)) {
            $headers['X-API-Key'] = $this->apiKey;
        }
        if (!empty($this->hfToken)) {
            $headers['Authorization'] = 'Bearer ' . $this->hfToken;
        }
        return $headers;
    }

    public function getPalette(Request $request): JsonResponse
    {
        $request->validate([
            'num_cluster' => 'nullable|integer|in:3,4,5',
            'num_clusters' => 'nullable|integer|in:3,4,5',
        ]);

        $file = $this->resolveUploadedImage($request);
        if ($file instanceof JsonResponse) {
            return $file;
        }

        if (empty($this->baseUrl)) {
            return response()->json([
                'status' => 503,
                'message' => 'ML_URL belum dikonfigurasi.',
                'errors' => ['ML_URL belum dikonfigurasi.'],
                'meta' => null,
                'result' => null,
            ], 503);
        }

        if (empty($this->apiKey)) {
            return response()->json([
                'status' => 503,
                'message' => 'ML_API_KEY belum dikonfigurasi.',
                'errors' => ['ML_API_KEY belum dikonfigurasi.'],
                'meta' => null,
                'result' => null,
            ], 503);
        }

        $numCluster = (int) $request->input('num_cluster', $request->input('num_clusters', 5));

        try {
            $url = $this->buildUrl(self::PALETTE_PATH);

            $response = Http::timeout(60)
                ->withHeaders($this->getMLHeaders())
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($url, [
                    'num_cluster' => $numCluster,
                ]);

            $payload = $response->json();
            if (!$response->successful() || !$this->isSuccessPayload($payload)) {
                $message = $this->extractApiError($payload, 'Gagal mengambil palette warna.');
                return response()->json([
                    'status' => $response->status(),
                    'message' => $message,
                    'errors' => [$message],
                    'meta' => null,
                    'result' => null,
                ], $response->status());
            }

            $rawPalette = $payload['data']['palette'] ?? [];
            if (!is_array($rawPalette)) {
                $rawPalette = [];
            }
            
            $rawColors = $payload['data']['colors'] ?? [];
            if (!is_array($rawColors)) {
                $rawColors = [];
            }
            
            $rawColorNames = $payload['data']['color_names'] ?? [];
            if (!is_array($rawColorNames)) {
                $rawColorNames = [];
            }

            $palettes = [];
            foreach (array_values($rawPalette) as $idx => $hex) {
                $index = $idx + 1;
                $hexStr = is_string($hex) ? strtoupper($hex) : '#000000';
                if (!str_starts_with($hexStr, '#')) {
                    $hexStr = '#' . $hexStr;
                }

                if (!preg_match('/^#[0-9A-F]{6}$/', $hexStr)) {
                    $hexStr = '#000000';
                }
                
                $colorInfo = $rawColors[$idx] ?? [];
                $percentage = isset($colorInfo[3]) ? (float) $colorInfo[3] : null;
                $colorName = (string) ($rawColorNames[$idx] ?? ('Warna ' . $index));

                $palettes[] = [
                    'no' => $index,
                    'palette' => $hexStr,
                    'name' => $colorName,
                    'percentage' => $percentage,
                ];
            }

            return response()->json([
                'status' => 200,
                'message' => 'Palette extracted',
                'errors' => [],
                'meta' => null,
                'result' => [
                    'palette' => $palettes,
                    'selected_palette_indexes' => array_column($palettes, 'no'),
                ],
            ]);
        } catch (\Throwable $error) {
            Log::error('Color palette proxy error: ' . $error->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Gagal menghubungi service palette.',
                'errors' => ['Gagal menghubungi service palette.'],
                'meta' => null,
                'result' => null,
            ], 500);
        }
    }

    public function getRecommendation(Request $request): JsonResponse
    {
        $request->validate([
            'num_cluster' => 'nullable|integer|in:3,4,5',
            'num_clusters' => 'nullable|integer|in:3,4,5',
            'top_k' => 'nullable|integer|min:1|max:100',
            'selected_colors' => 'nullable',
        ]);

        $file = $this->resolveUploadedImage($request);
        if ($file instanceof JsonResponse) {
            return $file;
        }

        if (empty($this->baseUrl)) {
            return response()->json([
                'status' => 503,
                'message' => 'ML_URL belum dikonfigurasi.',
                'errors' => ['ML_URL belum dikonfigurasi.'],
                'data' => null,
                'meta' => null,
                'success' => false,
            ], 503);
        }

        if (empty($this->apiKey)) {
            return response()->json([
                'status' => 503,
                'message' => 'ML_API_KEY belum dikonfigurasi.',
                'errors' => ['ML_API_KEY belum dikonfigurasi.'],
                'data' => null,
                'meta' => null,
                'success' => false,
            ], 503);
        }

        $numCluster = (int) $request->input('num_cluster', $request->input('num_clusters', 5));
        $topK = (int) $request->input('top_k', 15);
        $selectedColors = $this->normalizeSelectedColors($request->input('selected_colors', []), $numCluster);

        if ($selectedColors instanceof JsonResponse) {
            return $selectedColors;
        }

        try {
            $url = $this->buildUrl(self::RECOMMENDATION_PATH);

            $response = Http::timeout(60)
                ->withHeaders($this->getMLHeaders())
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($url, [
                    'num_cluster' => $numCluster,
                    'top_k' => $topK,
                    'selected_colors' => $selectedColors,
                ]);

            $payload = $response->json();
            if (!$response->successful() || !$this->isSuccessPayload($payload)) {
                $message = $this->extractApiError($payload, 'Gagal mengambil rekomendasi.');
                return response()->json([
                    'status' => $response->status(),
                    'message' => $message,
                    'errors' => [$message],
                    'data' => null,
                    'meta' => null,
                    'success' => false,
                ], $response->status());
            }

            $data = $payload['data'] ?? [];
            $rows = $data['results'] ?? $data['recommendations'] ?? $data['items'] ?? [];
            if (!is_array($rows)) {
                $rows = [];
            }

            $recommendations = [];
            foreach (array_values($rows) as $position => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $recommendations[] = [
                    'id' => (int) ($item['id'] ?? $item['batik_id'] ?? ($position + 1)),
                    'name' => (string) ($item['label'] ?? $item['name'] ?? $item['batik_name'] ?? $item['title'] ?? 'Batik'),
                    'image_url' => (string) ($item['image_url'] ?? $item['image'] ?? $item['thumbnail'] ?? 'https://placehold.co/240x180?text=Batik'),
                    'score' => isset($item['score']) ? (float) $item['score'] : null,
                    'distance' => isset($item['distance']) ? (float) $item['distance'] : null,
                    'color_names_label' => $item['color_names_label'] ?? [],
                ];
            }

            return response()->json([
                'status' => 200,
                'message' => 'Recommendation successful',
                'data' => [
                    'results' => $recommendations,
                    'result_count' => count($recommendations),
                ],
                'errors' => [],
                'meta' => null,
                'success' => true,
                'result' => [
                    'recommendations' => $recommendations,
                ],
            ]);
        } catch (\Throwable $error) {
            Log::error('Color recommendation proxy error: ' . $error->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Gagal menghubungi service rekomendasi.',
                'errors' => ['Gagal menghubungi service rekomendasi.'],
                'data' => null,
                'meta' => null,
                'success' => false,
            ], 500);
        }
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if (empty($query)) {
            return $url;
        }

        $segments = [];
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $segments[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $item);
                }
                continue;
            }

            $segments[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return $url . '?' . implode('&', $segments);
    }

    private function isSuccessPayload(?array $payload): bool
    {
        if (!$payload || !isset($payload['status'])) {
            return false;
        }

        $status = (int) $payload['status'];
        return $status >= 200 && $status < 300;
    }

    private function extractApiError(?array $payload, string $fallbackMessage): string
    {
        if (!$payload) {
            return $fallbackMessage;
        }

        if (!empty($payload['errors']) && is_array($payload['errors'])) {
            return (string) ($payload['errors'][0] ?? $fallbackMessage);
        }

        if (!empty($payload['message']) && is_string($payload['message'])) {
            return $payload['message'];
        }

        if (!empty($payload['detail']) && is_string($payload['detail'])) {
            return $payload['detail'];
        }

        return $fallbackMessage;
    }

    private function normalizeSelectedColors(mixed $selectedColors, int $numCluster): string|JsonResponse
    {
        if (is_string($selectedColors)) {
            return $selectedColors;
        }

        if (!is_array($selectedColors)) {
            return '';
        }

        $items = array_values(array_filter(array_map('intval', $selectedColors)));
        foreach ($items as $value) {
            if ($value < 1 || $value > $numCluster) {
                return response()->json([
                    'status' => 422,
                    'message' => 'selected_colors di luar rentang jumlah cluster.',
                    'errors' => ['selected_colors di luar rentang jumlah cluster.'],
                    'data' => null,
                    'meta' => null,
                    'success' => false,
                ], 422);
            }
        }

        return implode(',', $items);
    }

    private function resolveUploadedImage(Request $request): UploadedFile|JsonResponse
    {
        $file = $request->file('file') ?: $request->file('image');
        if (!$file) {
            return response()->json([
                'status' => 422,
                'message' => 'File gambar wajib diunggah.',
                'errors' => [
                    'image' => ['File gambar wajib diunggah.'],
                ],
                'data' => null,
                'meta' => null,
                'success' => false,
            ], 422);
        }

        if (!$file->isValid()) {
            $limit = ini_get('upload_max_filesize') ?: '2M';
            $message = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE => "Upload gagal: ukuran file melebihi batas server ({$limit}).",
                UPLOAD_ERR_FORM_SIZE => 'Upload gagal: ukuran file melebihi batas form.',
                UPLOAD_ERR_PARTIAL => 'Upload gagal: file hanya terunggah sebagian.',
                UPLOAD_ERR_NO_FILE => 'Upload gagal: tidak ada file yang terunggah.',
                UPLOAD_ERR_NO_TMP_DIR => 'Upload gagal: folder temporary server tidak tersedia.',
                UPLOAD_ERR_CANT_WRITE => 'Upload gagal: server tidak dapat menulis file sementara.',
                UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP.',
                default => 'Upload gagal: file tidak valid.',
            };

            return response()->json([
                'status' => 422,
                'message' => $message,
                'errors' => [
                    'image' => [$message],
                ],
                'data' => null,
                'meta' => null,
                'success' => false,
            ], 422);
        }

        $validator = Validator::make(
            ['image' => $file],
            ['image' => 'image|mimes:jpeg,jpg,png,webp|max:51200'],
            [
                'image.image' => 'File harus berupa gambar yang valid.',
                'image.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
                'image.max' => 'Ukuran gambar maksimal 50MB.',
            ],
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->first('image') ?: 'Validasi file gambar gagal.',
                'errors' => $validator->errors(),
                'data' => null,
                'meta' => null,
                'success' => false,
            ], 422);
        }

        return $file;
    }
}
