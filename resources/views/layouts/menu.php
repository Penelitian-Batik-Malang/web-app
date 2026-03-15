<?php

require_once app_path('Types/menu.type.php');

$menu = [
    new MenuItem('Dashboard', '/', 'bi-house-door'),
    new MenuItem('Galeri', '/galeri', 'bi-image'),
    new MenuItem('Jelajahi Fitur', '/fitur', 'bi-columns-gap'),
];