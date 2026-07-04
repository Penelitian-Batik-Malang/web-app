<div id="phase-upload">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4">
            <p class="text-sm text-white font-semibold mb-3">Unggah Foto Fashion</p>
            <div class="border-2 border-dashed border-amber-700/40 rounded-xl p-3 text-center">
                <img id="fashion-preview" class="w-full h-48 object-contain rounded-lg mb-3 bg-gray-800 hidden" alt="">
                <div id="fashion-placeholder" class="w-full h-48 rounded-lg mb-3 bg-gray-800 flex items-center justify-center">
                    <div class="text-center"><i class="bi bi-person-standing text-3xl text-gray-600"></i><p class="text-xs text-gray-600 mt-1">Belum ada foto</p></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button id="fashion-upload-btn" type="button" class="flex items-center justify-center gap-1 text-amber-400 text-sm border border-amber-700/60 rounded-lg py-2 hover:bg-amber-950/20 transition-colors">
                        <i class="bi bi-upload"></i> Unggah
                    </button>
                    <button id="fashion-camera-btn" type="button" class="flex items-center justify-center gap-1 text-amber-300 text-sm border border-amber-700/60 rounded-lg py-2 hover:bg-amber-950/20 transition-colors">
                        <i class="bi bi-camera-fill"></i> Kamera
                    </button>
                </div>
                <input id="fashion-input" type="file" accept=".jpg, .jpeg, .png, .webp, image/jpeg, image/png, image/webp" class="hidden">
                <input id="fashion-camera-input" type="file" accept=".jpg, .jpeg, .png, .webp, image/jpeg, image/png, image/webp" capture="environment" class="hidden">
            </div>
            <button id="analyze-btn" class="mt-4 w-full bg-primary hover:bg-amber-600 text-black font-bold py-2.5 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed" disabled>
                <i class="bi bi-cpu mr-2"></i>Analisis Pakaian
            </button>
            <p id="upload-status" class="text-xs text-gray-500 mt-2 text-center">Pilih foto fashion untuk memulai.</p>
        </div>
        <div class="lg:col-span-2">
            <p class="text-white font-semibold mb-3 text-sm">Contoh Fashion:</p>
            <div class="grid grid-cols-3 gap-2" id="fashion-samples">
                @foreach($fashionSamples as $sample)
                    <button type="button" class="sample-fashion border border-gray-700 rounded-lg overflow-hidden hover:border-primary transition-colors h-full" data-url="{{ $sample }}">
                        <img src="{{ $sample }}" class="w-full h-36 object-cover" alt="Fashion sample">
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
