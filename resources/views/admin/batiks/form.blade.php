@extends('layouts.layout')
@section('title', isset($batik) ? 'Kelola Batik' : 'Buat Data Batik')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css"/>
@endpush

@section('content')
<div class="max-w-6xl mx-auto py-10">
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold text-white">{{ isset($batik) ? 'Edit Galeri: ' . $batik->name : 'Data Batik Baru' }}</h1>
            <p class="text-gray-400 mt-1">Lengkapi metadata ensiklopedia dan unggah koleksi foto.</p>
        </div>
        <a href="{{ route('admin.batiks.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-600 text-gray-300 hover:bg-gray-800 transition-colors">Kembali ke Daftar</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Kolom Kiri: Metadata Utama --}}
        <div class="lg:col-span-1 border border-gray-700 rounded-3xl p-6 bg-gray-800 shadow-xl h-fit">
            <h2 class="text-xl font-bold text-white mb-6 border-b border-gray-700 pb-3">Informasi Metadata</h2>
            
            <form action="{{ isset($batik) ? route('admin.batiks.update', $batik->id) : route('admin.batiks.store') }}" method="POST">
                @csrf
                @if(isset($batik)) @method('PUT') @endif

                <div class="mb-5">
                    <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">Nama Batik</label>
                    <input type="text" name="name" value="{{ old('name', $batik->name ?? '') }}" required class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">Jenis Pembuatan</label>
                    <select name="type" required class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="tulis" {{ (old('type', $batik->type ?? '') === 'tulis') ? 'selected' : '' }}>Batik Tulis Tangan</option>
                        <option value="cap" {{ (old('type', $batik->type ?? '') === 'cap') ? 'selected' : '' }}>Batik Rekayasa Cap</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">Deskripsi & Narasi</label>
                    <textarea name="description" rows="5" required class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500">{{ old('description', $batik->description ?? '') }}</textarea>
                </div>

                <div class="mb-8">
                    <label class="flex items-center space-x-3 p-4 border border-gray-700 rounded-xl hover:bg-gray-700/50 transition-colors cursor-pointer cursor-pointer">
                        <input type="checkbox" name="is_active" class="w-5 h-5 rounded border-gray-600 text-primary focus:ring-primary focus:ring-2 bg-gray-900" {{ (old('is_active', $batik->is_active ?? true)) ? 'checked' : '' }}>
                        <div>
                            <p class="text-white font-medium">Batik Ter-publikasi</p>
                            <p class="text-gray-400 text-xs">Centang agar siap tayang di Galeri User</p>
                        </div>
                    </label>
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-amber-600 text-black font-bold py-3 rounded-xl transition-all shadow-lg shadow-primary/20">
                    {{ isset($batik) ? 'Simpan Perubahan' : 'Buat Ruang Koleksi Baru' }}
                </button>
            </form>
        </div>

        {{-- Kolom Kanan: Aset Foto & Liked Data (Hanya Muncul Jika Batik Telah Dibuat) --}}
        <div class="lg:col-span-2">
            @if(isset($batik))
                <div class="border border-gray-700 rounded-3xl p-6 bg-gray-800 shadow-xl mb-8">
                    <h2 class="text-xl font-bold text-white mb-6 border-b border-gray-700 pb-3 flex justify-between">
                        Koleksi Aset Visual (Gambar) 
                        <span class="text-amber-500 text-sm font-normal items-end flex">Drag & Drop Supported</span>
                    </h2>
                    
                    {{-- Dropzone Form --}}
                    <form action="{{ route('admin.batiks.images.store', $batik->id) }}" class="dropzone bg-gray-900 border-2 border-dashed border-gray-600 rounded-xl hover:border-amber-500 flex flex-col justify-center items-center min-h-[150px]" id="batikVisualsDropzone">
                        @csrf
                        <div class="dz-message" data-dz-message>
                            <span class="text-gray-400">Tarik dan lepas (*drag-n-drop*) berkas gambar disini, <br> atau <span class="text-amber-500 font-bold underline">klik untuk mencari fail</span>.</span>
                        </div>
                    </form>

                    {{-- Manajemen Gambar yang Sudah Diupload --}}
                    <div class="mt-8">
                        <h3 class="text-gray-400 font-medium mb-4 uppercase text-xs tracking-widest">Aset Visual Tersimpan</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($batik->images as $img)
                                <div class="relative group rounded-xl overflow-hidden border {{ $img->is_main ? 'border-primary shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'border-gray-700' }}">
                                    <div class="aspect-square bg-gray-900">
                                        <img src="{{ Storage::url($img->image_path) }}" class="w-full h-full object-cover">
                                    </div>
                                    
                                    {{-- Lencana "Gambar Utama" Status --}}
                                    @if($img->is_main)
                                        <div class="absolute top-2 left-2 bg-primary text-black text-xs px-2 py-1 rounded shadow drop-shadow-md font-bold">Thumbnail Utama</div>
                                    @endif

                                    {{-- Overlay Aksi --}}
                                    <div class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                        {{-- Hitungan Like Realtime ML Data Mentah --}}
                                        <span class="text-white text-xs mb-2">❤️ {{ $img->likes()->count() }} User Likes</span>

                                        @if(!$img->is_main)
                                            <form action="{{ route('admin.batiks.images.main', $img->id) }}" method="POST" class="w-full px-4">
                                                @csrf
                                                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-black text-xs py-2 rounded-lg font-bold">Jadikan Utama</button>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.batiks.images.destroy', $img->id) }}" method="POST" class="w-full px-4" onsubmit="return confirm('Hapus potret ini? Data jejak like otomatis memudar.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full border border-red-500 text-red-400 hover:bg-red-500 hover:text-white text-xs py-2 rounded-lg font-bold">Hapus Aset</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                            @if($batik->images->count() === 0)
                                <div class="col-span-full py-8 text-center border-2 border-dashed border-gray-700 rounded-xl text-gray-500 italic">
                                    Koleksi foto belum ada. Unggah foto pertama (akan otomatis diplot sebagai gambar visual utama).
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="border-2 border-dashed border-gray-700 rounded-3xl p-6 bg-gray-800/50 flex flex-col items-center justify-center min-h-[400px] text-center">
                    <i class="bi bi-images text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-400">Unggah Gambar & Aset Visual Terkunci</h3>
                    <p class="text-gray-500 mt-2 max-w-sm">Anda harus menyimpan informasi metadata *(form di kiri)* terlebih dahulu untuk menciptakan ruang koleksi, barulah Anda akan diizinkan merajut kumpulan unggahan fotonya.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script>
    Dropzone.autoDiscover = false;
    
    document.addEventListener('DOMContentLoaded', function() {
        if(document.getElementById('batikVisualsDropzone')) {
            let myDropzone = new Dropzone("#batikVisualsDropzone", {
                paramName: "file",
                maxFilesize: 5, // MB
                acceptedFiles: "image/jpeg,image/png,image/jpg,image/webp",
                dictDefaultMessage: "Drop berkas disini",
                init: function() {
                    this.on("success", function(file, response) {
                        // Reload untuk mendemonstrasikan perubahan UI image & status "is_main" dgn gampang
                        setTimeout(() => {
                            window.location.reload();
                        }, 800)
                    });
                    this.on("error", function(file, errorMessage) {
                        alert("Gagal mengunggah foto: " + errorMessage);
                    });
                }
            });
        }
    });
</script>
@endpush
