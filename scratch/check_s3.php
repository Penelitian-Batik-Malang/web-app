<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "--- Signature Bucket: Motif Batik Singa ---\n";
try {
    $files = Storage::disk('s3-batik')->files('Motif Batik Singa');
    echo "Files: " . count($files) . "\n";
    print_r(array_slice($files, 0, 10));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "--- Color Bucket: hijau ---\n";
try {
    $files = Storage::disk('s3-color-dominant')->files('hijau');
    echo "Files: " . count($files) . "\n";
    print_r(array_slice($files, 0, 10));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
