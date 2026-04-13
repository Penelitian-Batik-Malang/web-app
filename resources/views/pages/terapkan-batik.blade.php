@extends('layouts.layout')
@section('title', 'Terapkan Batik')

@section('content')
<div class="max-w-6xl mx-auto space-y-8" id="apply-batik-app">
    <div class="text-center">
        <h1 class="text-4xl font-playfair font-bold text-white">Terapkan Batik</h1>
        <p class="text-gray-400 mt-3 max-w-3xl mx-auto">
            Unggah fotomu, pilih batik dari galeri atau unggah batik sendiri, lalu terapkan motif batik ke citra fashion.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
        <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4">
            <p class="text-sm text-white font-semibold mb-3">Step 1: Unggah fotomu</p>
            <div class="border-2 border-dashed border-amber-700/50 rounded-xl p-4 text-center">
                <img id="fashion-preview" class="w-full h-56 object-cover rounded-lg mb-3 bg-gray-800" alt="Preview Fashion">
                <div class="grid grid-cols-2 gap-2">
                    <button id="fashion-upload-btn" type="button" class="inline-flex items-center justify-center gap-2 text-amber-400 text-sm font-medium border border-amber-700/60 rounded-lg py-2 hover:bg-amber-950/20 transition-colors">
                        <i class="bi bi-upload"></i> Unggah
                    </button>
                    <button id="fashion-camera-btn" type="button" class="inline-flex items-center justify-center gap-2 text-amber-300 text-sm font-medium border border-amber-700/60 rounded-lg py-2 hover:bg-amber-950/20 transition-colors">
                        <i class="bi bi-camera-fill"></i> Scan
                    </button>
                </div>
                <input id="fashion-input" type="file" accept="image/*" class="hidden">
                <input id="fashion-camera-input" type="file" accept="image/*" capture="environment" class="hidden">
            </div>
        </div>

        <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4">
            <p class="text-sm text-white font-semibold mb-3">Step 2: Pilih batik</p>
            <div class="border-2 border-dashed border-amber-700/50 rounded-xl p-4 text-center">
                <img id="batik-preview" class="w-full h-56 object-cover rounded-lg mb-3 bg-gray-800" alt="Preview Batik">
                <div class="grid grid-cols-2 gap-2">
                    <button id="batik-upload-btn" type="button" class="inline-flex items-center justify-center gap-2 text-amber-400 text-sm font-medium border border-amber-700/60 rounded-lg py-2 hover:bg-amber-950/20 transition-colors">
                        <i class="bi bi-upload"></i> Unggah
                    </button>
                    <button id="batik-camera-btn" type="button" class="inline-flex items-center justify-center gap-2 text-amber-300 text-sm font-medium border border-amber-700/60 rounded-lg py-2 hover:bg-amber-950/20 transition-colors">
                        <i class="bi bi-camera-fill"></i> Scan
                    </button>
                </div>
                <input id="batik-input" type="file" accept="image/*" class="hidden">
                <input id="batik-camera-input" type="file" accept="image/*" capture="environment" class="hidden">
            </div>
        </div>

        <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4">
            <p class="text-sm text-amber-400 font-semibold mb-3">Hasil Penerapan</p>
            <div class="w-full h-56 rounded-lg bg-gray-800 relative overflow-hidden">
                <img id="result-preview" class="w-full h-full object-cover hidden" alt="Hasil Penerapan">
                <div id="result-placeholder" class="absolute inset-0 bg-[linear-gradient(45deg,#1f2937_25%,#111827_25%,#111827_50%,#1f2937_50%,#1f2937_75%,#111827_75%,#111827_100%)] bg-[length:18px_18px] flex items-center justify-center">
                    <div class="text-center">
                        <i class="bi bi-image text-2xl text-gray-400"></i>
                        <p class="text-xs text-gray-400 mt-2">Hasil akan tampil di sini</p>
                    </div>
                </div>
            </div>
            <p id="apply-status" class="text-xs text-gray-400 mt-3">Pilih 2 input lalu klik Terapkan.</p>
        </div>
    </div>

    <div class="flex items-center justify-center gap-3">
        <button id="apply-btn" class="bg-primary hover:bg-amber-600 text-black font-bold px-10 py-2.5 rounded-lg transition-colors">
            Terapkan
        </button>
        <button id="reset-btn" class="border border-amber-700/60 hover:border-primary text-white font-bold px-10 py-2.5 rounded-lg transition-colors">
            Reset
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <p class="text-white font-semibold mb-3">Contoh Fashion:</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="fashion-samples">
                @foreach($fashionSamples as $sample)
                    <button type="button" class="sample-fashion border border-gray-700 rounded-lg overflow-hidden hover:border-primary transition-colors" data-url="{{ $sample }}">
                        <img src="{{ $sample }}" class="w-full h-40 object-cover" alt="Fashion sample">
                    </button>
                @endforeach
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between gap-3 mb-3">
                <p class="text-white font-semibold">Batik Malang:</p>
                <input id="batik-search" type="text" placeholder="Cari motif (misal: Kawung, Parang...)" class="w-72 max-w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
            </div>
            <div class="grid grid-cols-2 gap-3" id="batik-samples">
                @foreach($batikSamples as $batik)
                    <button
                        type="button"
                        class="sample-batik text-left border border-gray-700 rounded-lg overflow-hidden hover:border-primary transition-colors"
                        data-url="{{ $batik['image_url'] }}"
                        data-name="{{ strtolower($batik['name']) }}"
                    >
                        <img src="{{ $batik['image_url'] }}" class="w-full h-32 object-cover" alt="{{ $batik['name'] }}">
                        <div class="p-2">
                            <p class="text-primary text-sm font-semibold">{{ $batik['name'] }}</p>
                            <p class="text-gray-500 text-xs line-clamp-2">{{ $batik['description'] ?: '-' }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div id="webcam-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="w-full max-w-2xl bg-[#111] border border-gray-700 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-white font-semibold">Capture Kamera</h3>
                <button id="webcam-close-btn" type="button" class="text-gray-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
            </div>
            <video id="webcam-video" class="w-full h-80 object-cover rounded-lg bg-black" autoplay playsinline muted></video>
            <canvas id="webcam-canvas" class="hidden"></canvas>
            <div class="grid grid-cols-2 gap-3 mt-4">
                <button id="webcam-capture-btn" type="button" class="bg-primary hover:bg-amber-600 text-black font-bold py-2.5 rounded-lg">Ambil Foto</button>
                <button id="webcam-cancel-btn" type="button" class="border border-gray-600 hover:border-gray-500 text-white font-bold py-2.5 rounded-lg">Batal</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const fashionInput = document.getElementById('fashion-input');
    const fashionCameraInput = document.getElementById('fashion-camera-input');
    const batikInput = document.getElementById('batik-input');
    const batikCameraInput = document.getElementById('batik-camera-input');
    const fashionUploadBtn = document.getElementById('fashion-upload-btn');
    const fashionCameraBtn = document.getElementById('fashion-camera-btn');
    const batikUploadBtn = document.getElementById('batik-upload-btn');
    const batikCameraBtn = document.getElementById('batik-camera-btn');
    const fashionPreview = document.getElementById('fashion-preview');
    const batikPreview = document.getElementById('batik-preview');
    const resultPreview = document.getElementById('result-preview');
    const resultPlaceholder = document.getElementById('result-placeholder');
    const applyBtn = document.getElementById('apply-btn');
    const resetBtn = document.getElementById('reset-btn');
    const applyStatus = document.getElementById('apply-status');
    const batikSearch = document.getElementById('batik-search');
    const webcamModal = document.getElementById('webcam-modal');
    const webcamVideo = document.getElementById('webcam-video');
    const webcamCanvas = document.getElementById('webcam-canvas');
    const webcamCaptureBtn = document.getElementById('webcam-capture-btn');
    const webcamCancelBtn = document.getElementById('webcam-cancel-btn');
    const webcamCloseBtn = document.getElementById('webcam-close-btn');

    const state = {
        fashionFile: null,
        batikFile: null,
        webcamTarget: '',
        webcamStream: null,
    };

    const setPreview = (img, src) => { 
        if (!src) {
            img.src = '';
            return;
        }
        img.src = src; 
    };

    const urlToFile = async (url, filename) => {
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Gagal mengambil gambar sample.');
            const blob = await response.blob();
            return new File([blob], filename, { type: blob.type });
        } catch (err) {
            console.error('urlToFile error:', err);
            throw err;
        }
    };

    const readFile = (file, cb) => {
        const reader = new FileReader();
        reader.onload = (e) => cb(e.target.result);
        reader.readAsDataURL(file);
    };

    const openWebcam = async (target) => {
        state.webcamTarget = target;
        if (!navigator.mediaDevices?.getUserMedia) {
            // Fallback mobile capture input
            (target === 'fashion' ? fashionCameraInput : batikCameraInput).click();
            return;
        }

        try {
            if (state.webcamStream) {
                state.webcamStream.getTracks().forEach((track) => track.stop());
            }
            state.webcamStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            webcamVideo.srcObject = state.webcamStream;
            webcamModal.classList.remove('hidden');
            await webcamVideo.play();
        } catch (_err) {
            (target === 'fashion' ? fashionCameraInput : batikCameraInput).click();
        }
    };

    const closeWebcam = () => {
        webcamModal.classList.add('hidden');
        if (state.webcamStream) {
            state.webcamStream.getTracks().forEach((track) => track.stop());
            state.webcamStream = null;
        }
        webcamVideo.srcObject = null;
        state.webcamTarget = '';
    };

    const applyCapturedBlob = (blob, target) => {
        const safeTarget = target || state.webcamTarget || 'capture';
        const file = new File([blob], `${safeTarget}.jpg`, { type: 'image/jpeg' });
        readFile(file, (src) => {
            if (safeTarget === 'fashion') {
                state.fashionFile = file;
                setPreview(fashionPreview, src);
            } else if (safeTarget === 'batik') {
                state.batikFile = file;
                setPreview(batikPreview, src);
            }
        });
    };

    fashionInput.addEventListener('change', () => {
        const file = fashionInput.files?.[0];
        if (!file) return;
        state.fashionFile = file;
        readFile(file, (src) => setPreview(fashionPreview, src));
    });
    fashionCameraInput.addEventListener('change', () => {
        const file = fashionCameraInput.files?.[0];
        if (!file) return;
        state.fashionFile = file;
        readFile(file, (src) => setPreview(fashionPreview, src));
    });

    batikInput.addEventListener('change', () => {
        const file = batikInput.files?.[0];
        if (!file) return;
        state.batikFile = file;
        readFile(file, (src) => setPreview(batikPreview, src));
    });
    batikCameraInput.addEventListener('change', () => {
        const file = batikCameraInput.files?.[0];
        if (!file) return;
        state.batikFile = file;
        readFile(file, (src) => setPreview(batikPreview, src));
    });

    fashionUploadBtn.addEventListener('click', () => fashionInput.click());
    batikUploadBtn.addEventListener('click', () => batikInput.click());
    fashionCameraBtn.addEventListener('click', () => openWebcam('fashion'));
    batikCameraBtn.addEventListener('click', () => openWebcam('batik'));

    webcamCaptureBtn.addEventListener('click', () => {
        if (!state.webcamTarget || !webcamVideo.videoWidth || !webcamVideo.videoHeight) return;
        const target = state.webcamTarget; // lock target sebelum state di-reset
        webcamCanvas.width = webcamVideo.videoWidth;
        webcamCanvas.height = webcamVideo.videoHeight;
        const ctx = webcamCanvas.getContext('2d');
        ctx.drawImage(webcamVideo, 0, 0, webcamCanvas.width, webcamCanvas.height);
        webcamCanvas.toBlob((blob) => {
            if (!blob) return;
            applyCapturedBlob(blob, target);
            closeWebcam();
        }, 'image/jpeg', 0.92);
    });
    webcamCancelBtn.addEventListener('click', closeWebcam);
    webcamCloseBtn.addEventListener('click', closeWebcam);

    document.querySelectorAll('.sample-fashion').forEach((el) => {
        el.addEventListener('click', async () => {
            const url = el.dataset.url;
            if (!url) return;
            applyStatus.textContent = 'Memuat citra fashion...';
            try {
                const file = await urlToFile(url, 'fashion_sample.jpg');
                state.fashionFile = file;
                setPreview(fashionPreview, url);
                applyStatus.textContent = 'Citra fashion terpilih.';
            } catch (err) {
                applyStatus.textContent = 'Gagal memuat citra fashion.';
            }
        });
    });

    document.querySelectorAll('.sample-batik').forEach((el) => {
        el.addEventListener('click', async () => {
            const url = el.dataset.url;
            if (!url) return;
            applyStatus.textContent = 'Memuat citra batik...';
            try {
                const file = await urlToFile(url, 'batik_sample.jpg');
                state.batikFile = file;
                setPreview(batikPreview, url);
                applyStatus.textContent = 'Citra batik terpilih.';
            } catch (err) {
                applyStatus.textContent = 'Gagal memuat citra batik.';
            }
        });
    });

    batikSearch.addEventListener('input', () => {
        const q = batikSearch.value.toLowerCase();
        document.querySelectorAll('.sample-batik').forEach((el) => {
            const ok = (el.dataset.name || '').includes(q);
            el.classList.toggle('hidden', !ok);
        });
    });

    applyBtn.addEventListener('click', async () => {
        if (!state.fashionFile || !state.batikFile) {
            applyStatus.textContent = 'Pilih citra fashion dan citra batik terlebih dahulu.';
            return;
        }

        const formData = new FormData();
        formData.append('fashion_image', state.fashionFile);
        formData.append('batik_image', state.batikFile);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        applyBtn.disabled = true;
        applyStatus.textContent = 'Memproses penerapan batik...';

        try {
            const resp = await fetch("{{ route('api.apply.batik') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: formData
            });

            if (!resp.ok) {
                const json = await resp.json().catch(() => ({}));
                throw new Error(json.message || 'Gagal memproses gambar. Pastikan API model aktif.');
            }

            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);
            resultPreview.src = url;
            resultPreview.classList.remove('hidden');
            resultPlaceholder.classList.add('hidden');
            applyStatus.textContent = 'Berhasil diterapkan.';
        } catch (err) {
            applyStatus.textContent = err.message || 'Terjadi kesalahan.';
        } finally {
            applyBtn.disabled = false;
        }
    });

    resetBtn.addEventListener('click', () => {
        state.fashionFile = null;
        state.batikFile = null;
        fashionInput.value = '';
        batikInput.value = '';
        setPreview(fashionPreview, '');
        setPreview(batikPreview, '');
        setPreview(resultPreview, '');
        resultPreview.classList.add('hidden');
        resultPlaceholder.classList.remove('hidden');
        applyStatus.textContent = 'Pilih 2 input lalu klik Terapkan.';
        closeWebcam();
    });

    // Set default sample awal jika tersedia
    const initSamples = async () => {
        const firstFashion = document.querySelector('.sample-fashion');
        const firstBatik = document.querySelector('.sample-batik');
        
        if (firstFashion?.dataset.url) {
            try {
                const file = await urlToFile(firstFashion.dataset.url, 'fashion_init.jpg');
                state.fashionFile = file;
                setPreview(fashionPreview, firstFashion.dataset.url);
            } catch (e) {}
        }
        if (firstBatik?.dataset.url) {
            try {
                const file = await urlToFile(firstBatik.dataset.url, 'batik_init.jpg');
                state.batikFile = file;
                setPreview(batikPreview, firstBatik.dataset.url);
            } catch (e) {}
        }
    };
    initSamples();
})();
</script>
@endpush
