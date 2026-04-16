@extends('layouts.layout')

@section('title', 'Proses Pewarnaan Pallet')

@section('content')
<div class="flex items-center justify-center min-h-screen py-8 px-4">
    {{-- Modal Container --}}
    <div class="bg-gray-900 border border-gray-700 rounded-3xl shadow-2xl w-full max-w-4xl relative">
        

        {{-- Header --}}
        <div class="text-center pt-10 pb-6 px-8 border-b border-gray-800">
            <h2 class="text-3xl font-bold text-white font-playfair mb-2">Pewarnaan Pallet Batik</h2>
            <p class="text-gray-400 text-sm">Preview gambar batik dengan pallet warna yang Anda pilih</p>
        </div>

        {{-- Body --}}
        <div class="p-8">
            <div class="space-y-8">
                
                {{-- Content Container --}}
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    
                    {{-- Left: Gambar Batik --}}
                    <div class="flex-1 space-y-4">
                        <h3 class="text-center text-white font-semibold">Gambar Batikmu</h3>
                        
                        <div class="rounded-2xl overflow-hidden border border-amber-700/50 bg-black/50">
                            @if($batik && $batik->mainImage)
                                @if(filter_var($batik->mainImage->image_path, FILTER_VALIDATE_URL))
                                    <img src="{{ $batik->mainImage->image_path }}" 
                                         alt="{{ $batik->name }}" 
                                         class="w-full h-auto object-cover">
                                @else
                                    <img src="{{ Storage::url($batik->mainImage->image_path) }}" 
                                         alt="{{ $batik->name }}" 
                                         class="w-full h-auto object-cover">
                                @endif
                            @else
                                <div class="w-full h-80 flex items-center justify-center bg-gray-800 text-gray-600">
                                    <i class="bi bi-image text-5xl"></i>
                                </div>
                            @endif
                        </div>
                        
                        @if($batik)
                            <div class="bg-gray-800/50 rounded-xl p-4">
                                <p class="text-amber-500 font-bold text-sm mb-1">{{ $batik->name }}</p>
                                <p class="text-gray-400 text-xs">{{ $batik->description ?? 'Deskripsi tidak tersedia' }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Right: Pallet Warna --}}
                    <div class="flex-1 space-y-4">
                        <h3 class="text-center text-white font-semibold">Pallet Warnamu</h3>
                        
                        @if($colorImage)
                            <div class="rounded-2xl overflow-hidden border border-amber-700/50 bg-black/50">
                                <img src="{{ $colorImage }}" 
                                     alt="Color palette" 
                                     class="w-full h-auto object-cover">
                            </div>
                        @else
                            <div class="w-full h-80 flex items-center justify-center bg-gray-800 rounded-2xl border border-gray-700">
                                <div class="text-center">
                                    <i class="bi bi-palette text-5xl text-gray-600 mb-3 block"></i>
                                    <p class="text-gray-500 text-sm">Tidak ada pallet warna</p>
                                </div>
                            </div>
                        @endif
                        
                        @if($colorImage)
                            <div class="bg-gray-800/50 rounded-xl p-4 text-center">
                                <p class="text-amber-500 text-xs">Sumber warna dari upload Anda</p>
                            </div>
                        @endif
                    </div>

                </div>

            </div>
        </div>

        {{-- Footer --}}
        <div class="border-t border-gray-800 px-8 py-6">
            <p class="text-center text-white font-semibold text-sm mb-4">Ingin coba lagi?</p>
            <div class="flex justify-center gap-4">
                <form action="{{ route('pewarnaan.palet.proses') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="batik_id" value="{{ $batik->id ?? '' }}">
                    <button
                        type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-amber-700/20"
                    >
                        Proses Gambar
                    </button>
                </form>
                
                <a href="{{ route('pewarnaan.palet') }}" class="border border-gray-700 bg-gray-800/50 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-xl transition-all">
                    Reset
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
