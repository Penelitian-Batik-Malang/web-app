<?php

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class DeteksiJenisController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.deteksi-jenis');
    }

    public function detect(Request $request)
    {
        $path = $this->endpoints['jenis'] ?? '/tulis/scan';
        return $this->handleImageDetection($request, $path);
    }
}
