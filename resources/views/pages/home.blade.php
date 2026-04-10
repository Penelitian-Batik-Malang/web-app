@extends('layouts.layout')

@section('title', 'Beranda')

@section('content')
<!-- Hero Section -->
<div class="relative w-full h-[80vh] flex items-center justify-center -mt-8 rounded-b-[3rem] overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $content['hero_bg'] ?? '' }}');"></div>
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/70 bg-gradient-to-b from-transparent to-dark/90 text-center flex flex-col justify-center items-center">
        <!-- Hero Box -->
        <div class="relative z-10 w-full max-w-4xl px-6 pt-16">
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight font-playfair drop-shadow-lg">
                {{ $content['hero_title'] ?? 'Batik Malang,' }}
                <br>
                <span class="text-primary">{{ $content['hero_highlight'] ?? 'Cerdas & Lestari' }}</span>
            </h1>
            <p class="text-gray-300 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed mt-8 font-light tracking-wide bg-black/40 p-4 rounded-xl backdrop-blur-md border border-white/10">
                {{ $content['hero_subtitle'] ?? '' }}
            </p>
        </div>
    </div>
</div>

<!-- Tradisi & Teknologi Section -->
<div class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <!-- Text Left -->
        <div class="space-y-6">
            <h2 class="text-3xl md:text-5xl font-bold text-white leading-tight font-playfair tracking-wide">
                {{ str_replace('&', ' & ', $content['about_title'] ?? '') }}
            </h2>
            <div class="pt-4">
                <p class="text-gray-400 text-base md:text-lg leading-relaxed mb-4">
                    {{ $content['about_desc1'] ?? '' }}
                </p>
                <p class="text-gray-400 text-base md:text-lg leading-relaxed">
                    {{ $content['about_desc2'] ?? '' }}
                </p>
            </div>
        </div>

        <!-- 3 Langkah Mudah Right -->
        <div class="bg-gray-900 border border-gray-800 rounded-3xl p-8 lg:p-10 shadow-2xl">
            <h3 class="text-2xl font-bold text-primary mb-8 border-b border-gray-800 pb-4 inline-block">3 Langkah Mudah</h3>
            
            <div class="space-y-4">
                <!-- Step 1 -->
                <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-800/50 transition-colors">
                    <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-gray-800 text-primary font-bold text-lg border border-gray-700 shadow-inner">
                        1
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-white">{{ $content['step_1_title'] ?? '' }}</h4>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $content['step_1_desc'] ?? '' }}</p>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-800/50 transition-colors">
                    <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-gray-800 text-primary font-bold text-lg border border-gray-700 shadow-inner">
                        2
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-white">{{ $content['step_2_title'] ?? '' }}</h4>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $content['step_2_desc'] ?? '' }}</p>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-800/50 transition-colors">
                    <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-gray-800 text-primary font-bold text-lg border border-gray-700 shadow-inner">
                        3
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-white">{{ $content['step_3_title'] ?? '' }}</h4>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $content['step_3_desc'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Galeri Inspirasi Motif Section -->
<div class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center pb-24">
    <h2 class="text-4xl font-bold text-white mb-4 font-playfair tracking-wide">{{ $content['gallery_title'] ?? '' }}</h2>
    <p class="text-gray-400 max-w-2xl mx-auto mb-16 text-lg">{{ $content['gallery_subtitle'] ?? '' }}</p>

    <!-- 3 Card Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 h-auto md:h-[450px]">
        <!-- Card 1 -->
        <div class="relative rounded-3xl overflow-hidden group h-64 md:h-full lg:col-span-1 border border-white/10 shadow-xl">
            <img src="{{ $content['gallery_item_1_img'] ?? '' }}" alt="Mofif 1" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110">
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent flex flex-col justify-end p-8 text-left">
                <h3 class="text-2xl font-bold text-white mb-2 font-playfair">{{ $content['gallery_item_1_title'] ?? '' }}</h3>
                <p class="text-sm text-gray-300 leading-relaxed">{{ $content['gallery_item_1_desc'] ?? '' }}</p>
            </div>
        </div>
        
        <!-- Card 2 -->
        <div class="relative rounded-3xl overflow-hidden group h-64 md:h-full lg:col-span-1 border border-white/10 shadow-xl">
            <img src="{{ $content['gallery_item_2_img'] ?? '' }}" alt="Mofif 2" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110">
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent flex flex-col justify-end p-8 text-left">
                <h3 class="text-2xl font-bold text-white mb-2 font-playfair">{{ $content['gallery_item_2_title'] ?? '' }}</h3>
                <p class="text-sm text-gray-300 leading-relaxed">{{ $content['gallery_item_2_desc'] ?? '' }}</p>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="relative rounded-3xl overflow-hidden group h-64 md:h-full lg:col-span-1 border border-white/10 shadow-xl">
            <img src="{{ $content['gallery_item_3_img'] ?? '' }}" alt="Mofif 3" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110">
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent flex flex-col justify-end p-8 text-left">
                <h3 class="text-2xl font-bold text-white mb-2 font-playfair">{{ $content['gallery_item_3_title'] ?? '' }}</h3>
                <p class="text-sm text-gray-300 leading-relaxed">{{ $content['gallery_item_3_desc'] ?? '' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-16">
        <a href="{{ route('galeri') }}" class="inline-block border-2 border-gray-700 hover:border-primary bg-gray-900/50 text-gray-300 hover:text-white px-10 py-4 rounded-xl font-bold transition-all shadow-lg hover:shadow-primary/20">
            Jelajahi Semua Motif
        </a>
    </div>
</div>

@endsection
