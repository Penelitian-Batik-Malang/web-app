/**
 * =========================================================================
 * BatikApp — Main Orchestrator
 * =========================================================================
 *
 * File ini adalah entry point yang menginisialisasi semua modul
 * BatikApp dalam urutan yang benar. Pastikan semua modul sudah
 * di-load sebelum file ini.
 *
 * URUTAN LOADING (penting!):
 *   1. constants.js        — Warna & label bagian pakaian
 *   2. state.js             — State management
 *   3. helpers.js           — Utility functions
 *   4. fashion-upload.js    — Upload gambar fashion
 *   5. canvas.js            — Fashion canvas rendering
 *   6. parts-list.js        — Sidebar daftar bagian
 *   7. batik-panel.js       — Panel pilih & atur batik
 *   8. blend.js             — Blend API call
 *   9. workspace-controls.js — Reset, finish, back, save
 *  10. webcam.js            — Akses kamera
 *  11. inference.js         — Analisis fashion → deteksi
 *  12. main.js (INI)        — Orchestrator
 *
 * KONFIGURASI:
 *   Sebelum main.js di-load, pastikan window.BatikAppConfig sudah diset
 *   oleh Blade template (batik-app.blade.php) dengan:
 *     - isRekomendasiMode : boolean
 *     - apiInferenceRoute : string URL
 *     - apiResetRoute     : string URL
 *     - apiBlendRoute     : string URL
 *
 * @module  BatikApp.Main
 * @see     resources/views/pages/features/shared/scripts.blade.php
 * =========================================================================
 */

(function () {
    'use strict';

    /**
     * Inisialisasi semua modul BatikApp.
     *
     * Setiap modul memiliki method init() yang:
     *   - Mengambil referensi DOM elements
     *   - Mendaftarkan event listeners
     *   - Expose functions ke namespace global
     *
     * Guard: jika DOM element tidak ada (misal: halaman berbeda),
     * modul akan skip inisialisasi tanpa error.
     */
    function initAll() {
        const modules = [
            'FashionUpload',
            'Canvas',
            'PartsList',
            'BatikPanel',
            'Blend',
            'WorkspaceControls',
            'Webcam',
            'Inference', // Harus setelah Canvas dan PartsList
        ];

        for (const name of modules) {
            if (window.BatikApp[name]?.init) {
                try {
                    window.BatikApp[name].init();
                } catch (err) {
                    console.error(`[BatikApp] Failed to init module "${name}":`, err);
                }
            }
        }

        console.log('[BatikApp] All modules initialized.', {
            mode: window.BatikAppConfig?.isRekomendasiMode ? 'rekomendasi' : 'terapkan',
            modules: modules.filter(m => window.BatikApp[m]?.init),
        });
    }

    // Tunggu DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
