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

            ['key' => 'gallery_item_1_img', 'value' => 'https://is3.cloudhost.id/batik-signature-gdrive/Acha%20Mahakala/IMG_8750.jpg', 'type' => 'image'],
            ['key' => 'gallery_item_1_title', 'value' => 'Acha Mahakala', 'type' => 'text'],
            ['key' => 'gallery_item_1_desc', 'value' => 'Motif batik khas yang merepresentasikan karakter visual galeri utama.', 'type' => 'text'],

            ['key' => 'gallery_item_2_img', 'value' => 'https://is3.cloudhost.id/batik-signature-gdrive/Adi%20Luhung%20Butterfly/2025_01_30_12_15_IMG_1678.jpg', 'type' => 'image'],
            ['key' => 'gallery_item_2_title', 'value' => 'Adi Luhung Butterfly', 'type' => 'text'],
            ['key' => 'gallery_item_2_desc', 'value' => 'Salah satu motif yang sudah disiapkan di seed data batik.', 'type' => 'text'],

            ['key' => 'gallery_item_3_img', 'value' => 'https://is3.cloudhost.id/batik-signature-gdrive/Adi%20Luhung%20Jarit/2025_01_30_13_25_IMG_1839.jpg', 'type' => 'image'],
            ['key' => 'gallery_item_3_title', 'value' => 'Adi Luhung Jarit', 'type' => 'text'],
            ['key' => 'gallery_item_3_desc', 'value' => 'Motif lain dari kumpulan batik yang diambil langsung dari S3.', 'type' => 'text'],
        ];

        foreach ($contents as $content) {
            LandingContent::updateOrCreate(
                ['key' => $content['key']],
                ['value' => $content['value'], 'type' => $content['type']]
            );
        }
    }
}
