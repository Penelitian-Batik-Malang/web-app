/**
 * =========================================================================
 * BatikApp — Fashion Upload Module
 * =========================================================================
 *
 * Mengelola upload gambar fashion dari berbagai sumber:
 *   - File picker (galeri perangkat)
 *   - Kamera device (mobile capture)
 *   - Sample fashion images (preset dari server)
 *
 * Setelah gambar dipilih, preview ditampilkan dan tombol
 * "Analisis Pakaian" diaktifkan.
 *
 * @module  BatikApp.FashionUpload
 * @depends BatikApp.State, BatikApp.Helpers
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.FashionUpload = {};

/**
 * Inisialisasi modul fashion upload.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.FashionUpload.init = function () {
    const state = window.BatikApp.state;
    const { readAsDataURL, urlToFile } = window.BatikApp.Helpers;

    const $ = id => document.getElementById(id);
    const fashionInput       = $('fashion-input');
    const fashionCameraInput = $('fashion-camera-input');
    const fashionUploadBtn   = $('fashion-upload-btn');
    const fashionCameraBtn   = $('fashion-camera-btn');
    const fashionPreview     = $('fashion-preview');
    const fashionPlaceholder = $('fashion-placeholder');
    const analyzeBtn         = $('analyze-btn');
    const uploadStatus       = $('upload-status');

    /**
     * Set file gambar fashion ke state dan update UI preview.
     *
     * @param {File} file - File gambar yang dipilih user
     */
    const setFashionFile = async (file) => {
        state.fashionFile = file;
        const src = await readAsDataURL(file);
        fashionPreview.src = src;
        fashionPreview.classList.remove('hidden');
        fashionPlaceholder.classList.add('hidden');
        analyzeBtn.disabled = false;
        uploadStatus.textContent = 'Klik "Analisis Pakaian" untuk melanjutkan.';
    };

    // Expose for webcam module
    window.BatikApp.FashionUpload.setFashionFile = setFashionFile;

    // ── Event Listeners ───────────────────────────────────────────
    fashionInput?.addEventListener('change', () => {
        if (fashionInput.files?.[0]) setFashionFile(fashionInput.files[0]);
    });

    fashionCameraInput?.addEventListener('change', () => {
        if (fashionCameraInput.files?.[0]) setFashionFile(fashionCameraInput.files[0]);
    });

    fashionUploadBtn?.addEventListener('click', () => fashionInput.click());

    fashionCameraBtn?.addEventListener('click', () => {
        if (window.BatikApp.Webcam) {
            window.BatikApp.Webcam.open('fashion');
        } else {
            fashionCameraInput.click();
        }
    });

    // ── Sample Fashion Images ─────────────────────────────────────
    document.querySelectorAll('.sample-fashion').forEach(el => {
        el.addEventListener('click', async () => {
            uploadStatus.textContent = 'Memuat gambar...';
            try {
                const file = await urlToFile(el.dataset.url, 'fashion_sample.jpg');
                await setFashionFile(file);
            } catch (e) {
                uploadStatus.textContent = 'Gagal memuat gambar sample.';
            }
        });
    });
};
