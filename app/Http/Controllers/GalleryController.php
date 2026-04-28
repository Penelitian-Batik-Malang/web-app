<?php
/**
 * =========================================================================
 * GalleryController — Galeri Batik Publik & Like/Rekomendasi
 * =========================================================================
 *
 * Controller ini mengelola halaman galeri batik publik dan fitur
 * interaktif like & rekomendasi pada halaman detail batik.
 *
 * FITUR:
 *   1. index()     — Halaman galeri (grid thumbnail batik aktif)
 *   2. show()      — Halaman detail batik (gambar-gambar + info)
 *   3. toggleLike() — AJAX like/unlike gambar batik (auth required)
 *   4. autoLike()  — Auto-like setelah login redirect (guest → login → like)
 *   5. recommend() — Rekomendasi batik serupa setelah like (ML API)
 *
 * ALUR LIKE → REKOMENDASI:
 *   1. User melihat detail batik di /galeri/{batik}
 *   2. User klik "Suka" pada gambar variasi motif
 *   3. Frontend memanggil POST /api/batik-images/{id}/like
 *   4. Setelah like berhasil, frontend memanggil GET /api/batik-images/{id}/recommend
 *   5. Controller mengirim gambar yang di-like ke ML API untuk cari batik serupa
 *   6. ML API mengembalikan daftar batik dengan similarity score
 *   7. Frontend menampilkan grid rekomendasi di bawah galeri detail
 *
 * ALUR GUEST LIKE:
 *   1. Guest klik "Suka" → redirect ke /galeri/like/{imageId}
 *   2. Middleware auth redirect ke /login (intended URL tersimpan)
 *   3. Setelah login, redirect ke /galeri/like/{imageId}
 *   4. autoLike() meng-apply like secara idempotent
 *   5. Redirect ke halaman detail batik dengan flash message
 *
 * INTEGRASI ML:
 *   Method recommend() menggunakan endpoint CBIR (Content-Based Image
 *   Retrieval) dari ML API untuk mencari batik yang visual-nya serupa
 *   dengan gambar yang di-like user.
 *
 * @see config/services.php → services.ml.endpoints.search_batik
 * @see resources/views/pages/galeri/show.blade.php — Detail batik view
 * @see resources/views/pages/galeri/index.blade.php — Gallery index view
 * =========================================================================
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Batik;
use App\Models\BatikImage;

class GalleryController extends Controller
{
    /**
     * Tampilkan halaman galeri batik publik.
     *
     * Mendukung filter berdasarkan:
     *   - tipe : tulis | cap
     *   - cari : pencarian nama batik (LIKE query)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Batik::where('is_active', true)->with('mainImage');
        
        // Filter tipe
        if ($request->has('tipe') && in_array($request->tipe, ['tulis', 'cap'])) {
            $query->where('type', $request->tipe);
        }

        // Search by nama
        if ($request->filled('cari')) {
            $query->where('name', 'LIKE', '%' . $request->cari . '%');
        }

        $batiks = $query->latest()->paginate(15);
        return view('pages.galeri.index', compact('batiks'));
    }

    /**
     * Tampilkan halaman detail batik.
     *
     * Memuat semua variasi gambar dengan jumlah likes masing-masing.
     * Hanya batik aktif yang bisa diakses (404 jika nonaktif).
     *
     * @param  \App\Models\Batik  $batik  Route model binding
     * @return \Illuminate\View\View
     */
    public function show(Batik $batik)
    {
        abort_if(!$batik->is_active, 404);

        // Paginate images (20/page)
        $images = $batik->images()->withCount('likes')->paginate(20);

        // Cek apakah user sudah like gambar manapun dari batik ini (untuk rekomendasi section)
        $hasLikedAny = auth()->check()
            ? $batik->images()->whereHas('likes', fn($q) => $q->where('user_id', auth()->id()))->exists()
            : false;

        return view('pages.galeri.show', compact('batik', 'images', 'hasLikedAny'));
    }

    /**
     * Toggle like/unlike pada gambar batik (AJAX).
     *
     * POST /api/batik-images/{id}/like
     *
     * Jika user sudah like → unlike (detach).
     * Jika user belum like → like (attach).
     *
     * @param  int  $id  ID BatikImage
     * @return \Illuminate\Http\JsonResponse  { success, message, likes_count, is_liked }
     */
    public function toggleLike($id)
    {
        $image = BatikImage::findOrFail($id);
        $user = auth()->user();

        // Cek apakah user sudah melike ini
        $isLiked = $user->likedBatikImages()->where('batik_image_id', $image->id)->exists();

        if ($isLiked) {
            $user->likedBatikImages()->detach($image->id);
            $message = 'Like Dibatalkan';
        } else {
            $user->likedBatikImages()->attach($image->id);
            $message = 'Berhasil Menyukai';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'likes_count' => $image->likes()->count(),
            'is_liked' => !$isLiked
        ]);
    }

    /**
     * Auto-like setelah guest login (idempotent).
     *
     * GET /galeri/like/{imageId}
     *
     * Route ini dipanggil ketika guest yang ingin like diarahkan
     * ke login terlebih dahulu. Setelah login berhasil, middleware
     * auth mengarahkan kembali ke route ini yang otomatis meng-apply
     * like lalu redirect ke halaman detail batik.
     *
     * @param  int  $imageId  ID BatikImage yang akan di-like
     * @return \Illuminate\Http\RedirectResponse
     */
    public function autoLike($imageId)
    {
        $image = BatikImage::findOrFail($imageId);
        $user = auth()->user();

        // Hanya like jika belum pernah like (idempotent)
        $alreadyLiked = $user->likedBatikImages()->where('batik_image_id', $image->id)->exists();
        if (!$alreadyLiked) {
            $user->likedBatikImages()->attach($image->id);
        }

        // Redirect ke halaman detail batik dengan notifikasi sukses
        return redirect()->route('galeri.show', $image->batik_id)
            ->with('like_success', $image->id);
    }

    /**
     * Dapatkan rekomendasi batik serupa berdasarkan gambar yang di-like.
     *
     * GET /api/batik-images/{id}/recommend
     *
     * Method ini mengirim gambar yang di-like ke ML API endpoint CBIR
     * (Content-Based Image Retrieval) untuk mencari batik dengan
     * visual yang serupa dari database.
     *
     * Response format:
     *   {
     *     success: true,
     *     recommendations: [
     *       { name, image_url, type, similarity_score }
     *     ]
     *   }
     *
     * CATATAN IMPLEMENTASI:
     *   Saat ini method ini masih berupa structure/stub yang siap
     *   diintegrasikan dengan ML API. Ketika API tersedia, uncomment
     *   blok integrasi di bawah dan sesuaikan response mapping.
     *
     * @param  int  $id  ID BatikImage yang di-like
     * @return \Illuminate\Http\JsonResponse
     *
     * @see config/services.php → services.ml.endpoints.search_batik
     */
    public function recommend($id)
    {
        $image = BatikImage::findOrFail($id);

        // ── Konfigurasi Batik Service ─────────────────────────────────
        $batikUrl = rtrim((string) config('services.ml.batik_url', ''), '/');

        if (empty($batikUrl)) {
            return response()->json([
                'success'         => false,
                'message'         => 'Batik Service belum terhubung. Konfigurasi ML_BATIK_URL di .env.',
                'recommendations' => [],
            ], 501);
        }

        // ── Fetch gambar yang di-like dari S3 ─────────────────────────
        // Gambar tersimpan di S3, kita ambil biner-nya lalu kirim ke ML API
        try {
            $imageUrl = $image->full_url;
            $imgResp  = Http::timeout(20)->get($imageUrl);

            if (!$imgResp->successful()) {
                return response()->json([
                    'success'         => false,
                    'message'         => 'Gagal mengambil gambar dari S3 untuk dianalisis.',
                    'recommendations' => [],
                ], 500);
            }

            $imgBinary  = $imgResp->body();
            $imgMime    = $imgResp->header('Content-Type', 'image/jpeg');
            $imgExt     = str_contains($imgMime, 'png') ? 'png' : 'jpg';
            $imgFilename = 'liked_image.' . $imgExt;

        } catch (\Throwable $e) {
            Log::error('Recommend - S3 fetch failed: ' . $e->getMessage());
            return response()->json([
                'success'         => false,
                'message'         => 'Gagal mengambil gambar dari storage.',
                'recommendations' => [],
            ], 500);
        }

        // ── Kirim ke Batik Service /search/general ────────────────────
        try {
            $mlUrl    = $batikUrl . '/search/general';
            $response = Http::timeout(60)
                ->attach('file', $imgBinary, $imgFilename)
                ->post($mlUrl);

            if (!$response->successful()) {
                Log::warning('ML Recommend API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json([
                    'success'         => false,
                    'message'         => 'Model AI tidak memberikan respons yang valid.',
                    'recommendations' => [],
                ], $response->status());
            }

            $data       = $response->json();
            $s3Base     = rtrim((string) config('services.ml.s3_batik_base', 'https://is3.cloudhost.id/batik-signature-gdrive'), '/');

            $recommendations = collect($data['results'] ?? [])
                ->map(function ($item) use ($s3Base) {
                    $path      = ltrim(str_replace('\\', '/', $item['path_s3'] ?? ''), '/');
                    $label     = $item['label'] ?? '';
                    $imageUrl  = $s3Base . '/' . $path;
                    $galeriUrl = $this->findGaleriUrlByLabel($label);

                    return [
                        'name'       => $label,
                        'image_url'  => $imageUrl,
                        'type'       => '',
                        'similarity' => round(($item['similarity'] ?? 0) * 100, 1),
                        'galeri_url' => $galeriUrl,
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'success'         => true,
                'recommendations' => $recommendations,
            ]);

        } catch (\Throwable $e) {
            Log::error('ML Recommend Error: ' . $e->getMessage());
            return response()->json([
                'success'         => false,
                'message'         => 'Gagal menghubungi Batik Service untuk rekomendasi.',
                'recommendations' => [],
            ], 500);
        }
    }

    /**
     * Temukan URL galeri detail untuk label motif dari hasil CBIR.
     *
     * Label dari ML API bisa berbeda format dengan nama batik di DB,
     * misalnya "adiluhung" vs "Adi Luhung" atau "Topeng Gandring Wirasena"
     * Gunakan pencarian fuzzy (LIKE) case-insensitive.
     *
     * @param  string  $label  Label dari ML API (nama folder S3)
     * @return string|null
     */
    private function findGaleriUrlByLabel(string $label): ?string
    {
        if (empty($label)) return null;

        // Normalisasi: lowercase, ganti underscore/dash dengan spasi
        $normalized = strtolower(str_replace(['_', '-'], ' ', $label));

        $batik = \App\Models\Batik::where('is_active', true)
            ->whereRaw('LOWER(REPLACE(REPLACE(name, "_", " "), "-", " ")) LIKE ?', ["%{$normalized}%"])
            ->first();

        // Fallback: kata pertama saja
        if (!$batik) {
            $firstWord = explode(' ', $normalized)[0];
            if (strlen($firstWord) >= 3) {
                $batik = \App\Models\Batik::where('is_active', true)
                    ->whereRaw('LOWER(name) LIKE ?', ["%{$firstWord}%"])
                    ->first();
            }
        }

        return $batik ? route('galeri.show', $batik->id) : null;
    }
}
