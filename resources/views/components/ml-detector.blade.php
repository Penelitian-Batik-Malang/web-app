@props([
    'id'          => 'ml-detector-modal',   // ID unik modal (wajib bila 2 modal di 1 halaman)
    'title'       => 'Deteksi AI',          // Judul modal
    'subtitle'    => 'Unggah gambar dan biarkan AI menganalisis hasilnya.',
    'endpoint'    => '',                    // Route URL tujuan POST AJAX
    'resultLabel' => 'Hasil Deteksi',       // Judul kolom hasil
    'triggerText' => 'Buka Deteksi',        // Teks tombol trigger
    'triggerIcon' => 'bi-cpu',              // Bootstrap Icon class tombol trigger
    'inputType'   => 'image',               // image | text
    'outputType'  => 'text',                // text | image
    'scanText'    => 'Cari Rekomendasi',
    'textPlaceholder' => 'Masukkan prompt atau teks input...',
])

{{-- =====================================================================
     KOMPONEN: x-ml-detector
     Penggunaan:
       <x-ml-detector
           id="modal-deteksi-motif"
           title="Deteksi Motif Batik"
           subtitle="Unggah foto kain dan AI akan mengenali motifnya."
           endpoint="{{ route('api.detect.motif') }}"
           result-label="Hasil Deteksi Motif"
           trigger-text="Deteksi Motif"
           trigger-icon="bi-search"
       />
     ================================================================== --}}

{{-- Tombol Trigger --}}
<button
    onclick="MLDetector.open('{{ $id }}')"
    class="inline-flex items-center gap-2 bg-primary hover:bg-amber-600 text-black font-bold px-6 py-3 rounded-xl transition-all shadow-lg shadow-primary/20 hover:shadow-primary/40"
>
    <i class="bi {{ $triggerIcon }}"></i>
    {{ $triggerText }}
</button>

{{-- Modal Backdrop --}}
<div
    id="{{ $id }}"
    data-input-type="{{ $inputType }}"
    data-output-type="{{ $outputType }}"
    data-endpoint="{{ $endpoint }}"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4"
    onclick="MLDetector.handleBackdropClick(event, '{{ $id }}')"
