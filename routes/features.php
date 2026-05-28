<?php
/**
 * =========================================================================
 * routes/features.php — Semua route fitur ML Batik
 * =========================================================================
 *
 * Arsitektur microservice:
 *   - Batik Service  (port 8001): deteksi motif, deteksi jenis, pencarian CBIR
 *   - Fashion Service (port 8002): segmentasi, blending, session management
 *
 * KONVENSI PENAMAAN ROUTE:
 *   - Route halaman : <fitur>.<sub>           (e.g. deteksi.motif)
 *   - Route API     : api.<fitur>.<aksi>      (e.g. api.detect.motif)
 *
 * =========================================================================
 */

use App\Http\Controllers\ColorSearchController;
use App\Http\Controllers\Features\DeteksiMotifController;
use App\Http\Controllers\Features\DeteksiJenisController;
use App\Http\Controllers\Features\SharedMLController;
use App\Http\Controllers\Features\TerapkanBatikController;
use App\Http\Controllers\Features\RekomendasiBatikController;
use App\Http\Controllers\Features\PencarianBatikController;
use App\Http\Controllers\Features\PencarianWarnaController;
use App\Http\Controllers\Features\PewarnaanPaletController;
use App\Http\Controllers\Features\PewarnaanPromptController;
use App\Http\Controllers\Features\TextToImageController;
use Illuminate\Support\Facades\Route;

// ── Serve sample fashion images ───────────────────────────────────────────────
Route::get('/sample-fashion/{filename}', [SharedMLController::class, 'serveSampleFashion'])
    ->name('sample.fashion')
    ->where('filename', '[^/]+\.(jpg|jpeg|png|webp|gif)');

// ── S3 Image Proxy (same-origin canvas loading, avoids cross-origin taint) ───
Route::get('/img', [SharedMLController::class, 'proxyBatikImage'])->name('img.proxy');

// ── Proxy gambar batik S3 (Gallery: batik-signature-gdrive) ───────────────────────
Route::get('/storage/batik/{path}', function (string $path) {
    try {
        return \Illuminate\Support\Facades\Storage::disk('s3-batik')->response(
            ltrim($path, '/'), null, ['Cache-Control' => 'public, max-age=86400']
        );
    } catch (\Throwable $e) {
        abort(404);
    }
})->name('storage.batik.proxy')->where('path', '.+');

// ── Proxy gambar AI (Results: galeri-batik-digital) ───────────────────────────────
Route::get('/storage/ai/{path}', function (string $path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('s3-ai-results');
    $path = ltrim($path, '/');

    // 1. Coba path asli
    if ($disk->exists($path)) {
        return $disk->response($path, null, ['Cache-Control' => 'public, max-age=86400']);
    }

    // 2. Coba bersihkan prefix AI dan coba di root augmentasi/augmentasi/
    $cleanPath = preg_replace('/^(zoom|crops|random_crop|original|rotate|flip|grayscale)\//i', '', $path);
    
    // Coba di root augmentasi/augmentasi/
    $augmentPath = 'augmentasi/augmentasi/' . $path;
    if ($disk->exists($augmentPath)) {
        return $disk->response($augmentPath, null, ['Cache-Control' => 'public, max-age=86400']);
    }

    if ($cleanPath !== $path && $disk->exists($cleanPath)) {
        return $disk->response($cleanPath, null, ['Cache-Control' => 'public, max-age=86400']);
    }

    // 3. Coba part terakhir (folder + filename)
    $parts = explode('/', $path);
    if (count($parts) >= 3) {
        $prefix = $parts[0];
        $file   = $parts[count($parts) - 1];
        $folder = $parts[count($parts) - 2];
        $try1 = "augmentasi/augmentasi/$prefix/$folder/$file";
        $try2 = "augmentasi/augmentasi/$folder/$file";
        if ($disk->exists($try1)) return $disk->response($try1, null, ['Cache-Control' => 'public, max-age=86400']);
        if ($disk->exists($try2)) return $disk->response($try2, null, ['Cache-Control' => 'public, max-age=86400']);
    }

    abort(404);
})->name('storage.ai.proxy')->where('path', '.+');

// ── Proxy gambar CBIR warna (Smart Fallback untuk folder warna) ──────────────────
Route::get('/storage/cbir/{path}', function (string $path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('s3-color-dominant');
    $path = ltrim($path, '/');

    // 1. Coba path asli
    if ($disk->exists($path)) {
        return $disk->response($path, null, ['Cache-Control' => 'public, max-age=86400']);
    }

    // 2. Jika path hanya filename (v_...), coba cari di folder warna (hijau, merah, dll)
    if (strpos($path, '/') === false) {
        $colors = ['hijau', 'merah', 'biru', 'kuning', 'hitam', 'putih', 'coklat', 'abu-abu', 'ungu', 'pink', 'jingga'];
        foreach ($colors as $color) {
            $try = $color . '/' . $path;
            if ($disk->exists($try)) {
                return $disk->response($try, null, ['Cache-Control' => 'public, max-age=86400']);
            }
        }
    }

    abort(404);
})->name('storage.cbir.proxy')->where('path', '.+');

