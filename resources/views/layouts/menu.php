<?php

require_once app_path('Types/menu.type.php');

$menu = [
    new MenuItem('Home', '/', 'bi-house-door'),
];

if (!auth()->check() || auth()->user()->hasMenuAccess('galeri-batik')) {
    $menu[] = new MenuItem('Galeri', '/galeri', 'bi-image');
}

$menu[] = new MenuItem('Jelajahi Fitur', '/fitur', 'bi-columns-gap');

if (auth()->check() && auth()->user()->hasAdminAccess()) {
    $menu[] = new MenuItem('Admin Dashboard', '/admin/dashboard', 'bi-speedometer2');
}