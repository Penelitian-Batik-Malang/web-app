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
                    
                    {{-- Left: Gambar Batik Original --}}
                    <div class="flex-1 space-y-4">
                        <h3 class="text-center text-white font-semibold">Gambar Batik Original</h3>
                        
                        <div class="rounded-2xl overflow-hidden border border-amber-700/50 bg-black/50">
                            @if($batikImage)
                                <img src="{{ $batikImage }}" 
                                     alt="Gambar Batik" 
                                     class="w-full h-auto object-cover">
                            @else
                                <div class="w-full h-80 flex items-center justify-center bg-gray-800 text-gray-600">
                                    <i class="bi bi-image text-5xl"></i>
                                </div>
                            @endif
                        </div>
                        
                        <div class="bg-gray-800/50 rounded-xl p-4">
                            <p class="text-amber-500 font-bold text-sm mb-1">Gambar Batik Sumber</p>
                            <p class="text-gray-400 text-xs">Gambar batik yang Anda upload untuk di-recolor</p>
                        </div>
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
                        
                        @if($colorImage && (!empty($palettesKmeans) || !empty($palettesHistogram) || !empty($paletteMedianCut)))
                            <div class="bg-gray-800/50 rounded-xl p-4 space-y-4">
                                <p class="text-amber-500 text-xs font-semibold mb-3">Warna yang Di-Extract :</p>
                                
                                {{-- KMeans --}}
                                @if(!empty($palettesKmeans))
                                    <div class="border-l-2 border-blue-500 pl-3">
                                        <p class="text-amber-500 text-xs font-semibold mb-3"> kmeans</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($palettesKmeans as $color)
                                                <div class="flex items-center gap-1">
                                                    <div 
                                                        class="w-6 h-6 rounded border border-gray-500 shadow-sm"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                    ></div>
                                                    <span class="text-gray-300 text-[9px] font-mono">{{ strtoupper($color) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                {{-- Histogram --}}
                                @if(!empty($palettesHistogram))
                                    <div class="border-l-2 border-green-500 pl-3">
                                        <p class="text-amber-500 text-xs font-semibold mb-3">histogram</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($palettesHistogram as $color)
                                                <div class="flex items-center gap-1">
                                                    <div 
                                                        class="w-6 h-6 rounded border border-gray-500 shadow-sm"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                    ></div>
                                                    <span class="text-gray-300 text-[9px] font-mono">{{ strtoupper($color) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                {{-- Median Cut --}}
                                @if(!empty($paletteMedianCut))
                                    <div class="border-l-2 border-purple-500 pl-3">
                                        <p class="text-amber-500 text-xs font-semibold mb-3">median cut</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($paletteMedianCut as $color)
                                                <div class="flex items-center gap-1">
                                                    <div 
                                                        class="w-6 h-6 rounded border border-gray-500 shadow-sm"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                    ></div>
                                                    <span class="text-gray-300 text-[9px] font-mono">{{ strtoupper($color) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @elseif($colorImage)
                            <div class="bg-gray-800/50 rounded-xl p-4 text-center">
                                <p class="text-amber-500 text-xs">Sumber warna dari upload Anda</p>
                                <p class="text-gray-500 text-[10px] mt-1">Pallet akan di-extract saat proses</p>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Result Container (Hidden by default) --}}
                <div id="result-container" class="hidden border-t border-gray-800 pt-8 mt-8">
                    <h3 class="text-center text-white font-semibold mb-6">Hasil Pewarnaan (3 Metode)</h3>
                    
                    {{-- Loading Indicator --}}
                    <div id="result-loading" class="flex justify-center items-center mb-4">
                        <div class="flex flex-col items-center gap-2">
                            <i class="bi bi-hourglass-split animate-spin text-amber-500 text-3xl"></i>
                            <p class="text-gray-400 text-sm">Memproses 3 metode pewarnaan...</p>
                        </div>
                    </div>
                    
                    {{-- 3 Results in Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {{-- Result 1: KMeans --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">📊 KMeans</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-blue-500/50 bg-black/50">
                                <img 
                                    id="result-image-kmeans" 
                                    src="" 
                                    alt="Hasil KMeans" 
                                    class="w-full h-auto object-cover hidden"
                                    onload="handleImageLoad(this, 'kmeans')"
                                    onerror="handleImageError('kmeans')"
                                >
                                <div id="result-loading-kmeans" class="w-full h-40 flex items-center justify-center bg-gray-800">
                                    <i class="bi bi-hourglass-split animate-spin text-blue-400 text-xl"></i>
                                </div>
                                <div id="result-error-kmeans" class="hidden w-full h-40 flex items-center justify-center bg-gray-800">
                                    <i class="bi bi-exclamation-triangle text-red-500 text-xl"></i>
                                </div>
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300">
                                <p>Time: <span id="time-kmeans" class="text-amber-400">-</span>ms</p>
                            </div>
                        </div>
                        
                        {{-- Result 2: Histogram --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">📈 Histogram</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-green-500/50 bg-black/50">
                                <img 
                                    id="result-image-histogram" 
                                    src="" 
                                    alt="Hasil Histogram" 
                                    class="w-full h-auto object-cover hidden"
                                    onload="handleImageLoad(this, 'histogram')"
                                    onerror="handleImageError('histogram')"
                                >
                                <div id="result-loading-histogram" class="w-full h-40 flex items-center justify-center bg-gray-800">
                                    <i class="bi bi-hourglass-split animate-spin text-green-400 text-xl"></i>
                                </div>
                                <div id="result-error-histogram" class="hidden w-full h-40 flex items-center justify-center bg-gray-800">
                                    <i class="bi bi-exclamation-triangle text-red-500 text-xl"></i>
                                </div>
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300">
                                <p>Time: <span id="time-histogram" class="text-amber-400">-</span>ms</p>
                            </div>
                        </div>
                        
                        {{-- Result 3: Median Cut --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                                <h4 class="text-white font-semibold text-sm">🎨 Median Cut</h4>
                            </div>
                            <div class="rounded-2xl overflow-hidden border border-purple-500/50 bg-black/50">
                                <img 
                                    id="result-image-median" 
                                    src="" 
                                    alt="Hasil Median Cut" 
                                    class="w-full h-auto object-cover hidden"
                                    onload="handleImageLoad(this, 'median')"
                                    onerror="handleImageError('median')"
                                >
                                <div id="result-loading-median" class="w-full h-40 flex items-center justify-center bg-gray-800">
                                    <i class="bi bi-hourglass-split animate-spin text-purple-400 text-xl"></i>
                                </div>
                                <div id="result-error-median" class="hidden w-full h-40 flex items-center justify-center bg-gray-800">
                                    <i class="bi bi-exclamation-triangle text-red-500 text-xl"></i>
                                </div>
                            </div>
                            <div class="bg-gray-800/50 rounded-lg p-2 text-[10px] text-gray-300">
                                <p>Time: <span id="time-median" class="text-amber-400">-</span>ms</p>
                            </div>
                        </div>
                        
                    </div>
                    
                    {{-- Download Buttons --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <button 
                            onclick="downloadResultImage('kmeans')"
                            class="bg-blue-700 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition-all text-sm flex items-center justify-center gap-2"
                        >
                            <i class="bi bi-download"></i>
                            KMeans
                        </button>
                        <button 
                            onclick="downloadResultImage('histogram')"
                            class="bg-green-700 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-all text-sm flex items-center justify-center gap-2"
                        >
                            <i class="bi bi-download"></i>
                            Histogram
                        </button>
                        <button 
                            onclick="downloadResultImage('median')"
                            class="bg-purple-700 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded-lg transition-all text-sm flex items-center justify-center gap-2"
                        >
                            <i class="bi bi-download"></i>
                            Median Cut
                        </button>
                    </div>
                </div>

                {{-- Error Message (Hidden by default) --}}
                <div id="error-container" class="hidden border-t border-gray-800 pt-8 mt-8">
                    <div class="bg-red-900/30 border border-red-700 text-red-300 px-6 py-4 rounded-xl">
                        <p class="text-sm font-semibold mb-2">Terjadi Kesalahan</p>
                        <p id="error-message" class="text-xs"></p>
                    </div>
                </div>

            </div>
        </div>

        {{-- Footer --}}
        <div class="border-t border-gray-800 px-8 py-6">
            <p class="text-center text-white font-semibold text-sm mb-4">Ingin coba lagi?</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <button
                    id="process-button"
                    type="button"
                    onclick="handleColorize()"
                    class="bg-amber-700 hover:bg-amber-600 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-amber-700/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <span id="button-text">Proses Gambar</span>
                    <span id="loading-spinner" class="hidden">
                        <i class="bi bi-hourglass-split animate-spin"></i>
                    </span>
                </button>
                
                <a href="{{ route('pewarnaan.palet') }}" class="border border-gray-700 bg-gray-800/50 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-xl transition-all text-center">
                    Reset
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>

<script>
    // Store extracted palettes dari backend (3 metode)
    const palettesKmeans = {!! json_encode($palettesKmeans ?? []) !!};
    const palettesHistogram = {!! json_encode($palettesHistogram ?? []) !!};
    const paletteMedianCut = {!! json_encode($paletteMedianCut ?? []) !!};
    
    // Store result URLs for each method
    let resultUrls = {
        kmeans: '',
        histogram: '',
        median: ''
    };

    function handleImageLoad(imgElement, method) {
        console.log(`Image loaded: ${method}`);
        document.getElementById(`result-loading-${method}`).classList.add('hidden');
        document.getElementById(`result-error-${method}`).classList.add('hidden');
        imgElement.classList.remove('hidden');
    }

    function handleImageError(method) {
        console.error(`Failed to load result image: ${method}`);
        const loadingId = `result-loading-${method}`;
        const errorId = `result-error-${method}`;
        document.getElementById(loadingId).classList.add('hidden');
        document.getElementById(errorId).classList.remove('hidden');
    }

    function downloadResultImage(method) {
        if (!resultUrls[method]) {
            alert(`URL gambar ${method} tidak tersedia`);
            return;
        }

        // Create hidden anchor element
        const link = document.createElement('a');
        link.href = resultUrls[method];
        link.download = `batik-recolor-${method}-${new Date().getTime()}.jpg`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    async function processWithPalette(method, palette) {
        const batikImage = '{{ $batikImage }}';
        const colorImage = '{{ $colorImage }}';
        
        try {
            const response = await fetch('{{ route("api.colorize.palet") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    batik_image: batikImage,
                    color_image: colorImage,
                    palette: palette,
                    skip_extract: true,
                    method: method  // untuk tracking di backend
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || `Gagal memproses pewarnaan ${method}`);
            }
            
            const result = data.result;
            const resultUrl = result.result_image_url;
            
            if (!resultUrl) {
                throw new Error(`Server tidak mengembalikan URL gambar hasil untuk ${method}`);
            }
            
            console.log(`Result URL (${method}):`, resultUrl);
            
            // Store URL for download
            resultUrls[method] = resultUrl;
            
            // Set image src
            const imgElement = document.getElementById(`result-image-${method}`);
            imgElement.src = resultUrl;
            
            // Update processing time
            document.getElementById(`time-${method}`).textContent = result.processing_time_ms.toFixed(2);
            
            return { success: true, method, result };
            
        } catch (error) {
            console.error(`Process ${method} error:`, error);
            handleImageError(method);
            throw error;
        }
    }

    async function handleColorize() {
        const processButton = document.getElementById('process-button');
        const buttonText = document.getElementById('button-text');
        const loadingSpinner = document.getElementById('loading-spinner');
        const resultContainer = document.getElementById('result-container');
        const errorContainer = document.getElementById('error-container');
        
        // Disable button
        processButton.disabled = true;
        buttonText.textContent = 'Sedang Memproses...';
        loadingSpinner.classList.remove('hidden');
        
        try {
            const batikImage = '{{ $batikImage }}';
            const colorImage = '{{ $colorImage }}';
            
            if (!batikImage) {
                throw new Error('Gambar batik sumber tidak ditemukan.');
            }
            
            if (!colorImage) {
                throw new Error('Tidak ada gambar warna yang diunggah. Silakan upload gambar warna terlebih dahulu.');
            }
            
            // Validate palettes
            if (palettesKmeans.length === 0 || palettesHistogram.length === 0 || paletteMedianCut.length === 0) {
                throw new Error('Palette warna tidak lengkap. Silakan upload ulang gambar warna Anda.');
            }
            
            // Show result container dan loading indicators
            resultContainer.classList.remove('hidden');
            errorContainer.classList.add('hidden');
            document.getElementById('result-loading').classList.remove('hidden');
            
            // Reset all result loading states
            ['kmeans', 'histogram', 'median'].forEach(method => {
                document.getElementById(`result-loading-${method}`).classList.remove('hidden');
                document.getElementById(`result-error-${method}`).classList.add('hidden');
                document.getElementById(`result-image-${method}`).classList.add('hidden');
            });
            
            // Process 3 metode secara paralel
            const results = await Promise.all([
                processWithPalette('kmeans', palettesKmeans),
                processWithPalette('histogram', palettesHistogram),
                processWithPalette('median', paletteMedianCut)
            ]);
            
            console.log('All results processed successfully', results);
            document.getElementById('result-loading').classList.add('hidden');
            
        } catch (error) {
            console.error('Colorize error:', error);
            document.getElementById('error-message').textContent = error.message;
            errorContainer.classList.remove('hidden');
            resultContainer.classList.add('hidden');
        } finally {
            // Re-enable button
            processButton.disabled = false;
            buttonText.textContent = 'Proses Gambar';
            loadingSpinner.classList.add('hidden');
        }
    }

    // Auto-add CSRF token to all fetch requests
    document.addEventListener('DOMContentLoaded', function() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token) {
            // Token sudah ada di meta
        }
    });
</script>
@endsection