// ── [DONE] Deteksi Motif Batik ────────────────────────────────────────────────
// Batik Service: POST /detection/motif | GET /detection/motif/labels
Route::middleware('menu.access_or_guest:deteksi-motif')->group(function () {
    Route::get('/deteksi/motif', [DeteksiMotifController::class, 'show'])->name('deteksi.motif');
    Route::post('/api/batik/motif', [DeteksiMotifController::class, 'detect'])->name('api.detect.motif');
    Route::get('/api/batik/motif/labels', [DeteksiMotifController::class, 'labels'])->name('api.detect.motif.labels');
    // Alias route for backward compatibility
    Route::get('/api/batik/labels', [DeteksiMotifController::class, 'labels']);
});

// ── [DONE] Deteksi Jenis Batik ────────────────────────────────────────────────
// Batik Service: POST /detection/type | GET /detection/type/labels
Route::middleware('menu.access_or_guest:deteksi-jenis')->group(function () {
    Route::get('/deteksi/jenis', [DeteksiJenisController::class, 'show'])->name('deteksi.jenis');
    Route::post('/api/batik/type', [DeteksiJenisController::class, 'detect'])->name('api.detect.jenis');
    Route::get('/api/batik/type/labels', [DeteksiJenisController::class, 'labels'])->name('api.detect.jenis.labels');
});

// ── [DONE] Shared Fashion Service Session (terapkan-batik | rekomendasi-batik) ─
// Fashion Service: POST /fashion/segment | POST /fashion/reset-session | GET /fashion/session/{id}
Route::middleware('menu.access_or_guest:terapkan-batik,rekomendasi-batik')->group(function () {
    Route::post('/api/inference', [SharedMLController::class, 'inference'])->name('api.inference');
    Route::post('/api/reset', [SharedMLController::class, 'reset'])->name('api.reset');
    Route::get('/api/session/{sessionId}', [SharedMLController::class, 'getSession'])->name('api.session');
});

// ── [DONE] Terapkan Batik ─────────────────────────────────────────────────────
// Fashion Service: POST /fashion/blend-manual
Route::middleware('menu.access_or_guest:terapkan-batik')->group(function () {
    Route::get('/terapkan-batik', [TerapkanBatikController::class, 'show'])->name('terapkan.batik');
    Route::post('/api/blend', [TerapkanBatikController::class, 'blend'])->name('api.blend');
    Route::post('/api/reset-part', [TerapkanBatikController::class, 'resetPart'])->name('api.reset.part');
});

// ── [DONE] Rekomendasi Batik ──────────────────────────────────────────────────
// Fashion Service: POST /fashion/blend-cbir
Route::middleware('menu.access_or_guest:rekomendasi-batik')->group(function () {
    Route::get('/rekomendasi-batik', [RekomendasiBatikController::class, 'show'])->name('rekomendasi.batik');
    Route::post('/api/blend-from-cbir', [RekomendasiBatikController::class, 'blendFromCbir'])->name('api.blend.cbir');
});

// ── [DONE] Pencarian Batik (CBIR) ─────────────────────────────────────────────
// Batik Service: POST /search/general
Route::middleware('menu.access_or_guest:pencarian-batik')->group(function () {
    Route::get('/pencarian-batik', [PencarianBatikController::class, 'show'])->name('pencarian.batik');
    Route::post('/api/search/general', [PencarianBatikController::class, 'search'])->name('api.search.batik');
});

// ── [TODO] Pencarian by Warna Dominan ────────────────────────────────────────
Route::middleware('menu.access_or_guest:pencarian-warna')->group(function () {
    Route::get('/pencarian-warna', [PencarianWarnaController::class, 'show'])->name('pencarian.warna');
    Route::post('/api/search/color-palette', [ColorSearchController::class, 'getPalette'])
        ->name('api.search.color-palette');
    Route::post('/api/search/color-recommendation', [ColorSearchController::class, 'getRecommendation'])
        ->name('api.search.color-recommendation');
});

// ── [TODO] Pewarnaan by Palet Warna ──────────────────────────────────────────
Route::middleware('menu.access_or_guest:pewarnaan-palet')->group(function () {
    Route::get('/pewarnaan-palet', [PewarnaanPaletController::class, 'show'])->name('features.pewarnaan.palet');
    // TODO: Route::post('/api/pewarnaan/palet', [PewarnaanPaletController::class, 'process'])->name('api.pewarnaan.palet');
});

// ── [TODO] Pewarnaan by Prompt ────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:pewarnaan-prompt')->group(function () {
    Route::get('/pewarnaan-prompt', [PewarnaanPromptController::class, 'show'])->name('pewarnaan.prompt');
    // TODO: Route::post('/api/pewarnaan/prompt', [PewarnaanPromptController::class, 'process'])->name('api.pewarnaan.prompt');
});

// ── [TODO] Text to Image Batik ────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:text-to-image')->group(function () {
    Route::get('/text-to-image', [TextToImageController::class, 'show'])->name('text-to-image');
    // TODO: Route::post('/api/text-to-image', [TextToImageController::class, 'generate'])->name('api.text-to-image');
});
