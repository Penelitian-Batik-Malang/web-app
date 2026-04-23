<?php

namespace App\Http\Controllers\Features;

use Illuminate\Http\Request;

class DeteksiMotifController extends BaseMLController
{
    public function show()
    {
        return view('pages.features.deteksi-motif');
    }

    public function detect(Request $request)
    {
        $path = $this->endpoints['motif'] ?? '/motif/scan';
        return $this->handleImageDetection($request, $path);
    }
}
