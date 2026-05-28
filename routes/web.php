<?php

use Illuminate\Support\Facades\Route;

// ── Semua route fitur ML (deteksi, pencarian, pewarnaan, terapkan, dll.) ──────
require __DIR__ . '/features.php';

// ── Home & halaman fitur ──────────────────────────────────────────────────────
Route::get('/', function () {
    $content = \App\Models\LandingContent::getMap();
    return view('pages.home', compact('content'));
});

Route::get('/fitur', function () {
    return view('pages.features');
})->name('fitur');


Route::middleware('menu.access_or_guest:pewarnaan-palet')->group(function () {
    Route::get('/pewarnaan/palet', [App\Http\Controllers\Features\PewarnaanPaletController::class, 'show'])->name('pewarnaan.palet');
    Route::post('/pewarnaan/palet/proses', [App\Http\Controllers\Features\PewarnaanPaletController::class, 'processPalette'])->name('pewarnaan.palet.proses');
    // Unified palette extraction endpoint (FAISS) – uses single ML_URL
    Route::post('/api/color-palette-faiss', [App\Http\Controllers\Features\PewarnaanPaletController::class, 'colorize'])->name('api.colorize.palet');
    Route::get('/pewarnaan/output-gambar', [App\Http\Controllers\Features\PewarnaanPaletController::class, 'showOutput'])->name('pewarnaan.output');
    Route::post('/api/save-results', [App\Http\Controllers\Features\PewarnaanPaletController::class, 'saveResults'])->name('api.save.results');
});


Route::get('/login', function () {
    return view('pages.login');
})->name('login')->middleware('guest');

Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register.post')->middleware('guest');
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Google OAuth ──────────────────────────────────────────────────────────────
Route::get('/auth/google', [App\Http\Controllers\GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [App\Http\Controllers\GoogleAuthController::class, 'callback']);

// ── Profil User ───────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // ── Admin Panel ───────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            if (!auth()->user()->hasAdminAccess()) abort(403);
            return app(App\Http\Controllers\Admin\DashboardController::class)->index();
        })->name('dashboard');

        Route::middleware('menu.access:kelola-konten')->group(function () {
            Route::get('/landing-contents', [App\Http\Controllers\Admin\LandingContentController::class, 'index'])->name('landing-contents.index');
            Route::post('/landing-contents', [App\Http\Controllers\Admin\LandingContentController::class, 'update'])->name('landing-contents.update');
        });

        Route::middleware('menu.access:kelola-role')->group(function () {
            Route::resource('roles', App\Http\Controllers\Admin\RoleController::class)->except(['show']);
        });

        Route::middleware('menu.access:kelola-user')->group(function () {
            Route::resource('users', App\Http\Controllers\Admin\UserController::class)->except(['show']);
        });

        Route::middleware('menu.access:kelola-galeri')->group(function () {
            Route::resource('batiks', App\Http\Controllers\Admin\BatikGalleryController::class);
            Route::post('batiks/{batik}/images', [App\Http\Controllers\Admin\BatikGalleryController::class, 'uploadImage'])->name('batiks.images.store');
            Route::delete('batiks/images/{image}', [App\Http\Controllers\Admin\BatikGalleryController::class, 'destroyImage'])->name('batiks.images.destroy');
            Route::post('batiks/images/{image}/main', [App\Http\Controllers\Admin\BatikGalleryController::class, 'setMainImage'])->name('batiks.images.main');
            Route::post('batiks/sync-s3', [App\Http\Controllers\Admin\BatikGalleryController::class, 'syncFromS3'])->name('batiks.sync-s3');
            Route::post('batiks/{batik}/activate', [App\Http\Controllers\Admin\BatikGalleryController::class, 'activate'])->name('batiks.activate');
        });

        Route::middleware('menu.access:monitor-ai')->group(function () {
            Route::get('/monitor-ai', [App\Http\Controllers\Admin\MonitorAiController::class, 'index'])->name('monitor-ai.index');
        });
    });
});

// ── Galeri Batik (publik) ─────────────────────────────────────────────────────
Route::get('/galeri', [App\Http\Controllers\GalleryController::class, 'index'])->name('galeri');
Route::get('/galeri/{batik}', [App\Http\Controllers\GalleryController::class, 'show'])->name('galeri.show');

// ── Like & Rekomendasi (wajib login) ─────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/api/batik-images/{id}/like', [App\Http\Controllers\GalleryController::class, 'toggleLike'])->name('api.batik-images.like');
    Route::get('/api/batik-images/{id}/recommend', [App\Http\Controllers\GalleryController::class, 'recommend'])->name('api.batik-images.recommend');
    // Auto-like: guest klik like -> login -> route ini terpanggil -> like applied -> redirect ke detail batik
    Route::get('/galeri/like/{imageId}', [App\Http\Controllers\GalleryController::class, 'autoLike'])->name('galeri.auto-like');
});
