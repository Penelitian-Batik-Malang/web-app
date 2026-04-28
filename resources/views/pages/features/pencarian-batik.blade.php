@extends('layouts.layout')
@section('title', 'Pencarian Batik Serupa — CBIR')

@section('content')
<div class="max-w-5xl mx-auto space-y-10">

    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/30 text-primary text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-search"></i> Content-Based Image Retrieval
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Pencarian <span class="text-primary">Batik</span> Serupa
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Unggah foto kain batik sebagai referensi — AI akan menemukan batik paling mirip dari koleksi kami.
        </p>
    </div>

    <div class="bg-gradient-to-br from-gray-900 via-amber-950/20 to-gray-900 border border-gray-800 rounded-3xl p-8 shadow-2xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

            <div class="space-y-4">
                <h2 class="text-xl font-bold text-white">Unggah Gambar Batik</h2>
                <label for="cbir-input" id="cbir-dropzone"
                       class="flex flex-col items-center justify-center gap-4 border-2 border-dashed border-gray-700 rounded-2xl p-8 cursor-pointer hover:border-primary/60 hover:bg-primary/5 transition-all min-h-52 relative group">
                    <div id="cbir-preview-wrap" class="hidden w-full">
                        <img id="cbir-preview-img" src="" alt="Preview" class="w-full max-h-64 object-contain rounded-xl">
                    </div>
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
                <div class="flex gap-3">
                    <button id="cbir-search-btn" disabled
                            class="flex-1 bg-primary hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-black font-bold py-3 px-6 rounded-xl transition-colors shadow-lg flex items-center justify-center gap-2">
                        <i class="bi bi-search"></i>
                        <span id="cbir-search-btn-text">Cari Batik Serupa</span>
                    </button>
                    <button id="cbir-clear-btn" class="hidden border border-gray-700 hover:border-gray-500 text-gray-400 hover:text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div id="cbir-error" class="hidden bg-red-900/30 border border-red-700/50 text-red-300 px-4 py-3 rounded-xl text-sm"></div>
            </div>

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
                            <p class="text-gray-500 text-xs mt-0.5">Top-10 batik paling mirip dari koleksi galeri</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="cbir-results-section" class="hidden space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white">Hasil Pencarian</h2>
                <p class="text-gray-500 text-xs mt-0.5">Diurutkan berdasarkan kemiripan visual</p>
            </div>
            <span id="cbir-result-count" class="bg-amber-900/40 border border-amber-700/50 text-amber-400 text-xs font-bold px-3 py-1.5 rounded-full"></span>
        </div>
        <div id="cbir-results-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3"></div>
        <p id="cbir-no-results" class="hidden py-16 text-center border-2 border-dashed border-gray-800 rounded-3xl text-gray-500">
            Tidak ada batik serupa ditemukan.
        </p>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function() {
'use strict';

var inputEl     = document.getElementById('cbir-input');
var dropzone    = document.getElementById('cbir-dropzone');
var previewWrap = document.getElementById('cbir-preview-wrap');
var previewImg  = document.getElementById('cbir-preview-img');
var placeholder = document.getElementById('cbir-placeholder');
var searchBtn   = document.getElementById('cbir-search-btn');
var searchText  = document.getElementById('cbir-search-btn-text');
var clearBtn    = document.getElementById('cbir-clear-btn');
var errorEl     = document.getElementById('cbir-error');
var resultsSection  = document.getElementById('cbir-results-section');
var resultsGrid     = document.getElementById('cbir-results-grid');
var noResults       = document.getElementById('cbir-no-results');
var resultCount     = document.getElementById('cbir-result-count');

var selectedFile = null;

function setFile(file) {
    if (!file || !file.type.startsWith('image/')) { showError('File harus berupa gambar (JPG, PNG, WebP).'); return; }
    if (file.size > 10 * 1024 * 1024) { showError('Ukuran file melebihi 10MB.'); return; }
    selectedFile = file;
    hideError();
    var reader = new FileReader();
    reader.onload = function(e) {
        previewImg.src = e.target.result;
        previewWrap.classList.remove('hidden');
        placeholder.classList.add('hidden');
        searchBtn.disabled = false;
        clearBtn.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

inputEl.addEventListener('change', function() { setFile(inputEl.files[0]); });
dropzone.addEventListener('dragover', function(e) { e.preventDefault(); dropzone.classList.add('border-primary/60', 'bg-primary/5'); });
dropzone.addEventListener('dragleave', function() { dropzone.classList.remove('border-primary/60', 'bg-primary/5'); });
dropzone.addEventListener('drop', function(e) { e.preventDefault(); dropzone.classList.remove('border-primary/60', 'bg-primary/5'); setFile(e.dataTransfer.files[0]); });

clearBtn.addEventListener('click', function(e) {
    e.preventDefault(); selectedFile = null; inputEl.value = ''; previewImg.src = '';
    previewWrap.classList.add('hidden'); placeholder.classList.remove('hidden');
    searchBtn.disabled = true; clearBtn.classList.add('hidden');
    resultsSection.classList.add('hidden'); hideError();
});

searchBtn.addEventListener('click', function() {
    if (!selectedFile) return;
    searchBtn.disabled = true;
    searchText.textContent = 'Mencari...';
    hideError();
    resultsSection.classList.add('hidden');

    var fd = new FormData();
    fd.append('image', selectedFile);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("api.search.batik") }}', {
        method: 'POST',
        body: fd,
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (!data.success) { showError(data.message || 'Pencarian gagal.'); return; }
        renderResults(data.results || [], data.cluster_id);
    })
    .catch(function(err) {
        showError('Gagal menghubungi server.');
        console.error(err);
    })
    .finally(function() {
        searchBtn.disabled = false;
        searchText.textContent = 'Cari Batik Serupa';
    });
});

