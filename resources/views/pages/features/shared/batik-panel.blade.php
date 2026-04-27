{{--
=========================================================================
SHARED: Batik Panel — Panel Pilih & Atur Motif Batik
=========================================================================

Komponen partial ini menampilkan panel overlay untuk memilih motif batik
dan mengatur posisinya (drag, zoom, rotate) sebelum di-blend ke pakaian.

Digunakan oleh:
  - terapkan-batik.blade.php  (mode = 'terapkan')
  - rekomendasi-batik.blade.php (mode = 'rekomendasi')

Perbedaan berdasarkan mode:
  - terapkan   : Panel menampilkan galeri batik dari database + tombol upload
  - rekomendasi: Panel diisi oleh JS dengan data rekomendasi CBIR

Parameter:
  - $mode : 'terapkan' | 'rekomendasi' (wajib, dioper dari parent view)
  - $batikSamples : Collection batik dari database (hanya mode terapkan)

Elemen penting (ID dipakai oleh JS):
  - #batik-panel          : Container overlay panel
  - #batik-crop-canvas    : Canvas untuk preview + atur posisi motif
  - #panel-part-name      : Label nama bagian pakaian yang dipilih
  - #panel-part-color     : Indikator warna bagian pakaian
  - #panel-batik-gallery  : Grid pilihan batik
  - #apply-blend-btn      : Tombol terapkan blend
  - #panel-status         : Pesan error/status

@see public/js/batik-app/batik-panel.js  — Logic JS panel
@see public/js/batik-app/blend.js        — Logic blend API
=========================================================================
--}}

<div id="batik-panel" class="hidden fixed inset-0 z-50 bg-black/80 items-end lg:items-center justify-center p-0 lg:p-4" style="display:none">
    <div class="w-full max-w-4xl bg-[#0d0d0d] border border-amber-700/40 rounded-t-2xl lg:rounded-2xl overflow-hidden flex flex-col" style="max-height:90vh">

        {{-- Panel Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800 shrink-0">
            <div class="flex items-center gap-3">
                <div id="panel-part-color" class="w-3 h-3 rounded-full"></div>
                <div>
                    <h3 id="panel-part-name" class="text-white font-bold text-base"></h3>
                    <p id="panel-bbox-info" class="text-gray-500 text-xs"></p>
                </div>
            </div>
            <button id="panel-close-btn" class="text-gray-400 hover:text-white p-1"><i class="bi bi-x-lg text-lg"></i></button>
        </div>

        {{-- Panel Body --}}
        <div class="overflow-y-auto flex-1">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">

                {{-- Kolom Kiri: Canvas Preview --}}
                <div>
                    <p class="text-xs text-gray-400 mb-2">Atur posisi motif — drag untuk geser, scroll untuk zoom:</p>
                    <div class="rounded-xl overflow-hidden bg-gray-800 border border-gray-700">
                        <canvas id="batik-crop-canvas" class="block w-full" style="cursor:grab;touch-action:none"></canvas>
                    </div>
                    <div class="grid grid-cols-5 gap-1.5 mt-2">
                        <button id="zoom-in-btn"  class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-1.5 rounded-lg transition-colors" title="Perbesar"><i class="bi bi-zoom-in"></i></button>
                        <button id="zoom-out-btn" class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-1.5 rounded-lg transition-colors" title="Perkecil"><i class="bi bi-zoom-out"></i></button>
                        <button id="rotate-ccw-btn" class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-1.5 rounded-lg transition-colors" title="Putar kiri"><i class="bi bi-arrow-counterclockwise"></i></button>
                        <button id="rotate-cw-btn"  class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-1.5 rounded-lg transition-colors" title="Putar kanan"><i class="bi bi-arrow-clockwise"></i></button>
                        <button id="batik-reset-transform" class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-1.5 rounded-lg transition-colors" title="Reset posisi"><i class="bi bi-aspect-ratio"></i></button>
                    </div>
                </div>

                {{-- Kolom Kanan: Galeri Batik --}}
                <div>
                    @if($mode === 'terapkan')
                        {{-- Mode Terapkan: Upload + search + galeri dari database --}}
                        <p class="text-xs text-gray-400 mb-2">Pilih batik:</p>
                        <div class="flex gap-2 mb-3">
                            <button id="panel-upload-btn" class="flex items-center justify-center gap-1 text-amber-400 text-sm border border-amber-700/60 rounded-lg py-2 px-3 hover:bg-amber-950/20 transition-colors whitespace-nowrap">
                                <i class="bi bi-upload"></i> Unggah
                            </button>
                            <input id="panel-batik-input" type="file" accept="image/*" class="hidden">
                            <input id="panel-search" type="text" placeholder="Cari motif..." class="flex-1 bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white">
                        </div>
                        <div id="panel-batik-gallery" class="grid grid-cols-3 gap-2 overflow-y-auto" style="max-height:200px">
                            @foreach($batikSamples ?? [] as $batik)
                                <button type="button"
                                    class="panel-sample-batik border border-gray-700 rounded-lg overflow-hidden hover:border-primary transition-colors text-left"
                                    data-url="{{ $batik['image_url'] }}"
                                    data-name="{{ strtolower($batik['name']) }}">
                                    <img src="{{ $batik['image_url'] }}" class="w-full h-16 object-cover" alt="{{ $batik['name'] }}">
                                    <p class="text-xs text-primary truncate px-1 py-0.5">{{ $batik['name'] }}</p>
                                </button>
                            @endforeach
                        </div>
                    @else
                        {{-- Mode Rekomendasi: Diisi oleh JS dari data CBIR --}}
                        <p class="text-xs text-gray-400 mb-2">Pilih batik rekomendasi:</p>
                        <div id="panel-batik-gallery" class="grid grid-cols-3 gap-2 overflow-y-auto" style="max-height:300px">
                            {{-- Populated dynamically by JS (rekomendasi-batik custom_scripts) --}}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Status & Action Buttons --}}
            <p id="panel-status" class="hidden text-xs text-red-400 px-4 pb-1"></p>
            <div class="flex gap-3 px-4 pb-4">
                <button id="apply-blend-btn" class="flex-1 bg-primary hover:bg-amber-600 text-black font-bold py-2.5 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="bi bi-check2 mr-1"></i>Terapkan
                </button>
                <button id="panel-cancel-btn" class="px-6 border border-gray-600 hover:border-gray-500 text-white font-semibold py-2.5 rounded-lg transition-colors">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
