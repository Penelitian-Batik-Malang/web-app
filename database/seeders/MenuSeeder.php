<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Role;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', 'Admin')->first();
        $user = Role::where('name', 'User')->first();

        $menus = [
            // User & Admin Menus
            ['name' => 'Menu Galeri Batik', 'code' => 'galeri-batik', 'roles' => [$admin, $user]],
            ['name' => 'Menu Deteksi Motif Batik', 'code' => 'deteksi-motif', 'roles' => [$admin, $user]],
            ['name' => 'Menu Deteksi Jenis Batik', 'code' => 'deteksi-jenis', 'roles' => [$admin, $user]],
            ['name' => 'Menu Pencarian Batik', 'code' => 'pencarian-batik', 'roles' => [$admin, $user]],
            ['name' => 'Menu Pencarian By Warna Dominan', 'code' => 'pencarian-warna', 'roles' => [$admin, $user]],
            ['name' => 'Menu Rekomendasi By Fashion', 'code' => 'rekomendasi-fashion', 'roles' => [$admin, $user]],
            ['name' => 'Menu Pewarnaan By Palet Warna', 'code' => 'pewarnaan-palet', 'roles' => [$admin, $user]],
            ['name' => 'Menu Pewarna By Prompt', 'code' => 'pewarnaan-prompt', 'roles' => [$admin, $user]],
            ['name' => 'Menu Terapkan Batik', 'code' => 'terapkan-batik', 'roles' => [$admin, $user]],
            ['name' => 'Menu Text To Image Batik', 'code' => 'text-to-image', 'roles' => [$admin, $user]],
            // Admin Only Menus
            ['name' => 'Menu Kelola User', 'code' => 'kelola-user', 'roles' => [$admin]],
            ['name' => 'Menu Kelola Role', 'code' => 'kelola-role', 'roles' => [$admin]],
            ['name' => 'Menu Kelola Galeri Batik', 'code' => 'kelola-galeri', 'roles' => [$admin]],
            ['name' => 'Menu Kelola Konten Global di Landing', 'code' => 'kelola-konten', 'roles' => [$admin]],
            ['name' => 'Menu Monitor Model AI', 'code' => 'monitor-ai', 'roles' => [$admin]],
        ];

        foreach ($menus as $m) {
            $menu = Menu::firstOrCreate(['code' => $m['code']], ['name' => $m['name']]);
            foreach ($m['roles'] as $role) {
                if ($role) {
                    $menu->roles()->syncWithoutDetaching([$role->id]);
                }
            }
        }
    }
}
