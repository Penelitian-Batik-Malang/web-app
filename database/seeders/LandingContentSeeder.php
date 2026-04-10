<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingContent;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            ['key' => 'logo_icon', 'value' => '', 'type' => 'image'],
            ['key' => 'hero_title', 'value' => 'Batik Malang,', 'type' => 'text'],
            ['key' => 'hero_highlight', 'value' => 'Cerdas & Lestari', 'type' => 'text'],
            ['key' => 'hero_subtitle', 'value' => 'Menggabungkan kekayaan budaya Batik Malang dengan teknologi AI untuk pengalaman eksplorasi yang lebih cerdas dan interaktif.', 'type' => 'text'],
            ['key' => 'hero_bg', 'value' => 'https://t3.ftcdn.net/jpg/02/64/50/58/360_F_264505804_DHKnvdaT3YtdJteynvJyKCxjxwWNE1EI.jpg', 'type' => 'image'],
            
            ['key' => 'about_title', 'value' => 'Menghubungkan Tradisi & Teknologi', 'type' => 'text'],
            ['key' => 'about_desc1', 'value' => 'BatikMalang.ai lahir dari kecintaan terhadap warisan budaya batik dan visi untuk membuatnya relevan bagi generasi modern. Kami percaya setiap orang memiliki motif batik yang merepresentasikan karakter mereka.', 'type' => 'text'],
            ['key' => 'about_desc2', 'value' => 'Dengan kecerdasan buatan, kami memudahkan Anda menjelajahi kekayaan motif batik khas Malang yang otentik serta lebih personal dan bermakna.', 'type' => 'text'],
            
            ['key' => 'step_1_title', 'value' => 'Pindai atau Unggah', 'type' => 'text'],
            ['key' => 'step_1_desc', 'value' => 'Ambil foto gaya berpakaian Anda saat ini atau pilih dari galeri.', 'type' => 'text'],
            ['key' => 'step_2_title', 'value' => 'Analisis oleh AI', 'type' => 'text'],
            ['key' => 'step_2_desc', 'value' => 'Sistem cerdas kami akan menganalisis warna, pola, dan gaya Anda.', 'type' => 'text'],
            ['key' => 'step_3_title', 'value' => 'Dapatkan Rekomendasi', 'type' => 'text'],
            ['key' => 'step_3_desc', 'value' => 'Terima rekomendasi motif batik yang paling sesuai dengan kepribadian Anda.', 'type' => 'text'],
            
            ['key' => 'gallery_title', 'value' => 'Galeri Inspirasi Motif', 'type' => 'text'],
            ['key' => 'gallery_subtitle', 'value' => 'Jelajahi keindahan dan makna di balik beberapa motif batik khas Malang.', 'type' => 'text'],

            ['key' => 'gallery_item_1_img', 'value' => 'https://images.unsplash.com/photo-1596766467389-0d29ab6cda23?q=80&w=800&auto=format&fit=crop', 'type' => 'image'],
            ['key' => 'gallery_item_1_title', 'value' => 'Sido Mukti', 'type' => 'text'],
            ['key' => 'gallery_item_1_desc', 'value' => 'Melambangkan harapan akan kemuliaan dan kesejahteraan.', 'type' => 'text'],

            ['key' => 'gallery_item_2_img', 'value' => 'https://images.unsplash.com/photo-1626027150117-640a4cf0f235?q=80&w=800&auto=format&fit=crop', 'type' => 'image'],
            ['key' => 'gallery_item_2_title', 'value' => 'Parang', 'type' => 'text'],
            ['key' => 'gallery_item_2_desc', 'value' => 'Simbol keberanian dan kekuatan yang berkelanjutan.', 'type' => 'text'],

            ['key' => 'gallery_item_3_img', 'value' => 'https://images.unsplash.com/photo-1588693809628-89c0b11fbab5?q=80&w=800&auto=format&fit=crop', 'type' => 'image'],
            ['key' => 'gallery_item_3_title', 'value' => 'Kawung', 'type' => 'text'],
            ['key' => 'gallery_item_3_desc', 'value' => 'Mencerminkan kebijaksanaan dan keseimbangan batin.', 'type' => 'text'],
        ];

        foreach ($contents as $content) {
            LandingContent::updateOrCreate(
                ['key' => $content['key']],
                ['value' => $content['value'], 'type' => $content['type']]
            );
        }
    }
}
