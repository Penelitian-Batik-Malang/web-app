@extends('pages.features.shared.batik-app', [
    'title' => 'Rekomendasi Batik',
    'description' => 'Unggah foto fashion, kami akan mengekstrak warna dominan pakaian dan merekomendasikan motif batik dengan palet warna senada.',
    'mode' => 'rekomendasi'
])

{{-- ─── PHASE: CBIR Recommendation Result ─────────────────────────────────── --}}
@section('phase_cbir')
<div id="phase-cbir-result" class="hidden">
    {{-- 4-col grid: foto | info+warna | rekomendasi batik --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

        {{-- Col 1: Foto Pakaian --}}
        <div class="lg:col-span-1">
            <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4 h-full flex flex-col gap-4">
                <div>
                    <p class="text-xs text-amber-400 font-semibold uppercase tracking-widest mb-1">Foto Pakaian</p>
                    <div class="rounded-xl overflow-hidden bg-gray-800 flex items-center justify-center" style="min-height:200px;max-height:420px">
                        <img id="cbir-fashion-preview" src="" alt="Fashion Preview" class="w-full h-full object-contain" style="max-height:420px">
                    </div>
                </div>
                <div class="mt-auto flex flex-col gap-2 pt-2 border-t border-gray-800">
                    <button id="cbir-proceed-btn"
                        class="w-full bg-primary hover:bg-amber-600 text-black font-bold py-2.5 px-4 rounded-xl transition-colors shadow-lg flex items-center justify-center gap-2">
                        <i class="bi bi-brush-fill"></i>
                        Terapkan Rekomendasi
                    </button>
                    <button id="cbir-back-btn"
                        class="w-full border border-gray-600 hover:border-gray-400 text-gray-300 font-semibold py-2 px-4 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm">
                        <i class="bi bi-arrow-left"></i> Upload Ulang
                    </button>
                </div>
            </div>
        </div>

        {{-- Col 2: Info Ekstraksi Warna + Palette LAB --}}
        <div class="lg:col-span-1">
            <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4 h-full flex flex-col gap-4">

                {{-- Info metode ekstraksi --}}
                <div id="cbir-extraction-info" class="hidden">
                    <p class="text-xs text-amber-400 font-semibold uppercase tracking-widest mb-2">Metode Ekstraksi</p>
                    <div class="space-y-1.5">
                        {{-- Algoritma & Ruang Warna --}}
                        <div class="bg-gray-800/60 border border-gray-700/50 rounded-xl p-3 grid grid-cols-2 gap-2 text-[10px]">
                            <div>
                                <p class="text-gray-500 mb-0.5">Algoritma</p>
                                <p class="text-white font-semibold">K-Means</p>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-0.5">Ruang Warna</p>
                                <p class="text-white font-semibold">CIELAB</p>
                            </div>
                        </div>

                        {{-- Garment Comparison: heading + rows hidden by default, JS yang show --}}
                        <p id="cbir-garment-heading" class="hidden text-[10px] text-gray-500 font-semibold uppercase tracking-wider pt-0.5">Seleksi Garment</p>
                        {{-- Outer row --}}
                        <div id="cbir-garment-outer" class="hidden rounded-xl border p-2.5 text-[10px] transition-all">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-1.5">
                                    <span id="cbir-outer-badge" class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full border">OUTER</span>
                                    <span id="cbir-outer-selected-icon" class="hidden text-amber-400 text-[10px]">✓ Dipilih</span>
                                </div>
                                <span id="cbir-outer-pixels" class="font-mono text-gray-400"></span>
                            </div>
                            <p id="cbir-outer-labels" class="text-gray-300 font-semibold leading-tight"></p>
                        </div>
                        {{-- Inner row --}}
                        <div id="cbir-garment-inner" class="hidden rounded-xl border p-2.5 text-[10px] transition-all">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-1.5">
                                    <span id="cbir-inner-badge" class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full border">INNER</span>
                                    <span id="cbir-inner-selected-icon" class="hidden text-amber-400 text-[10px]">✓ Dipilih</span>
                                </div>
                                <span id="cbir-inner-pixels" class="font-mono text-gray-400"></span>
                            </div>
                            <p id="cbir-inner-labels" class="text-gray-300 font-semibold leading-tight"></p>
                        </div>
                    </div>
                </div>

                {{-- Palette warna dominan + nilai LAB --}}
                <div id="cbir-palette-wrap" class="hidden flex-1">
                    <p class="text-xs text-amber-400 font-semibold uppercase tracking-widest mb-2">
                        Warna Dominan
                        <span class="text-gray-600 normal-case font-normal ml-1">(L* a* b*)</span>
                    </p>
                    <div id="cbir-palette" class="flex flex-col gap-2"></div>
                </div>

                {{-- Placeholder jika data belum ada --}}
                <div id="cbir-analysis-placeholder" class="flex-1 flex flex-col items-center justify-center text-center py-6">
                    <div class="w-10 h-10 rounded-full bg-gray-800 border border-gray-700 flex items-center justify-center mb-2">
                        <i class="bi bi-palette text-amber-500 text-lg"></i>
                    </div>
                    <p class="text-gray-600 text-xs">Data analisis warna<br>akan tampil di sini</p>
                </div>

            </div>
        </div>

        {{-- Col 3–4: Rekomendasi Batik (grid) --}}
        <div class="lg:col-span-2">
            <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4 flex flex-col h-full">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-white font-bold text-base">Rekomendasi Batik</p>
                        <p class="text-gray-400 text-xs mt-0.5">Diurutkan berdasarkan kemiripan warna dominan</p>
                    </div>
                    <span id="cbir-count-badge" class="bg-amber-900/40 border border-amber-700/50 text-amber-400 text-xs font-bold px-3 py-1 rounded-full"></span>
                </div>

                <div id="cbir-grid" class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 gap-2.5 overflow-y-auto flex-1" style="max-height:480px">
                    {{-- populated by JS --}}
                </div>

                <p id="cbir-no-data" class="hidden text-gray-500 text-sm text-center py-10">
                    <i class="bi bi-exclamation-circle text-2xl block mb-2"></i>
                    Data rekomendasi tidak tersedia. Pastikan database batik telah dikonfigurasi.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ─── CUSTOM PANEL: Shared Batik Selector with Canvas ─────────── --}}
