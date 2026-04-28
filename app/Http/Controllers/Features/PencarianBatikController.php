<?php
/**
 * =========================================================================
 * PencarianBatikController — Pencarian Batik Serupa (CBIR Gambar)
 * =========================================================================
 *
 * Fitur ini memungkinkan user mencari batik yang serupa secara visual
 * menggunakan Content-Based Image Retrieval (CBIR) berbasis ConvNeXt.
 * User mengunggah gambar batik → sistem mengembalikan batik paling mirip.
 *
 * @status  DONE
 * @menu    pencarian-batik
 *
 * Alur kerja:
 *   1. User mengunggah gambar batik referensi
 *   2. Frontend mengirim gambar ke POST /api/search/batik
 *   3. Controller meneruskan ke Batik Service POST /search/general
 *   4. API mengembalikan top-10 batik serupa dengan path_s3 + similarity
 *   5. Controller memetakan path_s3 ke URL S3 publik dan mencari
 *      batik terkait di database galeri untuk link ke detail
 *
 * Response API ML:
 *   {
 *     success: true,
 *     cluster_id: 3,
 *     results: [{ path_s3, label, cluster, similarity }]
 *   }
 *
 * Catatan S3:
 *   - path_s3 adalah relative path di bucket CBIR (features_768_indexed_database.csv)
 *   - Bucket galeri utama: batik-signature-gdrive
 *   - Format path_s3: "NamaFolder/filename.JPG"
 *   - Label = nama folder = nama motif batik (bisa berbeda format dengan DB)
 *
 * @see resources/views/pages/features/pencarian-batik.blade.php — View
 * @see GalleryController::recommend()  — Rekomendasi dari like (flow serupa)
 * =========================================================================
 */

namespace App\Http\Controllers\Features;

use App\Models\Batik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PencarianBatikController extends BaseMLController
{
    /**
     * Base URL S3 untuk bucket batik-signature-gdrive (galeri utama).
     */
    private function s3BatikBase(): string
    {
        return rtrim((string) config('services.ml.s3_batik_base', 'https://is3.cloudhost.id/batik-signature-gdrive'), '/');
    }

    public function show()
    {
        return view('pages.features.pencarian-batik');
    }

    /**
     * Cari batik serupa menggunakan CBIR gambar.
     *
     * Memanggil Batik Service POST /search/general dengan field `file`.
     * Mengembalikan grid batik serupa dengan image_url (S3) dan galeri link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if (!$this->isBatikAvailable()) {
            return $this->notConfiguredResponse();
        }

        $url  = $this->batikServiceUrl('/search/general');
        $file = $request->file('image');

        try {
            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batik Service gagal merespons (HTTP ' . $response->status() . ').',
                ], $response->status());
            }

            $data = $response->json();

            if (!($data['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $data['message'] ?? 'Pencarian gagal.',
                ], 400);
            }

            // Petakan results ke format frontend dengan URL S3 + galeri link
            $s3Base = $this->s3BatikBase();
            $results = collect($data['results'] ?? [])
                ->map(function ($item) use ($s3Base) {
                    $path     = ltrim(str_replace('\\', '/', $item['path_s3'] ?? ''), '/');
                    $label    = $item['label'] ?? '';
                    $imageUrl = $s3Base . '/' . $path;

                    return [
                        'path_s3'    => $path,
                        'label'      => $label,
                        'image_url'  => $imageUrl,
                        'similarity' => round(($item['similarity'] ?? 0) * 100, 1),
                        'galeri_url' => $this->findGaleriUrl($label),
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'success'    => true,
                'cluster_id' => $data['cluster_id'] ?? null,
                'message'    => $data['message']    ?? '',
                'results'    => $results,
            ]);

        } catch (\Throwable $e) {
            Log::error('PencarianBatik CBIR Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server Model AI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Temukan URL galeri detail untuk label motif dari hasil CBIR.
     *
     * Label dari ML API bisa berbeda format dengan nama batik di DB,
     * misalnya "adiluhung" di CBIR vs "Adi Luhung" di galeri.
     * Gunakan pencarian fuzzy (LIKE) yang case-insensitive.
     *
     * @param  string  $label  Label dari ML API (nama folder S3)
     * @return string|null  URL galeri detail, atau null jika tidak ditemukan
     */
    private function findGaleriUrl(string $label): ?string
    {
        if (empty($label)) return null;

        // Normalisasi: hapus underscore/dash, lowercase → "topeng gandring wirasena"
        $normalized = strtolower(str_replace(['_', '-'], ' ', $label));

        $batik = Batik::where('is_active', true)
            ->whereRaw('LOWER(REPLACE(REPLACE(name, "_", " "), "-", " ")) LIKE ?', ["%{$normalized}%"])
            ->first();

        // Fallback: cari kata pertama saja
        if (!$batik) {
            $firstWord = explode(' ', $normalized)[0];
            if (strlen($firstWord) >= 3) {
                $batik = Batik::where('is_active', true)
                    ->whereRaw('LOWER(name) LIKE ?', ["%{$firstWord}%"])
                    ->first();
            }
        }

        return $batik ? route('galeri.show', $batik->id) : null;
    }
}
