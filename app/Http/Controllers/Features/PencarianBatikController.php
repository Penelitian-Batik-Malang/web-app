<?php

namespace App\Http\Controllers\Features;

use App\Models\Batik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PencarianBatikController extends BaseMLController
{
    private function s3BatikBase(): string
    {
        // Gunakan bucket khusus AI results agar tidak campur dengan galeri utama
        return 'https://is3.cloudhost.id/galeri-batik-digital';
    }

    public function show()
    {
        return view('pages.features.pencarian-batik');
    }

    public function search(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240']);

        if (!$this->isBatikAvailable()) {
            return $this->notConfiguredResponse();
        }

        $file = $request->file('image');

        try {
            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post($this->batikServiceUrl('/search/general'));

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'Batik Service error ' . $response->status()], $response->status());
            }

            $data   = $response->json();
            Log::info('Batik Search Raw Data:', ['data' => $data]);
            $s3Base = $this->s3BatikBase();

            $results = collect($data['results'] ?? [])
                ->map(function ($item) use ($s3Base) {
                    $label = $item['label'] ?? '';
                    $path  = ltrim(str_replace('\\', '/', $item['path_s3'] ?? ''), '/');
                    $batik = $this->findBatikByLabel($label);

                    // Prioritaskan gambar asli dari AI (S3 crop) agar hasil terlihat unik
                    // Fallback ke galeri image jika S3 path tidak ada
                    $imageUrl = ($s3Base && $path) 
                        ? $s3Base . '/' . $path 
                        : (($batik && optional($batik->mainImage)->full_url) ? $batik->mainImage->full_url : null);

                    // Proxy logic: Pilih disk yang sesuai berdasarkan bucket di URL
                    $s3SignatureBase = 'https://is3.cloudhost.id/batik-signature-gdrive/';
                    $s3GaleriBase    = 'https://is3.cloudhost.id/galeri-batik-digital/';
                    $s3ColorBase     = 'https://is3.cloudhost.id/color-dominant-batik/';
                    
                    $proxiedImageUrl = $imageUrl;
                    if ($imageUrl) {
                        if (strpos($imageUrl, $s3GaleriBase) === 0) {
                            $pathProxy = substr($imageUrl, strlen($s3GaleriBase));
                            $proxiedImageUrl = route('storage.ai.proxy', ['path' => $pathProxy]);
                        } elseif (strpos($imageUrl, $s3SignatureBase) === 0) {
                            $pathProxy = substr($imageUrl, strlen($s3SignatureBase));
                            $proxiedImageUrl = route('storage.batik.proxy', ['path' => $pathProxy]);
                        } elseif (strpos($imageUrl, $s3ColorBase) === 0) {
                            $pathProxy = substr($imageUrl, strlen($s3ColorBase));
                            $proxiedImageUrl = route('storage.cbir.proxy', ['path' => $pathProxy]);
                        }
                    }

                    // Fallback URL (Gambar asli dari galeri database jika AI path gagal)
                    $fallbackUrl = ($batik && optional($batik->mainImage)->full_url) ? $batik->mainImage->full_url : null;
                    if ($fallbackUrl && strpos($fallbackUrl, $s3SignatureBase) === 0) {
                        $pathProxy = substr($fallbackUrl, strlen($s3SignatureBase));
                        $fallbackUrl = route('storage.batik.proxy', ['path' => $pathProxy]);
                    }

                    return [
                        'label'        => $label,
                        'image_url'    => $proxiedImageUrl,
                        'fallback_url' => $fallbackUrl,
                        'similarity'   => round(($item['similarity'] ?? 0) * 100, 1),
                        'galeri_url'   => $batik ? route('galeri.show', $batik->id) : null,
                    ];
                })
                ->values()->all();

            return response()->json([
                'success'    => true,
                'cluster_id' => $data['cluster_id'] ?? null,
                'results'    => $results,
            ]);

        } catch (\Throwable $e) {
            Log::error('PencarianBatik CBIR Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function findBatikByLabel(string $label): ?Batik
    {
        if (empty($label)) return null;
        $n = strtolower(str_replace(['_', '-'], ' ', $label));

        $batik = Batik::where('is_active', true)->with('mainImage')
            ->whereRaw('LOWER(REPLACE(REPLACE(name,"_"," "),"-"," ")) LIKE ?', ["%{$n}%"])
            ->first();

        if (!$batik) {
            $word = explode(' ', $n)[0];
            if (strlen($word) >= 3) {
                $batik = Batik::where('is_active', true)->with('mainImage')
                    ->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"])
                    ->first();
            }
        }
        return $batik;
    }
}
