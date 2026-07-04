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
                    
                    <input type="file" id="batik-file-input" name="batik_source" accept="image/*" class="hidden">
                    
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
                <div id="color-source-section" class="hidden space-y-4 border-t border-gray-800 pt-8 mt-4 border-b border-gray-800 pb-8">
                    <h3 class="text-center text-white font-semibold">Pilih Sumber Warna</h3>
                    
                    {{-- Source Type Selection --}}
                    <div class="flex gap-4 justify-center flex-wrap">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="color_source_type" value="auto-extract" class="color-source-radio">
                            <span class="text-white text-sm">Auto-Extract dari Batik</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="color_source_type" value="upload" class="color-source-radio" checked>
                            <span class="text-white text-sm">Upload Gambar Warna</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="color_source_type" value="manual" class="color-source-radio">
                            <span class="text-white text-sm">Pilih Warna Manual</span>
                        </label>
                    </div>
                    
                    {{-- Auto-Extract Option (NEW) --}}
                    <div id="color-auto-extract-option" class="space-y-3">
                        <div class="bg-gradient-to-br from-amber-900/30 to-amber-950/20 border border-amber-700/50 rounded-xl p-6">
                            <div class="text-center space-y-3">
                                <div>
                                    <i class="bi bi-sparkles text-3xl text-amber-400 mb-2 block"></i>
                                    <p class="text-white font-semibold text-sm">Warna akan diekstrak otomatis</p>
                                    <p class="text-gray-400 text-xs mt-1">dari gambar batik yang Anda upload menggunakan 3 metode (KMeans, Histogram, Median Cut)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Upload Image Option --}}
                    <div id="color-upload-option" class="space-y-3">
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
                                <p class="text-gray-500 text-xs mt-1">JPG, JPEG, PNG less than 10MB</p>
                            </div>
                        </div>
                        
                        <input type="file" id="color-file-input" accept="image/*" class="hidden">
                        
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
                        
                        <input type="hidden" id="color-file-base64" name="color_image">
                    </div>
                    
                    {{-- Manual Color Picker Option --}}
                    <div id="color-manual-option" class="hidden space-y-3">
                        <div class="bg-gray-800/50 border border-amber-700/30 rounded-xl p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                {{-- Color 1 --}}
                                <div class="flex flex-col items-center gap-2">
                                    <input 
                                        type="color" 
                                        class="manual-color-picker" 
                                        data-color-index="0"
                                        value="#ff0000"
                                        class="w-16 h-16 rounded cursor-pointer border-2 border-amber-700/50"
                                    >
                                    <p class="text-gray-400 text-xs">Warna 1</p>
                                    <p class="color-code text-white text-sm font-mono font-bold">#ff0000</p>
                                </div>
                                
                                {{-- Color 2 --}}
                                <div class="flex flex-col items-center gap-2">
                                    <input 
                                        type="color" 
                                        class="manual-color-picker" 
                                        data-color-index="1"
                                        value="#00ff00"
                                        class="w-16 h-16 rounded cursor-pointer border-2 border-amber-700/50"
                                    >
                                    <p class="text-gray-400 text-xs">Warna 2</p>
                                    <p class="color-code text-white text-sm font-mono font-bold">#00ff00</p>
                                </div>
                                
                                {{-- Color 3 --}}
                                <div class="flex flex-col items-center gap-2">
                                    <input 
                                        type="color" 
                                        class="manual-color-picker" 
                                        data-color-index="2"
                                        value="#0000ff"
                                        class="w-16 h-16 rounded cursor-pointer border-2 border-amber-700/50"
                                    >
                                    <p class="text-gray-400 text-xs">Warna 3</p>
                                    <p class="color-code text-white text-sm font-mono font-bold">#0000ff</p>
                                </div>
                                
                                {{-- Color 4 --}}
                                <div class="flex flex-col items-center gap-2">
                                    <input 
                                        type="color" 
                                        class="manual-color-picker" 
                                        data-color-index="3"
                                        value="#ffff00"
                                        class="w-16 h-16 rounded cursor-pointer border-2 border-amber-700/50"
                                    >
                                    <p class="text-gray-400 text-xs">Warna 4</p>
                                    <p class="color-code text-white text-sm font-mono font-bold">#ffff00</p>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" id="color-picker-value" name="manual_color">
                    </div>
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


