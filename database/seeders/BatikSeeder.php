<?php

namespace Database\Seeders;

use Aws\S3\S3Client;
use Illuminate\Database\Seeder;
use App\Models\Batik;
use App\Models\BatikImage;

class BatikSeeder extends Seeder
{
    public function run(): void
    {
        // Reset data lama agar seed berjalan idempotent.
        BatikImage::query()->delete();
        Batik::query()->delete();

        $s3 = $this->makeS3Client();
        $bucket = (string) env('IDC_S3_BATIK_BUCKET', env('AWS_BUCKET', 'batik-signature-gdrive'));
        $defaultType = 'tulis';

        try {
            $directories = $this->listDirectories($s3, $bucket);
        } catch (\Throwable $e) {
            $this->command?->error('Gagal membaca bucket s3-batik: ' . $e->getMessage());
            return;
        }

        if (empty($directories)) {
            $this->command?->warn('Tidak ada folder motif ditemukan di bucket s3-batik.');
            return;
        }

        foreach ($directories as $directory) {
            $batikName = basename($directory);
            $imageKeys = $this->getImageFiles($s3, $bucket, $directory);

            if (empty($imageKeys)) {
                continue;
            }

            $batik = Batik::create([
                'name' => $batikName,
                'description' => null,
                'type' => $defaultType,
                'is_active' => true,
            ]);

            foreach ($imageKeys as $index => $imageKey) {
                BatikImage::create([
                    'batik_id' => $batik->id,
                    'image_path' => $imageKey,
                    'is_main' => $index === 0,
                    'storage_disk' => 's3-batik',
                    's3_key' => $imageKey,
                ]);
            }
        }
    }

    /**
     * Buat client S3 yang kompatibel dengan IDCloudHost.
     */
    private function makeS3Client(): S3Client
    {
        $verifySsl = filter_var(env('IDC_S3_VERIFY_SSL', true), FILTER_VALIDATE_BOOL);

        return new S3Client([
            'version' => 'latest',
            'region' => (string) env('IDC_S3_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
            'endpoint' => (string) env('IDC_S3_ENDPOINT', 'https://is3.cloudhost.id'),
            'use_path_style_endpoint' => true,
            'http' => [
                'verify' => $verifySsl,
            ],
            'credentials' => [
                'key' => (string) env('IDC_S3_KEY', env('AWS_ACCESS_KEY_ID', '')),
                'secret' => (string) env('IDC_S3_SECRET', env('AWS_SECRET_ACCESS_KEY', '')),
            ],
        ]);
    }

    /**
     * Ambil daftar folder motif dari bucket S3.
     */
    private function listDirectories(S3Client $s3, string $bucket): array
    {
        $result = $s3->listObjectsV2([
            'Bucket' => $bucket,
            'Delimiter' => '/',
        ]);

        $prefixes = $result->get('CommonPrefixes') ?? [];

        return array_values(array_filter(array_map(
            static fn (array $prefix): string => rtrim((string) ($prefix['Prefix'] ?? ''), '/'),
            $prefixes
        )));
    }

    /**
     * Ambil semua file gambar valid dari satu folder S3.
     */
    private function getImageFiles(S3Client $s3, string $bucket, string $directory): array
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];

        try {
            $result = $s3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => rtrim($directory, '/') . '/',
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        $objects = $result->get('Contents') ?? [];

        return array_values(array_filter(array_map(
            static fn (array $object): ?string => $object['Key'] ?? null,
            $objects
        ), function (?string $file) use ($extensions, $directory) {
            if ($file === null || str_ends_with($file, '/')) {
                return false;
            }

            if (! str_starts_with($file, rtrim($directory, '/') . '/')) {
                return false;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, $extensions, true);
        }));
    }
}
