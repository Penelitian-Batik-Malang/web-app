<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Batik;

class CacheBatikImages extends Command
{
    protected $signature = 'batik:cache-images {--retry=3}';
    protected $description = 'Pre-download batik images from S3 to local cache folder (bypass Fortinet gateway)';

    public function handle()
    {
        $retries = (int) $this->option('retry');
        
        $this->info('Starting batik image caching...');
        $this->line('');

        // Get all active batiks with main images
        $batiks = Batik::where('is_active', true)
            ->with('mainImage')
            ->whereHas('mainImage')
            ->get();

        if ($batiks->isEmpty()) {
            $this->warn('No active batiks with images found.');
            return;
        }

        $this->info("Found {$batiks->count()} batiks to cache");
        $this->line('');

        $success = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($batiks->count());
        $progressBar->start();

        foreach ($batiks as $batik) {
            $imageUrl = $batik->mainImage->full_url;
            $cacheKey = md5($imageUrl);
            $cachePath = 'batik_cache/' . $cacheKey . '.jpg';

            // Skip if already cached
            if (Storage::disk('public')->exists($cachePath)) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Try to fetch with retries
            $attempt = 0;
            $imageData = null;

            while ($attempt < $retries && !$imageData) {
                $attempt++;
                
                try {
                    Log::info("Fetching batik image (attempt $attempt/$retries)", [
                        'batik_id' => $batik->id,
                        'url' => $imageUrl
                    ]);

                    $response = Http::timeout(120)
                        ->withoutVerifying()
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                            'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                            'Referer' => parse_url($imageUrl, PHP_URL_SCHEME) . '://' . parse_url($imageUrl, PHP_URL_HOST) . '/',
                        ])
                        ->get($imageUrl);

                    if ($response->successful() && $response->body()) {
                        $imageData = $response->body();
                    }

                } catch (\Exception $e) {
                    Log::warning("Fetch attempt $attempt failed", [
                        'error' => $e->getMessage()
                    ]);
                    
                    if ($attempt < $retries) {
                        sleep(2); // Wait 2 seconds before retry
                    }
                }
            }

            // Cache successful
            if ($imageData) {
                try {
                    Storage::disk('public')->put($cachePath, $imageData);
                    $success++;
                    Log::info('Image cached successfully', [
                        'batik_id' => $batik->id,
                        'size' => strlen($imageData)
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Failed to cache image', [
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $failed++;
                Log::error('Failed to fetch image after retries', [
                    'batik_id' => $batik->id,
                    'url' => $imageUrl
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        // Summary
        $this->info("✓ Caching Complete!");
        $this->line("  Cached: $success");
        $this->line("  Failed: $failed");
        $this->line("  Skipped: $skipped");
        
        if ($failed > 0) {
            $this->warn("Some images failed to cache. Check storage/logs/laravel.log for details.");
        }

        return 0;
    }
}
