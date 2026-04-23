@push('scripts')
<script>
(() => {
'use strict';

// ─── Constants ───────────────────────────────────────────────────────────────
const PART_COLORS = {
    'shirt':     [128, 128, 128],
    't-shirt':   [100, 150, 200],
    'sweater':   [200, 150, 100],
    'cardigan':  [150, 200, 100],
    'jacket':    [200, 100, 150],
    'vest':      [150, 100, 200],
    'dress':     [100, 200, 150],
    'jumpsuit':  [250, 150,  50],
    'suit':      [ 50, 150, 250],
    'coat':      [150, 250,  50],
    'sleeve':    [255,  80,  80],
    'collar':    [ 80, 160, 255],
    'lapel':     [ 80, 200,  80],
    'hood':      [255, 180,  50],
    'pocket':    [180,  80, 255],
    'neckline':  [255, 255,  80],
    'epaulette': [ 80, 220, 220],
};
window.PART_COLORS = PART_COLORS;
const PART_LABELS = {
    'shirt': 'Kemeja', 't-shirt': 'Kaos', 'sweater': 'Sweater', 'cardigan': 'Kardigan',
    'jacket': 'Jaket', 'vest': 'Rompi', 'dress': 'Gaun', 'jumpsuit': 'Jumpsuit',
    'suit': 'Setelan', 'coat': 'Mantel', 'sleeve': 'Lengan', 'collar': 'Kerah',
    'lapel': 'Lapel', 'hood': 'Tudung', 'pocket': 'Saku', 'neckline': 'Leher',
    'epaulette': 'Epaulet',
};

// ─── State ───────────────────────────────────────────────────────────────────
const state = {
    fashionFile: null,
    sessionId: null,
    imageSize: null,
    partsList: [],       // [{key, partName, index, label, bbox, maskImg, area, score}]
    hoveredKey: null,
    blendedKeys: new Set(),
    appliedBatiks: [],
    currentBatikInfo: null,
    originalFashionImageSrc: null,
    fashionImage: null,  // current HTMLImageElement shown on canvas

    selectedPart: null,
    batikImg: null,
    batikTransform: { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 },
    cropBoxW: 200,
    cropBoxH: 200,

    isDragging: false,
    dragStart: { x: 0, y: 0 },
    dragStartOffset: { x: 0, y: 0 },

    webcamStream: null,
    webcamTarget: '',
};
window.state = state;

// ─── DOM refs ────────────────────────────────────────────────────────────────
const $ = id => document.getElementById(id);
const fashionInput       = $('fashion-input');
const fashionCameraInput = $('fashion-camera-input');
const fashionUploadBtn   = $('fashion-upload-btn');
const fashionCameraBtn   = $('fashion-camera-btn');
const fashionPreview     = $('fashion-preview');
const fashionPlaceholder = $('fashion-placeholder');
const analyzeBtn         = $('analyze-btn');
const uploadStatus       = $('upload-status');

const phaseUpload    = $('phase-upload');
const phaseLoading   = $('phase-loading');
const phaseWorkspace = $('phase-workspace');

const fashionCanvas  = $('fashion-canvas');
const canvasCtx      = fashionCanvas.getContext('2d');
const partsListEl    = $('parts-list');
const workspaceStatus= $('workspace-status');
const resetBtn       = $('reset-btn');
const finishBtn      = $('finish-btn');
const backBtn        = $('back-to-upload-btn');

const phaseResult    = $('phase-result');
const resultOrigImg  = $('result-original-img');
const resultFinalImg = $('result-final-img');
const resultPartsList= $('result-parts-list');
const resultSaveBtn  = $('result-save-btn');
const resultBackBtn  = $('result-back-btn');

const batikPanel     = $('batik-panel');
const panelPartColor = $('panel-part-color');
const panelPartName  = $('panel-part-name');
const panelBboxInfo  = $('panel-bbox-info');
const panelCloseBtn  = $('panel-close-btn');
const panelCancelBtn = $('panel-cancel-btn');
const panelUploadBtn = $('panel-upload-btn');
const panelBatikInput= $('panel-batik-input');
const panelSearch    = $('panel-search');
const panelStatus    = $('panel-status');
const applyBlendBtn  = $('apply-blend-btn');

const batikCanvas    = $('batik-crop-canvas');
const batikCtx       = batikCanvas ? batikCanvas.getContext('2d') : null;
const zoomInBtn      = $('zoom-in-btn');
const zoomOutBtn     = $('zoom-out-btn');
const rotateCwBtn    = $('rotate-cw-btn');
const rotateCcwBtn   = $('rotate-ccw-btn');
const batikResetBtn  = $('batik-reset-transform');

const webcamModal    = $('webcam-modal');
const webcamVideo    = $('webcam-video');
const webcamCanvasEl = $('webcam-canvas');
const webcamCapture  = $('webcam-capture-btn');
const webcamCancel   = $('webcam-cancel-btn');
const webcamClose    = $('webcam-close-btn');

// ─── Helpers ─────────────────────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
window.csrf = csrf;
const toRgba = ([r,g,b], a) => `rgba(${r},${g},${b},${a})`;
const toHex  = ([r,g,b]) => '#' + [r,g,b].map(x => x.toString(16).padStart(2,'0')).join('');

// Helper: parse JSON dan tangani jika server mengembalikan HTML (CSRF expired, 403, dll)
const safeJson = async (resp) => {
    const text = await resp.text();
    try {
        return JSON.parse(text);
    } catch (_) {
        if (text.includes('<!DOCTYPE') || text.includes('<html')) {
            if (resp.status === 419) throw new Error('Sesi telah kedaluwarsa. Silakan refresh halaman dan coba lagi.');
            if (resp.status === 403) throw new Error('Akses ditolak (403). Pastikan Anda memiliki izin akses menu Terapkan Batik.');
            if (resp.status === 302 || resp.url.includes('/login')) throw new Error('Sesi habis, silakan login kembali.');
            throw new Error(`Server mengembalikan HTML (status ${resp.status}). Coba refresh halaman.`);
        }
        throw new Error(`Response tidak valid dari server: ${text.substring(0, 100)}`);
    }
};
window.safeJson = safeJson;

const loadImage = src => new Promise((res, rej) => {
    const img = new Image();
    img.onload = () => res(img);
    img.onerror = rej;
    img.src = src;
});
window.loadImage = loadImage;

const readAsDataURL = file => new Promise((res, rej) => {
    const r = new FileReader();
    r.onload = e => res(e.target.result);
    r.onerror = rej;
    r.readAsDataURL(file);
});

const urlToFile = async (url, name) => {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('Gagal memuat gambar');
    const blob = await resp.blob();
    return new File([blob], name, { type: blob.type });
};

const setPhase = p => {
    phaseUpload.classList.toggle('hidden', p !== 'upload');
    phaseLoading.classList.toggle('hidden', p !== 'loading');
    phaseWorkspace.classList.toggle('hidden', p !== 'workspace');
    phaseResult?.classList.toggle('hidden', p !== 'result');
    // Phase CBIR Result — hanya ada di rekomendasi-batik
    const phaseCbir = document.getElementById('phase-cbir-result');
    if (phaseCbir) phaseCbir.classList.toggle('hidden', p !== 'cbir-result');
};
window.setPhase = setPhase;

// ─── Fashion upload ───────────────────────────────────────────────────────────
const setFashionFile = async file => {
    state.fashionFile = file;
    const src = await readAsDataURL(file);
    fashionPreview.src = src;
    fashionPreview.classList.remove('hidden');
    fashionPlaceholder.classList.add('hidden');
    analyzeBtn.disabled = false;
    uploadStatus.textContent = 'Klik "Analisis Pakaian" untuk melanjutkan.';
};

fashionInput?.addEventListener('change', () => { if (fashionInput.files?.[0]) setFashionFile(fashionInput.files[0]); });
fashionCameraInput?.addEventListener('change', () => { if (fashionCameraInput.files?.[0]) setFashionFile(fashionCameraInput.files[0]); });
fashionUploadBtn?.addEventListener('click', () => fashionInput.click());
fashionCameraBtn?.addEventListener('click', () => openWebcam('fashion'));

document.querySelectorAll('.sample-fashion').forEach(el => {
    el.addEventListener('click', async () => {
        uploadStatus.textContent = 'Memuat gambar...';
        try {
            const file = await urlToFile(el.dataset.url, 'fashion_sample.jpg');
            await setFashionFile(file);
        } catch (e) { uploadStatus.textContent = 'Gagal memuat gambar sample.'; }
    });
});

// ─── Inference ───────────────────────────────────────────────────────────────
analyzeBtn?.addEventListener('click', async () => {
    if (!state.fashionFile) return;
    setPhase('loading');

    const fd = new FormData();
    fd.append('image', state.fashionFile);
    fd.append('_token', csrf());

    try {
        const resp = await fetch(apiInferenceRoute, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        });
        const data = await safeJson(resp);
        if (!resp.ok || !data.session_id) throw new Error(data.message || 'Gagal menganalisis. Pastikan API aktif.');

        // Simpan CBIR data global sebelum initWorkspace
        window.cbirData = data.cbir || {};

        await initWorkspace(data);

        // Setelah workspace siap, branch berdasarkan mode:
        if (isRekomendasiMode) {
            // Tampilkan phase rekomendasi CBIR dulu
            if (typeof window.showCbirPhase === 'function') {
                window.showCbirPhase(window.cbirData);
            }
            setPhase('cbir-result');
        } else {
            setPhase('workspace');
        }
    } catch (err) {
        setPhase('upload');
        uploadStatus.textContent = err.message;
    }
});

async function initWorkspace(data) {
    state.sessionId  = data.session_id;
    state.imageSize  = data.image_size || null;   // {w, h} dari API — ukuran mask
    state.blendedKeys.clear();
    state.appliedBatiks = [];
    state.currentBatikInfo = null;
    state.partsList = [];
    state.hoveredKey = null;

    const src = await readAsDataURL(state.fashionFile);
    state.originalFashionImageSrc = src;
    state.fashionImage = await loadImage(src);

    const parts = data.parts || {};
    for (const [partName, value] of Object.entries(parts)) {
        const label = PART_LABELS[partName] || partName;
        const items = Array.isArray(value) ? value : [{ ...value, index: 0 }];
        for (const item of items) {
            const idx = item.index ?? 0;
            const key = `${partName}-${idx}`;
            let maskImg = null;
            if (item.mask_b64) {
                try { maskImg = await loadImage(`data:image/png;base64,${item.mask_b64}`); } catch (_) {}
            }
            state.partsList.push({ key, partName, index: idx, label, bbox: item.bbox, maskImg, area: item.area ?? 0, score: item.score ?? null });
        }
    }

    // Kategori pakaian utama vs bagian tambahan
    const MAIN_PARTS = ['shirt', 't-shirt', 'sweater', 'cardigan', 'jacket', 'vest', 'dress', 'jumpsuit', 'suit', 'coat'];
    
    // Sort: Pakaian Utama dulu, lalu bagian kecil.
    // Di dalam masing-masing grup, kumpulkan berdasarkan nama part, lalu urutkan berdasarkan indeks.
    state.partsList.sort((a, b) => {
        const aIsMain = MAIN_PARTS.includes(a.partName) ? 0 : 1;
        const bIsMain = MAIN_PARTS.includes(b.partName) ? 0 : 1;
        if (aIsMain !== bIsMain) return aIsMain - bIsMain;
        
        if (a.label !== b.label) return a.label.localeCompare(b.label);
        
        return a.index - b.index;
    });

    await renderFashionCanvas();
    renderPartsList();
    workspaceStatus.textContent = `${state.partsList.length} bagian terdeteksi. Klik bagian untuk terapkan batik.`;
    // Jangan set phase di sini — analyzeBtn handler yang mengatur phase berikutnya
    // berdasarkan mode (cbir-result untuk rekomendasi, workspace untuk terapkan)
}

// ─── Fashion canvas ───────────────────────────────────────────────────────────
async function renderFashionCanvas() {
    if (!state.fashionImage) return;
    const img = state.fashionImage;
    // Gunakan image_size dari API agar canvas sama persis dengan ukuran mask
    fashionCanvas.width  = state.imageSize?.w || img.naturalWidth;
    fashionCanvas.height = state.imageSize?.h || img.naturalHeight;
    canvasCtx.clearRect(0, 0, fashionCanvas.width, fashionCanvas.height);
    canvasCtx.drawImage(img, 0, 0, fashionCanvas.width, fashionCanvas.height);

    for (const part of state.partsList) {
        if (!part.maskImg) continue;
        const color = PART_COLORS[part.partName] || [200, 200, 200];
        const isHovered = state.hoveredKey === part.key;
        const isBlended = state.blendedKeys.has(part.key);

        if (isBlended && !isHovered) {
            // Jika sudah diblend dan tidak di-hover, cukup gambar icon centang saja.
            // Jangan gambar overlay warna lagi agar motif batik asli terlihat jelas.
            const b  = part.bbox;
            const fs = Math.max(14, fashionCanvas.width * 0.035);
            
            // Tambahkan box background tipis untuk icon centang agar terlihat di background gelap/terang
            canvasCtx.fillStyle = 'rgba(0,0,0,0.6)';
            canvasCtx.fillRect(b.x + b.w - fs - 8, b.y - 2, fs + 12, fs + 10);
            
            canvasCtx.font = `bold ${fs}px sans-serif`;
            canvasCtx.fillStyle = '#4ade80'; // emerald-400
            canvasCtx.fillText('✓', b.x + b.w - fs, b.y + fs + 2);
            continue;
        }

        // Buat mask berwarna di canvas sementara
        const tmp = document.createElement('canvas');
        tmp.width  = fashionCanvas.width;
        tmp.height = fashionCanvas.height;
        const tc = tmp.getContext('2d');
        tc.drawImage(part.maskImg, 0, 0, fashionCanvas.width, fashionCanvas.height);
        tc.globalCompositeOperation = 'source-in';
        tc.fillStyle = toRgba(color, isHovered ? 0.82 : 0.52);
        tc.fillRect(0, 0, tmp.width, tmp.height);

        if (isHovered) {
            // Glow mengikuti bentuk mask, bukan kotak bbox
            canvasCtx.save();
            canvasCtx.shadowColor = toHex(color);
            canvasCtx.shadowBlur  = Math.max(10, fashionCanvas.width * 0.02);
            canvasCtx.drawImage(tmp, 0, 0);
            canvasCtx.drawImage(tmp, 0, 0); // dua kali → efek glow lebih kuat
            canvasCtx.restore();

            // Label dengan background pill di atas/bawah area mask
            const b   = part.bbox;
            const fs  = Math.max(11, fashionCanvas.width * 0.038);
            const lbl = part.label + (part.index > 0 ? ` ${part.index + 1}` : '');
            canvasCtx.font = `bold ${fs}px sans-serif`;
            const tw = canvasCtx.measureText(lbl).width;
            const lx = Math.max(2, Math.min(b.x + b.w / 2 - tw / 2, fashionCanvas.width - tw - 10));
            const ly = b.y > fs + 10 ? b.y - 6 : b.y + b.h + fs + 4;
            canvasCtx.fillStyle = 'rgba(0,0,0,0.65)';
            canvasCtx.fillRect(lx - 5, ly - fs, tw + 10, fs + 6);
            canvasCtx.fillStyle = toHex(color);
            canvasCtx.fillText(lbl, lx, ly);
        } else {
            canvasCtx.drawImage(tmp, 0, 0);
        }


    }
}
window.renderFashionCanvas = renderFashionCanvas;

const canvasToImageCoords = e => {
    const rect = fashionCanvas.getBoundingClientRect();
    return {
        x: (e.clientX - rect.left) * (fashionCanvas.width / rect.width),
        y: (e.clientY - rect.top)  * (fashionCanvas.height / rect.height),
    };
};

const findPartAt = (cx, cy) => {
    let best = null, bestArea = Infinity;
    for (const p of state.partsList) {
        const b = p.bbox;
        if (cx >= b.x && cx <= b.x + b.w && cy >= b.y && cy <= b.y + b.h) {
            if (b.w * b.h < bestArea) { best = p; bestArea = b.w * b.h; }
        }
    }
    return best;
};

fashionCanvas.addEventListener('mousemove', e => {
    const {x, y} = canvasToImageCoords(e);
    const part = findPartAt(x, y);
    const key = part?.key || null;
    if (key !== state.hoveredKey) {
        state.hoveredKey = key;
        fashionCanvas.style.cursor = key ? 'pointer' : 'default';
        renderFashionCanvas();
    }
});
fashionCanvas.addEventListener('mouseleave', () => {
    if (state.hoveredKey) { state.hoveredKey = null; fashionCanvas.style.cursor = 'default'; renderFashionCanvas(); }
});
fashionCanvas.addEventListener('click', e => {
    const {x, y} = canvasToImageCoords(e);
    const part = findPartAt(x, y);
    if (part) openBatikPanel(part);
});

// ─── Parts list ───────────────────────────────────────────────────────────────
function renderPartsList() {
    partsListEl.innerHTML = '';
    if (!state.partsList.length) {
        partsListEl.innerHTML = '<p class="text-gray-500 text-sm">Tidak ada bagian terdeteksi.</p>';
        return;
    }
    for (const part of state.partsList) {
        const color = PART_COLORS[part.partName] || [200, 200, 200];
        const isBlended = state.blendedKeys.has(part.key);
        const label = part.label + (part.index > 0 ? ` ${part.index + 1}` : '');
        const score = part.score != null ? ` · ${(part.score * 100).toFixed(0)}%` : '';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-full flex items-center gap-3 px-3 py-2 rounded-xl border transition-colors text-left ' +
            (isBlended ? 'border-amber-600/50 bg-amber-950/20' : 'border-gray-700 hover:border-amber-600/40 hover:bg-gray-800/50');
        btn.innerHTML = `
            <div class="w-3 h-3 rounded-full shrink-0" style="background:${toRgba(color, 0.85)}"></div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium">${label}</p>
                <p class="text-gray-500 text-xs">${part.bbox.w}×${part.bbox.h}px${score}</p>
            </div>
            ${isBlended
                ? '<i class="bi bi-check-circle-fill text-amber-500 text-sm shrink-0"></i>'
                : '<i class="bi bi-chevron-right text-gray-600 text-xs shrink-0"></i>'}
        `;
        btn.addEventListener('mouseenter', () => { state.hoveredKey = part.key; renderFashionCanvas(); });
        btn.addEventListener('mouseleave', () => { state.hoveredKey = null; renderFashionCanvas(); });
        btn.addEventListener('click', () => openBatikPanel(part));
        partsListEl.appendChild(btn);
    }
}
window.renderPartsList = renderPartsList;

// ─── Reset & back ─────────────────────────────────────────────────────────────
resetBtn?.addEventListener('click', async () => {
    if (!state.sessionId) return;
    resetBtn.disabled = true;
    workspaceStatus.textContent = 'Mereset...';
    try {
        const resp = await fetch(apiResetRoute, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ session_id: state.sessionId }),
        });
        const data = await safeJson(resp);
        if (!resp.ok) throw new Error(data.message || 'Gagal reset.');
        if (data.image_b64) state.fashionImage = await loadImage(`data:image/jpeg;base64,${data.image_b64}`);
        state.blendedKeys.clear();
        state.appliedBatiks = [];
        await renderFashionCanvas();
        renderPartsList();
        workspaceStatus.textContent = 'Gambar direset.';
    } catch (err) {
        workspaceStatus.textContent = err.message;
    } finally {
        resetBtn.disabled = false;
    }
});

