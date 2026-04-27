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
            const isBlended = state.blendedKeys.has(part.key);

            if (isBlended && !isHovered) {
                // Bagian sudah di-blend: tampilkan tanda centang saja
                const b  = part.bbox;
                const fs = Math.max(14, fashionCanvas.width * 0.035);

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
                // Glow effect mengikuti bentuk mask
                canvasCtx.save();
                canvasCtx.shadowColor = toHex(color);
                canvasCtx.shadowBlur  = Math.max(10, fashionCanvas.width * 0.02);
                canvasCtx.drawImage(tmp, 0, 0);
                canvasCtx.drawImage(tmp, 0, 0); // dua kali → glow lebih kuat
                canvasCtx.restore();

                // Label dengan background pill
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
        const { x, y } = canvasToImageCoords(e);
        const part = findPartAt(x, y);
        if (part && window.BatikApp.BatikPanel) {
            window.BatikApp.BatikPanel.open(part);
        }
    });
};
