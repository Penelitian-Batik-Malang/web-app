<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Batik;
use App\Models\BatikImage;
use Illuminate\Support\Facades\Storage;

class BatikGalleryController extends Controller
{
    public function index()
    {
        $batiks = Batik::with('mainImage')->latest()->get();
        return view('admin.batiks.index', compact('batiks'));
    }

    public function create()
    {
        return view('admin.batiks.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:tulis,cap',
            'is_active' => 'nullable'
        ]);

        $validated['is_active'] = $request->has('is_active');
        $batik = Batik::create($validated);

        return redirect()->route('admin.batiks.edit', $batik->id)->with('success', 'Data batik berhasil dibuat! Silakan tambahkan gambar.');
    }

    public function edit(Batik $batik)
    {
        $batik->load('images');
        return view('admin.batiks.form', compact('batik'));
    }

    public function update(Request $request, Batik $batik)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:tulis,cap',
            'is_active' => 'nullable'
        ]);

        $validated['is_active'] = $request->has('is_active');
        $batik->update($validated);

        return redirect()->route('admin.batiks.index')->with('success', 'Metadata batik berhasil diperbarui.');
    }

    public function destroy(Batik $batik)
    {
        foreach ($batik->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $batik->delete();
        return redirect()->route('admin.batiks.index')->with('success', 'Galeri Batik dan seluruh aset gambarnya terhapus.');
    }

    public function uploadImage(Request $request, Batik $batik)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        if ($request->file('file')) {
            $path = $request->file('file')->store('batiks', 'public');
            
            // Jadikan yang pertama sebagai main image otomatis
            $isMain = $batik->images()->count() === 0;

            $image = $batik->images()->create([
                'image_path' => $path,
                'is_main' => $isMain
            ]);

            return response()->json(['success' => true, 'id' => $image->id, 'path' => Storage::url($path), 'is_main' => $isMain]);
        }

        return response()->json(['error' => 'Gagal unggah foto'], 400);
    }

    public function destroyImage(BatikImage $image)
    {
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        // Jika dia adalah main image, set gambar lain jadi main otomatis jika ada
        if ($image->is_main) {
            $anotherImage = $image->batik->images()->latest()->first();
            if ($anotherImage) {
                $anotherImage->update(['is_main' => true]);
            }
        }

        return back()->with('success', 'Gambar berhasil dihapus dari sistem.');
    }

    public function setMainImage(BatikImage $image)
    {
        // Matikan semua main image untuk batik ini
        BatikImage::where('batik_id', $image->batik_id)->update(['is_main' => false]);
        // Set yang dipilih jadi main
        $image->update(['is_main' => true]);

        return back()->with('success', 'Thumbnail utama berhasil diganti.');
    }
}
