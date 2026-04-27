/**
 * =========================================================================
 * BatikApp — Helper Utilities
 * =========================================================================
 *
 * Fungsi-fungsi utility yang digunakan oleh seluruh modul BatikApp.
 * Semua helper bersifat pure function (tanpa side-effect) kecuali
 * yang berinteraksi dengan DOM/network.
 *
 * @module  BatikApp.Helpers
 * @see     public/js/batik-app/main.js — Orchestrator
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.Helpers = {};

/**
 * Ambil CSRF token dari meta tag.
 * Digunakan untuk semua fetch request ke backend Laravel.
 *
 * @returns {string} CSRF token
 */
window.BatikApp.Helpers.csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content || '';

// Backward compatibility
window.csrf = window.BatikApp.Helpers.csrf;

/**
 * Konversi array RGB ke string rgba().
 *
 * @param {number[]} rgb - Array [R, G, B]
 * @param {number} a - Alpha (0-1)
 * @returns {string} String rgba(), misal "rgba(128,128,128,0.5)"
 */
window.BatikApp.Helpers.toRgba = ([r, g, b], a) => `rgba(${r},${g},${b},${a})`;

/**
 * Konversi array RGB ke string hex color.
 *
 * @param {number[]} rgb - Array [R, G, B]
 * @returns {string} String hex, misal "#808080"
 */
window.BatikApp.Helpers.toHex = ([r, g, b]) =>
    '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('');

/**
 * Parse response JSON secara aman.
 *
 * Menangani kasus ketika server mengembalikan HTML alih-alih JSON
 * (CSRF expired, 403, redirect ke login, dll).
 *
 * @param {Response} resp - Fetch Response object
 * @returns {Promise<Object>} Parsed JSON
 * @throws {Error} Pesan error yang user-friendly dalam Bahasa Indonesia
 */
window.BatikApp.Helpers.safeJson = async (resp) => {
    const text = await resp.text();
    try {
        return JSON.parse(text);
    } catch (_) {
        if (text.includes('<!DOCTYPE') || text.includes('<html')) {
            if (resp.status === 419) throw new Error('Sesi telah kedaluwarsa. Silakan refresh halaman dan coba lagi.');
            if (resp.status === 403) throw new Error('Akses ditolak (403). Pastikan Anda memiliki izin akses menu ini.');
            if (resp.status === 302 || resp.url.includes('/login')) throw new Error('Sesi habis, silakan login kembali.');
            throw new Error(`Server mengembalikan HTML (status ${resp.status}). Coba refresh halaman.`);
        }
        throw new Error(`Response tidak valid dari server: ${text.substring(0, 100)}`);
    }
};

// Backward compatibility
window.safeJson = window.BatikApp.Helpers.safeJson;

/**
 * Load gambar dari URL/src dan kembalikan HTMLImageElement.
 *
 * @param {string} src - URL atau data URL gambar
 * @returns {Promise<HTMLImageElement>} Gambar yang sudah dimuat
 */
window.BatikApp.Helpers.loadImage = src => new Promise((res, rej) => {
    const img = new Image();
    img.onload = () => res(img);
    img.onerror = rej;
    img.src = src;
});

// Backward compatibility
window.loadImage = window.BatikApp.Helpers.loadImage;

/**
 * Baca file sebagai Data URL (base64).
 *
 * @param {File} file - File object
 * @returns {Promise<string>} Data URL string
 */
window.BatikApp.Helpers.readAsDataURL = file => new Promise((res, rej) => {
    const r = new FileReader();
    r.onload = e => res(e.target.result);
    r.onerror = rej;
    r.readAsDataURL(file);
});

/**
 * Download gambar dari URL dan konversi ke File object.
 *
 * Digunakan untuk mengkonversi URL sample fashion/batik menjadi
 * File object yang bisa di-append ke FormData.
 *
 * @param {string} url - URL gambar
 * @param {string} name - Nama file yang diinginkan
 * @returns {Promise<File>} File object
 * @throws {Error} Jika fetch gagal
 */
window.BatikApp.Helpers.urlToFile = async (url, name) => {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('Gagal memuat gambar');
    const blob = await resp.blob();
    return new File([blob], name, { type: blob.type });
};

/**
 * Kelola perpindahan antar phase UI.
 *
 * Phase yang tersedia:
 *   - 'upload'     : Form upload gambar fashion
 *   - 'loading'    : Loading indicator saat inference
 *   - 'cbir-result': Hasil rekomendasi CBIR (hanya mode rekomendasi)
 *   - 'workspace'  : Canvas workspace + parts list
 *   - 'result'     : Perbandingan sebelum/sesudah
 *
 * @param {string} phase - Nama phase yang akan ditampilkan
 */
window.BatikApp.Helpers.setPhase = (phase) => {
    const $ = id => document.getElementById(id);
    $('phase-upload')?.classList.toggle('hidden', phase !== 'upload');
    $('phase-loading')?.classList.toggle('hidden', phase !== 'loading');
    $('phase-workspace')?.classList.toggle('hidden', phase !== 'workspace');
    $('phase-result')?.classList.toggle('hidden', phase !== 'result');

    // Phase CBIR Result — hanya ada di rekomendasi-batik
    const phaseCbir = document.getElementById('phase-cbir-result');
    if (phaseCbir) phaseCbir.classList.toggle('hidden', phase !== 'cbir-result');
};

// Backward compatibility
window.setPhase = window.BatikApp.Helpers.setPhase;
