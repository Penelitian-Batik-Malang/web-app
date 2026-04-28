/**
 * =========================================================================
 * BatikApp — Workspace Controls (Reset, Finish, Back, Save)
 * =========================================================================
 *
 * Mengelola tombol-tombol navigasi di workspace dan result phase:
 *   - Reset   : Kembalikan gambar ke original (tanpa blend)
 *   - Finish  : Tampilkan perbandingan before/after
 *   - Back    : Kembali ke phase sebelumnya
 *   - Save    : Download gambar hasil sebagai PNG
 *
 * @module  BatikApp.WorkspaceControls
 * @depends BatikApp.State, BatikApp.Helpers, BatikApp.Canvas, BatikApp.PartsList
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.WorkspaceControls = {};

/**
 * Inisialisasi modul workspace controls.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.WorkspaceControls.init = function () {
    const state   = window.BatikApp.state;
    const helpers = window.BatikApp.Helpers;
    const config  = window.BatikAppConfig || {};

    const $ = id => document.getElementById(id);
    const resetBtn        = $('reset-btn');
    const finishBtn       = $('finish-btn');
    const backBtn         = $('back-to-upload-btn');
    const workspaceStatus = $('workspace-status');

    const phaseResult     = $('phase-result');
    const resultOrigImg   = $('result-original-img');
    const resultFinalImg  = $('result-final-img');
    const resultPartsList = $('result-parts-list');
    const resultSaveBtn   = $('result-save-btn');
    const resultBackBtn   = $('result-back-btn');

    // ── Reset Button ──────────────────────────────────────────────

    resetBtn?.addEventListener('click', async () => {
        if (!state.sessionId) return;
        resetBtn.disabled = true;
        workspaceStatus.textContent = 'Mereset...';

        try {
            const resp = await fetch(config.apiResetRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': helpers.csrf(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ session_id: state.sessionId }),
            });
            const data = await helpers.safeJson(resp);
            if (!resp.ok) throw new Error(data.message || 'Gagal reset.');

            if (data.image_b64) {
                state.fashionImage = await helpers.loadImage(`data:image/jpeg;base64,${data.image_b64}`);
            }
            state.blendedKeys.clear();
            state.appliedBatiks = [];

            if (window.BatikApp.Canvas) await window.BatikApp.Canvas.render();
            if (window.BatikApp.PartsList) window.BatikApp.PartsList.render();

            workspaceStatus.textContent = 'Gambar direset.';
        } catch (err) {
            workspaceStatus.textContent = err.message;
        } finally {
            resetBtn.disabled = false;
        }
    });

    // ── Finish Button (Show Result) ───────────────────────────────

    finishBtn?.addEventListener('click', () => {
        if (!state.fashionImage) return;

        // Buat canvas bersih dari gambar terkini
        const tmp = document.createElement('canvas');
        tmp.width = state.fashionImage.naturalWidth;
        tmp.height = state.fashionImage.naturalHeight;
        tmp.getContext('2d').drawImage(state.fashionImage, 0, 0);

        // Set gambar original vs final
        if (resultOrigImg) resultOrigImg.src = state.originalFashionImageSrc;
        if (resultFinalImg) resultFinalImg.src = tmp.toDataURL('image/png');

        // Render daftar batik yang diterapkan
        if (resultPartsList) {
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
        }

        helpers.setPhase('result');
    });

    // ── Save Result Button ────────────────────────────────────────

    resultSaveBtn?.addEventListener('click', () => {
        if (!resultFinalImg?.src) return;
        const a = document.createElement('a');
        a.download = 'batik-hasil.png';
        a.href = resultFinalImg.src;
        a.click();
    });

    // ── Back Buttons ──────────────────────────────────────────────

    resultBackBtn?.addEventListener('click', () => {
        helpers.setPhase('workspace');
    });

    backBtn?.addEventListener('click', () => {
        if (config.isRekomendasiMode && window.cbirData && Object.keys(window.cbirData).length) {
            // Mode rekomendasi: kembali ke phase CBIR, bukan upload
            helpers.setPhase('cbir-result');
        } else {
            helpers.setPhase('upload');
            state.sessionId = null;
            state.partsList = [];
            state.blendedKeys.clear();
            state.appliedBatiks = [];
            state.hoveredKey = null;
        }
    });
};
