@extends('layouts.layout')

@section('title', 'Galeri Batik Malang')

@section('content')
<div class="space-y-8">
    {{-- Galeri Header & Filter --}}
    <div class="flex justify-center items-center gap-6 flex-col border-b border-gray-800 pb-8">
        <span class="text-md lg:text-lg font-medium text-gray-400 max-w-3xl text-center">
            Selamat datang di Galeri Batik Malang. Di sini Anda dapat mengeksplorasi beraneka motif batik Malang yang memiliki sejarah dan keunikannya masing-masing.
        </span>
        
        <form action="{{ route('galeri') }}" method="GET" class="flex flex-col sm:flex-row gap-3 w-full max-w-2xl">
            {{-- Search Input --}}
            <div class="relative flex-1">
                <input 
                    type="text" 
                    name="cari" 
                    value="{{ request('cari') }}"
                    placeholder="Cari nama batik..." 
                    class="w-full pl-10 pr-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all"
                >
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
            </div>
            {{-- Filter Tipe --}}
            <div class="flex gap-2">
                <a href="{{ route('galeri', ['cari' => request('cari')]) }}" 
                   class="px-4 py-3 rounded-xl border border-gray-600 text-sm font-medium whitespace-nowrap {{ !request('tipe') ? 'bg-primary text-black' : 'text-gray-300 hover:bg-gray-800' }} transition-colors">
                    Semua
                </a>
                <button type="submit" name="tipe" value="tulis" 
                    class="px-4 py-3 rounded-xl border border-gray-600 text-sm font-medium whitespace-nowrap {{ request('tipe') === 'tulis' ? 'bg-primary text-black' : 'text-gray-300 hover:bg-gray-800' }} transition-colors">
                    Tulis
                </button>
                <button type="submit" name="tipe" value="cap" 
                    class="px-4 py-3 rounded-xl border border-gray-600 text-sm font-medium whitespace-nowrap {{ request('tipe') === 'cap' ? 'bg-primary text-black' : 'text-gray-300 hover:bg-gray-800' }} transition-colors">
                    Cap
                </button>
            </div>
        </form>

        {{-- Info hasil pencarian --}}
        @if(request('cari'))
        <p class="text-gray-500 text-sm">
            Menampilkan hasil untuk: <span class="text-amber-500 font-medium">"{{ request('cari') }}"</span>
            — <a href="{{ route('galeri') }}" class="text-gray-400 hover:text-white underline">Reset</a>
        </p>
        @endif
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        @forelse($batiks as $batik)
            @php
                $imageUrl = $batik->mainImage ? $batik->mainImage->full_url : 'https://placehold.co/600x400/1f2937/a8a29e?text=Visual+Terkunci';
            @endphp
            <x-card
                title="{{ $batik->name }}"
                description="{{ mb_strimwidth($batik->description, 0, 80, '...') }}"
                image="{{ $imageUrl }}"
                href="{{ route('galeri.show', $batik->id) }}"
            />
        @empty
            <div class="col-span-full py-20 text-center flex flex-col items-center">
                <i class="bi bi-search text-5xl text-gray-700 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-400">Tidak Ada Hasil</h3>
                <p class="text-gray-500 mt-2">Tidak ada koleksi batik yang cocok dengan pencarian atau filter Anda.</p>
                <a href="{{ route('galeri') }}" class="mt-4 text-amber-500 hover:text-amber-400 underline text-sm">Tampilkan semua koleksi</a>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($batiks->hasPages())
        <div class="flex justify-center pt-6">
            {{ $batiks->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