<script>
function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

function handleBatikFileSelect(input) {
    const file = input.files?.[0];
    if (!file) return;
    
    if (!file.type.startsWith("image/")) {
        alert("File harus berupa gambar");
        input.value = "";
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        alert("File terlalu besar. Maksimal 10MB");
        input.value = "";
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = document.getElementById("batik-file-img");
        if (img) img.src = e.target.result;
        
        const name = document.getElementById("batik-file-name");
        if (name) name.textContent = file.name;
        
        const size = document.getElementById("batik-file-size");
        if (size) size.textContent = formatFileSize(file.size);
        
        const base64Input = document.getElementById("batik-file-base64");
        if (base64Input) base64Input.value = e.target.result;
        
        const dropzone = document.getElementById("batik-dropzone");
        if (dropzone) dropzone.classList.add("hidden");
        
        const preview = document.getElementById("batik-file-preview");
        if (preview) preview.classList.remove("hidden");
        
        const colorSection = document.getElementById("color-source-section");
        if (colorSection) colorSection.classList.remove("hidden");
    };
    reader.readAsDataURL(file);
}

function handleFileSelect(input) {
    const file = input.files?.[0];
    if (!file) return;
    
    if (!file.type.startsWith("image/")) {
        alert("File harus berupa gambar");
        input.value = "";
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        alert("File terlalu besar. Maksimal 10MB");
        input.value = "";
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = document.getElementById("file-img");
        if (img) img.src = e.target.result;
        
        const name = document.getElementById("file-name");
        if (name) name.textContent = file.name;
        
        const size = document.getElementById("file-size");
        if (size) size.textContent = formatFileSize(file.size);
        
        const base64Input = document.getElementById("color-file-base64");
        if (base64Input) base64Input.value = e.target.result;
        
        const dropzone = document.getElementById("color-dropzone");
        if (dropzone) dropzone.classList.add("hidden");
        
        const preview = document.getElementById("file-preview");
        if (preview) preview.classList.remove("hidden");
    };
    reader.readAsDataURL(file);
}

function resetBatikFile() {
    const input = document.getElementById("batik-file-input");
    if (input) input.value = "";
    
    const dropzone = document.getElementById("batik-dropzone");
    if (dropzone) dropzone.classList.remove("hidden");
    
    const preview = document.getElementById("batik-file-preview");
    if (preview) preview.classList.add("hidden");
    
    const base64 = document.getElementById("batik-file-base64");
    if (base64) base64.value = "";
    
    const colorSection = document.getElementById("color-source-section");
    if (colorSection) colorSection.classList.add("hidden");
    
    resetFile();
}

function resetFile() {
    const input = document.getElementById("color-file-input");
    if (input) input.value = "";
    
    const dropzone = document.getElementById("color-dropzone");
    if (dropzone) dropzone.classList.remove("hidden");
    
    const preview = document.getElementById("file-preview");
    if (preview) preview.classList.add("hidden");
    
    const base64 = document.getElementById("color-file-base64");
    if (base64) base64.value = "";
    
    // Reset 4 color pickers ke default
    const colorPickers = document.querySelectorAll(".manual-color-picker");
    const defaultColors = ["#ff0000", "#00ff00", "#0000ff", "#ffff00"];
    const colorCodes = document.querySelectorAll("#color-manual-option .color-code");
    
    colorPickers.forEach((picker, index) => {
        picker.value = defaultColors[index];
        if (colorCodes[index]) {
            colorCodes[index].textContent = defaultColors[index];
        }
    });
    
    updateManualColorValue();
    
    const colorPickerValue = document.getElementById("color-picker-value");
    if (colorPickerValue) colorPickerValue.value = "";
}

function resetFormAndFile() {
    const form = document.getElementById("pewarnaan-form");
    if (form) form.reset();
    resetBatikFile();
    resetFile();
}

