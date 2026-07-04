@extends('layouts.layout')

@section('title', 'Proses Pewarnaan Pallet')

@section('content')
{{-- Debug Info (Hidden but available in page source) --}}
@php
    $hasKmeans = !empty($palettesKmeans);
    $hasHistogram = !empty($palettesHistogram);
    $hasMedian = !empty($paletteMedianCut);
    $hasAnyPalette = $hasKmeans || $hasHistogram || $hasMedian;
@endphp

{{-- Meta tags untuk endpoint URLs --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="api-colorize-url" content="{{ route('api.colorize.palet') }}">
<meta name="api-save-results-url" content="{{ route('api.save.results') }}">
<meta name="output-url" content="{{ route('pewarnaan.output') }}">>
<div class="flex items-center justify-center min-h-screen py-8 px-4">
    {{-- Debug Banner for Missing Palettes --}}
    @if(!$hasAnyPalette && $colorImage)
        <div class="absolute top-4 right-4 bg-red-900/80 border border-red-500 text-red-200 px-4 py-3 rounded-lg text-xs max-w-sm z-50">
            <p class="font-bold">⚠️ Debug: Palettes Tidak Diextract</p>
            <ul class="mt-2 space-y-1 text-[10px]">
                <li>KMeans: {{ count($palettesKmeans ?? []) }} colors</li>
                <li>Histogram: {{ count($palettesHistogram ?? []) }} colors</li>
                <li>Median: {{ count($paletteMedianCut ?? []) }} colors</li>
            </ul>
            <p class="mt-2 text-[9px]">Cek console browser & storage/logs/laravel.log</p>
        </div>
    @endif

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
                        @elseif(!empty($manualColors))
                            {{-- Manual color palette mode - show color swatches --}}
                            <div class="w-full min-h-80 flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-amber-700/50 p-8">
                                <div class="text-center space-y-6">
                                    <div>
                                        <i class="bi bi-palette text-6xl text-amber-400 mb-4 block"></i>
                                        <p class="text-white font-semibold text-sm">Palet Warna Manual</p>
                                    </div>
                                    
                                    {{-- Color Swatches Grid --}}
                                    <div class="grid grid-cols-2 gap-4 mt-6">
                                        @foreach($manualColors as $index => $color)
                                            <div class="flex flex-col items-center gap-2">
                                                <div 
                                                    class="w-16 h-16 rounded-lg border-3 border-gray-600 shadow-lg hover:border-amber-400 transition-all cursor-pointer"
                                                    style="background-color: {{ $color }}"
                                                    title="{{ $color }}"
                                                ></div>
                                                <span class="text-gray-300 text-xs font-mono">{{ strtoupper($color) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                       
                        @endif
                        
                        {{-- FITUR BARU: Editing colors extracted from batik --}}
                        @if(($isAutoExtract ?? false) && (!empty($palettesKmeans) || !empty($palettesHistogram) || !empty($paletteMedianCut)))
                            <div class="bg-gray-800/50 rounded-xl p-4 space-y-4">
                                <p class="text-amber-500 text-xs font-semibold mb-3">Ubah Warna : <span class="text-gray-400 text-[9px]">(Klik untuk mengubah)</span></p>
                                
                                {{-- KMeans --}}
                                @if(!empty($palettesKmeans))
                                    <div class="border-l-2 border-blue-500 pl-3">
                                        <p class="text-amber-500 text-xs font-semibold mb-3">kmeans</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($palettesKmeans as $index => $color)
                                                <div class="flex items-center gap-1 group cursor-pointer">
                                                    <div 
                                                        class="w-6 h-6 rounded border-2 border-gray-500 shadow-sm hover:border-amber-400 transition-all palette-color-kmeans-{{ $index }}"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                        onclick="openColorPicker('kmeans', {{ $index }})"
                                                    ></div>
                                                    <input 
                                                        type="color" 
                                                        id="color-kmeans-{{ $index }}" 
                                                        value="{{ $color }}"
                                                        class="hidden"
                                                        onchange="updatePaletteColor('kmeans', {{ $index }}, this.value)"
                                                    >
                                                    <span class="text-gray-300 text-[9px] font-mono group-hover:text-amber-400 transition-colors" id="label-kmeans-{{ $index }}">{{ strtoupper($color) }}</span>
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
                                            @foreach($palettesHistogram as $index => $color)
                                                <div class="flex items-center gap-1 group cursor-pointer">
                                                    <div 
                                                        class="w-6 h-6 rounded border-2 border-gray-500 shadow-sm hover:border-amber-400 transition-all palette-color-histogram-{{ $index }}"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                        onclick="openColorPicker('histogram', {{ $index }})"
                                                    ></div>
                                                    <input 
                                                        type="color" 
                                                        id="color-histogram-{{ $index }}" 
                                                        value="{{ $color }}"
                                                        class="hidden"
                                                        onchange="updatePaletteColor('histogram', {{ $index }}, this.value)"
                                                    >
                                                    <span class="text-gray-300 text-[9px] font-mono group-hover:text-amber-400 transition-colors" id="label-histogram-{{ $index }}">{{ strtoupper($color) }}</span>
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
                                            @foreach($paletteMedianCut as $index => $color)
                                                <div class="flex items-center gap-1 group cursor-pointer">
                                                    <div 
                                                        class="w-6 h-6 rounded border-2 border-gray-500 shadow-sm hover:border-amber-400 transition-all palette-color-median-{{ $index }}"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                        onclick="openColorPicker('median', {{ $index }})"
                                                    ></div>
                                                    <input 
                                                        type="color" 
                                                        id="color-median-{{ $index }}" 
                                                        value="{{ $color }}"
                                                        class="hidden"
                                                        onchange="updatePaletteColor('median', {{ $index }}, this.value)"
                                                    >
                                                    <span class="text-gray-300 text-[9px] font-mono group-hover:text-amber-400 transition-colors" id="label-median-{{ $index }}">{{ strtoupper($color) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @elseif($colorImage && (!empty($palettesKmeans) || !empty($palettesHistogram) || !empty($paletteMedianCut)) && $colorSourceType !== 'manual')
                            <div class="bg-gray-800/50 rounded-xl p-4 space-y-4">
                                <p class="text-amber-500 text-xs font-semibold mb-3">Warna yang Di-Extract : <span class="text-gray-400 text-[9px]">(Klik untuk mengubah)</span></p>
                                
                                {{-- KMeans --}}
                                @if(!empty($palettesKmeans))
                                    <div class="border-l-2 border-blue-500 pl-3">
                                        <p class="text-amber-500 text-xs font-semibold mb-3">kmeans</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($palettesKmeans as $index => $color)
                                                <div class="flex items-center gap-1 group cursor-pointer">
                                                    <div 
                                                        class="w-6 h-6 rounded border-2 border-gray-500 shadow-sm hover:border-amber-400 transition-all palette-color-kmeans-{{ $index }}"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                        onclick="openColorPicker('kmeans', {{ $index }})"
                                                    ></div>
                                                    <input 
                                                        type="color" 
                                                        id="color-kmeans-{{ $index }}" 
                                                        value="{{ $color }}"
                                                        class="hidden"
                                                        onchange="updatePaletteColor('kmeans', {{ $index }}, this.value)"
                                                    >
                                                    <span class="text-gray-300 text-[9px] font-mono group-hover:text-amber-400 transition-colors" id="label-kmeans-{{ $index }}">{{ strtoupper($color) }}</span>
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
                                            @foreach($palettesHistogram as $index => $color)
                                                <div class="flex items-center gap-1 group cursor-pointer">
                                                    <div 
                                                        class="w-6 h-6 rounded border-2 border-gray-500 shadow-sm hover:border-amber-400 transition-all palette-color-histogram-{{ $index }}"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                        onclick="openColorPicker('histogram', {{ $index }})"
                                                    ></div>
                                                    <input 
                                                        type="color" 
                                                        id="color-histogram-{{ $index }}" 
                                                        value="{{ $color }}"
                                                        class="hidden"
                                                        onchange="updatePaletteColor('histogram', {{ $index }}, this.value)"
                                                    >
                                                    <span class="text-gray-300 text-[9px] font-mono group-hover:text-amber-400 transition-colors" id="label-histogram-{{ $index }}">{{ strtoupper($color) }}</span>
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
                                            @foreach($paletteMedianCut as $index => $color)
                                                <div class="flex items-center gap-1 group cursor-pointer">
                                                    <div 
                                                        class="w-6 h-6 rounded border-2 border-gray-500 shadow-sm hover:border-amber-400 transition-all palette-color-median-{{ $index }}"
                                                        style="background-color: {{ $color }}"
                                                        title="{{ $color }}"
                                                        onclick="openColorPicker('median', {{ $index }})"
                                                    ></div>
                                                    <input 
                                                        type="color" 
                                                        id="color-median-{{ $index }}" 
                                                        value="{{ $color }}"
                                                        class="hidden"
                                                        onchange="updatePaletteColor('median', {{ $index }}, this.value)"
                                                    >
                                                    <span class="text-gray-300 text-[9px] font-mono group-hover:text-amber-400 transition-colors" id="label-median-{{ $index }}">{{ strtoupper($color) }}</span>
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

                {{-- Color Picker Backdrop --}}
                <div id="color-picker-backdrop" class="hidden fixed inset-0 z-30"></div>

                {{-- Color Picker Modal --}}
                <div id="color-picker-modal" class="hidden fixed bg-gray-800 border border-gray-700 rounded-2xl shadow-2xl p-8 z-40" style="width: 500px; max-height: 90vh; overflow-y: auto; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                    <div class="space-y-6">
                        <div class="flex justify-between items-center">
                            <label class="text-white text-lg font-bold">Pilih Warna</label>
                            <button onclick="closeColorPicker()" class="text-gray-400 hover:text-white transition-colors"><i class="bi bi-x-lg text-xl"></i></button>
                        </div>
                        
                        {{-- Saturation/Brightness Box --}}
                        <div id="color-box-container" class="relative w-full h-64 rounded-lg cursor-crosshair overflow-hidden border-2 border-gray-600 shadow-inner">
                            {{-- Layer 1: Base Color (Hue) --}}
                            <div id="base-color-layer" class="absolute inset-0" style="background-color: #ff0000;"></div>
                            {{-- Layer 2: White Gradient (Saturation) --}}
                            <div class="absolute inset-0" style="background: linear-gradient(to right, #fff, transparent);"></div>
                            {{-- Layer 3: Black Gradient (Brightness) --}}
                            <div class="absolute inset-0" style="background: linear-gradient(to top, #000, transparent);"></div>
                            {{-- Selector Dot --}}
                            <div id="color-cursor" class="absolute w-5 h-5 border-2 border-white rounded-full shadow-md pointer-events-none" style="left: 0; top: 0; transform: translate(-50%, -50%);"></div>
                        </div>
                        
                        {{-- Hue Slider (Rainbow) --}}
                        <div class="space-y-2">
                            <label class="text-gray-300 text-xs font-semibold">Hue</label>
                            <input type="range" id="hue-slider" min="0" max="360" value="0" 
                                class="hue-slider w-full h-4 rounded-lg cursor-pointer" 
                                oninput="updateHue()">
                        </div>
                        
                        <div class="flex gap-4 items-start">
                            <div class="space-y-2 flex-shrink-0">
                                <label class="text-gray-300 text-xs font-semibold block">Preview</label>
                                <div id="color-preview" class="w-20 h-20 rounded-lg border-3 border-gray-600 shadow-inner"></div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <label class="text-gray-300 text-xs font-semibold block">Hex Value</label>
                                <input type="text" id="color-hex-input" 
                                    class="w-full bg-gray-900 text-white text-base font-mono px-4 py-3 rounded-lg border border-gray-700 focus:border-amber-500 outline-none" 
                                    maxlength="7"
                                    oninput="updateFromHexInput()">
                            </div>
                        </div>
                        
                        <button onclick="applyColorPicker()" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-lg transition-all text-base">
                            Terapkan Warna
                        </button>
                    </div>
                </div>

                {{-- Loading Modal (Temporary) --}}
                <div id="processing-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 rounded-2xl">
                    <div class="bg-gray-800 border border-gray-700 rounded-2xl p-8 text-center max-w-sm">
                        <div class="flex justify-center mb-4">
                            <i class="bi bi-hourglass-split animate-spin text-amber-500 text-4xl"></i>
                        </div>
                        <p class="text-white font-semibold mb-2">Memproses Pewarnaan</p>
                        <p class="text-gray-400 text-sm">Memproses 3 metode pewarnaan...</p>
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
    
    /* Hue Slider Styling */
    .hue-slider {
        -webkit-appearance: none;
        appearance: none;
        background: linear-gradient(to right, #f00 0%, #ff0 17%, #0f0 33%, #0ff 50%, #00f 67%, #f0f 83%, #f00 100%);
        border: 1px solid #555;
        outline: none;
    }
    
    .hue-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: white;
        border: 2px solid #333;
        cursor: pointer;
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.5);
    }
    
    .hue-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: white;
        border: 2px solid #333;
        cursor: pointer;
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.5);
    }
</style>

<script src="{{ asset('js/pewarnaan-palletnet.js') }}"></script>
<script>
    // Initialize PewarnaanPalletNet class dengan data dari server
    let pewarnaanApp = null;

    document.addEventListener('DOMContentLoaded', function() {
        const palettesKmeans = {!! json_encode($palettesKmeans ?? []) !!};
        const palettesHistogram = {!! json_encode($palettesHistogram ?? []) !!};
        const paletteMedianCut = {!! json_encode($paletteMedianCut ?? []) !!};
        const batikImage = '{{ $batikImage }}';
        const colorImage = '{{ $colorImage }}';
        const colorSourceType = '{{ $colorSourceType ?? 'upload' }}';

        // Initialize app
        pewarnaanApp = new PewarnaanPalletNet(palettesKmeans, palettesHistogram, paletteMedianCut);

        // Setup global functions untuk inline handlers (onclick)
        window.openColorPicker = (method, index) => pewarnaanApp.openColorPicker(method, index);
        window.closeColorPicker = () => pewarnaanApp.closeColorPicker();
        window.updateHue = () => pewarnaanApp.updateHue();
        window.updateFromHexInput = () => pewarnaanApp.updateFromHexInput();
        window.applyColorPicker = () => pewarnaanApp.applyColorPicker();
        window.updatePaletteColor = (method, index, color) => pewarnaanApp.updatePaletteColor(method, index, color);
        window.colorSourceType = colorSourceType;
        window.handleColorize = () => pewarnaanApp.handleColorize(batikImage, colorImage, colorSourceType);
    });
</script>
@endsection
