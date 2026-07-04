{{--
=========================================================================
TEMPLATE — Pewarnaan Batik by Prompt Teks
=========================================================================
--}}
@extends('layouts.layout')

@section('title', 'Pewarnaan by Prompt')

@section('content')
<div class="flex items-center justify-center min-h-screen py-8 px-4" id="pewarnaan-prompt-app">
    {{-- Main Container --}}
    <div class="bg-gray-900 border border-gray-700 rounded-3xl shadow-2xl w-full max-w-7xl relative overflow-hidden">
        
        {{-- Header --}}
        <div class="text-center pt-8 pb-6 px-8 border-b border-gray-800 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-gray-800 p-2 rounded-lg text-amber-500">
                    <i class="bi bi-palette text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white font-playfair m-0">Batik AI Colorizer</h2>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-400 bg-gray-800/50 px-3 py-1.5 rounded-full border border-gray-700">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                GPU Ready
            </div>
        </div>

        {{-- Body --}}
        <div class="flex flex-col lg:flex-row min-h-[600px]">
            
            {{-- LEFT COLUMN: Controls --}}
            <div class="w-full custom-left-panel bg-gray-900/50 border-r border-gray-800 p-6 flex flex-col gap-6">
                
                {{-- Error Message --}}
                <div id="error-message" class="hidden bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 rounded-lg text-sm"></div>

                {{-- Image Uploader --}}
                <div class="space-y-3">
                    <label class="text-white text-sm font-semibold flex justify-between">
                        <span>Upload Batik Image</span>
                        <span class="text-xs text-gray-500 font-normal">Max 10MB</span>
                    </label>
                    <div
                        id="batik-dropzone"
                        class="border-2 border-dashed border-gray-700 rounded-xl p-6 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-amber-500 hover:bg-amber-900/10 transition-colors bg-gray-800/30 min-h-[160px] group"
                        onclick="document.getElementById('batik-file-input').click()"
                    >
                        <div class="text-gray-500 group-hover:text-amber-400 transition-colors">
                            <i class="bi bi-cloud-upload text-4xl"></i>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-300 font-medium text-sm">Click to upload <span class="text-gray-500 font-normal">or drag and drop</span></p>
                            <p class="text-gray-500 text-xs mt-1">JPG, PNG, WEBP</p>
                        </div>
                    </div>
                    
                    <input type="file" id="batik-file-input" name="image" accept="image/*" class="hidden" onchange="handleBatikFileSelect(this)">
                    
                    {{-- File Preview --}}
                    <div id="batik-file-preview" class="hidden relative rounded-xl overflow-hidden border border-gray-700 bg-black/50 group">
                        <img id="batik-file-img" src="" alt="preview" class="w-full h-auto max-h-60 object-contain bg-gray-900/50 p-2">
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button" onclick="resetBatikFile()" class="bg-red-600/90 hover:bg-red-500 text-white p-2 rounded-full shadow-lg transition-transform hover:scale-110">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Prompts --}}
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-white text-sm font-semibold">Positive Prompt</label>
                        <textarea 
                            id="custom_prompt" 
                            rows="3" 
                            class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-sm text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition-all resize-none custom-scrollbar"
                            placeholder="Contoh: ubah menjadi warna merah marun dan emas yang elegan"
                            oninput="clearTemplateSelection()"
                        ></textarea>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-white text-sm font-semibold">Negative Prompt (Opsional)</label>
                        <textarea 
                            id="neg_prompt" 
                            rows="2" 
                            class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-sm text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition-all resize-none custom-scrollbar"
                            placeholder="Contoh: warna pudar, blur, kualitas buruk"
                            oninput="clearTemplateSelection()"
                        ></textarea>
                    </div>
                </div>

                {{-- Advanced Settings --}}
                <div class="border border-gray-800 rounded-xl bg-gray-800/30 overflow-hidden">
                    <button type="button" onclick="toggleAdvanced()" class="w-full p-4 flex justify-between items-center text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 transition-colors">
                        <span class="flex items-center gap-2"><i class="bi bi-sliders"></i> Advanced Settings</span>
                        <i class="bi bi-chevron-down text-xs transition-transform duration-300" id="advanced-icon"></i>
                    </button>
                    
                    <div id="advanced-content" class="hidden px-4 pb-4 space-y-4 border-t border-gray-800 pt-4 bg-gray-800/20">
                        {{-- Inference Steps --}}
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <label class="text-gray-400">Inference Steps</label>
                                <span id="steps-val" class="text-amber-500 font-mono">50</span>
                            </div>
                            <input type="range" id="steps" min="10" max="100" step="1" value="50" class="w-full accent-amber-500 h-1.5 bg-gray-700 rounded-lg appearance-none cursor-pointer" oninput="document.getElementById('steps-val').textContent = this.value">
                        </div>
                        
                        {{-- CFG Scale --}}
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <label class="text-gray-400">CFG Scale</label>
                                <span id="cfg-val" class="text-amber-500 font-mono">12.0</span>
                            </div>
                            <input type="range" id="cfg_scale" min="1.0" max="20.0" step="0.5" value="12.0" class="w-full accent-amber-500 h-1.5 bg-gray-700 rounded-lg appearance-none cursor-pointer" oninput="document.getElementById('cfg-val').textContent = parseFloat(this.value).toFixed(1)">
                        </div>
                        
                        {{-- Color Scale --}}
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <label class="text-gray-400">Color Scale</label>
                                <span id="color-val" class="text-amber-500 font-mono">0.8</span>
                            </div>
                            <input type="range" id="color_scale" min="0.1" max="1.5" step="0.1" value="0.8" class="w-full accent-amber-500 h-1.5 bg-gray-700 rounded-lg appearance-none cursor-pointer" oninput="document.getElementById('color-val').textContent = parseFloat(this.value).toFixed(1)">
                        </div>
                    </div>
                </div>

                {{-- Process Button --}}
                <div class="mt-auto pt-4">
                    <button 
                        id="btn-process"
                        type="button" 
                        onclick="processColorize()"
                        disabled
                        class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3.5 px-4 rounded-xl transition-all shadow-lg shadow-amber-900/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group"
                    >
                        <i class="bi bi-magic group-hover:rotate-12 transition-transform"></i> 
                        <span id="btn-text">Colorize Batik</span>
                        <i id="btn-spinner" class="bi bi-hourglass-split animate-spin hidden"></i>
                    </button>
                </div>
            </div>

            {{-- RIGHT COLUMN: Content / Result --}}
            <div class="w-full flex-1 p-8 bg-black/40 flex flex-col items-center justify-center relative min-h-[400px]">
                
                {{-- Initial State: Templates Grid --}}
                <div id="initial-state" class="w-full h-full flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-white font-semibold text-xl flex items-center gap-2">
                            <i class="bi bi-palette-fill text-amber-500"></i> Template Pewarnaan
                        </h3>
                        <span id="templates-loading" class="text-sm text-amber-500 hidden bg-amber-900/20 px-3 py-1 rounded-full border border-amber-500/30">
                            <i class="bi bi-arrow-repeat animate-spin inline-block mr-1"></i> Loading Templates...
                        </span>
                    </div>
                    
                    <div id="templates-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 flex-1 content-start overflow-y-auto custom-scrollbar pr-2 pb-4">
                        {{-- Templates akan diisi via JS --}}
                    </div>
                </div>

                {{-- Result State --}}
                <div id="result-state" class="hidden w-full h-full flex flex-col items-center">
                    <div class="flex justify-between items-center mb-4 w-full">
                        <div class="flex items-center gap-3">
                            <h3 class="text-white font-semibold text-lg flex items-center gap-2"><i class="bi bi-check-circle-fill text-green-500"></i> Result: <span id="res-template-name">Template</span></h3>
                            <span id="res-pipeline-badge" class="text-[10px] font-bold px-2.5 py-1 rounded-md tracking-wide text-amber-500" style="background-color: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3);">
                                🎨 Fine-Tuned
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="resetResult()" class="text-xs bg-gray-800 hover:bg-gray-700 text-white px-3 py-2 rounded-lg border border-gray-700 transition-colors">
                                Try Another Palette
                            </button>
                            <button id="btn-download" class="text-xs bg-amber-600 hover:bg-amber-500 text-white px-3 py-2 rounded-lg transition-colors flex items-center gap-1 shadow-md shadow-amber-900/20 font-semibold">
                                <i class="bi bi-download"></i> Download High-Res
                            </button>
                        </div>
                    </div>
                    
                    {{-- Metrics Row --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full mb-6">
                        <div class="bg-gray-800/40 p-4 rounded-xl border border-gray-700 flex items-center gap-3">
                            <div class="p-2.5 rounded-lg text-amber-500" style="background-color: rgba(245,158,11,0.1);"><i class="bi bi-clock text-xl"></i></div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider">Inference Time</div>
                                <div id="metric-time" class="text-lg font-bold text-white">0.0s</div>
                            </div>
                        </div>
                        <div class="bg-gray-800/40 p-4 rounded-xl border border-gray-700 flex items-center gap-3">
                            <div class="p-2.5 rounded-lg text-indigo-400" style="background-color: rgba(99,102,241,0.1);"><i class="bi bi-activity text-xl"></i></div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider">SSIM</div>
                                <div id="metric-ssim" class="text-lg font-bold text-white">—</div>
                            </div>
                        </div>
                        <div class="bg-gray-800/40 p-4 rounded-xl border border-gray-700 flex items-center gap-3">
                            <div class="p-2.5 rounded-lg text-emerald-400" style="background-color: rgba(16,185,129,0.1);"><i class="bi bi-hdd-fill text-xl"></i></div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider">PSNR</div>
                                <div id="metric-psnr" class="text-lg font-bold text-white">—</div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Images Side-by-Side --}}
                    <div class="flex flex-col lg:flex-row gap-6 w-full items-center justify-center">
                        {{-- Before --}}
                        <div class="flex-1 w-full max-w-sm group">
                            <p class="text-gray-400 text-xs text-center mb-2 font-medium uppercase tracking-wider">Original Input (Grayscale)</p>
                            <div class="rounded-xl overflow-hidden border border-gray-700 shadow-lg relative cursor-zoom-in" onclick="openLightbox(document.getElementById('res-original').src, 'Original Input')">
                                <img id="res-original" src="" class="w-full h-auto max-h-[400px] object-contain bg-gray-900 transition-transform duration-300 group-hover:scale-[1.02]">
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                    <i class="bi bi-zoom-in text-4xl text-white"></i>
                                </div>
                            </div>
                        </div>
                        
                        {{-- After --}}
                        <div class="flex-1 w-full max-w-sm group">
                            <p class="text-amber-500 text-xs text-center mb-2 font-medium uppercase tracking-wider">Colorized Output</p>
                            <div class="rounded-xl overflow-hidden border-2 border-amber-500/80 shadow-[0_0_20px_rgba(245,158,11,0.2)] relative cursor-zoom-in" onclick="openLightbox(document.getElementById('res-generated').src, 'Colorized Output')">
                                <img id="res-generated" src="" class="w-full h-auto max-h-[400px] object-contain bg-gray-900 transition-transform duration-300 group-hover:scale-[1.02]">
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                    <i class="bi bi-zoom-in text-4xl text-amber-500"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Prompt Info --}}
                    <div class="mt-6 p-4 bg-gray-800/40 rounded-xl border border-gray-700 w-full text-left">
                        <div class="text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Prompt Used:</div>
                        <p id="prompt-used-text" class="text-sm text-gray-300 italic">"..."</p>
                    </div>
                </div>

                {{-- Loading Overlay --}}
                <div id="loading-overlay" class="hidden absolute inset-0 bg-gray-900/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
                    <div class="relative mb-6">
                        <div class="w-16 h-16 rounded-full border-4 border-gray-700 border-t-amber-500 animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="bi bi-palette text-amber-500 text-xl animate-pulse"></i>
                        </div>
                    </div>
                    <h4 class="text-white font-bold text-lg mb-2 tracking-wide">Memproses Gambar...</h4>
                    <p class="text-gray-400 text-sm max-w-xs text-center">AI sedang mewarnai motif batik Anda sesuai dengan instruksi. Mohon tunggu beberapa saat.</p>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Lightbox Overlay --}}
