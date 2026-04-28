<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Batik;
use App\Models\BatikImage;

class BatikSeeder extends Seeder
{
    public function run(): void
    {
        
        // Clear existing batiks and images
        \App\Models\BatikImage::query()->delete();
        \App\Models\Batik::query()->delete();


        // Dummy batik data with placeholder image URLs
        $batikData = [
            [
                'name' => 'Kawung',
                'description' => 'Motif tradisional dengan pola lingkaran yang saling bersentuhan',
                'type' => 'tulis',
                'is_active' => true,
                'image' => 'https://via.placeholder.com/300x300/2d5016/ffffff?text=Kawung',
            ],
            [
                'name' => 'Parang Kusuma',
                'description' => 'Motif diagonal dengan bentuk seperti pisau yang melambangkan kekuatan',
                'type' => 'cap',
                'is_active' => true,
                'image' => 'https://via.placeholder.com/300x300/1a472a/ffffff?text=Parang',
            ],
            [
                'name' => 'Mega Mendung',
                'description' => 'Motif awan yang meriah dengan warna biru kehijauan khas Cirebon',
                'type' => 'tulis',
                'is_active' => true,
                'image' => 'https://via.placeholder.com/300x300/1e3a8a/ffffff?text=Mega',
            ],
            [
                'name' => 'Sido Mukti',
                'description' => 'Motif kesuksesan dengan pola yang kompleks dan elegan',
                'type' => 'cap',
                'is_active' => true,
                'image' => 'https://via.placeholder.com/300x300/7c2d12/ffffff?text=Sido',
            ],
            [
                'name' => 'Banji',
                'description' => 'Motif berulang dengan garis-garis geometris yang teratur',
                'type' => 'tulis',
                'is_active' => true,
                'image' => 'https://via.placeholder.com/300x300/4b5563/ffffff?text=Banji',
            ],
            [
                'name' => 'Sekar Jagad',
                'description' => 'Motif bunga yang melambangkan keindahan alam semesta',
                'type' => 'cap',
                'is_active' => true,
                'image' => 'https://via.placeholder.com/300x300/92400e/ffffff?text=Sekar',
            ],
        ];

        foreach ($batikData as $data) {
            $image = $data['image'];
            unset($data['image']);
            
            $batik = Batik::create($data);
            
            // Buat dummy image dengan URL placeholder
            BatikImage::create([
                'batik_id' => $batik->id,
                'image_path' => $image,
                'is_main' => true,
            ]);
        }
    }
}
