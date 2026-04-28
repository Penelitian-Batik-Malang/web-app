<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

Config::set('filesystems.disks.s3-ai-results.bucket', 'galeri-batik-digital');
$disk = Storage::disk('s3-ai-results');

$path = 'original/Teratai/Copy of Copy of batik_tulis_ (253).JPG';
$try1 = $path;
$try2 = 'augmentasi/augmentasi/' . $path;

echo "Checking: $try1\n";
echo "  Exists: " . ($disk->exists($try1) ? "YES" : "NO") . "\n";

echo "Checking: $try2\n";
echo "  Exists: " . ($disk->exists($try2) ? "YES" : "NO") . "\n";
