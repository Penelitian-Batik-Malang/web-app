<?php

namespace App\Http\Controllers;

use App\Models\Batik;
use App\Models\BatikImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $type   = $request->input('type', '');

        $query = Batik::query()
            ->where('is_active', true)
            ->with('mainImage');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($type) {
            $query->where('type', $type);
        }

        $batiks = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('pages.galeri.index', compact('batiks', 'search', 'type'));
    }

    public function show(Batik $batik)
    {
        if (!$batik->is_active) {
            abort(404);
        }

        $images = $batik->images()->orderByDesc('is_main')->paginate(20);
        $hasLikedAny = false;

        if (auth()->check()) {
            $hasLikedAny = $batik->images()
                ->whereHas('likes', fn($q) => $q->where('user_id', auth()->id()))
                ->exists();
        }

        return view('pages.galeri.show', compact('batik', 'images', 'hasLikedAny'));
    }

    public function autoLike($imageId)
    {
        $image = BatikImage::findOrFail($imageId);

        if (!auth()->check()) {
            return redirect()->route('login')->with('intended_like', $imageId);
        }

        $user = auth()->user();
        $user->likedBatikImages()->syncWithoutDetaching([$imageId]);

        return redirect()
            ->route('galeri.show', $image->batik_id)
            ->with('like_success', $imageId);
    }

    public function toggleLike(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Login diperlukan.'], 401);
        }

        $image = BatikImage::findOrFail($id);
        $user  = auth()->user();
        $result = $user->likedBatikImages()->toggle($id);

        $isLiked = count($result['attached']) > 0;

        return response()->json([
            'success'     => true,
            'is_liked'    => $isLiked,
            'likes_count' => $image->likes()->count(),
        ]);
    }

    public function recommend($id)
    {
        $image    = BatikImage::findOrFail($id);
        $batikUrl = rtrim((string) config('services.ml.url', ''), '/');

        if (empty($batikUrl)) {
            return response()->json(['success' => false, 'recommendations' => []], 501);
        }

        try {
            $imgResp = Http::timeout(20)->get($image->full_url);
            if (!$imgResp->successful()) {
                return response()->json(['success' => false, 'recommendations' => []], 500);
            }

            $mime    = $imgResp->header('Content-Type', 'image/jpeg');
            $ext     = str_contains($mime, 'png') ? 'png' : 'jpg';
            $response = Http::timeout(60)
                ->withHeaders(['X-API-Key' => trim((string) config('services.retrieval.api_key', ''))])
                ->attach('file', $imgResp->body(), 'liked_image.' . $ext)
                ->post($batikUrl . '/search/general');

            if (!$response->successful()) {
                return response()->json(['success' => false, 'recommendations' => []], $response->status());
            }

            $data    = $response->json();
            $s3AiBase   = 'https://is3.cloudhost.id/galeri-batik-digital/';
            $s3SigBase  = 'https://is3.cloudhost.id/batik-signature-gdrive/';

            $recommendations = collect($data['results'] ?? [])
                ->map(function ($item) use ($s3AiBase, $s3SigBase) {
                    $label    = $item['label'] ?? '';
                    $batik    = $this->findBatikByLabel($label);
                    $path     = ltrim(str_replace('\\', '/', $item['path_s3'] ?? ''), '/');
                    
                    // Gunakan AI Bucket sebagai primary source
                    $imageUrl = $s3AiBase . '/' . $path;
                    
                    // Proxy via storage.ai.proxy
                    $proxiedImageUrl = route('storage.ai.proxy', ['path' => $path]);

                    // Fallback: Gambar asli dari galeri
                    $fallbackUrl = ($batik && optional($batik->mainImage)->full_url) ? $batik->mainImage->full_url : null;
                    if ($fallbackUrl && strpos($fallbackUrl, $s3SigBase) === 0) {
                        $pathSig = substr($fallbackUrl, strlen($s3SigBase));
                        $fallbackUrl = route('storage.batik.proxy', ['path' => $pathSig]);
                    }

                    return [
                        'name'         => $label,
                        'image_url'    => $proxiedImageUrl,
                        'fallback_url' => $fallbackUrl,
                        'similarity'   => round(($item['similarity'] ?? 0) * 100, 1),
                        'galeri_url'   => $batik ? route('galeri.show', $batik->id) : null,
                    ];
                })
                ->values()->all();

            return response()->json(['success' => true, 'recommendations' => $recommendations]);

        } catch (\Throwable $e) {
            Log::error('ML Recommend Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'recommendations' => []], 500);
        }
    }

    private function findBatikByLabel(string $label): ?Batik
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
