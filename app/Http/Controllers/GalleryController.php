<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Batik;
use App\Models\BatikImage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = Batik::where('is_active', true)->with('mainImage');
        
        // Cek filter 'tipe'
        if ($request->has('tipe') && in_array($request->tipe, ['tulis', 'cap'])) {
            $query->where('type', $request->tipe);
        }

        $batiks = $query->latest()->get();
        return view('pages.galeri.index', compact('batiks'));
    }

    public function show(Batik $batik)
    {
        abort_if(!$batik->is_active, 404);
        
        // Memuat semua variasi gambar lengkap dengan jumlah likes
        $batik->load(['images' => function($q) {
            $q->withCount('likes');
        }]);

        return view('pages.galeri.show', compact('batik'));
    }

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
}
