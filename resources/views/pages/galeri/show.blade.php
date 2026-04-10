@extends('layouts.layout')
@section('title', $batik->name . ' - Detail Batik')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <div class="mb-4">
        <a href="{{ route('galeri') }}" class="text-amber-500 hover:text-amber-400 font-medium inline-flex items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Galeri
        </a>
    </div>

    {{-- Info Header Batik --}}
    <div class="bg-gray-900 border border-gray-800 rounded-3xl p-8 mb-10 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-10">
            <i class="bi bi-brush text-9xl"></i>
        </div>
        <div class="relative z-10 max-w-4xl">
            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4 font-playfair">{{ $batik->name }}</h1>
            <div class="flex gap-3 mb-6">
                <span class="bg-gray-800 border border-gray-700 text-gray-300 px-4 py-1.5 rounded-full text-sm font-medium tracking-wide">
                    Teknik: <span class="text-amber-500 uppercase">{{ $batik->type }}</span>
                </span>
            </div>
            <p class="text-gray-400 text-lg leading-relaxed">{{ $batik->description }}</p>
        </div>
    </div>

    {{-- Bagian Interaktif Foto Detail --}}
    <h2 class="text-2xl font-bold text-white mb-6 border-b border-gray-800 pb-3">Galeri Penjabaran Motif</h2>
    <p class="text-gray-400 mb-6">Tinggalkan jejak <i>like</i> pada variasi motif potong yang Anda sukai untuk mengasah rekomendasi AI selanjutnya.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($batik->images as $img)
            @php
                // Cek apakah formuler ini sudah dilike (butuh auth check)
                $isLiked = auth()->check() && auth()->user()->likedBatikImages()->where('batik_image_id', $img->id)->exists();
            @endphp
            <div class="bg-gray-800 border border-gray-700 rounded-2xl overflow-hidden group shadow-xl">
                <div class="aspect-square relative flex items-center justify-center bg-black">
                    <img src="{{ Storage::url($img->image_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @if($img->is_main)
                        <div class="absolute top-3 left-3 bg-primary text-black text-xs px-3 py-1.5 rounded shadow drop-shadow-md font-bold uppercase tracking-wider">Visual Utama</div>
                    @endif
                </div>
                
                {{-- Aksi Like --}}
                <div class="p-5 flex justify-between items-center bg-gray-900 border-t border-gray-800">
                    <div class="text-gray-400 font-medium">
                        <span id="like-count-{{ $img->id }}" class="text-white font-bold">{{ $img->likes_count }}</span> Pecinta Seni
                    </div>
                    
                    @auth
                        <button 
                            onclick="toggleLike({{ $img->id }})"
                            id="like-btn-{{ $img->id }}"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-medium border {{ $isLiked ? 'bg-red-500/20 text-red-500 border-red-500/50' : 'bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-500' }}"
                        >
                            <i class="bi {{ $isLiked ? 'bi-heart-fill' : 'bi-heart' }} transition-transform" id="like-icon-{{ $img->id }}"></i>
                            <span id="like-text-{{ $img->id }}">{{ $isLiked ? 'Batal Suka' : 'Suka' }}</span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-medium border bg-gray-800 text-gray-400 border-gray-600 hover:border-amber-500 hover:text-amber-500">
                            <i class="bi bi-heart"></i>
                            <span>Login untuk Suka</span>
                        </a>
                    @endauth
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center border-2 border-dashed border-gray-800 rounded-3xl">
                <i class="bi bi-images text-5xl text-gray-700 mb-4"></i>
                <p class="text-gray-500 font-medium">Aset Visual belum diterbitkan oleh Admin.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
@auth
<script>
    function toggleLike(imageId) {
        const btn = document.getElementById(`like-btn-${imageId}`);
        const icon = document.getElementById(`like-icon-${imageId}`);
        const text = document.getElementById(`like-text-${imageId}`);
        const count = document.getElementById(`like-count-${imageId}`);
        
        // Animasi pop effect
        icon.classList.add('scale-150');
        setTimeout(() => icon.classList.remove('scale-150'), 200);

        fetch(`/api/batik-images/${imageId}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Update hitungan
                count.innerText = data.likes_count;
                
                // Update styling UI
                if(data.is_liked) {
                    btn.className = "flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-medium border bg-red-500/20 text-red-500 border-red-500/50";
                    icon.className = "bi bi-heart-fill transition-transform";
                    text.innerText = "Batal Suka";
                } else {
                    btn.className = "flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-medium border bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-500";
                    icon.className = "bi bi-heart transition-transform";
                    text.innerText = "Suka";
                }
            }
        })
        .catch(err => console.error(err));
    }
</script>
@endauth
@endpush
