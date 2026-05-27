<div id="phase-workspace" class="hidden">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
        <div class="lg:col-span-3">
            <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-3 relative">
                <p class="text-xs text-gray-400 mb-2">Klik atau hover bagian pakaian untuk melihat area, lalu klik untuk terapkan batik:</p>
                
                <!-- Zoom Controls -->
                <div class="absolute top-10 right-4 z-10 flex flex-col gap-1 bg-gray-800/80 p-1.5 rounded-lg border border-gray-700/50 backdrop-blur-sm shadow-xl">
                    <button id="workspace-zoom-in" class="w-8 h-8 flex items-center justify-center text-gray-300 hover:text-white hover:bg-gray-700 rounded transition-colors" title="Zoom In">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button id="workspace-zoom-out" class="w-8 h-8 flex items-center justify-center text-gray-300 hover:text-white hover:bg-gray-700 rounded transition-colors" title="Zoom Out">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <button id="workspace-zoom-reset" class="w-8 h-8 flex items-center justify-center text-gray-300 hover:text-white hover:bg-gray-700 rounded transition-colors" title="Reset Zoom">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>

                <div class="relative rounded-xl overflow-hidden bg-gray-800 flex items-center justify-center p-1" id="canvas-container" style="cursor: grab;">
                    <canvas id="fashion-canvas" class="w-full h-auto block" style="max-height: 65vh; transform-origin: center center; transition: transform 0.1s ease-out;"></canvas>
                </div>
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="bg-gray-900/70 border border-amber-700/50 rounded-2xl p-4 h-full flex flex-col">
                <p class="text-sm font-semibold text-white mb-3">Bagian Terdeteksi</p>
                <div id="parts-list" class="space-y-2 flex-1 overflow-y-auto max-h-80"></div>
                <div class="mt-4 pt-4 border-t border-gray-800">
                    <p id="workspace-status" class="text-xs text-gray-400 mb-3">Klik bagian untuk memulai.</p>
                    <div class="flex gap-2">
                        <button id="reset-btn" class="flex-1 border border-amber-700/60 hover:border-primary text-white text-sm font-semibold py-2 rounded-lg transition-colors">
                            <i class="bi bi-arrow-counterclockwise mr-1"></i>Reset
                        </button>
                        <button id="finish-btn" class="bg-primary hover:bg-amber-600 text-black text-sm font-bold py-2 px-6 rounded-lg transition-colors shadow-lg">
                            Selesai <i class="bi bi-chevron-right ml-1"></i>
                        </button>
                        <button id="back-to-upload-btn" class="border border-gray-700 hover:border-gray-500 text-gray-400 text-sm py-2 px-3 rounded-lg transition-colors" title="Kembali">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
