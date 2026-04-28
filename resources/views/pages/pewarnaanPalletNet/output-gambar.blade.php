@extends('layouts.layout')

@section('title', 'Hasil Pewarnaan Pallet')

@section('content')
<div class="flex items-center justify-center min-h-screen py-8 px-4">
    <div class="bg-gray-900 border border-gray-700 rounded-3xl shadow-2xl w-full max-w-6xl relative">
        
        {{-- Header --}}
        <div class="text-center pt-10 pb-6 px-8 border-b border-gray-800">
            <h2 class="text-3xl font-bold text-white font-playfair mb-2">Hasil Pewarnaan Batik</h2>
            <p class="text-gray-400 text-sm">Perbandingan hasil pewarnaan menggunakan 3 metode berbeda</p>
        </div>

        {{-- Body --}}
        <div class="p-8">
            <div class="space-y-8">
                
                {{-- Original Images Row --}}
                <div class="flex flex-col lg:flex-row gap-8 items-start pb-8 border-b border-gray-800">
                    
                    {{-- Left: Gambar Batik Original --}}
                    <div class="flex-1 space-y-4">
                        <h3 class="text-center text-white font-semibold">Gambar Batik Original</h3>
                        <div class="rounded-2xl overflow-hidden border border-amber-700/50 bg-black/50">
                            @if($batikImage)
                                <img src="{{ $batikImage }}" 
                                     alt="Gambar Batik" 
                                     class="w-full h-auto object-cover max-h-96">
                            @else
                                <div class="w-full h-80 flex items-center justify-center bg-gray-800 text-gray-600">
                                    <i class="bi bi-image text-5xl"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Right: Pallet Warna --}}
                    <div class="flex-1 space-y-4">
                        <h3 class="text-center text-white font-semibold">Pallet Warna</h3>
                        @if($colorImage)
                            <div class="rounded-2xl overflow-hidden border border-amber-700/50 bg-black/50">
                                <img src="{{ $colorImage }}" 
                                     alt="Color palette" 
                                     class="w-full h-auto object-cover max-h-96">
                            </div>
                        @else
                            <div class="w-full h-80 flex items-center justify-center bg-gray-800 rounded-2xl border border-gray-700">
                                <i class="bi bi-palette text-5xl text-gray-600"></i>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 3 Results Grid --}}
                <div>
                    <h3 class="text-center text-white font-semibold mb-6">Hasil Pewarnaan (3 Metode)</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {{-- Result 1: KMeans --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">📊 KMeans</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-blue-500/50 bg-black/50">
                                @if($results['kmeans'] ?? null)
                                    <img 
                                        src="{{ $results['kmeans']['image_url'] }}" 
                                        alt="Hasil KMeans" 
                                        class="w-full h-auto object-cover"
                                    >
                                @else
                                    <div class="w-full h-40 flex items-center justify-center bg-gray-800">
                                        <i class="bi bi-exclamation-triangle text-red-500 text-xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300 space-y-1">
                                <p>Time: <span class="text-amber-400">{{ $results['kmeans']['processing_time_ms'] ?? '-' }}ms</span></p>
                                <button 
                                    onclick="downloadImage('{{ $results['kmeans']['image_url'] ?? '' }}', 'kmeans')"
                                    class="w-full mt-2 bg-blue-700 hover:bg-blue-600 text-white text-xs py-1 px-2 rounded transition-all"
                                >
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </div>
                        </div>
                        
                        {{-- Result 2: Histogram --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">📈 Histogram</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-green-500/50 bg-black/50">
                                @if($results['histogram'] ?? null)
                                    <img 
                                        src="{{ $results['histogram']['image_url'] }}" 
                                        alt="Hasil Histogram" 
                                        class="w-full h-auto object-cover"
                                    >
                                @else
                                    <div class="w-full h-40 flex items-center justify-center bg-gray-800">
                                        <i class="bi bi-exclamation-triangle text-red-500 text-xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300 space-y-1">
                                <p>Time: <span class="text-amber-400">{{ $results['histogram']['processing_time_ms'] ?? '-' }}ms</span></p>
                                <button 
                                    onclick="downloadImage('{{ $results['histogram']['image_url'] ?? '' }}', 'histogram')"
                                    class="w-full mt-2 bg-green-700 hover:bg-green-600 text-white text-xs py-1 px-2 rounded transition-all"
                                >
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </div>
                        </div>
                        
                        {{-- Result 3: Median Cut --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">🎨 Median Cut</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-purple-500/50 bg-black/50">
                                @if($results['median'] ?? null)
                                    <img 
                                        src="{{ $results['median']['image_url'] }}" 
                                        alt="Hasil Median Cut" 
                                        class="w-full h-auto object-cover"
                                    >
                                @else
                                    <div class="w-full h-40 flex items-center justify-center bg-gray-800">
                                        <i class="bi bi-exclamation-triangle text-red-500 text-xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300 space-y-1">
                                <p>Time: <span class="text-amber-400">{{ $results['median']['processing_time_ms'] ?? '-' }}ms</span></p>
                                <button 
                                    onclick="downloadImage('{{ $results['median']['image_url'] ?? '' }}', 'median')"
                                    class="w-full mt-2 bg-purple-700 hover:bg-purple-600 text-white text-xs py-1 px-2 rounded transition-all"
                                >
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="border-t border-gray-800 px-8 py-6">
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('pewarnaan.palet') }}" class="border border-gray-700 bg-gray-800/50 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-xl transition-all text-center">
                    Mulai Lagi
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function downloadImage(imageUrl, method) {
        if (!imageUrl) {
            alert(`URL gambar ${method} tidak tersedia`);
            return;
        }

        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = `batik-recolor-${method}-${new Date().getTime()}.jpg`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endsection
