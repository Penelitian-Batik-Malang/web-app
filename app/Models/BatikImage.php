<?php
/**
 * BatikImage — Gambar variasi motif batik.
 *
 * full_url accessor:
 *   - S3  → URL publik langsung ke IDCloudHost (bucket sudah public read)
 *   - Local → /storage/batiks/...
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BatikImage extends Model
{
    protected $fillable = ['batik_id', 'image_path', 'is_main', 'storage_disk', 's3_key'];

    public function batik()  { return $this->belongsTo(Batik::class); }
    public function likes()  { return $this->belongsToMany(User::class, 'batik_image_likes')->withTimestamps(); }

    /**
     * URL publik gambar.
     *
     * S3: direct URL — bucket sudah public read, browser load langsung.
     *   Contoh: https://is3.cloudhost.id/batik-signature-gdrive/Folder/file.jpg
     *
     * Local: /storage/batiks/xxx.jpg
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->storage_disk === 's3-batik') {
            $key = $this->s3_key ?? $this->image_path;
            return Storage::disk('s3-batik')->url($key);
        }
        return Storage::url($this->image_path);
    }
}
