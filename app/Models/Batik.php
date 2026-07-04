<?php
/**
 * =========================================================================
 * Batik Model — Data Motif Batik Malang
 * =========================================================================
 *
 * Setiap record mewakili satu motif batik (misal: "Acha Mahakala").
 * Satu motif bisa memiliki banyak variasi gambar (BatikImage).
 *
 * Kolom:
 *   - name        : Nama motif batik
 *   - description : Deskripsi motif (nullable, bisa diisi admin nanti)
 *   - type        : 'tulis' atau 'cap'
 *   - is_active   : Apakah tampil di galeri publik
 *
 * Sumber data:
 *   - Manual via admin panel (CRUD)
 *   - Auto-sync dari S3 IDCloudHost via `php artisan batik:sync-s3`
 *
 * @see BatikImage                     — Gambar variasi motif
 * @see GalleryController              — Frontend galeri
 * @see Admin\BatikGalleryController   — Admin CRUD
 * =========================================================================
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batik extends Model
{
    protected $fillable = ['name', 'description', 'type', 'is_active'];

    /**
     * Semua variasi gambar motif ini.
     */
    public function images()
    {
        return $this->hasMany(BatikImage::class);
    }

    public function mainImage()
    {
        return $this->hasOne(BatikImage::class)->where('is_main', true);
    }

    public function getRouteKeyName()
    {
        return 'name';
    }
}
