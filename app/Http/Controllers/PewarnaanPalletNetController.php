<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

/**
 * ⚠️ DEPRECATED - Class ini telah dipindahkan
 * 
 * Gunakan: App\Http\Controllers\Features\PewarnaanPaletController
 * File baru: app/Http/Controllers/Features/PewarnaanPaletController.php
 * 
 * Class lama ini disimpan untuk backward compatibility saja.
 * Semua routes sudah diupdate ke controller baru.
 */
class PewarnaanPalletNetController extends Controller
{
    public function __construct()
    {
        Log::warning('PewarnaanPalletNetController (deprecated) was instantiated. Use Features\PewarnaanPaletController instead.');
    }

    public function showPalet()
    {
        throw new \BadMethodCallException('Method moved to Features\PewarnaanPaletController::show()');
    }

    public function processPalette()
    {
        throw new \BadMethodCallException('Method moved to Features\PewarnaanPaletController::processPalette()');
    }

    public function colorize()
    {
        throw new \BadMethodCallException('Method moved to Features\PewarnaanPaletController::colorize()');
    }

    public function showOutput()
    {
        throw new \BadMethodCallException('Method moved to Features\PewarnaanPaletController::showOutput()');
    }

    public function saveResults()
    {
        throw new \BadMethodCallException('Method moved to Features\PewarnaanPaletController::saveResults()');
    }
}
