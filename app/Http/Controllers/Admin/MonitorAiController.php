<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorAiController extends Controller
{
    public function index()
    {
        $services = [
            'batik' => [
                'name' => 'Batik Service',
                'url' => rtrim((string) config('services.ml.batik_url', ''), '/health'),
            ],
            'fashion' => [
                'name' => 'Fashion Service',
                'url' => rtrim((string) config('services.ml.fashion_url', ''), '/health'),
            ],
        ];

        $results = [];
        $errorMessages = [];

        foreach ($services as $key => $service) {
            if (empty($service['url'])) {
                $results[$key] = null;
                $errorMessages[$key] = "URL untuk {$service['name']} belum dikonfigurasi.";
                continue;
            }

            $url = $service['url'] . '/health';

            try {
                $response = Http::timeout(10)->acceptJson()->get($url);
                if ($response->successful()) {
                    $data = $response->json();
                    $results[$key] = [
                        'name' => $service['name'],
                        'memory_usage_mb' => $data['memory_usage_mb'] ?? null,
                        'message' => $data['status'] ?? $data['message'] ?? '-',
                        'success' => (isset($data['status']) && $data['status'] === 'ok') || (bool)($data['success'] ?? false),
                        'timestamp' => $data['timestamp'] ?? null,
                    ];
                } else {
                    $results[$key] = [
                        'name' => $service['name'],
                        'success' => false,
                        'message' => 'HTTP ' . $response->status(),
                    ];
                    $errorMessages[$key] = "Gagal mengambil health {$service['name']}. HTTP " . $response->status() . ".";
                }
            } catch (\Throwable $e) {
                Log::error("Monitor AI health error ({$service['name']}): " . $e->getMessage());
                $results[$key] = [
                    'name' => $service['name'],
                    'success' => false,
                    'message' => 'Connection Error',
                ];
                $errorMessages[$key] = "Tidak dapat menghubungi {$service['name']}.";
            }
        }

        return view('admin.monitor-ai', [
            'services' => $results,
            'errorMessages' => $errorMessages
        ]);
    }
}
