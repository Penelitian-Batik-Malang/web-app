@extends('layouts.layout')
@section('title', 'Pencarian Batik Serupa — CBIR')

@section('content')
<div class="max-w-5xl mx-auto space-y-10">

    {{-- Hero Header --}}
    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/30 text-primary text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-search"></i> Content-Based Image Retrieval
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Pencarian <span class="text-primary">Batik</span> Serupa
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Unggah foto kain batik sebagai referensi — AI akan menemukan batik-batik dengan visual paling mirip dari koleksi kami menggunakan ConvNeXt feature extraction.
        </p>
    </div>

    {{-- Cara Kerja --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-upload text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">1. Unggah Referensi</h3>
            <p class="text-gray-500 text-xs">Upload foto batik yang ingin dicari kembaran visualnya</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-diagram-3 text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">2. Ekstraksi Fitur</h3>
            <p class="text-gray-500 text-xs">ConvNeXt Small mengekstrak fitur visual 768 dimensi dari gambar</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-grid-3x3 text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">3. Hasil Serupa</h3>
            <p class="text-gray-500 text-xs">Tampilkan grid batik paling mirip berdasarkan cosine similarity</p>
        </div>
    </div>

    {{-- Upload Area --}}
    <div class="bg-gradient-to-br from-gray-900 via-amber-950/20 to-gray-900 border border-gray-800 rounded-3xl p-8 shadow-2xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

            {{-- Left: Upload Zone --}}
            <div class="space-y-4">
                <h2 class="text-xl font-bold text-white">Unggah Gambar Batik</h2>

                {{-- Drop Zone --}}
                <label for="cbir-input"
                       id="cbir-dropzone"
                       class="flex flex-col items-center justify-center gap-4 border-2 border-dashed border-gray-700 rounded-2xl p-8 cursor-pointer hover:border-primary/60 hover:bg-primary/5 transition-all min-h-[220px] relative group">
                    {{-- Preview --}}
                    <div id="cbir-preview-wrap" class="hidden w-full">
                        <img id="cbir-preview-img" src="" alt="Preview" class="w-full max-h-64 object-contain rounded-xl">
                    </div>
                    {{-- Placeholder --}}
                    <div id="cbir-placeholder" class="flex flex-col items-center gap-3 text-center">
                        <div class="w-16 h-16 bg-primary/10 border border-primary/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="bi bi-image-fill text-primary text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-semibold text-sm">Klik atau seret gambar ke sini</p>
                            <p class="text-gray-500 text-xs mt-1">JPG, PNG, WebP · Maks 10MB</p>
                        </div>
                    </div>
                    <input type="file" id="cbir-input" accept="image/*" class="hidden">
                </label>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button id="cbir-search-btn"
                            disabled
                            class="flex-1 bg-primary hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-black font-bold py-3 px-6 rounded-xl transition-colors shadow-lg flex items-center justify-center gap-2">
                        <i class="bi bi-search"></i>
                        <span id="cbir-search-btn-text">Cari Batik Serupa</span>
                    </button>
                    <button id="cbir-clear-btn"
                            class="hidden border border-gray-700 hover:border-gray-500 text-gray-400 hover:text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Error message --}}
                <div id="cbir-error" class="hidden bg-red-900/30 border border-red-700/50 text-red-300 px-4 py-3 rounded-xl text-sm"></div>
            </div>

            {{-- Right: Info / Stats --}}
            <div class="space-y-4">
                <h2 class="text-xl font-bold text-white">Informasi Pencarian</h2>
                <div class="bg-gray-900/80 border border-gray-800 rounded-2xl p-5 space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-amber-500/10 border border-amber-500/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="bi bi-cpu text-amber-400 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Model: ConvNeXt Small</p>
                            <p class="text-gray-500 text-xs mt-0.5">Feature extraction 768 dimensi + KMeans clustering</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-500/10 border border-blue-500/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="bi bi-bar-chart text-blue-400 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Metrik: Cosine Similarity</p>
                            <p class="text-gray-500 text-xs mt-0.5">Mengembalikan top-10 batik paling mirip dalam kluster</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="bi bi-collection text-green-400 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Database Terindeks</p>
                            <p class="text-gray-500 text-xs mt-0.5">Seluruh koleksi batik Malang pada bucket S3</p>
                        </div>
                    </div>
                </div>

                {{-- Cluster badge --}}
                <div id="cbir-cluster-info" class="hidden bg-amber-950/30 border border-amber-800/40 rounded-xl px-4 py-3 flex items-center gap-3">
                    <i class="bi bi-diagram-2 text-amber-400 text-lg"></i>
                    <div>
                        <p class="text-amber-300 text-xs font-semibold">Kluster Visual</p>
                        <p id="cbir-cluster-value" class="text-white text-sm font-bold"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Results Section (hidden by default) --}}
    <div id="cbir-results-section" class="hidden space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white">Hasil Pencarian</h2>
                <p class="text-gray-500 text-xs mt-0.5">Diurutkan berdasarkan kemiripan visual (cosine similarity)</p>
            </div>
            <span id="cbir-result-count" class="bg-amber-900/40 border border-amber-700/50 text-amber-400 text-xs font-bold px-3 py-1.5 rounded-full"></span>
        </div>

        {{-- Grid hasil --}}
        <div id="cbir-results-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3"></div>

        {{-- Empty state --}}
        <div id="cbir-no-results" class="hidden py-16 text-center border-2 border-dashed border-gray-800 rounded-3xl">
            <i class="bi bi-search text-4xl text-gray-700 block mb-3"></i>
            <p class="text-gray-500">Tidak ada batik serupa ditemukan dalam kluster ini.</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(() => {
'use strict';

const inputEl    = document.getElementById('cbir-input');
const dropzone   = document.getElementById('cbir-dropzone');
const previewWrap= document.getElementById('cbir-preview-wrap');
const previewImg = document.getElementById('cbir-preview-img');
const placeholder= document.getElementById('cbir-placeholder');
const searchBtn  = document.getElementById('cbir-search-btn');
const searchText = document.getElementById('cbir-search-btn-text');
const clearBtn   = document.getElementById('cbir-clear-btn');
const errorEl    = document.getElementById('cbir-error');
const resultsSection = document.getElementById('cbir-results-section');
const resultsGrid    = document.getElementById('cbir-results-grid');
const noResults      = document.getElementById('cbir-no-results');
const resultCount    = document.getElementById('cbir-result-count');
const clusterInfo    = document.getElementById('cbir-cluster-info');
const clusterValue   = document.getElementById('cbir-cluster-value');

let selectedFile = null;

// ── File selection ───────────────────────────────────────────────────────────
function setFile(file) {
    if (!file || !file.type.startsWith('image/')) {
        showError('File harus berupa gambar (JPG, PNG, WebP).');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        showError('Ukuran file melebihi 10MB.');
        return;
    }
    selectedFile = file;
    hideError();

    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        previewWrap.classList.remove('hidden');
        placeholder.classList.add('hidden');
        searchBtn.disabled = false;
        clearBtn.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

inputEl.addEventListener('change', () => setFile(inputEl.files[0]));

// Drag & drop
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('border-primary/60', 'bg-primary/5'); });
dropzone.addEventListener('dragleave',  () => dropzone.classList.remove('border-primary/60', 'bg-primary/5'));
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('border-primary/60', 'bg-primary/5');
    setFile(e.dataTransfer.files[0]);
});

