<?php

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RekomendasiBatikController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.rekomendasi-batik', [
            'fashionSamples' => $this->getSampleFashionUrls(),
        ]);
    }

    public function blendFromCbir(Request $request)
    {
        $request->validate([
            'session_id'     => 'required|string',
            'part'           => 'required|string',
            'instance_index' => 'required|integer',
            'batik_filename' => 'required|string',
        ]);

        if (!$this->isMLAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url = $this->mlUrl('blend_cbir', '/blend-from-cbir');

        try {
            $response = Http::timeout(60)
                ->asMultipart()
                ->post($url, [
                    'session_id'     => $request->input('session_id'),
                    'part'           => $request->input('part'),
                    'instance_index' => $request->input('instance_index'),
                    'batik_filename' => $request->input('batik_filename'),
                ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'message' => 'API error ' . $response->status(),
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Fashionpedia Blend CBIR Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Blend CBIR error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
