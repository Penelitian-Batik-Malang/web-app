<?php
/**
 * =========================================================================
 * BatikGalleryController — Admin CRUD Galeri Batik
 * =========================================================================
 *
 * Mengelola data motif batik dan gambar variasi dari admin panel.
 * Mendukung penyimpanan ke local storage (public disk) dan
 * S3 IDCloudHost (s3-batik disk).
 *
 * Fitur:
 *   - CRUD data batik (nama, deskripsi, tipe, status aktif)
 *   - Upload gambar variasi (ke local atau S3)
 *   - Set gambar utama (thumbnail galeri)
 *   - Hapus gambar (dari disk yang sesuai)
 *
 * @see Batik model          — Data motif batik
 * @see BatikImage model     — Gambar variasi motif
 * @see SyncBatikFromS3      — Auto-sync dari S3
 * =========================================================================
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Batik;
use App\Models\BatikImage;
use Illuminate\Support\Facades\Storage;

class BatikGalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = Batik::with('mainImage')->latest();

        if ($request->filled('cari')) {
            $query->where('name', 'LIKE', '%' . $request->cari . '%');
        }
        if ($request->filled('tipe') && in_array($request->tipe, ['tulis', 'cap'])) {
            $query->where('type', $request->tipe);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'aktif');
        }

        $batiks = $query->paginate(15)->withQueryString();
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

        return redirect()->route('admin.batiks.edit', $batik)->with('success', 'Data batik berhasil dibuat! Silakan tambahkan gambar.');
    }

    public function edit(Batik $batik)
    {
        $images = $batik->images()->paginate(16);
        return view('admin.batiks.form', compact('batik', 'images'));
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

    /**
     * Non-aktifkan batik (sembunyikan dari galeri publik).
     *
     * TIDAK menghapus data dari database — data dan likes tetap aman.
     * Gunakan tombol "Aktifkan" di halaman edit untuk mengaktifkan kembali.
     * Untuk hapus permanen, gunakan destroyPermanent().
     */
    public function destroy(Request $request, Batik $batik)
    {
        $batik->update(['is_active' => false]);

        return redirect()->route('admin.batiks.index', $request->only(['page', 'cari', 'tipe', 'status']))
            ->with('success', "'{$batik->name}' disembunyikan dari galeri publik. Data & likes tetap aman.");
    }

    /**
     * Aktifkan kembali batik yang tersembunyi.
     */
    public function activate(Request $request, Batik $batik)
    {
        $batik->update(['is_active' => true]);

        return redirect()->route('admin.batiks.index', $request->only(['page', 'cari', 'tipe', 'status']))
            ->with('success', "'{$batik->name}' berhasil diaktifkan dan tampil di galeri.");
    }

    /**
     * Hapus permanen batik dan seluruh gambarnya.
     */
    public function destroyPermanent(Request $request, Batik $batik)
    {
        foreach ($batik->images as $image) {
            $this->deleteImageFile($image);
            $image->delete();
        }
        $batik->delete();

        return redirect()->route('admin.batiks.index', $request->only(['page', 'cari', 'tipe', 'status']))
            ->with('success', "Motif '{$batik->name}' beserta seluruh gambarnya telah dihapus secara permanen.");
    }

    /**
     * Upload gambar baru ke batik (disimpan ke local storage).
     */
    public function uploadImage(Request $request, Batik $batik)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:20480'
        ]);

        if ($request->file('file')) {
            $path = $request->file('file')->store('batiks', 'public');

            // Jadikan yang pertama sebagai main image otomatis
            $isMain = $batik->images()->count() === 0;

            $image = $batik->images()->create([
                'image_path'   => $path,
                'is_main'      => $isMain,
                'storage_disk' => 'public',
                's3_key'       => null,
            ]);

            return response()->json([
                'success' => true,
                'id'      => $image->id,
                'path'    => $image->full_url,
                'is_main' => $isMain,
            ]);
        }

        return response()->json(['error' => 'Gagal unggah foto'], 400);
    }

    /**
     * Hapus gambar individual dari disk yang sesuai.
     */
    public function destroyImage(BatikImage $image)
    {
        $batikId = $image->batik_id;
        $wasMain = $image->is_main;

        $this->deleteImageFile($image);
        $image->delete();

        // Jika dia adalah main image, set gambar lain jadi main otomatis jika ada
        if ($wasMain) {
            $anotherImage = BatikImage::where('batik_id', $batikId)->latest()->first();
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

    // ─── Helper ───────────────────────────────────────────────────

    /**
     * Hapus file fisik gambar dari disk yang sesuai.
     *
     * Untuk local storage: hapus dari disk 'public'.
     * Untuk S3: TIDAK hapus dari S3 (karena S3 adalah source of truth).
     * File S3 hanya dihapus dari database, bukan dari bucket.
     *
     * @param BatikImage $image
     */
    private function deleteImageFile(BatikImage $image): void
    {
        if ($image->storage_disk === 'public') {
            Storage::disk('public')->delete($image->image_path);
        }
        // S3 images: hanya hapus record DB, jangan hapus dari bucket
        // karena bucket adalah source of truth yang bisa di-sync ulang
    }

    /**
     * Sync metadata gambar dari S3 IDCloudHost ke database.
     * Dipanggil via tombol "Sync dari S3" di admin panel.
     *
     * - Hanya simpan path/key S3 ke database (tanpa download file)
     * - Gambar langsung di-serve dari URL S3
     * - Data existing (likes, manual upload) tetap aman
     */
    public function syncFromS3()
    {
        $s3 = Storage::disk('s3-batik');

        try {
            $directories = $s3->directories('');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal terhubung ke S3: ' . $e->getMessage());
        }

        $stats = ['created' => 0, 'skipped' => 0, 'images' => 0];

        foreach ($directories as $dir) {
            $folderName = basename($dir);

            // Skip jika motif sudah ada
            if (Batik::where('name', $folderName)->exists()) {
                $stats['skipped']++;
                continue;
            }

            // List file gambar
            $files = $this->getS3ImageFiles($s3, $dir);
            if (empty($files)) continue;

            // Buat record batik
            $batik = Batik::create([
                'name'        => $folderName,
                'description' => null,
                'type'        => 'tulis',
                'is_active'   => true,
            ]);

            // Buat record gambar (metadata saja)
            foreach ($files as $i => $s3Key) {
                BatikImage::create([
                    'batik_id'     => $batik->id,
                    'image_path'   => $s3Key,
                    'is_main'      => $i === 0,
                    'storage_disk' => 's3-batik',
                    's3_key'       => $s3Key,
                ]);
                $stats['images']++;
            }

            $stats['created']++;
        }

        $message = "Sync selesai! {$stats['created']} motif baru ({$stats['images']} gambar) ditambahkan. {$stats['skipped']} motif sudah ada (di-skip).";
        return back()->with('success', $message);
    }

    /**
     * Ambil daftar file gambar dari folder S3.
     */
    private function getS3ImageFiles($disk, string $directory): array
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];

        try {
            $files = $disk->files($directory);
        } catch (\Throwable $e) {
            return [];
        }

        return array_values(array_filter($files, function ($file) use ($extensions) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, $extensions);
        }));
    }
}
