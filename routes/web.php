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
    });
});

// User Features (Secured by auth & flagged menu)
Route::middleware('auth')->group(function () {
    // Galeri Batik Frontend
    Route::middleware('menu.access:galeri-batik')->group(function () {
        Route::get('/galeri', [App\Http\Controllers\GalleryController::class, 'index'])->name('galeri');
        Route::get('/galeri/{batik}', [App\Http\Controllers\GalleryController::class, 'show'])->name('galeri.show');
        Route::post('/api/batik-images/{id}/like', [App\Http\Controllers\GalleryController::class, 'toggleLike'])->name('api.batik-images.like');
    });
});
