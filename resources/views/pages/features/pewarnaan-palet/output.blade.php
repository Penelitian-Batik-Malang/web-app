@extends('layouts.layout')

@section('title', 'Hasil Pewarnaan Pallet')

@section('content')
<!-- DEBUG: Session Images Status
  - Has Batik Image: {{ !empty($batikImage) ? 'YES (' . strlen($batikImage) . ' chars)' : 'NO' }}
  - Has Color Image: {{ !empty($colorImage) ? 'YES (' . strlen($colorImage) . ' chars)' : 'NO' }}
  - Results Count: {{ count($results ?? []) }}
  - Kmeans Result: {{ !empty($results['kmeans']['image_url'] ?? null) ? 'YES' : 'NO' }}
  - Histogram Result: {{ !empty($results['histogram']['image_url'] ?? null) ? 'YES' : 'NO' }}
  - Median Result: {{ !empty($results['median']['image_url'] ?? null) ? 'YES' : 'NO' }}
-->

<div class="flex items-center justify-center min-h-screen py-8 px-4">
    <div class="bg-gray-900 border border-gray-700 rounded-3xl shadow-2xl w-full max-w-6xl relative">
        
        {{-- Header --}}
        <div class="text-center pt-10 pb-6 px-8 border-b border-gray-800">
            <h2 class="text-3xl font-bold text-white font-playfair mb-2">Hasil Pewarnaan Batik</h2>
            @php
                $hasAllMethods = !empty($results['histogram']['image_url'] ?? null) || !empty($results['median']['image_url'] ?? null);
            @endphp
            <p class="text-gray-400 text-sm">{{ $hasAllMethods ? 'Perbandingan hasil pewarnaan menggunakan 3 metode berbeda' : 'Hasil pewarnaan dengan palet warna pilihan Anda' }}</p>
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
                            @if($batikImage && (strpos($batikImage, 'data:') === 0 || strpos($batikImage, 'http') === 0))
                                <img src="{{ $batikImage }}" 
                                     alt="Gambar Batik" 
                                     class="w-auto h-auto max-h-96"
                                     onerror="this.parentElement.innerHTML='<div class=\"w-full h-20 flex items-center justify-center bg-gray-800 text-gray-600\"><i class=\"bi bi-exclamation-triangle text-5xl\"></i></div>'">
                            @else
                                <div class="w-full h-80 flex items-center justify-center bg-gray-800 text-gray-600">
                                    <div class="text-center">
                                        <i class="bi bi-image text-5xl mb-2 block"></i>
                                        <p class="text-xs text-gray-500">Gambar tidak tersedia</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Results Section --}}
                <div>
                    @php
                        $hasAllMethods = !empty($results['histogram']['image_url'] ?? null) || !empty($results['median']['image_url'] ?? null);
                    @endphp
                    <h3 class="text-center text-white font-semibold mb-6">{{ $hasAllMethods ? 'Hasil Pewarnaan (3 Metode)' : 'Hasil Pewarnaan' }}</h3>
                    
                    <div class="{{ $hasAllMethods ? 'grid grid-cols-1 md:grid-cols-3 gap-6' : 'flex justify-center' }}">
                        
                        {{-- Result 1: KMeans or Custom Result --}}
                        <div class="{{ !($hasAllMethods ?? false) ? 'w-full max-w-md' : '' }} space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 {{ $hasAllMethods ? 'bg-blue-500' : 'bg-amber-500' }} rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">{{ $hasAllMethods ? '📊 KMeans' : '🎨 Hasil Anda' }}</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-blue-500/50 bg-black/50">
                                @if($results['kmeans']['image_url'] ?? null)
                                    <img 
                                        src="{{ $results['kmeans']['image_url'] }}" 
                                        alt="Hasil KMeans" 
                                        class="w-full h-auto object-cover max-h-64"
                                    >
                                @else
                                    <div class="w-full h-40 flex items-center justify-center bg-gray-800">
                                        <div class="text-center">
                                            <i class="bi bi-exclamation-triangle text-red-500 text-xl block mb-2"></i>
                                            <p class="text-red-400 text-xs">{{ $results['kmeans']['error'] ?? 'Gagal memproses' }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300 space-y-1">
                                <p>Time: <span class="text-amber-400">{{ $results['kmeans']['processing_time_ms'] ?? '-' }}ms</span></p>
                                <button 
                                    onclick="downloadImage('{{ $results['kmeans']['image_url'] ?? '' }}', 'custom-result')"
                                    class="w-full mt-2 {{ $hasAllMethods ? 'bg-blue-700 hover:bg-blue-600' : 'bg-amber-700 hover:bg-amber-600' }} text-white text-xs py-1 px-2 rounded transition-all {{ !($results['kmeans']['image_url'] ?? null) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ !($results['kmeans']['image_url'] ?? null) ? 'disabled' : '' }}
                                >
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </div>
                        </div>
                        
                        {{-- Result 2: Histogram --}}
                        @if($hasAllMethods ?? false)
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">📈 Histogram</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-green-500/50 bg-black/50">
                                @if($results['histogram']['image_url'] ?? null)
                                    <img 
                                        src="{{ $results['histogram']['image_url'] }}" 
                                        alt="Hasil Histogram" 
                                        class="w-full h-auto object-cover max-h-64"
                                    >
                                @else
                                    <div class="w-full h-40 flex items-center justify-center bg-gray-800">
                                        <div class="text-center">
                                            <i class="bi bi-exclamation-triangle text-red-500 text-xl block mb-2"></i>
                                            <p class="text-red-400 text-xs">{{ $results['histogram']['error'] ?? 'Gagal memproses' }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300 space-y-1">
                                <p>Time: <span class="text-amber-400">{{ $results['histogram']['processing_time_ms'] ?? '-' }}ms</span></p>
                                <button 
                                    onclick="downloadImage('{{ $results['histogram']['image_url'] ?? '' }}', 'histogram')"
                                    class="w-full mt-2 bg-green-700 hover:bg-green-600 text-white text-xs py-1 px-2 rounded transition-all {{ !($results['histogram']['image_url'] ?? null) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ !($results['histogram']['image_url'] ?? null) ? 'disabled' : '' }}
                                >
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </div>
                        </div>
                        
                        {{-- Result 3: Median Cut --}}
                        @endif
                        @if($hasAllMethods ?? false)
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">🎨 Median Cut</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-purple-500/50 bg-black/50">
                                @if($results['median']['image_url'] ?? null)
                                    <img 
                                        src="{{ $results['median']['image_url'] }}" 
                                        alt="Hasil Median Cut" 
                                        class="w-full h-auto object-cover max-h-64"
                                    >
                                @else
                                    <div class="w-full h-40 flex items-center justify-center bg-gray-800">
                                        <div class="text-center">
                                            <i class="bi bi-exclamation-triangle text-red-500 text-xl block mb-2"></i>
                                            <p class="text-red-400 text-xs">{{ $results['median']['error'] ?? 'Gagal memproses' }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300 space-y-1">
                                <p>Time: <span class="text-amber-400">{{ $results['median']['processing_time_ms'] ?? '-' }}ms</span></p>
                                <button 
                                    onclick="downloadImage('{{ $results['median']['image_url'] ?? '' }}', 'median')"
                                    class="w-full mt-2 bg-purple-700 hover:bg-purple-600 text-white text-xs py-1 px-2 rounded transition-all {{ !($results['median']['image_url'] ?? null) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ !($results['median']['image_url'] ?? null) ? 'disabled' : '' }}
                                >
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </div>
                        </div>
                        @endif
                        
                    </div>
                </div>
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
