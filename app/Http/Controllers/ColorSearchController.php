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
    private const PALETTE_PATH = '/api/get-color-palette';
    private const RECOMMENDATION_PATH = '/api/get-recommendation';

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.retrieval.base_url', ''), '/');
    }

    public function getPalette(Request $request): JsonResponse
    {
        $request->validate([
            'num_clusters' => 'nullable|integer|in:3,4,5',
        ]);

        $file = $this->resolveUploadedImage($request);
        if ($file instanceof JsonResponse) {
            return $file;
        }

        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'RETRIEVAL_API_BASE_URL belum dikonfigurasi.',
            ], 503);
        }

        $numClusters = (int) $request->input('num_clusters', 5);

        try {
            $url = $this->buildUrl(self::PALETTE_PATH, [
                'num_clusters' => $numClusters,
            ]);

            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($url);

            $payload = $response->json();
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $payload['detail'] ?? $payload['message'] ?? 'Gagal mengambil palette warna.',
                ], $response->status());
            }

            $rawPalette = $payload['data'] ?? [];
            if (!is_array($rawPalette)) {
                $rawPalette = [];
            }

            $palettes = [];
            foreach (array_values($rawPalette) as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $index = (int) ($item['no'] ?? $item['index'] ?? $item['number'] ?? $item['id'] ?? ($idx + 1));
                $hex = strtoupper((string) ($item['palette'] ?? $item['hex'] ?? $item['hex_code'] ?? $item['color_hex'] ?? ''));
                if (!str_starts_with($hex, '#')) {
                    $hex = '#' . $hex;
                }

                // Jika backend tidak mengirim HEX valid, fallback aman ke hitam.
                if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) {
                    $hex = '#000000';
                }

                $palettes[] = [
                    'index' => $index,
                    'name' => (string) ($item['name'] ?? $item['color_name'] ?? ('Warna ' . $index)),
                    'hex' => $hex,
                    'percentage' => isset($item['percentage']) ? (float) $item['percentage'] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'result' => [
                    'palettes' => $palettes,
                    'selected_palette_indexes' => array_values(array_map(fn ($item) => $item['index'], $palettes)),
                ],
            ]);
        } catch (\Throwable $error) {
            Log::error('Color palette proxy error: ' . $error->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi service palette.',
            ], 500);
        }
    }

    public function getRecommendation(Request $request): JsonResponse
    {
        $request->validate([
            'num_clusters' => 'nullable|integer|in:3,4,5',
            'top_k' => 'nullable|integer|min:1|max:100',
            'selected_colors' => 'nullable|array',
            'selected_colors.*' => 'integer|min:1',
        ]);

        $file = $this->resolveUploadedImage($request);
        if ($file instanceof JsonResponse) {
            return $file;
        }

        if (empty($this->baseUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'RETRIEVAL_API_BASE_URL belum dikonfigurasi.',
            ], 503);
        }

        $numClusters = (int) $request->input('num_clusters', 5);
        $topK = (int) $request->input('top_k', 10);
        $selectedColors = array_values(array_map('intval', (array) $request->input('selected_colors', [])));

        try {
            $query = [
                'num_clusters' => $numClusters,
                'top_k' => $topK,
            ];

            foreach ($selectedColors as $color) {
                $query['selected_colors'][] = $color;
            }

            $url = $this->buildUrl(self::RECOMMENDATION_PATH, $query);

            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($url);

            $payload = $response->json();
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $payload['detail'] ?? $payload['message'] ?? 'Gagal mengambil rekomendasi.',
                ], $response->status());
            }

            $data = $payload['data'] ?? [];
            $rows = $data['recommendations'] ?? $data['items'] ?? $data['results'] ?? [];
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
                    'name' => (string) ($item['name'] ?? $item['batik_name'] ?? $item['title'] ?? 'Batik'),
                    'image_url' => (string) ($item['image_url'] ?? $item['image'] ?? $item['thumbnail'] ?? 'https://placehold.co/240x180?text=Batik'),
                    'score' => isset($item['score']) ? (float) $item['score'] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'result' => [
                    'recommendations' => $recommendations,
                ],
            ]);
        } catch (\Throwable $error) {
            Log::error('Color recommendation proxy error: ' . $error->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi service rekomendasi.',
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

    private function resolveUploadedImage(Request $request): UploadedFile|JsonResponse
    {
        $file = $request->file('image');
        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File gambar wajib diunggah.',
                'errors' => [
                    'image' => ['File gambar wajib diunggah.'],
                ],
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
                'success' => false,
                'message' => $message,
                'errors' => [
                    'image' => [$message],
                ],
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
                'success' => false,
                'message' => $validator->errors()->first('image') ?: 'Validasi file gambar gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return $file;
    }
}