// Clear
clearBtn.addEventListener('click', e => {
    e.preventDefault();
    selectedFile = null;
    inputEl.value = '';
    previewImg.src = '';
    previewWrap.classList.add('hidden');
    placeholder.classList.remove('hidden');
    searchBtn.disabled = true;
    clearBtn.classList.add('hidden');
    resultsSection.classList.add('hidden');
    clusterInfo.classList.add('hidden');
    hideError();
});

// ── Search ───────────────────────────────────────────────────────────────────
searchBtn.addEventListener('click', async () => {
    if (!selectedFile) return;

    searchBtn.disabled = true;
    searchText.textContent = 'Mencari...';
    hideError();
    resultsSection.classList.add('hidden');
    clusterInfo.classList.add('hidden');

    const formData = new FormData();
    formData.append('image', selectedFile);
    formData.append('_token', '{{ csrf_token() }}');

    try {
        const res  = await fetch('{{ route('api.search.batik') }}', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();

        if (!data.success) {
            showError(data.message ?? 'Pencarian gagal. Pastikan Batik Service berjalan.');
            return;
        }

        renderResults(data.results ?? [], data.cluster_id);

    } catch (err) {
        showError('Gagal menghubungi server. Periksa koneksi dan pastikan Batik Service aktif.');
        console.error(err);
    } finally {
        searchBtn.disabled = false;
        searchText.textContent = 'Cari Batik Serupa';
    }
});

// ── Render ───────────────────────────────────────────────────────────────────
function renderResults(items, clusterId) {
    resultsSection.classList.remove('hidden');

    if (clusterId !== null && clusterId !== undefined) {
        clusterInfo.classList.remove('hidden');
        clusterValue.textContent = `Kluster #${clusterId}`;
    }

    resultCount.textContent = `${items.length} hasil`;

    if (!items.length) {
        resultsGrid.innerHTML = '';
        noResults.classList.remove('hidden');
        return;
    }

    noResults.classList.add('hidden');

    resultsGrid.innerHTML = items.map(item => {
        const simBadge = `<span class="text-amber-400">${item.similarity ?? 0}%</span>`;
        const linkOpen  = item.galeri_url ? `<a href="${item.galeri_url}" title="Lihat di Galeri">` : `<div>`;
        const linkClose = item.galeri_url ? `</a>` : `</div>`;
        const galeriIcon = item.galeri_url
            ? `<span class="absolute top-1.5 right-1.5 bg-black/60 backdrop-blur-sm text-white text-[9px] px-1.5 py-0.5 rounded-full flex items-center gap-1"><i class="bi bi-box-arrow-up-right text-[8px]"></i> Galeri</span>`
            : '';

        return `
        <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden shadow-md group hover:border-primary/50 transition-colors">
            ${linkOpen}
            <div class="relative aspect-square overflow-hidden bg-gray-900">
                <img src="${item.image_url}" loading="lazy"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     onerror="this.parentElement.innerHTML='<div class=\'flex items-center justify-center w-full h-full text-gray-700\'><i class=\'bi bi-image text-2xl\'></i></div>'">
                ${galeriIcon}
            </div>
            <div class="p-2 bg-gray-900/80">
                <p class="text-white text-xs font-bold truncate" title="${item.label ?? ''}">${item.label || 'Batik Serupa'}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">Kemiripan: ${simBadge}</p>
            </div>
            ${linkClose}
        </div>`;
    }).join('');

    resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function showError(msg) { errorEl.textContent = msg; errorEl.classList.remove('hidden'); }
function hideError()    { errorEl.classList.add('hidden'); }

})();
</script>
@endpush
