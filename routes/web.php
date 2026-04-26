<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $content = \App\Models\LandingContent::getMap();
    return view('pages.home', compact('content'));
});

// Removed old galeri route

Route::get('/fitur', function () {
    return view('pages.features');
})->name('fitur');

Route::middleware('menu.access_or_guest:deteksi-motif')->group(function () {
    Route::get('/deteksi/motif', function () {
        return view('pages.deteksi-motif');
    })->name('deteksi.motif');
    Route::post('/api/detect/motif', [App\Http\Controllers\MLController::class, 'detectMotif'])->name('api.detect.motif');
});

Route::middleware('menu.access_or_guest:deteksi-jenis')->group(function () {
    Route::get('/deteksi/jenis', function () {
        return view('pages.deteksi-jenis');
    })->name('deteksi.jenis');
    Route::post('/api/detect/jenis', [App\Http\Controllers\MLController::class, 'detectJenis'])->name('api.detect.jenis');
});

Route::middleware('menu.access_or_guest:pewarnaan-palet')->group(function () {
    Route::get('/pewarnaan/palet', function () {
        $batiks = \App\Models\Batik::where('is_active', true)->with('mainImage')->get();
        return view('pages.pewarnaan-pallet-warna', compact('batiks'));
    })->name('pewarnaan.palet');
    
    Route::post('/pewarnaan/palet/proses', function (\Illuminate\Http\Request $request) {
        try {
            // Validasi gambar batik sumber
            $batikImage = $request->input('batik_image');
            $colorImage = $request->input('color_image');
            
            
            // Extract palette dari color_image menggunakan API - extract semua 3 metode
            $palettesKmeans = [];
            $palettesHistogram = [];
            $paletteMedianCut = [];
            $baseUrl = rtrim((string) config('services.ml.base_url', env('ML_API_BASE_URL', '')), '/');
            
            if (!empty($baseUrl)) {
                try {
                    // Convert base64 ke file content
                    $colorBase64 = $colorImage;
                    if (strpos($colorBase64, 'data:image') === 0) {
                        $colorBase64 = substr($colorBase64, strpos($colorBase64, ',') + 1);
                    }
                    $colorImageContent = base64_decode($colorBase64);
                    
                    // Call API extract palette dengan method all untuk dapet semua 3 metode
                    $response = \Illuminate\Support\Facades\Http::timeout(30)
                        ->attach('image', $colorImageContent, 'color_image.jpg')
                        ->attach('method', 'all')
                        ->attach('n_colors', '6')
                        ->post($baseUrl . '/palette/extract');
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        // Get semua 3 metode
                        $palettesKmeans = $data['palettes']['kmeans'] ?? [];
                        $palettesHistogram = $data['palettes']['histogram'] ?? [];
                        $paletteMedianCut = $data['palettes']['median_cut'] ?? [];
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Palette extract error: ' . $e->getMessage());
                    // Jika extract gagal, tetap lanjut tanpa palette
                }
            }
            
            // Pass gambar dan palettes (semua 3 metode) ke view
            return view('pages.pewarnaanPalletNet.proses-gambar', compact(
                'batikImage',
                'colorImage',
                'palettesKmeans',
                'palettesHistogram',
                'paletteMedianCut'
            ));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    })->name('pewarnaan.palet.proses');
    
    Route::post('/api/colorize/palet', [App\Http\Controllers\MLController::class, 'colorizePalet'])->name('api.colorize.palet');
});


Route::get('/login', function () {
    return view('pages.login');
})->name('login')->middleware('guest');

Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.post')->middleware('guest');

Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register.post')->middleware('guest');

Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/auth/google', [App\Http\Controllers\GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [App\Http\Controllers\GoogleAuthController::class, 'callback']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function() {
            if (!auth()->user()->hasAdminAccess()) abort(403);
            return app(App\Http\Controllers\Admin\DashboardController::class)->index();
        })->name('dashboard');

        // Kelola Konten
        Route::middleware('menu.access:kelola-konten')->group(function () {
            Route::get('/landing-contents', [App\Http\Controllers\Admin\LandingContentController::class, 'index'])->name('landing-contents.index');
            Route::post('/landing-contents', [App\Http\Controllers\Admin\LandingContentController::class, 'update'])->name('landing-contents.update');
        });

        // Kelola Role
        Route::middleware('menu.access:kelola-role')->group(function () {
            Route::resource('roles', App\Http\Controllers\Admin\RoleController::class)->except(['show']);
        });

        // Kelola User
        Route::middleware('menu.access:kelola-user')->group(function () {
            Route::resource('users', App\Http\Controllers\Admin\UserController::class)->except(['show']);
        });

        // Kelola Galeri Batik
        Route::middleware('menu.access:kelola-galeri')->group(function () {
            Route::resource('batiks', App\Http\Controllers\Admin\BatikGalleryController::class);
            Route::post('batiks/{batik}/images', [App\Http\Controllers\Admin\BatikGalleryController::class, 'uploadImage'])->name('batiks.images.store');
            Route::delete('batiks/images/{image}', [App\Http\Controllers\Admin\BatikGalleryController::class, 'destroyImage'])->name('batiks.images.destroy');
            Route::post('batiks/images/{image}/main', [App\Http\Controllers\Admin\BatikGalleryController::class, 'setMainImage'])->name('batiks.images.main');
        });

        // Monitor Model AI
        Route::middleware('menu.access:monitor-ai')->group(function () {
            Route::get('/monitor-ai', [App\Http\Controllers\Admin\MonitorAiController::class, 'index'])->name('monitor-ai.index');
        });
    });
});

// Galeri Batik Frontend - Bisa diakses tanpa login (publik)
Route::get('/galeri', [App\Http\Controllers\GalleryController::class, 'index'])->name('galeri');
Route::get('/galeri/{batik}', [App\Http\Controllers\GalleryController::class, 'show'])->name('galeri.show');

// Like & Rekomendasi - Wajib login (auto-like redirect dihandle oleh auth middleware)
Route::middleware('auth')->group(function () {
    Route::post('/api/batik-images/{id}/like', [App\Http\Controllers\GalleryController::class, 'toggleLike'])->name('api.batik-images.like');
    Route::get('/api/batik-images/{id}/recommend', [App\Http\Controllers\GalleryController::class, 'recommend'])->name('api.batik-images.recommend');
    // Auto-like setelah redirect login: guest klik like -> login -> route ini terpanggil -> like applied -> redirect ke detail batik
    Route::get('/galeri/like/{imageId}', [App\Http\Controllers\GalleryController::class, 'autoLike'])->name('galeri.auto-like');
});
