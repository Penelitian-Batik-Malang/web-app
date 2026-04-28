<div id="phase-result" class="hidden">
    <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-6 lg:p-8 max-w-5xl mx-auto my-6">
        <h2 class="text-3xl font-playfair font-bold text-white mb-8 text-center"><i class="bi bi-stars text-primary mr-2"></i>Hasil Akhir</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-10 mb-8 items-start">
            <div class="flex flex-col items-center">
                <p class="text-sm font-semibold text-gray-400 mb-3 bg-gray-800 px-4 py-1 rounded-full border border-gray-700">Foto Asli</p>
                <img id="result-original-img" class="w-full max-h-[50vh] object-contain rounded-xl bg-[#0a0a0a] border border-gray-800 shadow-inner" src="" alt="Asli">
            </div>
            <div class="flex flex-col items-center">
                <p class="text-sm font-semibold text-primary mb-3 bg-amber-950/40 px-4 py-1 rounded-full border border-amber-700/40">Hasil Terapkan Batik</p>
                <img id="result-final-img" class="w-full max-h-[50vh] object-contain rounded-xl bg-[#0a0a0a] border border-amber-700/40 shadow-[0_0_20px_rgba(217,119,6,0.15)]" src="" alt="Hasil Akhir">
            </div>
        </div>
        
        <div class="mb-8 max-w-2xl mx-auto">
            <p class="text-sm font-semibold text-white mb-3"><i class="bi bi-card-list mr-2"></i>Daftar Modifikasi Batik:</p>
            <div class="bg-[#0a0a0a] rounded-xl max-h-60 overflow-y-auto border border-gray-800 shadow-inner">
                <ul id="result-parts-list" class="divide-y divide-gray-800">
                </ul>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center gap-4 mt-10">
            <button id="result-save-btn" class="bg-primary hover:bg-amber-600 text-black font-bold py-3.5 px-8 rounded-xl transition-all shadow-[0_4px_15px_rgba(217,119,6,0.3)] hover:shadow-[0_6px_20px_rgba(217,119,6,0.5)] flex items-center justify-center">
                <i class="bi bi-download mr-2 text-lg"></i>Simpan Gambar
            </button>
            <button id="result-back-btn" class="border flex items-center justify-center border-gray-600 hover:border-gray-500 text-white font-semibold py-3.5 px-8 rounded-xl transition-colors">
                <i class="bi bi-pencil-square mr-2"></i>Kembali Edit
            </button>
        </div>
    </div>
</div>