<div id="lightbox" onclick="closeLightbox()" class="hidden fixed inset-0 z-50 bg-black/95 flex flex-col items-center justify-center p-4 transition-opacity duration-300 cursor-pointer">
    <div class="absolute top-6 right-6 flex gap-4">
        <button type="button" class="text-gray-400 hover:text-white transition-colors bg-gray-800 hover:bg-gray-700 rounded-full w-10 h-10 flex items-center justify-center">
            <i class="bi bi-x-lg text-xl"></i>
        </button>
    </div>
    <div class="max-w-6xl max-h-[85vh] relative flex flex-col items-center" onclick="event.stopPropagation(); closeLightbox();">
        <img id="lightbox-img" src="" class="max-w-full max-h-[80vh] object-contain shadow-2xl">
        <h3 id="lightbox-title" class="text-white mt-4 font-semibold text-lg tracking-wide text-center"></h3>
    </div>
</div>

<style>
    @media (min-width: 1024px) {
        .custom-left-panel {
            width: 350px !important;
            flex-shrink: 0 !important;
        }
    }

    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.2); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(75, 85, 99, 0.6); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(107, 114, 128, 0.8); }
</style>

<script>
    const fileInput = document.getElementById('batik-file-input');
    const dropzone = document.getElementById('batik-dropzone');
    const filePreview = document.getElementById('batik-file-preview');
    const previewImg = document.getElementById('batik-file-img');
    const btnProcess = document.getElementById('btn-process');
    const errorMsg = document.getElementById('error-message');
    
    // UI Elements
    const initialState = document.getElementById('initial-state');
    const resultState = document.getElementById('result-state');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    let selectedFile = null;
    let selectedTemplateId = null;
    let templatesData = [];

    // --- Templates Fetching & Rendering ---
    async function loadTemplates() {
        const container = document.getElementById('templates-container');
        const loading = document.getElementById('templates-loading');
        
        loading.classList.remove('hidden');
        try {
            const response = await fetch('{{ route("api.pewarnaan.templates") }}');
            const data = await response.json();
            
            if (data.templates && data.templates.length > 0) {
                templatesData = data.templates;
                renderTemplates();
            } else {
                container.innerHTML = '<p class="text-xs text-gray-500 italic">Tidak ada template tersedia.</p>';
            }
        } catch (e) {
            console.error('Failed to load templates', e);
            container.innerHTML = '<p class="text-xs text-red-500 italic">Gagal memuat template.</p>';
        } finally {
            loading.classList.add('hidden');
        }
    }

    function renderTemplates() {
        const container = document.getElementById('templates-container');
        container.innerHTML = '';
        
        templatesData.forEach(t => {
            const btn = document.createElement('button');
            btn.type = 'button';
            
            let baseClass = 'text-left px-4 py-4 rounded-xl border transition-all duration-300 flex flex-col justify-between h-full min-h-[100px] group relative overflow-hidden ';
            if (selectedTemplateId === t.id) {
                btn.className = baseClass + 'bg-amber-600/20 border-amber-500 text-white shadow-[0_0_15px_rgba(245,158,11,0.15)] ring-1 ring-amber-500';
            } else {
                btn.className = baseClass + 'bg-gray-800/40 border-gray-700 text-gray-300 hover:bg-gray-700/80 hover:border-gray-500';
            }
            
            btn.innerHTML = `
                <div class="absolute top-0 left-0 w-1 h-full ${selectedTemplateId === t.id ? 'bg-amber-500' : 'bg-transparent group-hover:bg-gray-500'} transition-colors"></div>
                <div class="font-bold text-base mb-2 pl-2 text-white">${t.name}</div>
                <div class="text-xs text-gray-400 line-clamp-3 pl-2">${t.positive_indo || t.positive_eng}</div>
            `;
            btn.onclick = () => selectTemplate(t.id);
            container.appendChild(btn);
        });
    }

    function selectTemplate(id) {
        selectedTemplateId = id;
        renderTemplates();
        
        const t = templatesData.find(x => x.id === id);
        if (t) {
            document.getElementById('custom_prompt').value = t.positive_indo || t.positive_eng || '';
            document.getElementById('neg_prompt').value = t.negative_indo || t.negative_eng || '';
        }
    }

    function clearTemplateSelection() {
        if (selectedTemplateId !== null) {
            selectedTemplateId = null;
            renderTemplates();
        }
    }

    // Call on load
    document.addEventListener('DOMContentLoaded', () => {
        loadTemplates();
    });

    // --- File Drag & Drop Handlers ---
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.add('border-amber-500', 'bg-amber-900/10'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('border-amber-500', 'bg-amber-900/10'), false);
    });

    dropzone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleBatikFileSelect(fileInput);
        }
    });

    // --- File Selection ---
    function handleBatikFileSelect(input) {
        const file = input.files ? input.files[0] : null;
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            showError('Ukuran file terlalu besar. Maksimal 10MB.');
            resetBatikFile();
            return;
        }

        if (!file.type.startsWith('image/')) {
            showError('File harus berupa gambar (JPG/PNG/WEBP).');
            resetBatikFile();
            return;
        }

        selectedFile = file;
        errorMsg.classList.add('hidden');
        btnProcess.disabled = false;

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            dropzone.classList.add('hidden');
            filePreview.classList.remove('hidden');
            
            // Set for original result preview as well
            document.getElementById('res-original').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function resetBatikFile() {
        fileInput.value = '';
        selectedFile = null;
        dropzone.classList.remove('hidden');
        filePreview.classList.add('hidden');
        btnProcess.disabled = true;
    }

    // --- UI Helpers ---
    function toggleAdvanced() {
        const content = document.getElementById('advanced-content');
        const icon = document.getElementById('advanced-icon');
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }

    function showError(msg) {
        errorMsg.textContent = msg;
        errorMsg.classList.remove('hidden');
    }

    function setLoading(isLoading) {
        btnProcess.disabled = isLoading;
        document.getElementById('btn-text').textContent = isLoading ? 'Memproses...' : 'Colorize Batik';
        
        if (isLoading) {
            document.getElementById('btn-spinner').classList.remove('hidden');
            loadingOverlay.classList.remove('hidden');
        } else {
            document.getElementById('btn-spinner').classList.add('hidden');
            loadingOverlay.classList.add('hidden');
        }
    }

    function resetResult() {
        resultState.classList.add('hidden');
        initialState.classList.remove('hidden');
    }

    function openLightbox(src, title) {
        const lb = document.getElementById('lightbox');
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox-title').textContent = title;
        lb.classList.remove('hidden');
        
        // Setup escape key to close
        document.addEventListener('keydown', handleEscapeKey);
    }

    function closeLightbox() {
        const lb = document.getElementById('lightbox');
        lb.classList.add('hidden');
        document.removeEventListener('keydown', handleEscapeKey);
    }
    
    function handleEscapeKey(e) {
        if (e.key === 'Escape') closeLightbox();
    }

    // --- Main Process AJAX ---
    async function processColorize() {
        if (!selectedFile) {
            showError('Pilih gambar batik terlebih dahulu.');
            return;
        }

        setLoading(true);
        errorMsg.classList.add('hidden');

        const formData = new FormData();
        formData.append('image', selectedFile);
        formData.append('prompt_mode', selectedTemplateId ? 'template' : 'custom');
        formData.append('template_id', selectedTemplateId || 1);
        formData.append('custom_prompt', document.getElementById('custom_prompt').value);
        formData.append('neg_prompt', document.getElementById('neg_prompt').value);
        formData.append('steps', document.getElementById('steps').value);
        formData.append('cfg_scale', document.getElementById('cfg_scale').value);
        formData.append('color_scale', document.getElementById('color_scale').value);

        try {
            const response = await fetch('{{ route("api.pewarnaan.prompt") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || data.error || 'Terjadi kesalahan saat memproses gambar.');
            }

            // Success, display result
            // Assuming response contains image_url or base64 in data.result_image_url or data.image
            const resultImageUrl = data.result_image_url || data.image || data.url || (data.result && data.result.image_url);
            
            if (!resultImageUrl) {
                throw new Error('Respons tidak menyertakan URL gambar hasil.');
            }

            document.getElementById('res-generated').src = resultImageUrl;
            
            // Setup download button
            const btnDownload = document.getElementById('btn-download');
            btnDownload.onclick = () => {
                const link = document.createElement('a');
                link.href = resultImageUrl;
                link.download = `batik-recolor-${new Date().getTime()}.jpg`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };

            // Populate metrics & info
            if (data.input_image_b64) {
                document.getElementById('res-original').src = `data:image/jpeg;base64,${data.input_image_b64}`;
            }
            if (data.metrics) {
                document.getElementById('metric-time').textContent = data.metrics.time ? data.metrics.time.toFixed(1) + 's' : '—';
                document.getElementById('metric-ssim').textContent = data.metrics.ssim ? data.metrics.ssim.toFixed(4) : '—';
                document.getElementById('metric-psnr').textContent = data.metrics.psnr ? data.metrics.psnr.toFixed(2) + ' dB' : '—';
            }
            if (data.template_name) {
                document.getElementById('res-template-name').textContent = data.template_name;
            }
            if (data.prompt_used) {
                document.getElementById('prompt-used-text').textContent = '"' + data.prompt_used.positive_indo + '"';
            }
            if (data.pipeline_mode) {
                const badge = document.getElementById('res-pipeline-badge');
                if (data.pipeline_mode === 'finetuned') {
                    badge.innerHTML = '🎨 Fine-Tuned';
                    badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-md tracking-wide text-amber-500';
                    badge.style.backgroundColor = 'rgba(245,158,11,0.1)';
                    badge.style.border = '1px solid rgba(245,158,11,0.3)';
                } else {
                    badge.innerHTML = '🧩 ControlNet';
                    badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-md tracking-wide text-indigo-400';
                    badge.style.backgroundColor = 'rgba(99,102,241,0.1)';
                    badge.style.border = '1px solid rgba(99,102,241,0.3)';
                }
            }

            initialState.classList.add('hidden');
            resultState.classList.remove('hidden');

        } catch (err) {
            console.error('Error:', err);
            showError(err.message);
        } finally {
            setLoading(false);
        }
    }
</script>
@endsection