function toggleColorSourceType(sourceType) {
    const autoExtractOption = document.getElementById("color-auto-extract-option");
    const uploadOption = document.getElementById("color-upload-option");
    const manualOption = document.getElementById("color-manual-option");
    
    if (sourceType === "auto-extract") {
        if (autoExtractOption) autoExtractOption.classList.remove("hidden");
        if (uploadOption) uploadOption.classList.add("hidden");
        if (manualOption) manualOption.classList.add("hidden");
    } else if (sourceType === "upload") {
        if (autoExtractOption) autoExtractOption.classList.add("hidden");
        if (uploadOption) uploadOption.classList.remove("hidden");
        if (manualOption) manualOption.classList.add("hidden");
    } else if (sourceType === "manual") {
        if (autoExtractOption) autoExtractOption.classList.add("hidden");
        if (uploadOption) uploadOption.classList.add("hidden");
        if (manualOption) manualOption.classList.remove("hidden");
    }
}

function updateManualColorValue() {
    const colorPickers = document.querySelectorAll(".manual-color-picker");
    const colors = Array.from(colorPickers).map(picker => picker.value);
    const colorPickerValue = document.getElementById("color-picker-value");
    if (colorPickerValue) {
        colorPickerValue.value = JSON.stringify(colors);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const batikInput = document.getElementById("batik-file-input");
    if (batikInput) {
        batikInput.addEventListener("change", function() {
            handleBatikFileSelect(this);
        });
    }
    
    const colorInput = document.getElementById("color-file-input");
    if (colorInput) {
        colorInput.addEventListener("change", function() {
            handleFileSelect(this);
        });
    }
    
    const colorSourceRadios = document.querySelectorAll(".color-source-radio");
    colorSourceRadios.forEach(radio => {
        radio.addEventListener("change", (e) => {
            toggleColorSourceType(e.target.value);
        });
    });
    
    // Event listeners untuk 4 color pickers
    const colorPickers = document.querySelectorAll(".manual-color-picker");
    colorPickers.forEach((picker, index) => {
        picker.addEventListener("input", (e) => {
            const color = e.target.value;
            const colorCodes = document.querySelectorAll("#color-manual-option .color-code");
            if (colorCodes[index]) {
                colorCodes[index].textContent = color;
            }
            updateManualColorValue();
        });
    });
    
    // Set initial value
    updateManualColorValue();
    
    const form = document.getElementById("pewarnaan-form");
    if (form) {
        form.addEventListener("submit", (e) => {
            const batikBase64 = document.getElementById("batik-file-base64");
            const colorSourceType = document.querySelector("input[name='color_source_type']:checked")?.value;
            
            if (!batikBase64?.value) {
                e.preventDefault();
                alert("Upload gambar batik sumber terlebih dahulu!");
                return false;
            }
            
            if (colorSourceType === "upload") {
                const colorImage = document.getElementById("color-file-base64");
                if (!colorImage?.value) {
                    e.preventDefault();
                    alert("Upload gambar sumber warna terlebih dahulu!");
                    return false;
                }
                // Clear manual color value if upload is selected
                const manualColor = document.getElementById("color-picker-value");
                if (manualColor) manualColor.value = "";
            } else if (colorSourceType === "manual") {
                const manualColor = document.getElementById("color-picker-value");
                if (!manualColor?.value) {
                    e.preventDefault();
                    alert("Pilih warna terlebih dahulu!");
                    return false;
                }
                // Try to parse as JSON array
                try {
                    const colors = JSON.parse(manualColor.value);
                    if (!Array.isArray(colors) || colors.length === 0) {
                        e.preventDefault();
                        alert("Pilih warna terlebih dahulu!");
                        return false;
                    }
                } catch (err) {
                    e.preventDefault();
                    alert("Format warna tidak valid!");
                    return false;
                }
                // Clear color image value if manual is selected
                const colorImage = document.getElementById("color-file-base64");
                if (colorImage) colorImage.value = "";
            }
        });
    }
});
</script>
@endsection
