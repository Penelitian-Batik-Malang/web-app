{{--
=========================================================================
SHARED: Batik Panel — Panel Full-Screen Pilih & Atur Motif Batik
=========================================================================

Panel overlay full-screen untuk memilih motif batik dan mengatur
posisinya (drag, zoom, rotate) sebelum di-blend ke pakaian.

Digunakan oleh:
  - terapkan-batik.blade.php  (mode = 'terapkan')
  - rekomendasi-batik.blade.php (mode = 'rekomendasi')

Elemen penting (ID dipakai oleh JS):
  - #batik-panel          : Container overlay panel
  - #batik-crop-canvas    : Canvas preview + atur posisi motif
  - #panel-part-name      : Label nama bagian pakaian
  - #panel-part-color     : Indikator warna
  - #panel-toolbar        : Toolbar atas (search + upload)
  - #panel-batik-gallery  : Grid motif utama
  - #panel-batik-subgallery : Sub-gallery gambar motif
  - #panel-batik-subgrid  : Grid gambar dalam sub-gallery
  - #panel-sub-title      : Judul sub-gallery
  - #panel-back-btn       : Tombol kembali dari sub-gallery
  - #apply-blend-btn      : Tombol terapkan blend
  - #panel-status         : Pesan error/status
=========================================================================
--}}

<div id="batik-panel" class="hidden fixed inset-0 z-50 bg-black/90 items-start justify-center" style="display:none">
    <div class="w-full h-full flex flex-col bg-[#0d0d0d]">

        {{-- ── Panel Header ──────────────────────────────────────────── --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800 shrink-0">
            <div class="flex items-center gap-3">
                <div id="panel-part-color" class="w-3 h-3 rounded-full bg-primary"></div>
                <div>
                    <h3 id="panel-part-name" class="text-white font-bold text-base leading-tight"></h3>
                    <p id="panel-bbox-info" class="text-gray-500 text-xs"></p>
                </div>
            </div>
            <button id="panel-close-btn" class="text-gray-400 hover:text-white p-2 rounded-lg hover:bg-gray-800 transition-colors">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        {{-- ── Panel Body ────────────────────────────────────────────── --}}
        <div class="flex flex-1 overflow-hidden">

            {{-- Kolom Kiri: Canvas Preview --}}
            <div class="w-full lg:w-2/5 flex flex-col border-r border-gray-800 p-4 gap-3 shrink-0">
                <p class="text-xs text-gray-400">Atur posisi motif — drag untuk geser, scroll untuk zoom:</p>
                <div class="flex-1 rounded-xl overflow-hidden bg-gray-800 border border-gray-700 flex items-center justify-center" style="min-height:200px;max-height:340px">
                    <canvas id="batik-crop-canvas" class="block w-full h-full" style="cursor:grab;touch-action:none;max-height:340px"></canvas>
                </div>
                <div class="grid grid-cols-5 gap-1.5">
                    <button id="zoom-in-btn"  class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition-colors" title="Perbesar"><i class="bi bi-zoom-in"></i></button>
                    <button id="zoom-out-btn" class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition-colors" title="Perkecil"><i class="bi bi-zoom-out"></i></button>
                    <button id="rotate-ccw-btn" class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition-colors" title="Putar kiri"><i class="bi bi-arrow-counterclockwise"></i></button>
                    <button id="rotate-cw-btn"  class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition-colors" title="Putar kanan"><i class="bi bi-arrow-clockwise"></i></button>
                    <button id="batik-reset-transform" class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition-colors" title="Reset"><i class="bi bi-aspect-ratio"></i></button>
                </div>
                <p id="panel-status" class="hidden text-xs text-red-400 mt-1"></p>
                <div class="flex gap-3 mt-auto">
                    <button id="apply-blend-btn" class="flex-1 bg-primary hover:bg-amber-600 text-black font-bold py-2.5 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                        <i class="bi bi-check2"></i> Terapkan
                    </button>
                    <button id="panel-cancel-btn" class="px-5 border border-gray-600 hover:border-gray-500 text-white font-semibold py-2.5 rounded-lg transition-colors">
                        Batal
                    </button>
                </div>
            </div>

            {{-- Kolom Kanan: Galeri Batik --}}
            <div class="flex-1 flex flex-col overflow-hidden">

                @if($mode === 'terapkan')
                    {{-- Toolbar: Upload + Search --}}
                    <div id="panel-toolbar" class="flex gap-2 px-4 pt-4 pb-3 shrink-0">
                        <button id="panel-upload-btn" class="flex items-center gap-1 text-amber-400 text-sm border border-amber-700/60 rounded-lg py-2 px-3 hover:bg-amber-950/20 transition-colors whitespace-nowrap">
                            <i class="bi bi-upload"></i> Unggah
                        </button>
                        <input id="panel-batik-input" type="file" accept="image/*" class="hidden">
                        <div class="flex-1 relative">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                            <input id="panel-search" type="text" placeholder="Cari motif batik..."
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg pl-8 pr-3 py-2 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-primary/60">
                        </div>
                    </div>

                    {{-- Main Gallery: motif cards --}}
                    <div id="panel-batik-gallery" class="flex-1 overflow-y-auto px-4 pb-4">
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                            @foreach($batikSamples ?? [] as $batik)
                                <button type="button"
                                        class="panel-sample-batik border-2 border-gray-700 rounded-xl overflow-hidden hover:border-primary transition-colors text-left bg-gray-900 hover:bg-gray-800"
                                        data-url="{{ $batik['image_url'] }}"
                                        data-name="{{ strtolower($batik['name']) }}"
                                        data-images="{{ json_encode($batik['images'] ?? []) }}">
                                    <div class="aspect-square overflow-hidden bg-gray-800">
                                        <img src="{{ $batik['image_url'] }}" class="w-full h-full object-cover hover:scale-110 transition-transform duration-300" alt="{{ $batik['name'] }}" loading="lazy"
                                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-gray-700\'><i class=\'bi bi-image text-xl\'></i></div>'">
                                    </div>
                                    <p class="text-xs text-gray-300 truncate px-2 py-1.5 font-medium">{{ $batik['name'] }}</p>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Sub-gallery: gambar dari motif yang dipilih --}}
                    <div id="panel-batik-subgallery" class="hidden flex-1 overflow-y-auto px-4 pb-4 flex flex-col gap-3">
                        <div class="flex items-center gap-3 py-2 shrink-0">
                            <button id="panel-back-btn" class="flex items-center gap-1 text-gray-400 hover:text-white text-sm transition-colors">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </button>
                            <h4 id="panel-sub-title" class="text-white text-sm font-bold truncate"></h4>
                        </div>
                        <div id="panel-batik-subgrid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                            {{-- Diisi oleh JS --}}
                        </div>
                    </div>

                @else
                    {{-- Mode Rekomendasi: Diisi oleh JS dari data CBIR --}}
                    <div id="panel-toolbar" class="flex gap-2 px-4 pt-4 pb-3 shrink-0">
                        <div class="flex-1 py-2">
                            <p class="text-xs text-gray-400">Pilih batik rekomendasi untuk diterapkan:</p>
                        </div>
                    </div>
                    <div id="panel-batik-gallery" class="flex-1 overflow-y-auto px-4 pb-4">
                        <div id="panel-batik-grid" class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-5 gap-2">
                            {{-- Populated dynamically by JS (rekomendasi-batik custom_scripts) --}}
                        </div>
                    </div>
                @endif

            </div>{{-- /Kolom Kanan --}}
        </div>{{-- /Panel Body --}}
    </div>
</div>
