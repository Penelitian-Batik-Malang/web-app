/**
 * MLDetector — Shared object untuk popup integrasi model ML.
 * Mendukung pola reusable:
 * - Input: image / text
 * - Output: text / image
 */
window.MLDetector = {
    _files: {},
    _streams: {},
    _liveTimers: {},
    _liveInFlight: {},

    open(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    },

    close(id) {
        this.stopWebcam(id);
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    },

    handleBackdropClick(event, id) {
        if (event.target === document.getElementById(id)) {
            this.close(id);
        }
    },

    _getMeta(id) {
        const modal = document.getElementById(id);
        return {
            inputType: modal?.dataset?.inputType || 'image',
            outputType: modal?.dataset?.outputType || 'text',
            endpoint: modal?.dataset?.endpoint || '',
        };
    },

    handleDrop(event, id) {
        event.preventDefault();
        const dropzone = document.getElementById(`${id}-dropzone`);
        if (dropzone) {
            dropzone.classList.remove('border-primary', 'bg-primary/5');
        }
        const file = event.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            this.handleFile(file, id);
        }
    },

    handleFile(file, id) {
        if (!file) return;
        this._files[id] = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById(`${id}-preview-img`);
            if (preview) {
                preview.src = e.target.result;
            }

            const uploadZone = document.getElementById(`${id}-upload-zone`);
            const previewZone = document.getElementById(`${id}-preview-zone`);
            if (uploadZone) uploadZone.classList.add('hidden');
            if (previewZone) previewZone.classList.remove('hidden');

            const previewCol = document.getElementById(`${id}-preview-col`);
            if (previewCol) previewCol.classList.remove('hidden');

            this._hideResultCols(id);
        };
        reader.readAsDataURL(file);
    },

    reset(id) {
        const meta = this._getMeta(id);
        this._files[id] = null;
        this.stopWebcam(id);

        const uploadZone = document.getElementById(`${id}-upload-zone`);
        const previewZone = document.getElementById(`${id}-preview-zone`);
        const resultCol = document.getElementById(`${id}-result-col`);
        const footerLabel = document.getElementById(`${id}-footer-label`);
        const fileInput = document.getElementById(`${id}-file-input`);
        const cameraInput = document.getElementById(`${id}-camera-input`);
        const textInput = document.getElementById(`${id}-text-input`);

        if (uploadZone) {
            if (meta.inputType === 'image') uploadZone.classList.remove('hidden');
            else uploadZone.classList.add('hidden');
        }
        if (previewZone) {
            if (meta.inputType === 'image') previewZone.classList.add('hidden');
            else previewZone.classList.remove('hidden');
        }
        if (resultCol) {
            if (meta.inputType === 'text') {
                resultCol.classList.remove('hidden');
                resultCol.classList.add('flex');
            } else {
                resultCol.classList.add('hidden');
            }
        }
        if (footerLabel) footerLabel.textContent = 'Deteksi Sekarang?';
        if (fileInput) fileInput.value = '';
        if (cameraInput) cameraInput.value = '';
        if (textInput) textInput.value = '';

        this._hideResultCols(id);
    },

    _hideResultCols(id) {
        const loading = document.getElementById(`${id}-loading`);
        const resultCard = document.getElementById(`${id}-result-card`);
        const resultImageCard = document.getElementById(`${id}-result-image-card`);
        const errorCard = document.getElementById(`${id}-error-card`);
        const bar = document.getElementById(`${id}-result-bar`);

        if (loading) {
            loading.classList.add('hidden');
            loading.classList.remove('flex');
        }
        if (resultCard) resultCard.classList.add('hidden');
        if (resultImageCard) resultImageCard.classList.add('hidden');
        if (errorCard) errorCard.classList.add('hidden');
        if (bar) bar.style.width = '0%';
    },

    async mlRequest({ endpoint, method = 'POST', payloadType = 'form-data', payload }) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
        };

        let body = payload;
        if (payloadType === 'json') {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(payload || {});
        }

        const response = await fetch(endpoint, {
            method,
            headers,
            body,
        });
        return response.json();
    },

    async scan(id, endpoint, inputType = 'image', outputType = 'text') {
        const meta = this._getMeta(id);
        const effectiveInputType = inputType || meta.inputType || 'image';
        const effectiveOutputType = outputType || meta.outputType || 'text';
        const resultCol = document.getElementById(`${id}-result-col`);
        const footerLabel = document.getElementById(`${id}-footer-label`);
        const scanBtn = document.getElementById(`${id}-scan-btn`);

        let payloadType = 'form-data';
        let payload = null;

        if (effectiveInputType === 'text') {
            payloadType = 'json';
            const textInput = document.getElementById(`${id}-text-input`);
            const textValue = textInput?.value?.trim() || '';
            if (!textValue) {
                alert('Masukkan teks terlebih dahulu.');
                return;
            }
            payload = { text: textValue };
        } else {
            const file = this._files[id];
            if (!file) {
                // UX: kalau belum ada file, coba buka kamera langsung (mobile capture) supaya sesuai ekspektasi "Scan".
                const camInput = document.getElementById(`${id}-camera-input`);
                if (camInput) {
                    camInput.click();
                    return;
                }
                alert('Pilih atau unggah gambar terlebih dahulu.');
                return;
            }
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            payload = formData;
        }

        if (resultCol) {
            resultCol.classList.remove('hidden');
            resultCol.classList.add('flex');
        }
        this._hideResultCols(id);
        const loading = document.getElementById(`${id}-loading`);
        if (loading) {
            loading.classList.remove('hidden');
            loading.classList.add('flex');
        }
        if (scanBtn) scanBtn.disabled = true;

        try {
            const data = await this.mlRequest({
                endpoint,
                payloadType,
                payload,
            });

            if (loading) {
                loading.classList.add('hidden');
                loading.classList.remove('flex');
            }
            if (scanBtn) scanBtn.disabled = false;
            if (footerLabel) footerLabel.textContent = 'Ingin coba lagi?';

            if (data.success && data.result) {
                this._showResult(id, data.result, effectiveOutputType);
            } else {
                const errCard = document.getElementById(`${id}-error-card`);
                const errMsg = document.getElementById(`${id}-error-msg`);
                if (errMsg) {
                    errMsg.textContent = data.message || 'Terjadi kesalahan tidak diketahui.';
                }
                if (errCard) errCard.classList.remove('hidden');
            }
        } catch (_err) {
            if (loading) {
                loading.classList.add('hidden');
                loading.classList.remove('flex');
            }
            if (scanBtn) scanBtn.disabled = false;
            const errCard = document.getElementById(`${id}-error-card`);
            const errMsg = document.getElementById(`${id}-error-msg`);
            if (errMsg) {
                errMsg.textContent = 'Tidak dapat menghubungi server AI. Periksa koneksi.';
            }
            if (errCard) errCard.classList.remove('hidden');
        }
    },

    _showResult(id, result, outputType = 'text') {
        if (outputType === 'image') {
            const imageCard = document.getElementById(`${id}-result-image-card`);
            const imageEl = document.getElementById(`${id}-result-image`);
            const captionEl = document.getElementById(`${id}-result-image-caption`);
            const src = result.image_url || result.image || result.output_image || '';
            if (imageEl) imageEl.src = src;
            if (captionEl) captionEl.textContent = result.description || result.caption || '';
            if (imageCard) imageCard.classList.remove('hidden');
            return;
        }

        const label = result.label || 'Tidak Diketahui';
        const confidence = Number(result.confidence || 0);
        const pct = confidence > 1 ? confidence : confidence * 100;
        const desc = result.description || '-';

        const labelEl = document.getElementById(`${id}-result-label`);
        const pctEl = document.getElementById(`${id}-result-pct`);
        const descEl = document.getElementById(`${id}-result-desc`);
        const card = document.getElementById(`${id}-result-card`);
        const bar = document.getElementById(`${id}-result-bar`);

        if (labelEl) labelEl.textContent = label;
        if (pctEl) pctEl.textContent = pct.toFixed(2) + '%';
        if (descEl) descEl.textContent = desc;
        if (card) card.classList.remove('hidden');
        if (bar) {
            setTimeout(() => {
                bar.style.width = Math.min(Math.max(pct, 0), 100) + '%';
            }, 100);
        }
    },

    primaryAction(id, endpoint, inputType = 'image', outputType = 'text') {
        // Satu tombol utama:
        // - jika input image dan belum ada file → buka kamera
        // - jika sudah ada file → jalankan scan
        if (inputType === 'image') {
            const file = this._files[id];
            if (!file) {
                this.openCamera(id);
                return;
            }
        }
        this.scan(id, endpoint, inputType, outputType);
    },

    openCamera(id) {
        // Prefer webcam preview (desktop). Jika tidak tersedia, fallback ke input capture (mobile).
        if (navigator.mediaDevices?.getUserMedia) {
            this.startWebcam(id);
            return;
        }
        const camInput = document.getElementById(`${id}-camera-input`);
        if (camInput) camInput.click();
        else alert('Fitur kamera tidak tersedia di perangkat ini.');
    },

    async startWebcam(id) {
        const zone = document.getElementById(`${id}-webcam-zone`);
        const video = document.getElementById(`${id}-webcam-video`);
        if (!zone || !video) return;

        // Stop existing stream (if any), tapi jangan sembunyikan zone sebelum kita tampilkan.
        this.stopWebcam(id, { hideZone: false });
        zone.classList.remove('hidden');

        if (!navigator.mediaDevices?.getUserMedia) {
            alert('Browser tidak mendukung akses kamera (getUserMedia). Gunakan unggah galeri.');
            zone.classList.add('hidden');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
                audio: false,
            });
            this._streams[id] = stream;
            video.srcObject = stream;
            await video.play();
            this.startLiveDetection(id);
        } catch (_err) {
            alert('Izin kamera ditolak atau kamera tidak tersedia.');
            zone.classList.add('hidden');
        }
    },

    stopWebcam(id, opts = { hideZone: true }) {
        this.stopLiveDetection(id);
        const zone = document.getElementById(`${id}-webcam-zone`);
        const video = document.getElementById(`${id}-webcam-video`);
        const stream = this._streams[id];
        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            delete this._streams[id];
        }
        if (video) {
            try { video.pause(); } catch (_e) {}
            video.srcObject = null;
        }
        if (zone && opts.hideZone) zone.classList.add('hidden');
    },

    captureWebcam(id) {
        const video = document.getElementById(`${id}-webcam-video`);
        const canvas = document.getElementById(`${id}-webcam-canvas`);
        if (!video || !canvas) return;

        const w = video.videoWidth || 1280;
        const h = video.videoHeight || 720;
        canvas.width = w;
        canvas.height = h;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.drawImage(video, 0, 0, w, h);

        canvas.toBlob((blob) => {
            if (!blob) return;
            const file = new File([blob], 'webcam.jpg', { type: 'image/jpeg' });
            this.handleFile(file, id);
            this.stopWebcam(id);

            // Auto-detect setelah ambil foto (sesuai ekspektasi "scan" via kamera).
            const meta = this._getMeta(id);
            if (meta.endpoint) {
                this.scan(id, meta.endpoint, meta.inputType, meta.outputType);
            }
        }, 'image/jpeg', 0.92);
    },

    startLiveDetection(id) {
        const meta = this._getMeta(id);
        if (!meta.endpoint || meta.inputType !== 'image') return;

        // Reset loop lama dulu sebelum menyalakan UI live baru.
        this.stopLiveDetection(id);
        this._liveInFlight[id] = false;

        const liveCard = document.getElementById(`${id}-live-result`);
        const labelEl = document.getElementById(`${id}-live-label`);
        const confEl = document.getElementById(`${id}-live-confidence`);
        if (liveCard) liveCard.classList.remove('hidden');
        if (labelEl) labelEl.textContent = 'Mendeteksi...';
        if (confEl) confEl.textContent = 'Akurasi: -';

        const runOnce = async () => {
            if (this._liveInFlight[id]) return;
            const stream = this._streams[id];
            if (!stream) return;

            const video = document.getElementById(`${id}-webcam-video`);
            const canvas = document.getElementById(`${id}-webcam-canvas`);
            if (!video || !canvas || video.readyState < 2) return;

            const w = video.videoWidth || 640;
            const h = video.videoHeight || 360;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            ctx.drawImage(video, 0, 0, w, h);

            this._liveInFlight[id] = true;
            try {
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.75));
                if (!blob) return;
                const formData = new FormData();
                formData.append('image', blob, 'live-scan.jpg');
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

                const data = await this.mlRequest({
                    endpoint: meta.endpoint,
                    payloadType: 'form-data',
                    payload: formData,
                });

                if (data?.success && data?.result) {
                    const label = data.result.label || 'Tidak Diketahui';
                    const confidence = Number(data.result.confidence || 0);
                    const pct = confidence > 1 ? confidence : confidence * 100;
                    if (labelEl) labelEl.textContent = `${meta.outputType === 'text' ? 'Hasil' : 'Prediksi'}: ${label}`;
                    if (confEl) confEl.textContent = `Akurasi: ${pct.toFixed(2)}%`;
                }
            } catch (_err) {
                // Abaikan error sesekali agar live loop tetap jalan.
            } finally {
                this._liveInFlight[id] = false;
            }
        };

        // Jalankan sekali langsung, lalu lanjut berkala.
        runOnce();
        // Live scan berkala, cukup cepat tapi tetap aman untuk server.
        this._liveTimers[id] = setInterval(runOnce, 1200);
    },

    stopLiveDetection(id) {
        const t = this._liveTimers[id];
        if (t) {
            clearInterval(t);
            delete this._liveTimers[id];
        }
        delete this._liveInFlight[id];

        const liveCard = document.getElementById(`${id}-live-result`);
        if (liveCard) liveCard.classList.add('hidden');
    },
};
