<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LandingContent;
use Illuminate\Support\Facades\Storage;

class LandingContentController extends Controller
{
    public function index()
    {
        $contents = LandingContent::orderBy('id')->get();
        return view('admin.landing-contents.index', compact('contents'));
    }

    public function update(Request $request)
    {
        // Loop through all existing keys so we don't depend solely on what was submitted
        $contents = LandingContent::all();
        
        foreach ($contents as $item) {
            $key = $item->key;
            
            // Check if file is uploaded for image types
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $path = $file->store('landing', 'public');
                $item->value = asset('storage/' . $path);
                $item->save();
            } 
            // Else update if text exists
            elseif ($request->has($key)) {
                $item->value = $request->input($key);
                $item->save();
            }
        }

        return redirect()->back()->with('success', 'Konten Global Landing berhasil diperbarui!');
    }
}