>
    {{-- Modal Box --}}
    <div class="bg-[#111] border border-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto relative">

        {{-- Tombol Tutup --}}
        <button
            onclick="MLDetector.close('{{ $id }}')"
            class="absolute top-5 right-5 text-gray-500 hover:text-white transition-colors z-10"
        >
            <i class="bi bi-x-lg text-xl"></i>
        </button>

        {{-- Header --}}
        <div class="text-center pt-10 pb-6 px-8 border-b border-gray-800">
            <h2 class="text-3xl font-bold text-white font-playfair mb-2">{{ $title }}</h2>
            <p class="text-gray-400 text-sm leading-relaxed max-w-md mx-auto">{{ $subtitle }}</p>
        </div>

        {{-- Body --}}
        <div class="p-8">

            {{-- ZONA UPLOAD (khusus input image) --}}
            <div id="{{ $id }}-upload-zone" class="{{ $inputType === 'image' ? '' : 'hidden' }}">
                <p class="text-sm font-semibold text-white mb-3 text-center">Gambar Batikmu</p>

                {{-- Drag & Drop Area --}}
                <div
                    id="{{ $id }}-dropzone"
                    class="border-2 border-dashed border-amber-700/60 rounded-2xl p-8 flex flex-col items-center justify-center gap-4 cursor-pointer hover:border-primary transition-colors bg-gray-900/40 min-h-[220px]"
                    onclick="document.getElementById('{{ $id }}-file-input').click()"
                    ondragover="event.preventDefault(); this.classList.add('border-primary', 'bg-primary/5')"
                    ondragleave="this.classList.remove('border-primary', 'bg-primary/5')"
                    ondrop="MLDetector.handleDrop(event, '{{ $id }}')"
                >
                    <div class="w-16 h-16 bg-gray-800 border border-gray-700 rounded-2xl flex items-center justify-center">
                        <i class="bi bi-camera text-2xl text-gray-400"></i>
                    </div>
                    <div class="text-center">
                        <p class="text-white font-semibold mb-1">Masukkan Gambar</p>
                        <p class="text-sm text-gray-500">
                            <span class="text-amber-500 font-medium cursor-pointer">Pilih opsi</span> untuk memasukkan gambar,
                            <br>atau <span class="text-amber-500 font-medium">seret &amp; lepas gambar disini</span>
                        </p>
                        <div class="flex gap-2 justify-center mt-3">
                            <span class="text-xs text-gray-600 border border-gray-700 px-2 py-0.5 rounded-full">JPG</span>
                            <span class="text-xs text-gray-600 border border-gray-700 px-2 py-0.5 rounded-full">PNG</span>
                            <span class="text-xs text-gray-600 border border-gray-700 px-2 py-0.5 rounded-full">WEBP</span>
                            <span class="text-xs text-gray-600 border border-gray-700 px-2 py-0.5 rounded-full">MAX 10MB</span>
                        </div>
                    </div>
                </div>

                {{-- Hidden file inputs --}}
                <input type="file" id="{{ $id }}-file-input" accept="image/*" class="hidden" onchange="MLDetector.handleFile(this.files[0], '{{ $id }}')">
                <input type="file" id="{{ $id }}-camera-input" accept="image/*" capture="environment" class="hidden" onchange="MLDetector.handleFile(this.files[0], '{{ $id }}')">

                {{-- Tombol aksi --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                    <button
                        onclick="MLDetector.openCamera('{{ $id }}')"
                        class="flex items-center justify-center gap-2 border border-amber-700/50 bg-amber-950/30 text-amber-400 hover:bg-amber-900/40 rounded-xl py-3 text-sm font-medium transition-colors"
                    >
                        <i class="bi bi-camera-fill"></i> Scan (Kamera)
                    </button>
                    <button
                        onclick="document.getElementById('{{ $id }}-file-input').click()"
                        class="flex items-center justify-center gap-2 border border-gray-700 bg-gray-800/50 text-gray-300 hover:bg-gray-700 rounded-xl py-3 text-sm font-medium transition-colors"
                    >
                        <i class="bi bi-upload"></i> Unggah Galeri
                    </button>
                </div>

                {{-- Webcam Panel (Desktop) --}}
                <div id="{{ $id }}-webcam-zone" class="hidden mt-5 border border-gray-800 bg-gray-900/30 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-white">Kamera</p>
                        <button onclick="MLDetector.stopWebcam('{{ $id }}')" class="text-xs text-gray-400 hover:text-white">
                            Tutup
                        </button>
                    </div>
                    <div class="rounded-xl overflow-hidden border border-gray-800 bg-black">
                        <div class="relative">
                            <video id="{{ $id }}-webcam-video" class="w-full h-64 object-cover" autoplay playsinline muted></video>
                            <div id="{{ $id }}-live-result" class="absolute bottom-3 left-3 right-3 hidden">
                                <div class="inline-block bg-black/70 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                                    <p id="{{ $id }}-live-label" class="text-white text-lg font-bold leading-tight">Mendeteksi...</p>
                                    <p id="{{ $id }}-live-confidence" class="text-gray-200 text-sm mt-1">Akurasi: -</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="{{ $id }}-webcam-canvas" class="hidden"></canvas>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                    <button
                        onclick="MLDetector.captureWebcam('{{ $id }}')"
                            class="flex items-center justify-center gap-2 bg-primary hover:bg-amber-600 text-black font-bold py-3 rounded-xl transition-all shadow-lg"
                        >
                            <i class="bi bi-camera2"></i> Ambil Foto
                        </button>
                        <button
                            onclick="MLDetector.stopWebcam('{{ $id }}')"
                            class="flex items-center justify-center gap-2 border border-gray-700 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-xl transition-all"
                        >
                            <i class="bi bi-x-circle"></i> Batal
                        </button>
                    </div>
                    <p class="text-gray-500 text-xs mt-3">Jika browser menolak izin kamera, gunakan “Scan Langsung” (mobile) atau unggah dari galeri.</p>
                </div>
            </div>

            {{-- ZONA INPUT TEKS (khusus input text) --}}
            <div id="{{ $id }}-text-zone" class="{{ $inputType === 'text' ? '' : 'hidden' }}">
                <label for="{{ $id }}-text-input" class="text-sm font-semibold text-white mb-3 block">Masukkan Teks</label>
                <textarea
                    id="{{ $id }}-text-input"
                    rows="6"
                    class="w-full rounded-2xl border border-gray-700 bg-gray-900/50 text-white placeholder:text-gray-500 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50"
                    placeholder="{{ $textPlaceholder }}"
                ></textarea>
            </div>

            {{-- PREVIEW & HASIL (tampil setelah gambar dipilih / saat output ditampilkan) --}}
            <div id="{{ $id }}-preview-zone" class="{{ $inputType === 'text' ? '' : 'hidden' }}">
                <p class="text-sm font-semibold text-white mb-3 text-center">Gambar Batikmu</p>
                <div id="{{ $id }}-result-wrapper" class="flex flex-col md:flex-row gap-6">
                    {{-- Kolom Kiri: Preview Gambar --}}
                    <div id="{{ $id }}-preview-col" class="flex-1 {{ $inputType === 'text' ? 'hidden' : '' }}">
                        <img id="{{ $id }}-preview-img" src="" alt="Preview" class="w-full rounded-2xl object-cover max-h-80 border border-gray-700 shadow-xl">
                    </div>

                    {{-- Kolom Kanan: Hasil Deteksi (tersembunyi sampai analisis) --}}
                    <div id="{{ $id }}-result-col" class="flex-1 {{ $inputType === 'text' ? 'flex' : 'hidden' }} flex-col justify-center">
                        <p class="text-amber-500 font-semibold text-sm mb-3">{{ $resultLabel }}</p>

                        {{-- Loading state --}}
                        <div id="{{ $id }}-loading" class="hidden flex-col items-center justify-center gap-3 py-8">
                            <svg class="animate-spin h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <p class="text-gray-400 text-sm">AI sedang menganalisis gambar...</p>
                        </div>

                        {{-- Result card --}}
                        <div id="{{ $id }}-result-card" class="hidden border border-amber-700/50 bg-amber-950/20 rounded-2xl p-5">
                            <h3 id="{{ $id }}-result-label" class="text-xl font-bold text-white mb-3"></h3>
                            <div class="mb-3">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-400">Keyakinan AI</span>
                                    <span id="{{ $id }}-result-pct" class="text-amber-400 font-bold"></span>
                                </div>
                                <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                                    <div id="{{ $id }}-result-bar" class="bg-primary h-2 rounded-full transition-all duration-1000" style="width:0%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Result image card --}}
                        <div id="{{ $id }}-result-image-card" class="hidden border border-amber-700/50 bg-amber-950/20 rounded-2xl p-5">
                            <img id="{{ $id }}-result-image" src="" alt="Hasil AI" class="w-full rounded-xl object-cover max-h-80 border border-gray-700">
                            <p id="{{ $id }}-result-image-caption" class="text-gray-300 text-sm leading-relaxed mt-3"></p>
                        </div>

                        {{-- Error/Stub card --}}
                        <div id="{{ $id }}-error-card" class="hidden border border-dashed border-amber-800/40 bg-amber-950/20 rounded-2xl p-5 text-center">
                            <i class="bi bi-cpu text-3xl text-amber-600 mb-3 block"></i>
                            <p id="{{ $id }}-error-msg" class="text-amber-400 text-sm font-medium"></p>
                            <p class="text-gray-500 text-xs mt-1">Endpoint model AI belum aktif. Hasil akan tampil ketika terhubung.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Aksi --}}
        <div class="border-t border-gray-800 px-8 py-6">
            <p id="{{ $id }}-footer-label" class="text-center text-sm text-gray-400 mb-4">Deteksi Sekarang?</p>
            <div class="grid grid-cols-2 gap-4">
                <button
                    id="{{ $id }}-scan-btn"
                    onclick="MLDetector.primaryAction('{{ $id }}', '{{ $endpoint }}', '{{ $inputType }}', '{{ $outputType }}')"
                    class="bg-primary hover:bg-amber-600 text-black font-bold py-3 rounded-xl transition-all shadow-lg"
                >
                    Cari Rekomendasi
                </button>
                <button
                    onclick="MLDetector.reset('{{ $id }}')"
                    class="border border-gray-700 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-xl transition-all"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>
</div>
