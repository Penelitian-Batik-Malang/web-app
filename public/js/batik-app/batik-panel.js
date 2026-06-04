/**
 * =========================================================================
 * BatikApp — Batik Panel Module (Panel Pilih & Atur Motif Batik)
 * =========================================================================
 *
 * Mengelola panel overlay untuk memilih motif batik dan mengatur
 * posisinya sebelum di-blend ke bagian pakaian tertentu.
 *
 * Fitur:
 *   - Buka/tutup panel overlay
 *   - Crop canvas: preview motif di atas mask bagian pakaian
 *   - Drag, zoom, rotate motif batik
 *   - Pilih batik dari galeri atau upload custom
 *   - Search/filter batik di galeri panel
 *
 * @module  BatikApp.BatikPanel
 * @depends BatikApp.State, BatikApp.Constants, BatikApp.Helpers
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.BatikPanel = {};

/**
 * Inisialisasi modul batik panel.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.BatikPanel.init = function () {
    const state       = window.BatikApp.state;
    const PART_COLORS = window.BatikApp.PART_COLORS;
    const { toRgba, toHex, loadImage, urlToFile } = window.BatikApp.Helpers;

    const $ = id => document.getElementById(id);
    const batikPanel     = $('batik-panel');
    const panelPartColor = $('panel-part-color');
    const panelPartName  = $('panel-part-name');
    const panelBboxInfo  = $('panel-bbox-info');
    const panelCloseBtn  = $('panel-close-btn');
    const panelCancelBtn = $('panel-cancel-btn');
    const panelUploadBtn = $('panel-upload-btn');
    const panelCameraBtn = $('panel-camera-btn');
    const panelBatikInput= $('panel-batik-input');
    const panelBatikCameraInput = $('panel-batik-camera-input');
    const panelSearch    = $('panel-search');
    const panelStatus    = $('panel-status');
    const gallery        = $('panel-batik-gallery');

    const batikCanvas = $('batik-crop-canvas');
    if (!batikCanvas) return;
    const batikCtx    = batikCanvas.getContext('2d');

    const zoomInBtn      = $('zoom-in-btn');
    const zoomOutBtn     = $('zoom-out-btn');
    const batikResetBtn  = $('batik-reset-transform');

    // ── Open/Close Panel ──────────────────────────────────────────

    /**
     * Buka panel batik untuk bagian pakaian tertentu.
     *
     * @param {Object} part - Part object dari state.partsList
     * @param {string} part.key - Unique key (e.g. "shirt-0")
     * @param {string} part.partName - Nama bagian (e.g. "shirt")
     * @param {Object} part.bbox - Bounding box {x, y, w, h}
     */
    function openBatikPanel(part) {
        // Jika ada custom panel handler (dari rekomendasi mode), gunakan itu
        if (window.openBatikPanelFunc) {
            window.openBatikPanelFunc(part);
            return;
        }

        state.selectedPart = part;
        
        // Restore transform jika atribut ini sudah pernah diterapkan batik sebelumnya
        const existing = state.appliedBatiks.find(x => x.key === part.key);
        if (existing && existing.transform) {
            state.batikTransform = { ...existing.transform };
            document.getElementById('reset-part-btn')?.classList.remove('hidden');
        } else {
            // Jika belum, reset transform agar pas dengan dimensi atribut baru
            state.batikTransform = { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 };
            document.getElementById('reset-part-btn')?.classList.add('hidden');
        }
        
        // PENTING: state.batikImg dan currentBatikInfo sengaja TIDAK di-reset 
        // dan TIDAK di-restore dari history agar pilihan motif tersinkronisasi 
        // secara global untuk semua atribut pakaian.

        const color = PART_COLORS[part.partName] || [200, 200, 200];
        panelPartColor.style.background = toRgba(color, 0.9);
        panelPartName.textContent = part.label + (part.index > 0 ? ` ${part.index + 1}` : '');
        panelBboxInfo.textContent = `Area: ${part.bbox.w}×${part.bbox.h}px · Posisi: (${part.bbox.x}, ${part.bbox.y})`;

        // Setup canvas
        const CANVAS_SIZE = 320;
        batikCanvas.width  = CANVAS_SIZE;
        batikCanvas.height = CANVAS_SIZE;

        // Hitung crop box berdasarkan aspect ratio bagian pakaian
        const ratio = part.bbox.w / (part.bbox.h || 1);
        const maxBox = CANVAS_SIZE * 0.68;
        if (ratio >= 1) {
            state.cropBoxW = Math.round(Math.min(maxBox, part.bbox.w));
            state.cropBoxH = Math.round(state.cropBoxW / ratio);
        } else {
            state.cropBoxH = Math.round(Math.min(maxBox, part.bbox.h));
            state.cropBoxW = Math.round(state.cropBoxH * ratio);
        }

        // Reset status
        panelStatus.textContent = '';
        panelStatus.classList.add('hidden');
        document.querySelectorAll('.panel-sample-batik').forEach(e => e.classList.remove('border-primary'));

        // Reset sub-gallery state setiap panel dibuka
        const subgallery = $('panel-batik-subgallery');
        if (subgallery && !subgallery.classList.contains('hidden')) {
            subgallery.classList.add('hidden');
            $('panel-batik-gallery')?.classList.remove('hidden');
            $('panel-toolbar')?.classList.remove('hidden');
            const subgrid = $('panel-batik-subgrid');
            if (subgrid) subgrid.innerHTML = '';
        }

        drawBatikCanvas();
        batikPanel.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    /**
     * Tutup panel batik dan restore scroll body.
     */
    function closeBatikPanel() {
        batikPanel.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Expose functions
    window.BatikApp.BatikPanel.open = openBatikPanel;
    window.BatikApp.BatikPanel.close = closeBatikPanel;
    window.closeBatikPanel = closeBatikPanel; // backward compat

    // ── Panel Event Listeners ─────────────────────────────────────

    panelCloseBtn?.addEventListener('click', closeBatikPanel);
    panelCancelBtn?.addEventListener('click', closeBatikPanel);
    batikPanel?.addEventListener('click', e => { if (e.target === batikPanel) closeBatikPanel(); });

    // ── Batik Canvas Drawing ──────────────────────────────────────

    /**
     * Render batik crop canvas.
     *
     * Menampilkan:
     *   - Background gelap (#1a1a1a)
     *   - Gambar motif batik dengan transform (scale, offset, rotation)
     *   - Overlay mask bagian pakaian (atau fallback kotak) sebagai guide area
     *   - Glow border di tepi mask
     */
    function drawBatikCanvas() {
        const W = batikCanvas.width, H = batikCanvas.height;
        batikCtx.clearRect(0, 0, W, H);
        batikCtx.fillStyle = '#1a1a1a';
        batikCtx.fillRect(0, 0, W, H);

        // Gambar motif batik dengan transformasi
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
            // Placeholder text jika belum ada batik dipilih
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
            dc.fillStyle = 'rgba(0,0,0,0.38)';
            dc.fillRect(0, 0, W, H);
            dc.globalCompositeOperation = 'destination-out';
            dc.drawImage(mCvs, cx, cy);
            batikCtx.drawImage(dark, 0, 0);

            // Layer 2: glow border di tepi mask
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
            bc.drawImage(solidMask, cx, cy);
            bc.restore();
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

    // Expose draw function
    window.BatikApp.BatikPanel.draw = drawBatikCanvas;
    window.drawBatikCanvas = drawBatikCanvas; // backward compat

    // ── Drag Controls ─────────────────────────────────────────────

    batikCanvas.addEventListener('mousedown', e => {
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
    batikCanvas.addEventListener('touchstart', e => {
        e.preventDefault();
        const t = e.touches[0];
        state.isDragging = true;
        state.dragStart = { x: t.clientX, y: t.clientY };
        state.dragStartOffset = { ...state.batikTransform };
    }, { passive: false });
    batikCanvas.addEventListener('touchmove', e => {
        e.preventDefault();
        if (!state.isDragging) return;
        const t = e.touches[0];
        state.batikTransform.offsetX = state.dragStartOffset.offsetX + (t.clientX - state.dragStart.x);
        state.batikTransform.offsetY = state.dragStartOffset.offsetY + (t.clientY - state.dragStart.y);
        drawBatikCanvas();
    }, { passive: false });
    batikCanvas.addEventListener('touchend', () => { state.isDragging = false; });

    // Scroll zoom
    batikCanvas.addEventListener('wheel', e => {
        e.preventDefault();
        state.batikTransform.scale = Math.max(0.1, Math.min(10, state.batikTransform.scale * (e.deltaY > 0 ? 0.9 : 1.1)));
        drawBatikCanvas();
    }, { passive: false });

    // ── Zoom & Rotate Buttons ─────────────────────────────────────

    zoomInBtn?.addEventListener('click',  () => { state.batikTransform.scale = Math.min(10, state.batikTransform.scale * 1.2); drawBatikCanvas(); });
    zoomOutBtn?.addEventListener('click', () => { state.batikTransform.scale = Math.max(0.1, state.batikTransform.scale / 1.2); drawBatikCanvas(); });
    batikResetBtn?.addEventListener('click', () => { state.batikTransform = { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 }; drawBatikCanvas(); });

    // ── Batik Selection ───────────────────────────────────────────

    /**
     * Set gambar batik yang dipilih ke state dan redraw canvas.
     *
     * @param {File|null} file - File gambar (nullable jika src sudah ada)
     * @param {string|null} src - URL/data URL gambar
     * @param {string} name - Nama motif batik
     */
    const setBatikImage = async (file, src, name) => {
        state.batikImg = await loadImage(src || URL.createObjectURL(file));
        // Mencegah reset transform agar posisi motif yang sudah diatur tidak hilang (sesuai request)
        // state.batikTransform = { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 };
        state.currentBatikInfo = { src: src || (file ? URL.createObjectURL(file) : null), name: name || 'Unggahan Custom' };
        drawBatikCanvas();
    };

    // Expose for CBIR panel
    window.BatikApp.BatikPanel.setBatikImage = setBatikImage;
    window.setBatikImage = setBatikImage; // backward compat

    panelUploadBtn?.addEventListener('click', () => panelBatikInput.click());
    panelBatikInput?.addEventListener('change', async () => {
        const file = panelBatikInput.files?.[0];
        if (file) {
            try {
                await setBatikImage(file, null, file.name);
            } catch (err) {
                console.error("Gagal memuat gambar unggahan:", err);
            }
            panelBatikInput.value = ''; // Clear value to allow re-uploading the same file
        }
    });

    panelCameraBtn?.addEventListener('click', () => {
        if (window.BatikApp.Webcam) {
            window.BatikApp.Webcam.open('batik_panel');
        } else {
            panelBatikCameraInput?.click();
        }
    });

    panelBatikCameraInput?.addEventListener('change', async () => {
        const file = panelBatikCameraInput.files?.[0];
        if (file) {
            try {
                await setBatikImage(file, null, file.name);
            } catch (err) {
                console.error("Gagal memuat gambar kamera:", err);
            }
            panelBatikCameraInput.value = ''; // Clear value
        }
    });

    // ── Drill-down sub-gallery (mode terapkan) ───────────────────────

    function showBatikSubGallery(batikName, images) {
        const gallery    = $('panel-batik-gallery');
        const subgallery = $('panel-batik-subgallery');
        const subgrid    = $('panel-batik-subgrid');
        const subTitle   = $('panel-sub-title');
        const toolbar    = $('panel-toolbar');
        if (!gallery || !subgallery || !subgrid) return;

        gallery.classList.add('hidden');
        toolbar?.classList.add('hidden');
        subTitle.textContent = batikName;

        subgrid.innerHTML = '';
        images.forEach(imgData => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'border border-gray-700 rounded-lg overflow-hidden hover:border-primary transition-colors';
            const img = document.createElement('img');
            img.src = imgData.url;
            img.className = 'w-full h-16 object-cover';
            img.alt = batikName;
            img.onerror = () => { img.style.display = 'none'; };
            btn.appendChild(img);
            btn.addEventListener('click', async () => {
                subgrid.querySelectorAll('button').forEach(b => b.classList.remove('border-primary'));
                btn.classList.add('border-primary');
                await setBatikImage(null, imgData.url, batikName);
            });
            subgrid.appendChild(btn);
        });

        subgallery.classList.remove('hidden');
    }

    $('panel-back-btn')?.addEventListener('click', () => {
        $('panel-batik-subgallery')?.classList.add('hidden');
        $('panel-batik-gallery')?.classList.remove('hidden');
        $('panel-toolbar')?.classList.remove('hidden');
        const subgrid = $('panel-batik-subgrid');
        if (subgrid) subgrid.innerHTML = '';
    });

    // Galeri batik dari database (mode terapkan)
    // Gunakan event delegation agar elemen dinamis tetap bisa di-klik
    gallery?.addEventListener('click', async (e) => {
        const el = e.target.closest('.panel-sample-batik');
        if (!el) return;

        try {
            const rawName   = el.dataset.name || 'Batik Galeri';
            const titleName = rawName.replace(/\b\w/g, l => l.toUpperCase());
            const images    = JSON.parse(el.dataset.images || '[]');

            if (images.length > 1) {
                showBatikSubGallery(titleName, images);
            } else {
                document.querySelectorAll('.panel-sample-batik').forEach(e => e.classList.remove('border-primary'));
                el.classList.add('border-primary');
                await setBatikImage(null, el.dataset.url, titleName);
            }
        } catch (err) { console.error('Gallery click error:', err); }
    });


    // Search/filter batik gallery
    panelSearch?.addEventListener('input', () => {
        const q = panelSearch.value.toLowerCase();
        document.querySelectorAll('.panel-sample-batik').forEach(el => {
            el.classList.toggle('hidden', !(el.dataset.name || '').includes(q));
        });
    });
};
