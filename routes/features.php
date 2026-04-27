<?php
/**
 * =========================================================================
 * routes/features.php — Semua route fitur ML Batik
 * =========================================================================
 *
 * File ini mendefinisikan seluruh route yang berkaitan dengan fitur-fitur
 * Machine Learning pada aplikasi Galeri Digital Batik. Di-include dari
 * routes/web.php agar routing terpisah dan mudah di-maintain.
 *
 * ARSITEKTUR:
 *   Setiap fitur ML memiliki:
 *   - Controller  : app/Http/Controllers/Features/<NamaFitur>Controller.php
 *   - View        : resources/views/pages/features/<nama-fitur>.blade.php
 *   - Config      : config/services.php → services.ml.endpoints.<key>
 *
 * KONVENSI PENAMAAN ROUTE:
 *   - Route halaman : <fitur>.<sub>           (e.g. deteksi.motif, pencarian.batik)
 *   - Route API     : api.<fitur>.<aksi>      (e.g. api.detect.motif, api.search.batik)
 *
 * MIDDLEWARE:
 *   - menu.access_or_guest:<slug>  → Guest boleh akses, user login dicek flagging menu
 *   - menu.access:<slug>           → Hanya user login dengan akses menu yang sesuai
 *
 * KATEGORI FITUR (sesuai UI halaman /fitur):
 *   ┌─────────────────────────────────────────────────────────────────┐
 *   │ DETEKSI & ANALISIS                                             │
 *   │   [DONE] Deteksi Motif Batik    → DeteksiMotifController       │
 *   │   [DONE] Deteksi Jenis Batik    → DeteksiJenisController       │
 *   ├─────────────────────────────────────────────────────────────────┤
 *   │ PENCARIAN BATIK                                                │
 *   │   [TODO] Pencarian Umum         → PencarianBatikController     │
 *   │   [TODO] Pencarian Warna        → PencarianWarnaController     │
 *   │   [DONE] Rekomendasi by Fashion → RekomendasiBatikController   │
 *   ├─────────────────────────────────────────────────────────────────┤
 *   │ KREASI & GENERASI                                              │
 *   │   [TODO] Pewarnaan by Palet     → PewarnaanPaletController     │
 *   │   [TODO] Pewarnaan by Prompt    → PewarnaanPromptController    │
 *   │   [DONE] Terapkan Batik         → TerapkanBatikController      │
 *   │   [TODO] Text to Image Batik    → TextToImageController        │
 *   ├─────────────────────────────────────────────────────────────────┤
 *   │ SHARED (dipakai bersama terapkan-batik & rekomendasi-batik)    │
 *   │   SharedMLController: inference, reset, session                │
 *   └─────────────────────────────────────────────────────────────────┘
 *
 * @see config/services.php       — Konfigurasi endpoint ML API
 * @see docs/ML_API_STRUCTURE_PLAN.md — Arsitektur API ML
 * =========================================================================
 */

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

// ── [DONE] Deteksi Motif Batik ────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:deteksi-motif')->group(function () {
    Route::get('/deteksi/motif', [DeteksiMotifController::class, 'show'])->name('deteksi.motif');
    Route::post('/api/detect/motif', [DeteksiMotifController::class, 'detect'])->name('api.detect.motif');
});

// ── [DONE] Deteksi Jenis Batik ────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:deteksi-jenis')->group(function () {
    Route::get('/deteksi/jenis', [DeteksiJenisController::class, 'show'])->name('deteksi.jenis');
    Route::post('/api/detect/jenis', [DeteksiJenisController::class, 'detect'])->name('api.detect.jenis');
});

// ── [DONE] Shared Fashionpedia Session (terapkan-batik | rekomendasi-batik) ───
Route::middleware('menu.access_or_guest:terapkan-batik,rekomendasi-batik')->group(function () {
    Route::post('/api/inference', [SharedMLController::class, 'inference'])->name('api.inference');
    Route::post('/api/reset', [SharedMLController::class, 'reset'])->name('api.reset');
    Route::get('/api/session/{sessionId}', [SharedMLController::class, 'getSession'])->name('api.session');
});

// ── [DONE] Terapkan Batik ─────────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:terapkan-batik')->group(function () {
    Route::get('/terapkan-batik', [TerapkanBatikController::class, 'show'])->name('terapkan.batik');
    Route::post('/api/detect/mask', [TerapkanBatikController::class, 'detectMask'])->name('api.detect.mask');
    Route::post('/api/apply-batik', [TerapkanBatikController::class, 'applyBatik'])->name('api.apply.batik');
    Route::post('/api/blend', [TerapkanBatikController::class, 'blend'])->name('api.blend');
});

// ── [DONE] Rekomendasi Batik ──────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:rekomendasi-batik')->group(function () {
    Route::get('/rekomendasi-batik', [RekomendasiBatikController::class, 'show'])->name('rekomendasi.batik');
    Route::post('/api/blend-from-cbir', [RekomendasiBatikController::class, 'blendFromCbir'])->name('api.blend.cbir');
});

// ── [TODO] Pencarian Batik (CBIR) ─────────────────────────────────────────────
Route::middleware('menu.access_or_guest:pencarian-batik')->group(function () {
    Route::get('/pencarian-batik', [PencarianBatikController::class, 'show'])->name('pencarian.batik');
    // TODO: Route::post('/api/search/batik', [PencarianBatikController::class, 'search'])->name('api.search.batik');
});

// ── [TODO] Pencarian by Warna Dominan ────────────────────────────────────────
Route::middleware('menu.access_or_guest:pencarian-warna')->group(function () {
    Route::get('/pencarian-warna', [PencarianWarnaController::class, 'show'])->name('pencarian.warna');
    // TODO: Route::post('/api/search/warna', [PencarianWarnaController::class, 'search'])->name('api.search.warna');
});

// ── [TODO] Pewarnaan by Palet Warna ──────────────────────────────────────────
Route::middleware('menu.access_or_guest:pewarnaan-palet')->group(function () {
    Route::get('/pewarnaan-palet', [PewarnaanPaletController::class, 'show'])->name('pewarnaan.palet');
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
