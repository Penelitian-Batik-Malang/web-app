@extends('layouts.layout')

@section('title', 'Galeri Batik Malang')

@section('content')
<div class="space-y-8">
    {{-- Galeri Header & Filter --}}
    <div class="flex justify-center items-center gap-6 flex-col border-b border-gray-800 pb-8">
        <span class="text-md lg:text-lg font-medium text-gray-400 max-w-3xl text-center">
            Selamat datang di Ruang Pamer Batik Malang. Di sini Anda dapat mengeksplorasi beraneka rupa corak yang memiliki sejarah dan keunikannya masing-masing.
        </span>
        
        <div class="flex items-center gap-4 w-full justify-center mt-4">
            {{-- Filter Sederhana Berbasis Query String --}}
            <form action="{{ route('galeri') }}" method="GET" class="flex gap-2">
                <a href="{{ route('galeri') }}" class="px-5 py-2.5 rounded-xl border border-gray-600 text-sm font-medium {{ !request('tipe') ? 'bg-primary text-black' : 'text-gray-300 hover:bg-gray-800' }} transition-colors">Semua Seni</a>
                <button type="submit" name="tipe" value="tulis" class="px-5 py-2.5 rounded-xl border border-gray-600 text-sm font-medium {{ request('tipe') === 'tulis' ? 'bg-primary text-black' : 'text-gray-300 hover:bg-gray-800' }} transition-colors">Koleksi Tulis</button>
                <button type="submit" name="tipe" value="cap" class="px-5 py-2.5 rounded-xl border border-gray-600 text-sm font-medium {{ request('tipe') === 'cap' ? 'bg-primary text-black' : 'text-gray-300 hover:bg-gray-800' }} transition-colors">Koleksi Cap</button>
            </form>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($batiks as $batik)
            @php
                $imageUrl = $batik->mainImage ? Storage::url($batik->mainImage->image_path) : 'https://placehold.co/600x400/1f2937/a8a29e?text=Visual+Terkunci';
            @endphp
            <x-card
                title="{{ $batik->name }}"
                description="{{ mb_strimwidth($batik->description, 0, 80, '...') }}"
                image="{{ $imageUrl }}"
                href="{{ route('galeri.show', $batik->id) }}"
            />
        @empty
            <div class="col-span-full py-20 text-center flex flex-col items-center">
                <i class="bi bi-images text-5xl text-gray-700 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-400">Belum Ada Karya Visual</h3>
                <p class="text-gray-500 mt-2">Tidak ada koleksi batik dengan filter ini. Cobalah di lain kesempatan.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
