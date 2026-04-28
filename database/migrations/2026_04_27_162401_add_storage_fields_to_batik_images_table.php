<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom storage_disk dan s3_key ke tabel batik_images
 * untuk mendukung penyimpanan gambar dari S3 Object Storage (IDCloudHost)
 * di samping local storage yang sudah ada.
 *
 * - storage_disk : 'public' (lokal) atau 's3-batik' (S3 IDCloudHost)
 * - s3_key       : Full key di S3, misal: "Acha Mahakala/foto1.webp"
 *
 * Data lama tetap berfungsi karena default storage_disk = 'public'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batik_images', function (Blueprint $table) {
            $table->string('storage_disk')->default('public')->after('is_main');
            $table->string('s3_key')->nullable()->after('storage_disk');
        });
    }

    public function down(): void
    {
        Schema::table('batik_images', function (Blueprint $table) {
            $table->dropColumn(['storage_disk', 's3_key']);
        });
    }
};
