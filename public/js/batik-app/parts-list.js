/**
 * =========================================================================
 * BatikApp — Parts List Module (Sidebar Daftar Bagian Pakaian)
 * =========================================================================
 *
 * Mengelola sidebar yang menampilkan daftar bagian pakaian terdeteksi.
 * Setiap item menampilkan nama bagian, dimensi, confidence score,
 * dan status blend (centang jika sudah diterapkan).
 *
 * Interaksi:
 *   - Hover item → highlight mask di canvas
 *   - Klik item  → buka panel batik untuk blend
 *
 * @module  BatikApp.PartsList
 * @depends BatikApp.State, BatikApp.Constants, BatikApp.Canvas
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.PartsList = {};

/**
 * Inisialisasi modul parts list.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.PartsList.init = function () {
    const state       = window.BatikApp.state;
    const PART_COLORS = window.BatikApp.PART_COLORS;
    const { toRgba }  = window.BatikApp.Helpers;

    const partsListEl = document.getElementById('parts-list');
    if (!partsListEl) return;

    /**
     * Render daftar bagian pakaian di sidebar.
     *
     * Membuat tombol untuk setiap bagian terdeteksi dengan:
     *   - Indikator warna (dot)
     *   - Nama bagian + dimensi + score
     *   - Icon status (centang jika sudah blend, chevron jika belum)
     *   - Hover & click event handlers
     */
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
                (isBlended
                    ? 'border-amber-600/50 bg-amber-950/20'
                    : 'border-gray-700 hover:border-amber-600/40 hover:bg-gray-800/50');
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

            btn.addEventListener('mouseenter', () => {
                state.hoveredKey = part.key;
                if (window.BatikApp.Canvas) window.BatikApp.Canvas.render();
            });
            btn.addEventListener('mouseleave', () => {
                state.hoveredKey = null;
                if (window.BatikApp.Canvas) window.BatikApp.Canvas.render();
            });
            btn.addEventListener('click', () => {
                if (window.BatikApp.BatikPanel) window.BatikApp.BatikPanel.open(part);
            });

            partsListEl.appendChild(btn);
        }
    }

    // Expose render function
    window.BatikApp.PartsList.render = renderPartsList;
    window.renderPartsList = renderPartsList; // backward compat
};
