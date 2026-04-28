<?php
/**
 * =========================================================================
 * SyncBatikFromS3 — Artisan Command untuk Sync Galeri dari S3
 * =========================================================================
 *
 * Membaca struktur folder di bucket S3 IDCloudHost dan membuat
 * record database (batiks + batik_images). TIDAK download file —
 * gambar langsung di-serve dari S3 via URL publik.
 *
 * Struktur S3:
 *   batik-signature-gdrive/
 *     ├── Acha Mahakala/         ← folder = 1 motif batik
 *     │   ├── foto1.webp         ← file = 1 batik_image
 *     │   └── foto2.webp
 *     └── ...
 *
 * PENTING: Data existing (likes, manual upload, dll) tetap aman.
 *
 * Usage:
 *   php artisan batik:sync-s3               # Sync folder baru
 *   php artisan batik:sync-s3 --dry-run     # Preview tanpa execute
 *   php artisan batik:sync-s3 --force       # Re-sync semua dari S3
 *   php artisan batik:sync-s3 --type=cap    # Default tipe = cap
 *
 * @see config/filesystems.php → disks.s3-batik
 * =========================================================================
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Batik;
use App\Models\BatikImage;

class SyncBatikFromS3 extends Command
{
    protected $signature = 'batik:sync-s3
                            {--dry-run : Preview saja, tanpa menyimpan ke database}
                            {--force : Re-sync semua, termasuk yang sudah ada}
                            {--type=tulis : Default tipe batik (tulis/cap)}
                            {--cleanup : Hapus data partial sync sebelumnya}';

    protected $description = 'Sync metadata galeri batik dari S3 IDCloudHost ke database (tanpa download file)';

    public function handle(): int
    {
        // ── Cleanup mode ─────────────────────────────────────────
        if ($this->option('cleanup')) {
            return $this->runCleanup();
        }

        $s3      = Storage::disk('s3-batik');
        $dryRun  = $this->option('dry-run');
        $force   = $this->option('force');
        $type    = $this->option('type');

        $this->info('');
        $this->info('══════════════════════════════════════════════════');
        $this->info('  Sync Galeri Batik dari S3 IDCloudHost');
        $this->info('══════════════════════════════════════════════════');
        $this->info("  Bucket  : " . config('filesystems.disks.s3-batik.bucket'));
        $this->info("  Mode    : " . ($dryRun ? '🔍 DRY RUN (preview)' : '✏️  METADATA SYNC'));
        $this->info("  Force   : " . ($force ? 'Ya (re-sync semua)' : 'Tidak (skip existing)'));
        $this->info("  Type    : " . $type);
        $this->info("  Catatan : Gambar langsung di-serve dari S3 (tanpa download)");
        $this->info('══════════════════════════════════════════════════');
        $this->info('');

        // ── Step 1: List semua folder (motif batik) ──────────────
        $this->info('📂 Membaca daftar folder di bucket...');

        try {
            $directories = $s3->directories('');
        } catch (\Throwable $e) {
            $this->error('❌ Gagal terhubung ke S3: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (empty($directories)) {
            $this->warn('⚠️  Tidak ada folder ditemukan di bucket.');
            return self::SUCCESS;
        }

        $this->info("   Ditemukan " . count($directories) . " folder motif batik.");
        $this->info('');

        // ── Step 2: Proses setiap folder ─────────────────────────
        $stats = ['created' => 0, 'skipped' => 0, 'images' => 0, 'errors' => 0];
        $bar = $this->output->createProgressBar(count($directories));
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% — %message%");
        $bar->start();

        foreach ($directories as $dir) {
            $folderName = basename($dir);
            $bar->setMessage($folderName);

            // Cek apakah motif sudah ada di database
            $existing = Batik::where('name', $folderName)->first();

            if ($existing && !$force) {
                $stats['skipped']++;
                $bar->advance();
                continue;
            }

            // List file gambar di folder ini
            $files = $this->getImageFiles($s3, $dir);

            if (empty($files)) {
                $stats['skipped']++;
                $bar->advance();
                continue;
            }

            if ($dryRun) {
                $this->line("   [DRY] {$folderName} — " . count($files) . " gambar");
                $stats['created']++;
                $stats['images'] += count($files);
                $bar->advance();
                continue;
            }

            // ── Buat/update record database ──────────────────────
            try {
                $batik = $existing ?? Batik::create([
                    'name'        => $folderName,
                    'description' => null,
                    'type'        => $type,
                    'is_active'   => true,
                ]);

                // Jika force, hapus gambar S3 lama (bukan manual upload)
                if ($force && $existing) {
                    $existing->images()
                        ->where('storage_disk', 's3-batik')
                        ->delete();
                }

                // Tambah gambar baru (metadata saja, tanpa download)
                $isFirst = $batik->images()->count() === 0;
                foreach ($files as $i => $s3Key) {
                    BatikImage::create([
                        'batik_id'     => $batik->id,
                        'image_path'   => $s3Key,
                        'is_main'      => $isFirst && $i === 0,
                        'storage_disk' => 's3-batik',
                        's3_key'       => $s3Key,
                    ]);
                    $stats['images']++;
                }

                $stats['created']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->newLine();
                $this->error("   ❌ Error pada '{$folderName}': " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // ── Step 3: Summary ──────────────────────────────────────
        $this->info('══════════════════════════════════════════════════');
        $this->info('  HASIL SYNC');
        $this->info('══════════════════════════════════════════════════');
        $this->info("  ✅ Motif batik dibuat/diproses : {$stats['created']}");
        $this->info("  🖼️  Total gambar ditambahkan   : {$stats['images']}");
        $this->info("  ⏭️  Motif di-skip (sudah ada)  : {$stats['skipped']}");
        if ($stats['errors'] > 0) {
            $this->error("  ❌ Error                      : {$stats['errors']}");
        }
        $this->info('══════════════════════════════════════════════════');
        $this->info('');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN — tidak ada perubahan. Jalankan tanpa --dry-run untuk execute.');
        }

        return self::SUCCESS;
    }

    /**
     * Hapus data partial sync (dari percobaan download sebelumnya).
     */
    private function runCleanup(): int
    {
        $this->info('🧹 Membersihkan data partial sync...');

        // Hapus BatikImage yang punya s3_key tapi storage_disk = 'public' (partial download)
        $partialImages = BatikImage::where('storage_disk', 'public')
            ->whereNotNull('s3_key')
            ->delete();
        $this->info("   Deleted partial download records: {$partialImages}");

        // Hapus orphan batiks (tanpa gambar, tanpa description = dari sync)
        $orphans = Batik::doesntHave('images')->whereNull('description')->delete();
        $this->info("   Deleted orphan batiks: {$orphans}");

        // Hapus folder download yang partial
        $path = storage_path('app/public/batiks');
        $cleanedFolders = 0;
        if (is_dir($path)) {
            foreach (glob($path . '/*', GLOB_ONLYDIR) as $folder) {
                $basename = basename($folder);
                // Folder motif dari S3 sync (bukan file individual dari upload)
                if (strlen($basename) > 3 && !preg_match('/^[a-f0-9]+$/', $basename)) {
                    array_map('unlink', glob("{$folder}/*.*"));
                    if (is_dir($folder)) @rmdir($folder);
                    $cleanedFolders++;
                }
            }
        }
        $this->info("   Cleaned download folders: {$cleanedFolders}");

        $this->info('');
        $this->info("Remaining: " . Batik::count() . " batiks, " . BatikImage::count() . " images");
        $this->info('✅ Cleanup selesai. Jalankan `php artisan batik:sync-s3` untuk re-sync metadata.');

        return self::SUCCESS;
    }

    /**
     * Ambil daftar file gambar dari folder S3.
     */
    private function getImageFiles($disk, string $directory): array
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
