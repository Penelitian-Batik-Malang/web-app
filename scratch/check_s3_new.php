<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

// Temporarily override bucket to test
Config::set('filesystems.disks.s3-batik.bucket', 'galeri-batik-digital');

$disk = Storage::disk('s3-batik');

$testPaths = [
    'augmentasi/augmentasi/zoom/Arca Ganesa/IMG_8737.JPG',
    'zoom/Arca Ganesa/IMG_8737.JPG',
    'augmentasi/augmentasi/original/Arca Ganesa/IMG_8737.JPG'
];

foreach ($testPaths as $p) {
    echo "Checking: $p\n";
    try {
        $exists = $disk->exists($p);
        echo "  Exists: " . ($exists ? "YES" : "NO") . "\n";
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
