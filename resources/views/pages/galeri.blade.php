@extends('layouts.layout')

@section('title', 'Galeri Batik')

@section('content')
<div class="space-y-6">
    {{-- Galeri Header --}}
    <div class="flex justify-center items-center gap-8 flex-col">
        <span class="text-md lg:text-lg font-medium text-gray-400 max-w-3xl text-center">Selamat datang di galeri Batik Malang. Di sini Anda dapat menjelajahi beragam motif 
batik Malang, masing-masing dengan filosofi dan sejarahnya yang unik.</span>
            {{-- Button Search --}}
            <div class="flex items-center gap-4 w-full justify-center">
                <div class="relative w-full max-w-md px-6 py-4">
                    <input
                        type="text"
                        placeholder="Cari motif (misal: Balai Kota, Teratai)..."
                        class="w-full px-4 py-3 pr-10 rounded-lg bg-gray-800 text-gray-200 focus:outline-none focus:ring-2 focus:ring-amber-500 transition duration-200"
                    >
                    <svg class="absolute right-8 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            {{-- Main Content --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $batiks = [
                ['title' => 'Batik Malang Teratai', 'description' => 'Motif bunga teratai yang melambangkan kemurnian dan keindahan.', 'href' => '/galeri/2', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Mahkota', 'description' => 'Motif mahkota yang mencerminkan kebanggaan dan kehormatan.', 'href' => '/galeri/3', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Gunung Semeru', 'description' => 'Terinspirasi dari keindahan Gunung Semeru yang megah.', 'href' => '/galeri/4', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Apel', 'description' => 'Motif apel yang melambangkan kelezatan buah khas Malang.', 'href' => '/galeri/5', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Burung Elang', 'description' => 'Motif elang yang melambangkan kekuatan dan keberanian.', 'href' => '/galeri/6', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Pelangi', 'description' => 'Motif pelangi yang melambangkan keberagaman dan harapan.', 'href' => '/galeri/7', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Batu Permata', 'description' => 'Motif batu mulia yang mencerminkan kemewahan dan prestige.', 'href' => '/galeri/8', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Bulan Bintang', 'description' => 'Motif malam yang mencerminkan ketenangan dan mistis.', 'href' => '/galeri/9', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Keris', 'description' => 'Motif keris tradisional yang menceritakan warisan budaya.', 'href' => '/galeri/10', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
                ['title' => 'Batik Malang Ombak Laut', 'description' => 'Motif ombak yang melambangkan kekuatan dan kedamaian alam.', 'href' => '/galeri/11', 'image' => 'https://static.vecteezy.com/system/resources/previews/029/090/148/original/traditional-indonesian-batik-kawung-motif-batik-design-free-vector.jpg'],
            ];
        @endphp
        @foreach($batiks as $batik)
            <x-card
                title="{{ $batik['title'] }}"
                description="{{ $batik['description'] }}"
                image="{{ $batik['image'] }}"
                href="{{ $batik['href'] }}"
            />
        @endforeach
    </div>
    </div>
</div>
@endsection
