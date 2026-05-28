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
        // Hapus suffix '/api' untuk mendapatkan base URL tanpa prefix
        $baseWithoutApi = preg_replace('#/api$#', '', $mlBase);

        $services = [
            'ml' => [
                'name' => 'ML Service',
                'url'  => $baseWithoutApi,
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
                    
                    // Check if response format is the new FastAPI APIResponse format
                    if (isset($data['status']) && is_numeric($data['status']) && isset($data['data'])) {
                        $nestedData = $data['data'];
                        $isHealthy = ($nestedData['status'] ?? '') === 'healthy';
                        
                        // Extract loaded models details
                        $loadedModels = [];
                        if (!empty($nestedData['models']) && is_array($nestedData['models'])) {
                            foreach ($nestedData['models'] as $mName => $mStatus) {
                                if ($mStatus) {
                                    $loadedModels[] = $mName;
                                }
                            }
                        }
                        
                        $modelsStr = count($loadedModels) > 0 
                            ? ' (Models loaded: ' . implode(', ', $loadedModels) . ')' 
                            : ' (No models loaded)';
                            
                        $results[$key] = [
                            'name' => $service['name'],
                            'memory_usage_mb' => null,
                            'message' => $isHealthy ? 'healthy' . $modelsStr : 'unhealthy',
                            'success' => $isHealthy,
                            'timestamp' => now()->toIso8601String(),
                        ];
                    } else {
                        // Standard fallback to the old format
                        $results[$key] = [
                            'name' => $service['name'],
                            'memory_usage_mb' => $data['memory_usage_mb'] ?? null,
                            'message' => $data['status'] ?? $data['message'] ?? '-',
                            'success' => (isset($data['status']) && $data['status'] === 'ok') || (bool)($data['success'] ?? false),
                            'timestamp' => $data['timestamp'] ?? null,
                        ];
                    }
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
