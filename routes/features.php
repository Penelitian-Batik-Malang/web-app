<?php
/**
 * =========================================================================
 * routes/features.php — Semua route fitur ML Batik
 * =========================================================================
 * Di-include dari routes/web.php.
 *
 * Konvensi penamaan:
 *   - Route show  : <feature>.<nama>        (e.g. deteksi.motif, pencarian.batik)
 *   - Route API   : api.<feature>.<aksi>    (e.g. api.detect.motif, api.search.batik)
 *
 * Status implementasi:
 *   [DONE] Deteksi Motif, Deteksi Jenis
 *   [DONE] Terapkan Batik, Rekomendasi Batik
 *   [TODO] Pencarian Batik, Pencarian Warna, Pewarnaan Palet, Pewarnaan Prompt, Text-to-Image
 * =========================================================================
 */

use App\Http\Controllers\Features\DeteksiMotifController;
use App\Http\Controllers\Features\DeteksiJenisController;
use App\Http\Controllers\Features\SharedMLController;
use App\Http\Controllers\Features\TerapkanBatikController;
use App\Http\Controllers\Features\RekomendasiBatikController;
use App\Http\Controllers\Features\PencarianBatikController;
use App\Http\Controllers\Features\PencarianWarnaController;
use App\Http\Controllers\Features\PewarnaaanPaletController;
use App\Http\Controllers\Features\PewarnaaanPromptController;
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
    Route::get('/pewarnaan-palet', [PewarnaaanPaletController::class, 'show'])->name('pewarnaan.palet');
    // TODO: Route::post('/api/pewarnaan/palet', [PewarnaaanPaletController::class, 'process'])->name('api.pewarnaan.palet');
});

// ── [TODO] Pewarnaan by Prompt ────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:pewarnaan-prompt')->group(function () {
    Route::get('/pewarnaan-prompt', [PewarnaaanPromptController::class, 'show'])->name('pewarnaan.prompt');
    // TODO: Route::post('/api/pewarnaan/prompt', [PewarnaaanPromptController::class, 'process'])->name('api.pewarnaan.prompt');
});

// ── [TODO] Text to Image Batik ────────────────────────────────────────────────
Route::middleware('menu.access_or_guest:text-to-image')->group(function () {
    Route::get('/text-to-image', [TextToImageController::class, 'show'])->name('text-to-image');
    // TODO: Route::post('/api/text-to-image', [TextToImageController::class, 'generate'])->name('api.text-to-image');
});
