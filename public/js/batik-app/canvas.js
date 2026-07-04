/**
 * =========================================================================
 * BatikApp — Canvas Module (Fashion Canvas Rendering & Interaction)
 * =========================================================================
 *
 * Mengelola rendering fashion canvas yang menampilkan gambar pakaian
 * dengan overlay mask berwarna untuk setiap bagian terdeteksi.
 *
 * Fitur:
 *   - Render gambar fashion dengan overlay mask bagian pakaian
 *   - Hover effect: highlight + glow + label pada bagian
 *   - Click: buka panel batik untuk bagian yang diklik
 *   - Tanda centang (✓) pada bagian yang sudah di-blend
 *
 * @module  BatikApp.Canvas
 * @depends BatikApp.State, BatikApp.Helpers, BatikApp.Constants
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.Canvas = {};

/**
 * Inisialisasi modul canvas.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.Canvas.init = function () {
    const state       = window.BatikApp.state;
    const PART_COLORS = window.BatikApp.PART_COLORS;
    const { toRgba, toHex } = window.BatikApp.Helpers;

    const fashionCanvas = document.getElementById('fashion-canvas');
    if (!fashionCanvas) return;

    const canvasCtx = fashionCanvas.getContext('2d');

    // ── Canvas Rendering ──────────────────────────────────────────

    /**
     * Render fashion canvas: gambar pakaian + overlay mask + hover effects.
     *
     * Dipanggil setiap kali:
     *   - Gambar fashion dimuat pertama kali
     *   - User hover/leave pada bagian pakaian
     *   - Setelah blend berhasil (tanda centang muncul)
     *   - Setelah reset
     */
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

            if (isHovered) {
                // Buat mask berwarna di canvas sementara hanya saat hover
                const tmp = document.createElement('canvas');
                tmp.width  = fashionCanvas.width;
                tmp.height = fashionCanvas.height;
                const tc = tmp.getContext('2d');
                tc.drawImage(part.maskImg, 0, 0, fashionCanvas.width, fashionCanvas.height);
                tc.globalCompositeOperation = 'source-in';
                tc.fillStyle = toRgba(color, 0.75); // Opacity hover
                tc.fillRect(0, 0, tmp.width, tmp.height);

                // Glow effect mengikuti bentuk mask
                canvasCtx.save();
                canvasCtx.shadowColor = toHex(color);
                canvasCtx.shadowBlur  = Math.max(10, fashionCanvas.width * 0.02);
                canvasCtx.drawImage(tmp, 0, 0);
                canvasCtx.drawImage(tmp, 0, 0); 
                canvasCtx.restore();

                // Label
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
            }
        }
    }

    // Expose render function
    window.BatikApp.Canvas.render = renderFashionCanvas;
    window.renderFashionCanvas = renderFashionCanvas; // backward compat

    // ── Coordinate Conversion ─────────────────────────────────────

    /**
     * Konversi koordinat mouse/touch ke koordinat gambar di canvas.
     *
     * @param {MouseEvent|Touch} e - Event mouse atau touch
     * @returns {{x: number, y: number}} Koordinat pada gambar
     */
    const canvasToImageCoords = e => {
        const rect = fashionCanvas.getBoundingClientRect();
        return {
            x: (e.clientX - rect.left) * (fashionCanvas.width / rect.width),
            y: (e.clientY - rect.top)  * (fashionCanvas.height / rect.height),
        };
    };

    /**
     * Cari bagian pakaian di koordinat tertentu.
     * Jika beberapa bagian tumpang tindih, pilih yang area bbox-nya terkecil.
     *
     * @param {number} cx - Koordinat X pada gambar
     * @param {number} cy - Koordinat Y pada gambar
     * @returns {Object|null} Part object atau null jika tidak ada
     */
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

    // ── Canvas Event Listeners ────────────────────────────────────

    fashionCanvas.addEventListener('mousemove', e => {
        const { x, y } = canvasToImageCoords(e);
        const part = findPartAt(x, y);
        const key = part?.key || null;
        if (key !== state.hoveredKey) {
            state.hoveredKey = key;
            fashionCanvas.style.cursor = key ? 'pointer' : 'default';
            renderFashionCanvas();
        }
    });

    fashionCanvas.addEventListener('mouseleave', () => {
        if (state.hoveredKey) {
            state.hoveredKey = null;
            fashionCanvas.style.cursor = 'default';
            renderFashionCanvas();
        }
    });

    fashionCanvas.addEventListener('click', e => {
        // Prevent click if we were dragging
        if (state.wasDraggingCanvas) {
            state.wasDraggingCanvas = false;
            return;
        }
        const { x, y } = canvasToImageCoords(e);
        const part = findPartAt(x, y);
        if (part && window.BatikApp.BatikPanel) {
            window.BatikApp.BatikPanel.open(part);
        }
    });

    // ── Zoom and Pan Implementation ───────────────────────────────
    let zoomLevel = 1;
    let panX = 0;
    let panY = 0;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    const canvasContainer = document.getElementById('canvas-container');
    const zoomInBtn = document.getElementById('workspace-zoom-in');
    const zoomOutBtn = document.getElementById('workspace-zoom-out');
    const zoomResetBtn = document.getElementById('workspace-zoom-reset');

    const updateTransform = () => {
        fashionCanvas.style.transform = `translate(${panX}px, ${panY}px) scale(${zoomLevel})`;
    };

    const setZoom = (newZoom) => {
        zoomLevel = Math.max(1, Math.min(5, newZoom));
        if (zoomLevel === 1) {
            panX = 0; panY = 0;
        }
        updateTransform();
    };

    zoomInBtn?.addEventListener('click', () => setZoom(zoomLevel + 0.5));
    zoomOutBtn?.addEventListener('click', () => setZoom(zoomLevel - 0.5));
    zoomResetBtn?.addEventListener('click', () => setZoom(1));

    canvasContainer?.addEventListener('wheel', e => {
        e.preventDefault();
        setZoom(zoomLevel + (e.deltaY < 0 ? 0.2 : -0.2));
    }, { passive: false });

    canvasContainer?.addEventListener('mousedown', e => {
        if (zoomLevel <= 1) return;
        isDragging = true;
        state.wasDraggingCanvas = false;
        dragStartX = e.clientX - panX;
        dragStartY = e.clientY - panY;
        canvasContainer.style.cursor = 'grabbing';
    });

    window.addEventListener('mousemove', e => {
        if (!isDragging) return;
        state.wasDraggingCanvas = true; // Mark as dragged so click doesn't trigger
        panX = e.clientX - dragStartX;
        panY = e.clientY - dragStartY;
        updateTransform();
    });

    window.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            canvasContainer.style.cursor = 'grab';
            // wasDraggingCanvas remains true until click event fires and resets it
            setTimeout(() => { state.wasDraggingCanvas = false; }, 100);
        }
    });

};
