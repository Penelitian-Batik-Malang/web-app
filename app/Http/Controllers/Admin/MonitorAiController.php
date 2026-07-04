<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorAiController extends Controller
{
    public function index()
    {
        $mlBase = rtrim((string) config('services.ml.url', ''), '/');

        $services = [
            'ml' => [
                'name'       => 'ML Service',
                'url'        => $mlBase,
                'healthPath' => '/api/health',
            ],
        ];

        $results       = [];
        $errorMessages = [];

        foreach ($services as $key => $service) {
            if (empty($service['url'])) {
                $results[$key]       = null;
                $errorMessages[$key] = "URL untuk {$service['name']} belum dikonfigurasi di .env (RETRIEVAL_API_BASE_URL).";
                continue;
            }

            $url = $service['url'] . ($service['healthPath'] ?? '/api/health');

            try {
                $hfToken = trim((string) config('services.ml.hf_token', ''));
                $headers = [];
                if (!empty($hfToken)) {
                    $headers['Authorization'] = 'Bearer ' . $hfToken;
                }

                $response = Http::timeout(10)
                    ->withHeaders($headers)
                    ->acceptJson()
                    ->get($url);

                if ($response->successful()) {
                    $raw = $response->json();

                    // Unwrap FastAPI APIResponse envelope: { status, message, data: { ... } }
                    $data = (isset($raw['data']) && is_array($raw['data'])) ? $raw['data'] : $raw;

                    $isHealthy    = ($data['status'] ?? '') === 'healthy';
                    $modelsStatus = $data['models'] ?? [];   // { motif: bool, tulis: bool, cbir: bool }
                    $uptime       = $data['uptime'] ?? null;

                    // Build human-readable message
                    $loadedNames = array_keys(array_filter($modelsStatus));
                    $modelsStr   = count($loadedNames) > 0
                        ? 'Models loaded: ' . implode(', ', $loadedNames)
                        : 'No models loaded';

                    $results[$key] = [
                        'name'            => $service['name'],
                        'success'         => $isHealthy,
                        'message'         => $isHealthy ? 'healthy (' . $modelsStr . ')' : 'unhealthy',
                        'models'          => $modelsStatus, // structured { motif: bool, tulis: bool, cbir: bool }
                        'uptime'          => $uptime,
                        'memory_usage_mb' => $data['memory_usage_mb'] ?? null,
                        'timestamp'       => now()->toDateTimeString(),
                    ];
                } else {
                    $results[$key] = [
                        'name'    => $service['name'],
                        'success' => false,
                        'message' => 'HTTP ' . $response->status(),
                        'models'  => [],
                    ];
                    $errorMessages[$key] = "Gagal menghubungi {$service['name']}. HTTP " . $response->status() . ".";
                }
            } catch (\Throwable $e) {
                Log::error("Monitor AI health error ({$service['name']}): " . $e->getMessage());
                $results[$key] = [
                    'name'    => $service['name'],
                    'success' => false,
                    'message' => 'Connection Error — server tidak berjalan atau tidak bisa dijangkau.',
                    'models'  => [],
                ];
                $errorMessages[$key] = "Tidak dapat menghubungi {$service['name']}. Pastikan ML server sudah dijalankan.";
            }
        }

        return view('admin.monitor-ai', [
            'services'      => $results,
            'errorMessages' => $errorMessages,
        ]);
    }
}