finishBtn?.addEventListener('click', () => {
    if (!state.fashionImage) return;
    
    // Buat canvas bersih ukurannya asli gambar
    const tmp = document.createElement('canvas');
    tmp.width = state.fashionImage.naturalWidth;
    tmp.height = state.fashionImage.naturalHeight;
    tmp.getContext('2d').drawImage(state.fashionImage, 0, 0);
    
    // Set gambarnya ke result
    resultOrigImg.src = state.originalFashionImageSrc;
    resultFinalImg.src = tmp.toDataURL('image/png');
    
    resultPartsList.innerHTML = '';
    if (state.appliedBatiks.length === 0) {
        resultPartsList.innerHTML = '<li class="p-5 text-gray-500 text-sm text-center italic">Tidak ada modifikasi motif batik pada foto ini.</li>';
    } else {
        for (const b of state.appliedBatiks) {
            let imgSrc = b.batikSrc || 'https://via.placeholder.com/80?text=Custom';
            resultPartsList.innerHTML += `
                <li class="flex items-center gap-4 p-4 hover:bg-gray-800/50 transition-colors">
                    <img src="${imgSrc}" class="w-16 h-16 object-cover rounded-lg border border-gray-700 shadow-sm">
                    <div>
                        <p class="text-white text-sm font-semibold">${b.partLabel}</p>
                        <p class="text-primary text-xs flex items-center gap-1 mt-1"><i class="bi bi-palette text-[10px]"></i> ${b.batikName}</p>
                    </div>
                </li>
            `;
        }
    }
    
    setPhase('result');
});

