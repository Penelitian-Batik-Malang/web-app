/**
 * =========================================================================
 * BatikApp — Inference Module (Analisis Fashion → Deteksi Bagian Pakaian)
 * =========================================================================
 *
 * Mengelola proses inference: mengirim gambar fashion ke ML API,
 * menerima hasil deteksi bagian pakaian (parts), dan menyiapkan
 * workspace untuk interaksi blend.
 *
 * Alur:
 *   1. User klik "Analisis Pakaian"
 *   2. Gambar dikirim ke POST /api/inference
 *   3. ML API mengembalikan: session_id, parts, cbir (optional)
 *   4. Parts disimpan ke state, canvas dan sidebar di-render
 *   5. Berdasarkan mode:
 *      - terapkan    → langsung ke phase workspace
 *      - rekomendasi → ke phase cbir-result dulu
 *
 * @module  BatikApp.Inference
 * @depends BatikApp.State, BatikApp.Helpers, BatikApp.Canvas, BatikApp.PartsList
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.Inference = {};

/**
 * Inisialisasi modul inference.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.Inference.init = function () {
    const state   = window.BatikApp.state;
    const helpers = window.BatikApp.Helpers;
    const config  = window.BatikAppConfig || {};
    const PART_LABELS = window.BatikApp.PART_LABELS;

    const $ = id => document.getElementById(id);
    const analyzeBtn     = $('analyze-btn');
    const uploadStatus   = $('upload-status');
    const workspaceStatus = $('workspace-status');

    // ── Analyze Button Click ──────────────────────────────────────
    analyzeBtn?.addEventListener('click', async () => {
        if (!state.fashionFile) return;
        helpers.setPhase('loading');

        const fd = new FormData();
        fd.append('image', state.fashionFile);
        fd.append('_token', helpers.csrf());

        try {
            const resp = await fetch(config.apiInferenceRoute, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': helpers.csrf(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-API-Key': config.apiKey || '',
                },
                body: fd,
            });
            const data = await helpers.safeJson(resp);
            if (!resp.ok || !data.session_id) {
                throw new Error(data.message || 'Gagal menganalisis. Pastikan API aktif.');
            }

            // Cek apakah ada mask/pakaian yang terdeteksi
            const parts = data.parts || {};
            const isPartsEmpty = Array.isArray(parts) ? parts.length === 0 : Object.keys(parts).length === 0;
            if (isPartsEmpty) {
                alert('Peringatan: Tidak terdeteksi atribut pakaian pada foto ini. Silakan unggah foto lain.');
                throw new Error('Tidak terdeteksi atribut pakaian pada foto ini.');
            }

            // Simpan CBIR data global (dipakai oleh rekomendasi-batik)
            window.cbirData = data.cbir || {};

            await initWorkspace(data);

            // Branch berdasarkan mode
            if (config.isRekomendasiMode) {
                if (typeof window.showCbirPhase === 'function') {
                    window.showCbirPhase(window.cbirData);
                }
                helpers.setPhase('cbir-result');
            } else {
                helpers.setPhase('workspace');
            }
        } catch (err) {
            helpers.setPhase('upload');
            uploadStatus.textContent = err.message;
        }
    });

    /**
     * Inisialisasi workspace setelah inference berhasil.
     *
     * Memproses data dari ML API:
     *   - Simpan session_id dan image_size
     *   - Parse dan sort parts list
     *   - Render fashion canvas dan parts sidebar
     *
     * @param {Object} data - Response dari ML API inference
     * @param {string} data.session_id - UUID session
     * @param {{w: number, h: number}} data.image_size - Dimensi gambar
     * @param {Object} data.parts - Parts terdeteksi { partName: [{bbox, mask_b64, ...}] }
     */
    async function initWorkspace(data) {
        state.sessionId  = data.session_id;
        state.imageSize  = data.image_size || null;
        state.blendedKeys.clear();
        state.appliedBatiks = [];
        state.currentBatikInfo = null;
        state.partsList = [];
        state.hoveredKey = null;

        const src = await helpers.readAsDataURL(state.fashionFile);
        state.originalFashionImageSrc = src;
        state.fashionImage = await helpers.loadImage(src);

        const parts = data.parts || {};
        for (const [partName, value] of Object.entries(parts)) {
            const label = PART_LABELS[partName] || partName;
            const items = Array.isArray(value) ? value : [{ ...value, index: 0 }];
            
            let seqIdx = 0;
            for (const item of items) {
                const idx = item.index ?? 0;
                const key = `${partName}-${idx}`;
                let maskImg = null;
                if (item.mask_b64) {
                    try {
                        maskImg = await helpers.loadImage(`data:image/png;base64,${item.mask_b64}`);
                    } catch (_) {}
                }
                state.partsList.push({
                    key, partName, index: seqIdx, label,
                    bbox: item.bbox, maskImg,
                    area: item.area ?? 0,
                    score: item.score ?? null,
                });
                seqIdx++;
            }
        }

        // Sort: pakaian utama dulu, lalu aksesoris
        const MAIN_PARTS = [
            'shirt', 't-shirt', 'sweater', 'cardigan', 'jacket',
            'vest', 'dress', 'jumpsuit', 'suit', 'coat',
        ];
        state.partsList.sort((a, b) => {
            const aIsMain = MAIN_PARTS.includes(a.partName) ? 0 : 1;
            const bIsMain = MAIN_PARTS.includes(b.partName) ? 0 : 1;
            if (aIsMain !== bIsMain) return aIsMain - bIsMain;
            if (a.label !== b.label) return a.label.localeCompare(b.label);
            return a.index - b.index;
        });

        // Render canvas dan parts list
        if (window.BatikApp.Canvas) await window.BatikApp.Canvas.render();
        if (window.BatikApp.PartsList) window.BatikApp.PartsList.render();

        if (workspaceStatus) {
            workspaceStatus.textContent = `${state.partsList.length} bagian terdeteksi. Klik bagian untuk terapkan batik.`;
        }
    }
};
