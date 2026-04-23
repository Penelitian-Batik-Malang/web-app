<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseMLController extends Controller
{
    protected string $baseUrl;
    protected array $endpoints;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.base_url', env('ML_API_BASE_URL', '')), '/');
        $this->endpoints = (array) config('services.ml.endpoints', []);
    }

    protected function isMLAvailable(): bool
    {
        return !empty($this->baseUrl);
    }

    protected function mlUrl(string $endpointKey, string $default): string
    {
        $path = $this->endpoints[$endpointKey] ?? $default;
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    protected function notConfiguredResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Model AI belum terhubung. Endpoint ML_API_BASE_URL belum dikonfigurasi.',
        ], 503);
    }

    protected function handleImageDetection(Request $request, string $mlPath)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isMLAvailable()) {
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
                        'label'       => $data['label'] ?? $data['class'] ?? $data['result'] ?? 'Tidak Diketahui',
                        'confidence'  => $data['confidence'] ?? $data['probability'] ?? $data['score'] ?? 0,
                        'description' => $data['description'] ?? $data['desc'] ?? $data['message'] ?? '-',
                    ],
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

    protected function attachFile($http, string $name, UploadedFile $file)
    {
        return $http->asMultipart()->attach(
            $name,
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        );
    }

    protected function resolveInputImageBinary(?UploadedFile $file, ?string $url): ?array
    {
        if ($file instanceof UploadedFile) {
            return [
                'body'         => file_get_contents($file->getRealPath()),
                'content_type' => $file->getMimeType() ?: 'image/jpeg',
            ];
        }

        if (!empty($url)) {
            $resp = Http::timeout(20)->get($url);
            if ($resp->successful()) {
                return [
                    'body'         => $resp->body(),
                    'content_type' => $resp->header('Content-Type', 'image/jpeg'),
                ];
            }
        }

        return null;
    }

    protected function getSampleFashionUrls(): array
    {
        $dir = base_path('sample_fashion');
        if (!is_dir($dir)) {
            return [];
        }

        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $files = array_values(array_filter(
            scandir($dir),
            fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $extensions)
        ));

        return array_map(
            fn ($f) => route('sample.fashion', ['filename' => $f]),
            $files
        );
    }
}