resultSaveBtn?.addEventListener('click', () => {
    if (!resultFinalImg.src) return;
    const a = document.createElement('a');
    a.download = 'batik-hasil.png';
    a.href = resultFinalImg.src;
    a.click();
});

resultBackBtn?.addEventListener('click', () => {
    setPhase('workspace');
});

backBtn?.addEventListener('click', () => {
    if (isRekomendasiMode && window.cbirData && Object.keys(window.cbirData).length) {
        // Dalam mode rekomendasi: kembali ke phase rekomendasi, bukan upload
        setPhase('cbir-result');
    } else {
        setPhase('upload');
        state.sessionId = null;
        state.partsList = [];
        state.blendedKeys.clear();
        state.appliedBatiks = [];
        state.hoveredKey = null;
    }
});

// ─── Batik panel open/close ───────────────────────────────────────────────────
function openBatikPanel(part) {
    if (window.openBatikPanelFunc) {
        window.openBatikPanelFunc(part);
        return;
    }
    
    state.selectedPart = part;
    state.batikTransform = { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 };

    const color = PART_COLORS[part.partName] || [200, 200, 200];
    panelPartColor.style.background = toRgba(color, 0.9);
    panelPartName.textContent = part.label + (part.index > 0 ? ` ${part.index + 1}` : '');
    panelBboxInfo.textContent = `Area: ${part.bbox.w}×${part.bbox.h}px · Posisi: (${part.bbox.x}, ${part.bbox.y})`;

    const CANVAS_SIZE = 320;
    batikCanvas.width  = CANVAS_SIZE;
    batikCanvas.height = CANVAS_SIZE;

    const ratio = part.bbox.w / (part.bbox.h || 1);
    const maxBox = CANVAS_SIZE * 0.68;
    if (ratio >= 1) {
        state.cropBoxW = Math.round(Math.min(maxBox, part.bbox.w));
        state.cropBoxH = Math.round(state.cropBoxW / ratio);
    } else {
        state.cropBoxH = Math.round(Math.min(maxBox, part.bbox.h));
        state.cropBoxW = Math.round(state.cropBoxH * ratio);
    }

    // Reset status & batik selection highlight
    panelStatus.textContent = '';
    panelStatus.classList.add('hidden');
    document.querySelectorAll('.panel-sample-batik').forEach(e => e.classList.remove('border-primary'));

    drawBatikCanvas();
    batikPanel.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

const closeBatikPanel = () => {
    batikPanel.style.display = 'none';
    document.body.style.overflow = '';
};
window.closeBatikPanel = closeBatikPanel;

panelCloseBtn?.addEventListener('click', closeBatikPanel);
panelCancelBtn?.addEventListener('click', closeBatikPanel);
batikPanel?.addEventListener('click', e => { if (e.target === batikPanel) closeBatikPanel(); });

// ─── Batik canvas draw ────────────────────────────────────────────────────────
function drawBatikCanvas() {
    const W = batikCanvas.width, H = batikCanvas.height;
    batikCtx.clearRect(0, 0, W, H);
    batikCtx.fillStyle = '#1a1a1a';
    batikCtx.fillRect(0, 0, W, H);

    if (state.batikImg) {
        const { scale, offsetX, offsetY, rotation } = state.batikTransform;
        const iw = state.batikImg.naturalWidth, ih = state.batikImg.naturalHeight;
        const fitScale = Math.max(W / iw, H / ih) * 1.05;
        const eff = fitScale * scale;
        batikCtx.save();
        batikCtx.translate(W / 2 + offsetX, H / 2 + offsetY);
        batikCtx.rotate(rotation * Math.PI / 180);
        batikCtx.drawImage(state.batikImg, -iw * eff / 2, -ih * eff / 2, iw * eff, ih * eff);
        batikCtx.restore();
    } else {
        batikCtx.fillStyle = '#444';
        batikCtx.font = '12px sans-serif';
        batikCtx.textAlign = 'center';
        batikCtx.textBaseline = 'middle';
        batikCtx.fillText('Pilih batik dari galeri', W / 2, H / 2 - 8);
        batikCtx.fillText('atau unggah gambar', W / 2, H / 2 + 10);
    }

    // Overlay mask atau fallback kotak
    const cx = (W - state.cropBoxW) / 2;
    const cy = (H - state.cropBoxH) / 2;
    const part = state.selectedPart;

    if (part?.maskImg) {
        const { bbox, maskImg, partName } = part;
        const color = PART_COLORS[partName] || [200, 200, 200];

        // Ekstrak region bbox dari mask, skala ke cropBox
        const mCvs = document.createElement('canvas');
        mCvs.width  = state.cropBoxW;
        mCvs.height = state.cropBoxH;
        const mc = mCvs.getContext('2d');
        mc.drawImage(maskImg, bbox.x, bbox.y, bbox.w, bbox.h, 0, 0, state.cropBoxW, state.cropBoxH);

        // Binarkan alpha (>30 → 255, lainnya → 0) supaya tepi bersih
        const px = mc.getImageData(0, 0, state.cropBoxW, state.cropBoxH);
        for (let i = 3; i < px.data.length; i += 4) {
            px.data[i] = px.data[i] > 30 ? 255 : 0;
        }
        mc.putImageData(px, 0, 0);

        // Layer 1: area luar mask digelapkan
        const dark = document.createElement('canvas');
        dark.width = W; dark.height = H;
        const dc = dark.getContext('2d');
        dc.fillStyle = 'rgba(0,0,0,0.38)';   // lebih transparan → batik tetap kelihatan
        dc.fillRect(0, 0, W, H);
        dc.globalCompositeOperation = 'destination-out';
        dc.drawImage(mCvs, cx, cy);
        batikCtx.drawImage(dark, 0, 0);

        // Layer 2: border outline saja — gambar filled+glow di canvas terpisah,
        //           lalu hapus bagian dalamnya → tersisa glow di tepi saja
        const solidMask = document.createElement('canvas');
        solidMask.width = state.cropBoxW; solidMask.height = state.cropBoxH;
        const sm = solidMask.getContext('2d');
        sm.drawImage(mCvs, 0, 0);
        sm.globalCompositeOperation = 'source-in';
        sm.fillStyle = toHex(color);
        sm.fillRect(0, 0, state.cropBoxW, state.cropBoxH);

        const borderCvs = document.createElement('canvas');
        borderCvs.width = W; borderCvs.height = H;
        const bc = borderCvs.getContext('2d');
        bc.save();
        bc.shadowColor = toHex(color);
        bc.shadowBlur  = 16;
        bc.drawImage(solidMask, cx, cy);
        bc.drawImage(solidMask, cx, cy); // dua kali → glow lebih tegas
        bc.restore();
        // Hapus isi bagian dalam → tersisa hanya glow di tepi
        bc.globalCompositeOperation = 'destination-out';
        bc.drawImage(mCvs, cx, cy);

        batikCtx.drawImage(borderCvs, 0, 0);

        // Label ukuran referensi
        batikCtx.fillStyle = 'rgba(255,255,255,0.5)';
        batikCtx.font = '10px sans-serif';
        batikCtx.textAlign = 'left';
        batikCtx.textBaseline = 'alphabetic';
        batikCtx.fillText(`${bbox.w}×${bbox.h}px`, cx + 2, cy > 14 ? cy - 4 : cy + state.cropBoxH + 14);

    } else {
        // Fallback: kotak biasa jika mask belum tersedia
        batikCtx.fillStyle = 'rgba(0,0,0,0.45)';
        batikCtx.fillRect(0, 0, W, cy);
        batikCtx.fillRect(0, cy + state.cropBoxH, W, H - cy - state.cropBoxH);
        batikCtx.fillRect(0, cy, cx, state.cropBoxH);
        batikCtx.fillRect(cx + state.cropBoxW, cy, W - cx - state.cropBoxW, state.cropBoxH);
        batikCtx.strokeStyle = 'rgba(255,255,255,0.88)';
        batikCtx.lineWidth = 2;
        batikCtx.strokeRect(cx, cy, state.cropBoxW, state.cropBoxH);
        const hs = 8;
        batikCtx.fillStyle = '#fff';
        [[cx,cy],[cx+state.cropBoxW-hs,cy],[cx,cy+state.cropBoxH-hs],[cx+state.cropBoxW-hs,cy+state.cropBoxH-hs]]
            .forEach(([x,y]) => batikCtx.fillRect(x,y,hs,hs));
    }
}
window.drawBatikCanvas = drawBatikCanvas;

// Drag
batikCanvas?.addEventListener('mousedown', e => {
    state.isDragging = true;
    state.dragStart = { x: e.clientX, y: e.clientY };
    state.dragStartOffset = { ...state.batikTransform };
    batikCanvas.style.cursor = 'grabbing';
});
window.addEventListener('mousemove', e => {
    if (!state.isDragging) return;
    state.batikTransform.offsetX = state.dragStartOffset.offsetX + (e.clientX - state.dragStart.x);
    state.batikTransform.offsetY = state.dragStartOffset.offsetY + (e.clientY - state.dragStart.y);
    drawBatikCanvas();
});
window.addEventListener('mouseup', () => {
    if (state.isDragging) { state.isDragging = false; batikCanvas.style.cursor = 'grab'; }
});

// Touch drag
batikCanvas?.addEventListener('touchstart', e => {
    e.preventDefault();
    const t = e.touches[0];
    state.isDragging = true;
    state.dragStart = { x: t.clientX, y: t.clientY };
    state.dragStartOffset = { ...state.batikTransform };
}, { passive: false });
batikCanvas?.addEventListener('touchmove', e => {
    e.preventDefault();
    if (!state.isDragging) return;
    const t = e.touches[0];
    state.batikTransform.offsetX = state.dragStartOffset.offsetX + (t.clientX - state.dragStart.x);
    state.batikTransform.offsetY = state.dragStartOffset.offsetY + (t.clientY - state.dragStart.y);
    drawBatikCanvas();
}, { passive: false });
batikCanvas?.addEventListener('touchend', () => { state.isDragging = false; });

// Scroll zoom
batikCanvas?.addEventListener('wheel', e => {
    e.preventDefault();
    state.batikTransform.scale = Math.max(0.1, Math.min(10, state.batikTransform.scale * (e.deltaY > 0 ? 0.9 : 1.1)));
    drawBatikCanvas();
}, { passive: false });

zoomInBtn?.addEventListener('click',  () => { state.batikTransform.scale = Math.min(10, state.batikTransform.scale * 1.2); drawBatikCanvas(); });
zoomOutBtn?.addEventListener('click', () => { state.batikTransform.scale = Math.max(0.1, state.batikTransform.scale / 1.2); drawBatikCanvas(); });
rotateCwBtn?.addEventListener('click',  () => { state.batikTransform.rotation = (state.batikTransform.rotation + 15) % 360; drawBatikCanvas(); });
rotateCcwBtn?.addEventListener('click', () => { state.batikTransform.rotation = (state.batikTransform.rotation - 15 + 360) % 360; drawBatikCanvas(); });
batikResetBtn?.addEventListener('click', () => { state.batikTransform = { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 }; drawBatikCanvas(); });

// ─── Batik selection ──────────────────────────────────────────────────────────
const setBatikImage = async (file, src, name) => {
    state.batikImg = await loadImage(src || URL.createObjectURL(file));
    state.batikTransform = { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 };
    state.currentBatikInfo = { src: src || (file ? URL.createObjectURL(file) : null), name: name || 'Unggahan Custom' };
    drawBatikCanvas();
};
window.setBatikImage = setBatikImage;

panelUploadBtn?.addEventListener('click', () => panelBatikInput.click());
panelBatikInput?.addEventListener('change', async () => {
    const file = panelBatikInput.files?.[0];
    if (file) await setBatikImage(file, null, file.name);
});

document.querySelectorAll('.panel-sample-batik').forEach(el => {
    el.addEventListener('click', async () => {
        try {
            document.querySelectorAll('.panel-sample-batik').forEach(e => e.classList.remove('border-primary'));
            el.classList.add('border-primary');
            const file = await urlToFile(el.dataset.url, 'batik.jpg');
            
            // Get name from attribute data-name or inner p tag
            const rawName = el.dataset.name || 'Batik Galeri';
            // Capitalize each word
            const titleName = rawName.replace(/\b\w/g, l => l.toUpperCase());
            
            await setBatikImage(file, el.dataset.url, titleName);
        } catch (e) { console.error(e); }
    });
});

panelSearch?.addEventListener('input', () => {
    const q = panelSearch.value.toLowerCase();
    document.querySelectorAll('.panel-sample-batik').forEach(el => {
        el.classList.toggle('hidden', !(el.dataset.name || '').includes(q));
    });
});

// ─── Blend ───────────────────────────────────────────────────────────────────
function getCroppedBlob() {
    return new Promise((resolve, reject) => {
        const W = batikCanvas.width, H = batikCanvas.height;
        const cx = (W - state.cropBoxW) / 2;
        const cy = (H - state.cropBoxH) / 2;

        // Faktor pengali resolusi agar hasil crop tidak pecah saat diaplikasikan ke baju besar
        const SCALE_FACTOR = 3;

        const tmp = document.createElement('canvas');
        tmp.width  = state.cropBoxW * SCALE_FACTOR;
        tmp.height = state.cropBoxH * SCALE_FACTOR;
        const tc = tmp.getContext('2d');

        if (state.batikImg) {
            const { scale, offsetX, offsetY, rotation } = state.batikTransform;
            const iw = state.batikImg.naturalWidth, ih = state.batikImg.naturalHeight;
            const fitScale = Math.max(W / iw, H / ih) * 1.05;
            const eff = fitScale * scale * SCALE_FACTOR;
            const bCx = (W / 2 + offsetX) * SCALE_FACTOR;
            const bCy = (H / 2 + offsetY) * SCALE_FACTOR;
            
            tc.save();
            tc.translate(bCx - (cx * SCALE_FACTOR), bCy - (cy * SCALE_FACTOR));
            tc.rotate(rotation * Math.PI / 180);
            tc.drawImage(state.batikImg, -iw * eff / 2, -ih * eff / 2, iw * eff, ih * eff);
            tc.restore();
        }

        tmp.toBlob(blob => {
            if (!blob) { reject(new Error('Gagal membuat gambar batik. Coba lagi.')); return; }
            resolve(blob);
        }, 'image/jpeg', 0.95);
    });
}

applyBlendBtn?.addEventListener('click', async () => {
    const showErr = msg => {
        panelStatus.textContent = msg;
        panelStatus.classList.remove('hidden');
    };

    if (!state.batikImg) { showErr('Pilih gambar batik terlebih dahulu.'); return; }
    if (!state.sessionId) { showErr('Sesi tidak valid, coba analisis ulang.'); return; }
    if (!state.selectedPart) return;

    panelStatus.classList.add('hidden');
    applyBlendBtn.disabled = true;
    applyBlendBtn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Menerapkan...';

    try {
        const blob = await getCroppedBlob();
        const fd = new FormData();
        fd.append('session_id', state.sessionId);
        fd.append('part', state.selectedPart.partName);
        fd.append('instance_index', String(state.selectedPart.index));
        fd.append('batik', new File([blob], 'batik_crop.jpg', { type: 'image/jpeg' }));
        fd.append('_token', csrf());

        const resp = await fetch("{{ route('api.blend') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        });
        const data = await safeJson(resp);
        if (!resp.ok || !data.image_b64) throw new Error(data.message || `HTTP ${resp.status}: Gagal menerapkan batik.`);

        state.fashionImage = await loadImage(`data:image/jpeg;base64,${data.image_b64}`);
        state.blendedKeys.add(state.selectedPart.key);
        
        // Simpan info diterapkan
        const partLabel = panelPartName.textContent;
        const existing = state.appliedBatiks.find(x => x.key === state.selectedPart.key);
        if (existing) {
            existing.batikName = state.currentBatikInfo?.name || 'Batik Custom';
            existing.batikSrc = state.currentBatikInfo?.src || null;
        } else {
            state.appliedBatiks.push({
                key: state.selectedPart.key,
                partLabel: partLabel,
                batikName: state.currentBatikInfo?.name || 'Batik Custom',
                batikSrc: state.currentBatikInfo?.src || null
            });
        }
        
        await renderFashionCanvas();
        renderPartsList();
        workspaceStatus.textContent = `✓ ${panelPartName.textContent} berhasil diterapkan.`;
        closeBatikPanel();
    } catch (err) {
        console.error('[blend]', err);
        showErr(err.message || 'Gagal menerapkan batik. Periksa API.');
    } finally {
        applyBlendBtn.disabled = false;
        applyBlendBtn.innerHTML = '<i class="bi bi-check2 mr-1"></i>Terapkan';
    }
});

// ─── Webcam ───────────────────────────────────────────────────────────────────
const openWebcam = async target => {
    state.webcamTarget = target;
    if (!navigator.mediaDevices?.getUserMedia) { fashionCameraInput.click(); return; }
    try {
        if (state.webcamStream) state.webcamStream.getTracks().forEach(t => t.stop());
        state.webcamStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        webcamVideo.srcObject = state.webcamStream;
        webcamModal.style.display = 'flex';
        await webcamVideo.play();
    } catch (_) { fashionCameraInput.click(); }
};

const closeWebcam = () => {
    webcamModal.style.display = 'none';
    if (state.webcamStream) { state.webcamStream.getTracks().forEach(t => t.stop()); state.webcamStream = null; }
    webcamVideo.srcObject = null;
};

webcamCapture.addEventListener('click', () => {
    if (!webcamVideo.videoWidth) return;
    webcamCanvasEl.width  = webcamVideo.videoWidth;
    webcamCanvasEl.height = webcamVideo.videoHeight;
    webcamCanvasEl.getContext('2d').drawImage(webcamVideo, 0, 0);
    webcamCanvasEl.toBlob(blob => {
        if (!blob) return;
        setFashionFile(new File([blob], 'capture.jpg', { type: 'image/jpeg' }));
        closeWebcam();
    }, 'image/jpeg', 0.92);
});
webcamCancel.addEventListener('click', closeWebcam);
webcamClose.addEventListener('click', closeWebcam);

})();
</script>
@endpush
