@extends('layouts.layout')

@section('title', 'Pewarnaan Pallet Warna')

@section('content')
<div class="flex items-center justify-center min-h-screen py-8 px-4">
    {{-- Modal Container --}}
    <div class="bg-gray-900 border border-gray-700 rounded-3xl shadow-2xl w-full max-w-2xl relative">
        

        {{-- Header --}}
        <div class="text-center pt-10 pb-8 px-8 border-b border-gray-800">
            <h2 class="text-3xl font-bold text-white font-playfair mb-2">Pewarnaan Ulang Motif Batik</h2>
            <p class="text-gray-400 text-sm">Pilih Motif batik Malang dan masukkan pallet yang anda inginkan</p>
        </div>

        {{-- Body --}}
        <div class="p-8">
            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="mb-6 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 rounded-lg text-sm">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form id="pewarnaan-form" action="{{ route('pewarnaan.palet.proses') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf
                
                {{-- Section 1: Upload Gambar Batik Sumber --}}
                <div class="space-y-4">
                    <h3 class="text-center text-white font-semibold">Upload Gambar Batik Sumber</h3>
                    
                    {{-- Batik Upload Area --}}
                    <div
                        id="batik-dropzone"
                        class="border-2 border-dashed border-amber-700/50 rounded-2xl p-6 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors bg-amber-950/20 min-h-40 group mx-auto"
                        onclick="document.getElementById('batik-file-input').click()"
                    >
                        <div class="text-amber-600/80 group-hover:text-amber-400 transition-colors">
                            <i class="bi bi-cloud-upload text-4xl"></i>
                        </div>
                        <div class="text-center">
                            <p class="text-white font-bold text-sm">
                                Click to upload <span class="text-amber-500 font-normal">or drag and drop</span>
                            </p>
                            <p class="text-gray-500 text-xs mt-1">JPG, JPEG, PNG less than 10MB</p>
                        </div>
                    </div>
                    
                    <input type="file" id="batik-file-input" name="batik_source" accept="image/*" class="hidden" onchange="handleBatikFileSelect(this)">
                    
                    {{-- Batik File Preview --}}
                    <div id="batik-file-preview" class="hidden bg-gray-800/50 border border-amber-700/30 rounded-xl p-3 flex items-center gap-3">
                        <img id="batik-file-img" src="" alt="preview" class="w-10 h-10 rounded object-cover border border-gray-700">
                        <div class="flex-1">
                            <p id="batik-file-name" class="text-white text-xs font-semibold truncate"></p>
                            <p id="batik-file-size" class="text-gray-500 text-[10px]"></p>
                        </div>
                        <button type="button" onclick="resetBatikFile()" class="text-gray-500 hover:text-red-500 text-lg">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                    
                    {{-- Hidden field untuk batik file base64 --}}
                    <input type="hidden" id="batik-file-base64" name="batik_image">
                </div>

                {{-- Section 2: Pilih Gambar Batikmu (COMMENTED)
                <div class="space-y-4">
                    <h3 class="text-center text-white font-semibold">Pilih Gambar Batikmu</h3>
                    
                    <div class="border border-amber-700/60 rounded-2xl p-4 bg-black/40">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            @forelse($batiks as $index => $batik)
                                <label class="cursor-pointer relative group">
                                    <input type="radio" name="batik_id" value="{{ $batik->id }}" class="peer sr-only" {{ $index === 0 ? 'checked' : '' }}>
                                    
                                    <div class="rounded-xl overflow-hidden border-2 border-gray-700 bg-gray-800 
                                              group-hover:border-amber-500/50 transition-all duration-300
                                              peer-checked:border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-500/30">
                                        
                                        <div class="aspect-square w-full bg-gray-700 relative">
                                            @if($batik->mainImage)
                                                @if(filter_var($batik->mainImage->image_path, FILTER_VALIDATE_URL))
                                                    <img src="{{ $batik->mainImage->image_path }}" 
                                                         alt="{{ $batik->name }}" 
                                                         class="w-full h-full object-cover">
                                                @else
                                                    <img src="{{ Storage::url($batik->mainImage->image_path) }}" 
                                                         alt="{{ $batik->name }}" 
                                                         class="w-full h-full object-cover">
                                                @endif
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-600">
                                                    <i class="bi bi-image text-2xl"></i>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="p-2 bg-gray-900/50">
                                            <p class="text-amber-500 text-xs font-bold truncate">{{ $batik->name }}</p>
                                            <p class="text-gray-500 text-[10px] line-clamp-2">{{ $batik->description ?? '-' }}</p>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="col-span-3 text-center py-4 text-gray-500 text-sm">
                                    Tidak ada data batik tersedia
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div> --}}

                

                {{-- Section 3: Pilih Sumber Warna --}}
                <div class="space-y-3 border-t border-gray-800 pt-8 mt-4 border-b border-gray-800 pb-8">
                    <h3 class="text-center text-white font-semibold">Pilih Sumber Warna</h3>
                    <p class="text-center text-gray-500 text-xs mb-6">kosongkan jika ingin langsung dengan menggunakan pallet</p>
                    
                    {{-- Upload Area --}}
                    <div
                        id="color-dropzone"
                        class="border-2 border-dashed border-amber-700/50 rounded-2xl p-6 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors bg-amber-950/20 min-h-40 group mx-auto"
                        onclick="document.getElementById('color-file-input').click()"
                    >
                        <div class="text-amber-600/80 group-hover:text-amber-400 transition-colors">
                            <i class="bi bi-cloud-upload text-4xl"></i>
                        </div>
                        <div class="text-center">
                            <p class="text-white font-bold text-sm">
                                Click to upload <span class="text-amber-500 font-normal">or drag and drop</span>
                            </p>
                            <p class="text-gray-500 text-xs mt-1">JPG, JPEG, PNG less than 1MB</p>
                        </div>
                    </div>
                    
                    <input type="file" id="color-file-input" accept="image/*" class="hidden" onchange="handleFileSelect(this)">
                    
                    {{-- File Preview --}}
                    <div id="file-preview" class="hidden bg-gray-800/50 border border-amber-700/30 rounded-xl p-3 flex items-center gap-3">
                        <img id="file-img" src="" alt="preview" class="w-10 h-10 rounded object-cover border border-gray-700">
                        <div class="flex-1">
                            <p id="file-name" class="text-white text-xs font-semibold truncate"></p>
                            <p id="file-size" class="text-gray-500 text-[10px]"></p>
                        </div>
                        <button type="button" onclick="resetFile()" class="text-gray-500 hover:text-red-500 text-lg">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                    
                    {{-- Hidden field untuk color file --}}
                    <input type="hidden" id="color-file-base64" name="color_image">
                </div>

                {{-- Footer Actions Inside Form --}}
                <div class="border-t border-gray-800 -mx-8 px-8 pt-8">
                    <p class="text-center text-white font-semibold text-sm mb-4">Proses Sekarang?</p>
                    <div class="grid grid-cols-2 gap-4">
                        <button
                            type="submit"
                            class="bg-amber-700 hover:bg-amber-600 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-amber-700/20"
                        >
                            Proses Gambar
                        </button>
                        <button
                            type="button"
                            onclick="resetFormAndFile()"
                            class="border border-gray-700 bg-gray-800/50 hover:bg-gray-700 text-white font-bold py-3 rounded-xl transition-all"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(217, 119, 6, 0.4);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(217, 119, 6, 0.6);
    }
