<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorAiController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim((string) config('services.ml.base_url', ''), '/');
        $healthPath = (string) (config('services.ml.endpoints.health') ?? '/health');

        $health = null;
        $errorMessage = null;

        if (empty($baseUrl)) {
            $errorMessage = 'ML_API_BASE_URL belum dikonfigurasi.';
        } else {
            $url = $baseUrl . '/' . ltrim($healthPath, '/');

            try {
                $response = Http::timeout(15)->acceptJson()->get($url);
                if ($response->successful()) {
                    $data = $response->json();

                    $health = [
                        'memory_usage_mb' => $data['memory_usage_mb'] ?? null,
                        'message' => $data['message'] ?? '-',
                        'success' => (bool) ($data['success'] ?? false),
                        'timestamp' => $data['timestamp'] ?? null,
                    ];
                } else {
                    $errorMessage = 'Gagal mengambil health model. HTTP ' . $response->status() . '.';
                }
            } catch (\Throwable $e) {
                Log::error('Monitor AI health error: ' . $e->getMessage());
                $errorMessage = 'Tidak dapat menghubungi layanan health model.';
            }
        }

        return view('admin.monitor-ai', compact('health', 'errorMessage'));
    }
}
