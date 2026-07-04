<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ThumbnailController extends Controller
{
    public function generate(Request $request)
    {
        $url = $request->query('url');
        $width = $request->query('w', 300);
        $height = $request->query('h'); // Optional

        if (!$url) {
            abort(400, 'URL is required');
        }

        // Cache key based on URL and dimensions
        $cacheKey = 'thumbnail_' . md5($url . '_' . $width . '_' . $height);

        // Check if image is cached
        $cachedImage = Cache::get($cacheKey);
        
        if ($cachedImage) {
            return response($cachedImage['content'])
                ->header('Content-Type', $cachedImage['mime'])
                ->header('Cache-Control', 'public, max-age=86400'); // 1 day
        }

        try {
            $imageContent = null;
            $appUrl = rtrim(config('app.url'), '/');
            $requestHost = $request->getSchemeAndHttpHost();

            // Detect if URL is local
            $isLocal = false;
            $localPath = '';

            if (str_starts_with($url, '/storage/')) {
                $isLocal = true;
                $localPath = substr($url, 9); // remove '/storage/'
            } elseif (str_starts_with($url, $appUrl . '/storage/')) {
                $isLocal = true;
                $localPath = substr($url, strlen($appUrl . '/storage/'));
            } elseif (str_starts_with($url, $requestHost . '/storage/')) {
                $isLocal = true;
                $localPath = substr($url, strlen($requestHost . '/storage/'));
            }

            if ($isLocal) {
                // Read from local public disk to avoid HTTP request deadlocks in single-threaded dev server
                if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($localPath)) {
                    abort(404, 'Local image not found');
                }
                $imageContent = \Illuminate\Support\Facades\Storage::disk('public')->get($localPath);
            } else {
                // If it's relative but not storage, make it absolute (this shouldn't happen for images but just in case)
                if (str_starts_with($url, '/')) {
                    $url = url($url);
                }

                // Fetch external image
                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->get($url);

                if (!$response->successful()) {
                    abort(404, 'Image not found');
                }
                $imageContent = $response->body();
            }
            
            // Create image manager with GD driver
            $manager = new ImageManager(new Driver());
            
            // Read image from string
            $image = $manager->read($imageContent);
            
            // Resize image (cover/crop to square if height is not provided, otherwise scale down)
            if ($height) {
                $image->cover((int)$width, (int)$height);
            } else {
                // If only width is provided, scale proportionally
                $image->scale(width: (int)$width);
            }
            
            // Encode as webp for optimization
            $encoded = $image->toWebp(75);
            $mime = 'image/webp';
            
            // Save to cache
            Cache::put($cacheKey, [
                'content' => (string) $encoded,
                'mime' => $mime
            ], now()->addDays(7)); // Cache for 7 days
            
            return response((string) $encoded)
                ->header('Content-Type', $mime)
                ->header('Cache-Control', 'public, max-age=604800'); // 7 days

        } catch (\Exception $e) {
            // Fallback to redirecting to original url if error occurs
            return redirect($url);
        }
    }
}
