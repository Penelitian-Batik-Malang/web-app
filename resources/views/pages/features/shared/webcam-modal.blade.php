<div id="webcam-modal" class="hidden fixed inset-0 z-60 bg-black/80 items-center justify-center p-4" style="display:none">
    <div class="w-full max-w-xl bg-[#111] border border-gray-700 rounded-2xl p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-white font-semibold">Capture Kamera</h3>
            <button id="webcam-close-btn" class="text-gray-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
        </div>
        <video id="webcam-video" class="w-full h-72 object-cover rounded-lg bg-black" autoplay playsinline muted></video>
        <canvas id="webcam-canvas" class="hidden"></canvas>
        <div class="grid grid-cols-2 gap-3 mt-4">
            <button id="webcam-capture-btn" class="bg-primary hover:bg-amber-600 text-black font-bold py-2.5 rounded-lg">Ambil Foto</button>
            <button id="webcam-cancel-btn" class="border border-gray-600 text-white font-bold py-2.5 rounded-lg">Batal</button>
        </div>
    </div>
</div>
