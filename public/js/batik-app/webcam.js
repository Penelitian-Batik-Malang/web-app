/**
 * =========================================================================
 * BatikApp — Webcam Module
 * =========================================================================
 *
 * Mengelola akses webcam/kamera device untuk capture gambar fashion
 * langsung dari browser (desktop webcam atau kamera mobile).
 *
 * Fallback: jika getUserMedia tidak tersedia (browser lama, iOS Safari
 * restrictions), gunakan input file capture="environment".
 *
 * @module  BatikApp.Webcam
 * @depends BatikApp.State, BatikApp.FashionUpload
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};
window.BatikApp.Webcam = {};

/**
 * Inisialisasi modul webcam.
 * Dipanggil oleh main.js setelah DOM ready.
 */
window.BatikApp.Webcam.init = function () {
    const state = window.BatikApp.state;

    const $ = id => document.getElementById(id);
    const webcamModal    = $('webcam-modal');
    const webcamVideo    = $('webcam-video');
    const webcamCanvasEl = $('webcam-canvas');
    const webcamCapture  = $('webcam-capture-btn');
    const webcamCancel   = $('webcam-cancel-btn');
    const webcamClose    = $('webcam-close-btn');
    const fashionCameraInput = $('fashion-camera-input');

    if (!webcamModal || !webcamVideo) return;

    /**
     * Buka webcam dengan facing mode environment (kamera belakang).
     *
     * @param {string} target - Target capture ('fashion')
     */
    async function openWebcam(target) {
        state.webcamTarget = target;

        if (!navigator.mediaDevices?.getUserMedia) {
            fashionCameraInput?.click();
            return;
        }

        try {
            if (state.webcamStream) {
                state.webcamStream.getTracks().forEach(t => t.stop());
            }
            state.webcamStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
                audio: false,
            });
            webcamVideo.srcObject = state.webcamStream;
            webcamModal.style.display = 'flex';
            await webcamVideo.play();
        } catch (_) {
            fashionCameraInput?.click();
        }
    }

    /**
     * Tutup webcam dan release semua tracks.
     */
    function closeWebcam() {
        webcamModal.style.display = 'none';
        if (state.webcamStream) {
            state.webcamStream.getTracks().forEach(t => t.stop());
            state.webcamStream = null;
        }
        webcamVideo.srcObject = null;
    }

    // Expose
    window.BatikApp.Webcam.open = openWebcam;
    window.BatikApp.Webcam.close = closeWebcam;

    // ── Event Listeners ───────────────────────────────────────────

    webcamCapture?.addEventListener('click', () => {
        if (!webcamVideo.videoWidth) return;
        webcamCanvasEl.width  = webcamVideo.videoWidth;
        webcamCanvasEl.height = webcamVideo.videoHeight;
        webcamCanvasEl.getContext('2d').drawImage(webcamVideo, 0, 0);
        webcamCanvasEl.toBlob(blob => {
            if (!blob) return;
            const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
            
            if (state.webcamTarget === 'batik_panel' && window.BatikApp.BatikPanel?.setBatikImage) {
                 const reader = new FileReader();
                 reader.onload = async (e) => {
                     await window.BatikApp.BatikPanel.setBatikImage(null, e.target.result, file.name);
                 };
                 reader.readAsDataURL(file);
            } else {
                 const setFile = window.BatikApp.FashionUpload?.setFashionFile;
                 if (setFile) {
                     setFile(file);
                 }
            }
            closeWebcam();
        }, 'image/jpeg', 0.92);
    });

    webcamCancel?.addEventListener('click', closeWebcam);
    webcamClose?.addEventListener('click', closeWebcam);
};