@section('custom_panel')
    @include('pages.features.shared.batik-panel', ['mode' => 'rekomendasi'])
@endsection

{{-- ─── CUSTOM SCRIPTS ─────────────────────────────────────────────────────── --}}
@section('custom_scripts')
<script>
(() => {
'use strict';

// ─── Helper: render a CBIR grid into a container ─────────────────────────────
function renderCbirGrid(container, items, onSelect) {
    container.innerHTML = '';
    if (!items || !items.length) return;
    items.forEach(item => {
        const galeriIcon = item.galeri_url
            ? `<span class="absolute top-1 right-1 bg-black/60 backdrop-blur-sm text-white text-[8px] px-1 py-0.5 rounded-full z-10"><i class="bi bi-box-arrow-up-right"></i></span>`
            : '';
        const thumbSrc = item.thumbnail_b64 || item.image_url || '';

        const div = document.createElement('div');
        div.className = 'cbir-grid-item relative bg-gray-800 border border-gray-700 rounded-xl overflow-hidden text-left flex flex-col hover:border-primary/50 transition-colors';
        div.dataset.filename = item.filename || item.image_url || '';

        div.innerHTML = `
            <div class="relative w-full overflow-hidden bg-gray-900" style="padding-bottom:100%">
                ${thumbSrc
                    ? `<img src="${thumbSrc}" class="absolute inset-0 w-full h-full object-cover" loading="lazy">`
                    : `<div class="absolute inset-0 flex items-center justify-center text-gray-600"><i class="bi bi-image text-2xl"></i></div>`
                }
                ${galeriIcon}
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent px-1.5 py-1">
                    <p class="text-white text-[9px] font-semibold leading-tight truncate">${item.label}</p>
                </div>
            </div>
            <div class="p-1.5 w-full">
                <p class="text-[10px] font-semibold text-gray-300 truncate leading-tight" title="${item.label}">${item.label}</p>
                <p class="text-[9px] text-gray-600 mt-0.5">#${item.rank} · ${item.jarak !== undefined ? item.jarak.toFixed(2) : ''}</p>
            </div>
        `;

        // Klik: jika ada galeri_url, buka di tab baru; jika ada onSelect, panggil callback
        div.addEventListener('click', (e) => {
            if (item.galeri_url) {
                window.open(item.galeri_url, '_blank');
            }
            if (onSelect) onSelect(item);
        });

        container.appendChild(div);
    });
}

// ─── PHASE CBIR RESULT: populate on show ────────────────────────────────────
window.showCbirPhase = function(cbir) {
    const grid        = document.getElementById('cbir-grid');
    const noData      = document.getElementById('cbir-no-data');
    const badge       = document.getElementById('cbir-count-badge');
    const preview     = document.getElementById('cbir-fashion-preview');
    const palette     = document.getElementById('cbir-palette');
    const palWrap     = document.getElementById('cbir-palette-wrap');
    const extInfo     = document.getElementById('cbir-extraction-info');
    const placeholder = document.getElementById('cbir-analysis-placeholder');

    // Label terjemahan untuk label names garment
    const GARMENT_ID_LABELS = {
        'shirt': 'Kemeja', 't-shirt': 'Kaos', 'sweater': 'Sweater',
        'cardigan': 'Kardigan', 'jacket': 'Jaket', 'vest': 'Rompi',
        'dress': 'Gaun', 'jumpsuit': 'Jumpsuit', 'suit': 'Setelan', 'coat': 'Mantel',
    };
    const translateLabels = (labels) =>
        (labels || []).map(l => GARMENT_ID_LABELS[l] || l).join(', ') || '—';

    // Set fashion image preview
    if (state.fashionFile) {
        preview.src = URL.createObjectURL(state.fashionFile);
    }

    const items = cbir?.top_15 || [];
    if (badge) badge.textContent = `${items.length} rekomendasi`;

    let hasAnalysisData = false;

    // ── Info ekstraksi warna: garment_selection ──────────────────────────────
    const gs = cbir?.garment_selection;
    if (gs && extInfo) {
        const selected    = gs.selected_category;
        const outerPx     = gs.outer_pixels  || 0;
        const innerPx     = gs.inner_pixels  || 0;
        const outerLabels = gs.outer_labels  || [];
        const innerLabels = gs.inner_labels  || [];
        const hasAnyLabel = outerLabels.length > 0 || innerLabels.length > 0;

        // Helper: tampilkan / sembunyikan baris garment
        const applyRow = (rowId, badgeId, iconId, pixId, labelsId, labels, pixels, isSelected) => {
            const rowEl    = document.getElementById(rowId);
            const badgeEl  = document.getElementById(badgeId);
            const iconEl   = document.getElementById(iconId);
            const pixEl    = document.getElementById(pixId);
            const labelsEl = document.getElementById(labelsId);
            if (!rowEl) return;

            if (!labels.length) {
                // Sembunyikan baris jika tidak ada garment kategori ini
                rowEl.classList.add('hidden');
                return;
            }

            rowEl.classList.remove('hidden');
            pixEl.textContent    = pixels.toLocaleString('id-ID') + ' px';
            labelsEl.textContent = labels.map(l => GARMENT_ID_LABELS[l] || l).join(', ');

            if (isSelected) {
                rowEl.className   = 'rounded-xl border p-2.5 text-[10px] transition-all border-amber-600/60 bg-amber-950/30 shadow-sm';
                badgeEl.className = 'text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full border border-amber-500 text-amber-400 bg-amber-950/50';
                iconEl.classList.remove('hidden');
            } else {
                rowEl.className   = 'rounded-xl border p-2.5 text-[10px] transition-all border-gray-700/50 bg-gray-800/40';
                badgeEl.className = 'text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full border border-gray-600 text-gray-500';
                iconEl.classList.add('hidden');
            }
        };

        // Heading hanya muncul jika ada label data
        const garmentHeading = document.getElementById('cbir-garment-heading');
        if (garmentHeading) garmentHeading.classList.toggle('hidden', !hasAnyLabel);

        applyRow('cbir-garment-outer', 'cbir-outer-badge', 'cbir-outer-selected-icon',
                 'cbir-outer-pixels', 'cbir-outer-labels', outerLabels, outerPx, selected === 'outer');
        applyRow('cbir-garment-inner', 'cbir-inner-badge', 'cbir-inner-selected-icon',
                 'cbir-inner-pixels', 'cbir-inner-labels', innerLabels, innerPx, selected === 'inner');

        extInfo.classList.remove('hidden');
        hasAnalysisData = true;
    }

    // ── Palette dari query_centroids (LAB → visual swatch + angka) ───────────
    if (cbir?.query_centroids?.length && palette) {
        palette.innerHTML = '';
        cbir.query_centroids.forEach((c, i) => {
            const hue = Math.round(Math.atan2(c[2], c[1]) * (180 / Math.PI) + 360) % 360;
            const sat = Math.min(100, Math.round(Math.sqrt(c[1]*c[1]+c[2]*c[2]) * 2.5));
            const lig = Math.min(90, Math.max(20, Math.round(c[0] * 0.8)));

            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 bg-gray-800/60 border border-gray-700/50 rounded-xl px-3 py-2';
            row.innerHTML = `
                <div class="w-9 h-9 rounded-lg border-2 border-white/20 shadow-lg shrink-0"
                     style="background:hsl(${hue},${sat}%,${lig}%)"
                     title="Warna dominan ${i+1}"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-[10px] font-semibold mb-0.5">Warna Dominan ${i+1}</p>
                    <div class="flex gap-2 flex-wrap">
                        <span class="bg-gray-900/80 rounded px-1.5 py-0.5 text-[9px] font-mono text-gray-300">
                            L* <span class="text-amber-400 font-bold">${c[0].toFixed(1)}</span>
                        </span>
                        <span class="bg-gray-900/80 rounded px-1.5 py-0.5 text-[9px] font-mono text-gray-300">
                            a* <span class="text-green-400 font-bold">${c[1].toFixed(1)}</span>
                        </span>
                        <span class="bg-gray-900/80 rounded px-1.5 py-0.5 text-[9px] font-mono text-gray-300">
                            b* <span class="text-blue-400 font-bold">${c[2].toFixed(1)}</span>
                        </span>
                    </div>
                </div>
            `;
            palette.appendChild(row);
        });
        palWrap.classList.remove('hidden');
        hasAnalysisData = true;
    }

    // Sembunyikan placeholder jika ada data, tampilkan jika tidak ada
    if (placeholder) {
        placeholder.classList.toggle('hidden', hasAnalysisData);
    }

    if (!items.length) {
        grid.classList.add('hidden');
        noData.classList.remove('hidden');
    } else {
        noData.classList.add('hidden');
        grid.classList.remove('hidden');
        renderCbirGrid(grid, items, () => {}); // selection not needed here (just preview)
    }
};

// ─── "Terapkan" button: proceed to workspace ─────────────────────────────────
document.getElementById('cbir-proceed-btn')?.addEventListener('click', () => {
    window.setPhase('workspace');
});

// ─── "Upload Ulang" button ────────────────────────────────────────────────────
document.getElementById('cbir-back-btn')?.addEventListener('click', () => {
    window.setPhase('upload');
    if (window.state) {
        state.sessionId = null;
        state.partsList = [];
        state.blendedKeys.clear();
        state.appliedBatiks = [];
        state.hoveredKey = null;
        window.cbirData = null;
    }
});

// ─── Override openBatikPanel for rekomendasi mode ────────────────────────────
window.openBatikPanelFunc = function(part) {
    const panelName    = document.getElementById('panel-part-name');
    const panelColor   = document.getElementById('panel-part-color');
    const panelBbox    = document.getElementById('panel-bbox-info');
    const panelGrid    = document.getElementById('panel-batik-gallery');
    const panelStatus  = document.getElementById('panel-status');

    window.state.selectedPart   = part;
    window.state.batikTransform = { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 };
    window.state.batikImg       = null;

    const color = window.PART_COLORS[part.partName] || [200, 200, 200];
    panelColor.style.background = `rgba(${color[0]},${color[1]},${color[2]},0.9)`;
    panelName.textContent = part.label + (part.index > 0 ? ` ${part.index + 1}` : '');
    panelBbox.textContent = `Area: ${part.bbox.w}×${part.bbox.h}px · (${part.bbox.x}, ${part.bbox.y})`;

    panelStatus.classList.add('hidden');
    
    const batikCanvas = document.getElementById('batik-crop-canvas');
    const CANVAS_SIZE = 320;
    batikCanvas.width  = CANVAS_SIZE;
    batikCanvas.height = CANVAS_SIZE;
    const ratio = part.bbox.w / (part.bbox.h || 1);
    const maxBox = CANVAS_SIZE * 0.68;
    if (ratio >= 1) {
        window.state.cropBoxW = Math.round(Math.min(maxBox, part.bbox.w));
        window.state.cropBoxH = Math.round(window.state.cropBoxW / ratio);
    } else {
        window.state.cropBoxH = Math.round(Math.min(maxBox, part.bbox.h));
        window.state.cropBoxW = Math.round(window.state.cropBoxH * ratio);
    }

    const cbir  = window.cbirData || {};
    const items = cbir.top_15 || [];
    
    panelGrid.innerHTML = '';
    if (!items.length) {
        panelGrid.innerHTML = '<p class="col-span-3 text-gray-500 text-sm text-center py-6">Tidak ada data rekomendasi.</p>';
    } else {
        items.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'panel-sample-batik border border-gray-700 rounded-lg overflow-hidden hover:border-primary transition-colors text-left';
            btn.dataset.url = item.thumbnail_b64 || '';
            btn.dataset.name = item.label;
            btn.innerHTML = `
                <img src="${item.thumbnail_b64}" class="w-full h-16 object-cover" alt="${item.label}">
                <p class="text-[10px] text-primary truncate px-1 py-0.5">${item.label}</p>
                <p class="text-[9px] text-gray-500 px-1 pb-0.5 mt-[-2px]">#${item.rank} · ${item.jarak.toFixed(2)}</p>
            `;
            btn.addEventListener('click', async () => {
                document.querySelectorAll('.panel-sample-batik').forEach(e => e.classList.remove('border-primary'));
                btn.classList.add('border-primary');
                
                document.getElementById('panel-status').classList.add('hidden');
                
                const titleName = item.label.replace(/\b\w/g, l => l.toUpperCase());
                
                if (window.setBatikImage) {
                    await window.setBatikImage(null, item.thumbnail_b64, 'Rekomendasi: ' + titleName);
                }
            });
            panelGrid.appendChild(btn);
        });
    }

    if (window.drawBatikCanvas) window.drawBatikCanvas();

    const batikPanel = document.getElementById('batik-panel');
    batikPanel.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

})();
</script>
@endsection
