@props([
    'id' => 'color-search-modal',
    'title' => 'Rekomendasi Batik By Warna',
    'subtitle' => 'Unggah foto kain batik dan dapatkan rekomendasi berdasarkan palette warna dominan.',
    'paletteEndpoint' => '',
    'recommendationEndpoint' => '',
])

<div
    id="{{ $id }}"
    data-palette-endpoint="{{ $paletteEndpoint }}"
    data-recommendation-endpoint="{{ $recommendationEndpoint }}"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm p-2 sm:p-4"
    onclick="ColorSearchModal.handleBackdropClick(event, '{{ $id }}')"
>
    <div class="relative w-full max-w-6xl max-h-[95vh] overflow-y-auto rounded-2xl border border-gray-800 bg-[#111] shadow-2xl sm:rounded-3xl">
        <button
            type="button"
            onclick="ColorSearchModal.close('{{ $id }}')"
            class="absolute right-4 top-4 text-gray-500 transition-colors hover:text-white sm:right-5 sm:top-5"
            aria-label="Tutup modal"
        >
            <i class="bi bi-x-lg text-xl"></i>
        </button>

        <div class="border-b border-gray-800 px-4 pb-5 pt-9 text-center sm:px-6 md:px-10">
            <h2 class="font-playfair text-2xl font-bold text-white sm:text-3xl lg:text-5xl">{{ $title }}</h2>
            <p class="mx-auto mt-2 max-w-2xl text-xs leading-relaxed text-gray-400 sm:text-sm">{{ $subtitle }}</p>
        </div>

        <div class="space-y-5 p-4 sm:p-6 md:space-y-6 md:p-8">
            <div id="{{ $id }}-alert" class="hidden rounded-xl border px-4 py-3 text-sm">
                <div class="flex items-start gap-2">
                    <i id="{{ $id }}-alert-icon" class="bi bi-info-circle-fill mt-0.5"></i>
                    <p id="{{ $id }}-alert-message" class="leading-relaxed"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2 xl:gap-6">
                <div class="space-y-3">
                    <p class="text-center text-sm font-semibold text-white">Gambar Batikmu</p>

                    <div
                        id="{{ $id }}-dropzone"
                        class="group min-h-[250px] cursor-pointer rounded-2xl border-2 border-dashed border-amber-700/60 bg-gray-900/40 p-4 transition-colors hover:border-primary sm:min-h-[300px] sm:p-5 lg:min-h-[320px]"
                        onclick="document.getElementById('{{ $id }}-file-input').click()"
                        ondragover="ColorSearchModal.handleDragOver(event, '{{ $id }}')"
                        ondragleave="ColorSearchModal.handleDragLeave(event, '{{ $id }}')"
                        ondrop="ColorSearchModal.handleDrop(event, '{{ $id }}')"
                    >
                        <div id="{{ $id }}-upload-state" class="flex h-full flex-col items-center justify-center gap-4 text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-gray-700 bg-gray-800">
                                <i class="bi bi-camera text-2xl text-gray-400"></i>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-white">Masukkan Gambar</p>
                                <p class="mt-1 text-xs text-gray-500 sm:text-sm">
                                    Pilih gambar atau seret &amp; lepas di sini.
                                </p>
                                <p class="mt-2 text-xs text-amber-500">JPG • PNG • WEBP • MAX 50MB (otomatis dioptimasi sebelum upload)</p>
                            </div>
                        </div>

                        <img
                            id="{{ $id }}-preview"
                            src=""
                            alt="Preview Gambar Batik"
                            class="hidden h-[250px] w-full rounded-xl border border-gray-700 object-cover sm:h-[300px] lg:h-[320px]"
                        >
                    </div>

                    <input
                        id="{{ $id }}-file-input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        onchange="ColorSearchModal.handleFile(this.files[0], '{{ $id }}')"
                    >

                    <input
                        id="{{ $id }}-camera-input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        capture="environment"
                        class="hidden"
                        onchange="ColorSearchModal.handleFile(this.files[0], '{{ $id }}')"
                    >

                    <div class="mx-auto grid w-full max-w-md grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            onclick="document.getElementById('{{ $id }}-camera-input').click()"
                            class="rounded-xl border border-amber-700/50 bg-amber-950/30 px-3 py-3 text-sm font-semibold text-amber-400 transition-colors hover:bg-amber-900/40"
                        >
                            <i class="bi bi-camera-fill mr-2"></i>Scan Langsung
                        </button>
                        <button
                            type="button"
                            onclick="document.getElementById('{{ $id }}-file-input').click()"
                            class="rounded-xl border border-gray-700 bg-gray-800/60 px-3 py-3 text-sm font-semibold text-gray-300 transition-colors hover:bg-gray-700"
                        >
                            <i class="bi bi-upload mr-2"></i>Unggah Galeri
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-center text-sm font-semibold text-amber-500">Pallet Warnamu</p>
                    <div id="{{ $id }}-palette-empty" class="flex min-h-[250px] items-center justify-center rounded-2xl border border-gray-800 bg-gray-900/30 p-5 text-center text-sm text-gray-500 sm:min-h-[300px] lg:min-h-[320px]">
                        Hasil palette akan muncul setelah proses pindai gambar.
                    </div>
                    <div id="{{ $id }}-palette-list" class="hidden min-h-[250px] rounded-2xl border border-amber-700/60 bg-gray-900/30 p-3 sm:min-h-[300px] sm:p-4 lg:min-h-[320px]"></div>
                    <div id="{{ $id }}-refresh-wrap" class="hidden text-center">
                        <button
                            id="{{ $id }}-refresh-btn"
                            type="button"
                            onclick="ColorSearchModal.refreshPalette('{{ $id }}')"
                            class="inline-flex items-center gap-2 rounded-xl border border-amber-700/50 bg-amber-950/30 px-4 py-2 text-xs font-semibold text-amber-300 transition-colors hover:bg-amber-900/40"
                        >
                            <i class="bi bi-arrow-clockwise"></i>
                            Refresh Palette
                        </button>
                    </div>
                </div>
            </div>

            <div id="{{ $id }}-action-section" class="hidden border-t border-gray-800 pt-5">
                <p id="{{ $id }}-action-label" class="mb-4 text-center text-lg font-semibold text-white sm:text-xl">Lakukan Pencarian Sekarang?</p>
                <p id="{{ $id }}-action-note" class="mb-3 text-center text-xs text-gray-400"></p>
                <div class="mx-auto grid max-w-md grid-cols-1 gap-3 sm:grid-cols-2">
                    <button
                        id="{{ $id }}-scan-btn"
                        type="button"
                        onclick="ColorSearchModal.search('{{ $id }}')"
                        class="rounded-xl bg-primary py-3 font-bold text-black transition-colors hover:bg-amber-600"
                    >
                        Cari Rekomendasi
                    </button>
                    <button
                        type="button"
                        onclick="ColorSearchModal.reset('{{ $id }}')"
                        class="rounded-xl border border-gray-700 bg-gray-800 py-3 font-bold text-white transition-colors hover:bg-gray-700"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <div id="{{ $id }}-recommend-section" class="hidden border-t border-gray-800 pt-6">
                <div class="flex flex-col items-start justify-between gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <h3 class="text-lg font-bold text-white sm:text-xl lg:text-2xl">Rekomendasi Batik Warna Sesuai Untukmu</h3>
                    <span id="{{ $id }}-recommend-count" class="text-sm text-gray-400"></span>
                </div>

                <div id="{{ $id }}-recommend-list" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"></div>
            </div>
        </div>
    </div>
</div>
