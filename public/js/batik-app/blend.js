/**
 * =========================================================================
 * BatikApp — Blend Module (Terapkan Motif Batik ke Pakaian)
 * =========================================================================
 *
 * Mengelola proses blend: crop motif batik dari panel canvas,
 * kirim ke ML API bersama session_id dan part info, dan update
 * gambar fashion dengan hasil blend.
 *
 * Alur blend:
 *   1. User klik "Terapkan" di panel batik
 *   2. getCroppedBlob() — crop area motif dari canvas
 *   3. FormData dikirim ke POST /api/blend
 *   4. ML API blend motif ke segmen pakaian + return image_b64
 *   5. Fashion image di-update, tanda centang ditambahkan
 *
 * @module  BatikApp.Blend
 * @depends BatikApp.State, BatikApp.Helpers, BatikApp.Canvas, BatikApp.PartsList
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.Blend = {};

/**
 * Inisialisasi modul blend.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.Blend.init = function () {
    const state   = window.BatikApp.state;
    const helpers = window.BatikApp.Helpers;
    const config  = window.BatikAppConfig || {};

    const $ = id => document.getElementById(id);
    const applyBlendBtn  = $('apply-blend-btn');
    const resetPartBtn   = $('reset-part-btn');
    const panelStatus    = $('panel-status');
    const panelPartName  = $('panel-part-name');
    const workspaceStatus = $('workspace-status');
    const batikCanvas    = $('batik-crop-canvas');

    if (!applyBlendBtn || !batikCanvas) return;

    /**
     * Crop area motif dari batik canvas dan return sebagai Blob.
     *
     * CATATAN PENTING:
     *   SCALE_FACTOR = 3 digunakan agar gambar crop tidak pecah saat
     *   di-apply ke segmen pakaian besar (resolusi mask bisa sampai
     *   800x600px sementara canvas preview hanya 320px).
     *
     * @returns {Promise<Blob>} JPEG blob dari crop area
     */
    function getCroppedBlob() {
        return new Promise((resolve, reject) => {
            const W = batikCanvas.width, H = batikCanvas.height;
            const cx = (W - state.cropBoxW) / 2;
            const cy = (H - state.cropBoxH) / 2;
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

    // ── Apply Blend Button Handler ────────────────────────────────

    applyBlendBtn.addEventListener('click', async () => {
        const showErr = msg => {
            panelStatus.textContent = msg;
            panelStatus.classList.remove('hidden');
        };

        if (!state.batikImg)    { showErr('Pilih gambar batik terlebih dahulu.'); return; }
        if (!state.sessionId)   { showErr('Sesi tidak valid, coba analisis ulang.'); return; }
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
            fd.append('_token', helpers.csrf());

            const resp = await fetch(config.apiBlendRoute, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': helpers.csrf(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });
            const data = await helpers.safeJson(resp);
            if (!resp.ok || !data.image_b64) {
                throw new Error(data.message || `HTTP ${resp.status}: Gagal menerapkan batik.`);
            }

            // Update fashion image dengan hasil blend
            state.fashionImage = await helpers.loadImage(`data:image/jpeg;base64,${data.image_b64}`);
            state.blendedKeys.add(state.selectedPart.key);

            // Simpan riwayat blend (untuk result view)
            const partLabel = panelPartName.textContent;
            const existing = state.appliedBatiks.find(x => x.key === state.selectedPart.key);
            if (existing) {
                existing.batikName = state.currentBatikInfo?.name || 'Batik Custom';
                existing.batikSrc = state.currentBatikInfo?.src || null;
                existing.batikImg = state.batikImg;
                existing.transform = { ...state.batikTransform };
            } else {
                state.appliedBatiks.push({
                    key: state.selectedPart.key,
                    partLabel: partLabel,
                    batikName: state.currentBatikInfo?.name || 'Batik Custom',
                    batikSrc: state.currentBatikInfo?.src || null,
                    batikImg: state.batikImg,
                    transform: { ...state.batikTransform }
                });
            }

            // Re-render canvas dan parts list
            if (window.BatikApp.Canvas) await window.BatikApp.Canvas.render();
            if (window.BatikApp.PartsList) window.BatikApp.PartsList.render();

            if (workspaceStatus) {
                workspaceStatus.textContent = `✓ ${partLabel} berhasil diterapkan.`;
            }
            if (window.BatikApp.BatikPanel) window.BatikApp.BatikPanel.close();

        } catch (err) {
            console.error('[blend]', err);
            showErr(err.message || 'Gagal menerapkan batik. Periksa API.');
        } finally {
            applyBlendBtn.disabled = false;
            applyBlendBtn.innerHTML = '<i class="bi bi-check2"></i> Terapkan';
        }
    });

    // ── Reset Part Button Handler ────────────────────────────────

    resetPartBtn?.addEventListener('click', async () => {
        const showErr = msg => {
            panelStatus.textContent = msg;
            panelStatus.classList.remove('hidden');
        };

        if (!state.sessionId) return;
        if (!state.selectedPart) return;

        panelStatus.classList.add('hidden');
        resetPartBtn.disabled = true;
        
        try {
            const fd = new FormData();
            fd.append('session_id', state.sessionId);
            fd.append('part', state.selectedPart.partName);
            fd.append('instance_index', String(state.selectedPart.index));
            fd.append('_token', helpers.csrf());

            // Use the new /api/reset-part endpoint (add to config or hardcode for now)
            const route = (config.apiBlendRoute || '').replace('/api/blend', '/api/reset-part');
            
            const resp = await fetch(route, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': helpers.csrf(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });
            const data = await helpers.safeJson(resp);
            if (!resp.ok || !data.image_b64) {
                throw new Error(data.message || `HTTP ${resp.status}: Gagal menghapus batik.`);
            }

            // Update fashion image
            state.fashionImage = await helpers.loadImage(`data:image/jpeg;base64,${data.image_b64}`);
            
            // Hapus dari state
            state.blendedKeys.delete(state.selectedPart.key);
            state.appliedBatiks = state.appliedBatiks.filter(x => x.key !== state.selectedPart.key);

            // Re-render
            if (window.BatikApp.Canvas) await window.BatikApp.Canvas.render();
            if (window.BatikApp.PartsList) window.BatikApp.PartsList.render();

            if (workspaceStatus) {
                workspaceStatus.textContent = `✓ Batik dihapus dari ${panelPartName.textContent}.`;
            }
            if (window.BatikApp.BatikPanel) window.BatikApp.BatikPanel.close();

        } catch (err) {
            console.error('[reset-part]', err);
            showErr(err.message || 'Gagal menghapus batik. Periksa API.');
        } finally {
            resetPartBtn.disabled = false;
        }
    });
};