function renderResults(items, clusterId) {
    resultsSection.classList.remove('hidden');
    resultCount.textContent = items.length + ' hasil';

    if (!items.length) { resultsGrid.innerHTML = ''; noResults.classList.remove('hidden'); return; }
    noResults.classList.add('hidden');

    resultsGrid.innerHTML = '';
    items.forEach(function(item) {
        var card = document.createElement('div');
        card.className = 'bg-gray-800 border border-gray-700 rounded-xl overflow-hidden shadow-md group hover:border-primary/50 transition-colors' + (item.galeri_url ? ' cursor-pointer' : '');

        var imgWrap = document.createElement('div');
        imgWrap.className = 'relative aspect-square overflow-hidden bg-gray-900';

        var img = document.createElement('img');
        img.src = item.image_url;
        img.loading = 'lazy';
        img.className = 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500';
        img.addEventListener('error', function() {
            if (item.fallback_url && img.src !== item.fallback_url) {
                img.src = item.fallback_url;
            } else {
                imgWrap.innerHTML = '<div class="w-full h-full flex items-center justify-center"><i class="bi bi-image text-gray-700 text-2xl"></i></div>';
            }
        });
        imgWrap.appendChild(img);

        if (item.galeri_url) {
            var badge = document.createElement('span');
            badge.className = 'absolute top-1.5 right-1.5 bg-black/60 text-white text-xs px-1.5 py-0.5 rounded-full';
            badge.innerHTML = '<i class="bi bi-box-arrow-up-right text-xs"></i> Galeri';
            imgWrap.appendChild(badge);
        }

        var info = document.createElement('div');
        info.className = 'p-2 bg-gray-900/80';
        info.innerHTML = '<p class="text-white text-xs font-bold truncate">' + (item.label || 'Batik Serupa') + '</p>'
            + '<p class="text-amber-400 text-xs mt-0.5">' + (item.similarity || 0) + '% mirip</p>';

        card.appendChild(imgWrap);
        card.appendChild(info);

        if (item.galeri_url) {
            card.addEventListener('click', function() { window.open(item.galeri_url, '_blank'); });
        }

        resultsGrid.appendChild(card);
    });

    resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function showError(msg) { errorEl.textContent = msg; errorEl.classList.remove('hidden'); }
function hideError()    { errorEl.classList.add('hidden'); }

}());
</script>
@endpush
