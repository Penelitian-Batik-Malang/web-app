@extends('layouts.layout')

@section('title', 'Fitur')

@section('content')
<div class="space-y-12">

    @php 
        $user = auth()->user();
        $isGuest = !auth()->check();
    @endphp

    {{-- DETEKSI & ANALISIS Section --}}
    @if($isGuest || $user->hasMenuAccess('deteksi-motif') || $user->hasMenuAccess('deteksi-jenis'))
    <div class="space-y-6 border-b border-secondary pb-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Hero Section --}}
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl md:text-3xl font-bold text-primary tracking-wider">Deteksi & Analisis</h1>
                <p class="text-gray-400 text-sm max-w-md">Jelajahi fitur deteksi motif dan jenis batik Malang secara otomatis menggunakan teknologi AI canggih.</p>
            </div>
            
            {{-- Deteksi Motif Batik --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('deteksi-motif'))
            <x-card-features 
                title="Deteksi Motif Batik" 
                description="Identifikasi motif batik Malang dari gambar secara otomatis"
                icon="bi-image"
                iconBgColor="bg-amber-500/10"
                iconTextColor="text-amber-500"
                :url="route('deteksi.motif')"
            />
            @endif

            {{-- Deteksi Jenis Batik --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('deteksi-jenis'))
            <x-card-features 
                title="Deteksi Jenis Batik" 
                description="Klasifikasi jenis batik (tulis/cap) dari kain secara otomatis"
                icon="bi-file-text"
                iconBgColor="bg-blue-500/10"
                iconTextColor="text-blue-500"
                :url="route('deteksi.jenis')"
            />
            @endif
        </div>
    </div>
    @endif

    {{-- PENCARIAN BATIK Section --}}
    @if($isGuest || $user->hasMenuAccess('pencarian-batik') || $user->hasMenuAccess('pencarian-warna') || $user->hasMenuAccess('rekomendasi-batik'))
    <div class="space-y-6 border-b border-secondary pb-10">
        <h2 class="text-xl md:text-2xl font-bold text-secondary uppercase tracking-wider">Pencarian Batik</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Pencarian Umum --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('pencarian-batik'))
            <x-card-features
                title="Pencarian Umum"
                description="Cari batik serupa menggunakan fitur visual global dari gambar"
                icon="bi-search"
                iconBgColor="bg-orange-500/10"
                iconTextColor="text-orange-500"
                :url="route('pencarian.batik')"
            />
            @endif

            {{-- Pencarian by Warna Dominan --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('pencarian-warna'))
            <x-card-features
                title="Pencarian by Warna Dominan"
                description="Temukan batik berdasarkan warna dominan pada kain"
                icon="bi-palette"
                iconBgColor="bg-amber-500/10"
                iconTextColor="text-amber-500"
                :url="route('pencarian.warna')"
            />
            @endif

            {{-- Rekomendasi by Fashion --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('rekomendasi-batik'))
            <x-card-features 
                title="Rekomendasi by Fashion" 
                description="Rekomendasi batik dari warna dominan citra fashion + terapkan langsung"
                icon="bi-stars"
                iconBgColor="bg-cyan-500/10"
                iconTextColor="text-cyan-500"
                :url="route('rekomendasi.batik')"
            />
            @endif
        </div>
    </div>
    @endif

    {{-- KREASI & GENERASI Section --}}
    @if($isGuest || $user->hasMenuAccess('pewarnaan-palet') || $user->hasMenuAccess('pewarnaan-prompt') || $user->hasMenuAccess('terapkan-batik') || $user->hasMenuAccess('text-to-image'))
    <div class="space-y-6 pb-10">
        <h2 class="text-xl md:text-2xl font-bold text-secondary uppercase tracking-wider">Kreasi & Generasi</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Pewarnaan by Palet Warna --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('pewarnaan-palet'))
            <x-card-features
                title="Pewarnaan by Palet Warna"
                description="Ubah warna kain batik menggunakan palet warna pilihan"
                icon="bi-palette2"
                iconBgColor="bg-amber-500/10"
                iconTextColor="text-amber-500"
                :url="route('pewarnaan.palet')"
            />
            @endif

            {{-- Pewarnaan by Prompt --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('pewarnaan-prompt'))
            <x-card-features
                title="Pewarnaan by Prompt"
                description="Beri instruksi teks untuk mewarnai ulang motif batik secara AI"
                icon="bi-chat-dots"
                iconBgColor="bg-gray-500/10"
                iconTextColor="text-gray-400"
                :url="route('pewarnaan.prompt')"
            />
            @endif

            {{-- Terapkan Batik --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('terapkan-batik'))
            <x-card-features 
                title="Terapkan Batik" 
                description="Terapkan motif batik galeri ke citra pakaian fashion Anda"
                icon="bi-puzzle"
                iconBgColor="bg-amber-500/10"
                iconTextColor="text-amber-500"
                :url="route('terapkan.batik')"
            />
            @endif

            {{-- Text to Image Batik --}}
            @if(!auth()->check() || auth()->user()->hasMenuAccess('text-to-image'))
            <x-card-features
                title="Text to Image Batik"
                description="Generate motif batik Malang baru dari deskripsi teks"
                icon="bi-lightning-charge"
                iconBgColor="bg-amber-500/10"
                iconTextColor="text-amber-500"
                badge="AI"
                :url="route('text-to-image')"
            />
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