</style>

<script>
    const fileInput = document.getElementById('color-file-input');
    const dropzone = document.getElementById('color-dropzone');
    const filePreview = document.getElementById('file-preview');
    const pewarnoanForm = document.getElementById('pewarnaan-form');

    const batikFileInput = document.getElementById('batik-file-input');
    const batikDropzone = document.getElementById('batik-dropzone');
    const batikFilePreview = document.getElementById('batik-file-preview');

    // Debug: Log form submission
    pewarnoanForm.addEventListener('submit', function(e) {
        const colorImageValue = document.getElementById('color-file-base64').value;
        const batikImageValue = document.getElementById('batik-file-base64').value;
        
        console.log('Form submitted', {
            hasColorImage: colorImageValue ? 'ADA' : 'TIDAK ADA',
            hasBatikImage: batikImageValue ? 'ADA' : 'TIDAK ADA',
        });
        
        if (!batikImageValue) {
            e.preventDefault();
            alert('Upload gambar batik sumber terlebih dahulu!');
            return false;
        }
        
        if (!colorImageValue) {
            e.preventDefault();
            alert('Upload gambar warna terlebih dahulu!');
            return false;
        }
    });

    // ===== BATIK FILE UPLOAD HANDLERS =====
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        batikDropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        batikDropzone.addEventListener(eventName, () => {
            batikDropzone.classList.add('border-primary', 'bg-primary/5');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        batikDropzone.addEventListener(eventName, () => {
            batikDropzone.classList.remove('border-primary', 'bg-primary/5');
        }, false);
    });

    batikDropzone.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            batikFileInput.files = files;
            handleBatikFileSelect({ files: files });
        }
    });

    function handleBatikFileSelect(input) {
        const file = input.files ? input.files[0] : null;
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            alert('File terlalu besar. Maksimal 10MB.');
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('File harus berupa gambar.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('batik-file-img').src = e.target.result;
            document.getElementById('batik-file-name').textContent = file.name;
            document.getElementById('batik-file-size').textContent = formatBytes(file.size);
            document.getElementById('batik-file-base64').value = e.target.result;
            
            batikDropzone.classList.add('hidden');
            batikFilePreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    function resetBatikFile() {
        batikFileInput.value = '';
        batikDropzone.classList.remove('hidden');
        batikFilePreview.classList.add('hidden');
        document.getElementById('batik-file-base64').value = '';
    }

    // ===== COLOR FILE UPLOAD HANDLERS =====
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
            dropzone.classList.add('border-primary', 'bg-primary/5');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
            dropzone.classList.remove('border-primary', 'bg-primary/5');
        }, false);
    });

    dropzone.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect({ files: files });
        }
    });

    function handleFileSelect(input) {
        const file = input.files ? input.files[0] : null;
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            alert('File terlalu besar. Maksimal 1MB.');
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('File harus berupa gambar.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('file-img').src = e.target.result;
            document.getElementById('file-name').textContent = file.name;
            document.getElementById('file-size').textContent = formatBytes(file.size);
            document.getElementById('color-file-base64').value = e.target.result;
            
            dropzone.classList.add('hidden');
            filePreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    function resetFile() {
        fileInput.value = '';
        dropzone.classList.remove('hidden');
        filePreview.classList.add('hidden');
        document.getElementById('color-file-base64').value = '';
    }

    function resetFormAndFile() {
        document.getElementById('pewarnaan-form').reset();
        resetBatikFile();
        resetFile();
    }

    function formatBytes(bytes) {
        if (!bytes) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Handle dropdown drag leave properly
    let dragCounter = 0;
    batikDropzone.addEventListener('dragenter', () => dragCounter++);
    batikDropzone.addEventListener('dragleave', () => dragCounter--);
    dropzone.addEventListener('dragenter', () => dragCounter++);
    dropzone.addEventListener('dragleave', () => dragCounter--);
</script>
@endsection