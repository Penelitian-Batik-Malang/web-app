@extends('layouts.layout')
@section('title', $batik->name . ' - Detail Batik')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pt-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('galeri') }}" class="text-amber-500 hover:text-amber-400 font-medium inline-flex items-center gap-2 text-sm">
        <i class="bi bi-arrow-left"></i> Kembali ke Galeri
    </a>

    {{-- Flash --}}
    @if(session('like_success'))
        <div id="flash-like" class="flex items-center gap-3 bg-green-900/40 border border-green-700 text-green-300 px-5 py-3 rounded-xl text-sm">
            <i class="bi bi-heart-fill text-red-400"></i>
            <span>Gambar berhasil disukai! Preferensi Anda telah tercatat untuk rekomendasi AI.</span>
            <button onclick="document.getElementById('flash-like').remove()" class="ml-auto text-green-500 hover:text-white"><i class="bi bi-x"></i></button>
        </div>
    @endif

    {{-- Hero: Info Batik --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Thumbnail Utama --}}
        <div class="lg:col-span-1">
            @if($batik->mainImage)
                <img src="{{ $batik->mainImage->full_url }}" alt="{{ $batik->name }}"
                     class="w-full aspect-square object-cover rounded-2xl border border-gray-700 shadow-2xl">
            @else
                <div class="w-full aspect-square bg-gray-800 rounded-2xl border border-gray-700 flex items-center justify-center text-gray-600">
                    <i class="bi bi-image text-6xl"></i>
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-0 right-0 p-6 opacity-5"><i class="bi bi-brush text-8xl"></i></div>
            <div class="relative z-10">
                <h1 class="text-3xl lg:text-4xl font-bold text-white mb-3 font-playfair">{{ $batik->name }}</h1>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="bg-gray-800 border border-gray-700 text-gray-300 px-3 py-1 rounded-full text-xs font-medium">
                        Teknik: <span class="text-amber-500 uppercase">{{ $batik->type }}</span>
                    </span>
                    <span class="bg-gray-800 border border-gray-700 text-gray-300 px-3 py-1 rounded-full text-xs font-medium">
                        <span class="text-amber-400">{{ $images->total() }}</span> variasi motif
                    </span>
                </div>
                <p class="text-gray-400 leading-relaxed text-sm">{{ $batik->description }}</p>
            </div>
            <div class="relative z-10 mt-4 pt-4 border-t border-gray-800">
                <p class="text-gray-500 text-xs">
                    <i class="bi bi-heart text-red-400 mr-1"></i>
                    Klik <i>Suka</i> pada variasi motif untuk mengasah rekomendasi AI.
                    @guest <span class="text-amber-500">— Login diperlukan.</span> @endguest
                </p>
            </div>
        </div>
    </div>

    {{-- Gallery Grid --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-white">Galeri Variasi Motif</h2>
            @if($images->hasPages())
                <span class="text-gray-500 text-xs">Halaman {{ $images->currentPage() }} dari {{ $images->lastPage() }}</span>
            @endif
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @forelse($images as $img)
                @php
                    $isLiked = auth()->check() && auth()->user()->likedBatikImages()->where('batik_image_id', $img->id)->exists();
                    $justLiked = session('like_success') == $img->id;
                    if ($justLiked) $isLiked = true;
                @endphp
                <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden group shadow-md {{ $justLiked ? 'ring-2 ring-red-500/60' : '' }}"
                     data-image-id="{{ $img->id }}">
                    <div class="aspect-square relative group cursor-pointer" 
                         @auth ondblclick="handleDoubleClick(event, {{ $img->id }})" @endauth
                         @guest ondblclick="window.location.href='{{ route('galeri.auto-like', $img->id) }}'" @endguest>
                        <img src="{{ $img->full_url }}" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 select-none">
                        
                        {{-- Double Click Heart Animation Container --}}
                        <div id="heart-anim-{{ $img->id }}" class="absolute inset-0 flex items-center justify-center opacity-0 scale-50 transition-all duration-300 pointer-events-none z-20">
                            <i class="bi bi-heart-fill text-red-500 drop-shadow-xl text-7xl"></i>
                        </div>
                        @if($img->is_main)
                            <div class="absolute top-1.5 left-1.5 bg-primary text-black text-[10px] px-1.5 py-0.5 rounded font-bold uppercase">Utama</div>
                        @endif
                        @if($justLiked)
                            <div class="absolute top-1.5 right-1.5 bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full animate-bounce">❤️</div>
                        @endif
                    </div>
                    <div class="px-2.5 py-2 flex justify-between items-center bg-gray-900 border-t border-gray-800">
                        <span class="text-white text-xs font-bold" id="like-count-{{ $img->id }}">{{ $img->likes_count }}</span>
                        @auth
                            <button onclick="toggleLike({{ $img->id }})"
                                    id="like-btn-{{ $img->id }}"
                                    class="flex items-center gap-1 px-2 py-1 rounded-lg transition-all text-[11px] border {{ $isLiked ? 'bg-red-500/20 text-red-400 border-red-500/50' : 'bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-400' }}">
                                <i class="bi {{ $isLiked ? 'bi-heart-fill' : 'bi-heart' }}" id="like-icon-{{ $img->id }}"></i>
                                <span id="like-text-{{ $img->id }}">{{ $isLiked ? 'Batal' : 'Suka' }}</span>
                            </button>
                        @else
                            <a href="{{ route('galeri.auto-like', $img->id) }}"
                               class="flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] border bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-400 transition-all">
                                <i class="bi bi-heart"></i> Suka
                            </a>
                        @endauth
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center border-2 border-dashed border-gray-800 rounded-3xl">
                    <i class="bi bi-images text-5xl text-gray-700 mb-4 block"></i>
                    <p class="text-gray-500 font-medium">Aset Visual belum diterbitkan oleh Admin.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($images->hasPages())
            <div class="flex justify-center pt-6">
                {{ $images->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- Rekomendasi Section --}}
    @auth
    <div id="rekomendasi-section" class="{{ $hasLikedAny ? '' : 'hidden' }}">
        <div class="border-t border-gray-800 pt-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-1 h-6 bg-primary rounded-full"></div>
                <div>
                    <h2 class="text-lg font-bold text-white">Rekomendasi Motif Serupa</h2>
                    <p class="text-gray-500 text-xs">Berdasarkan gambar yang Anda sukai — diproses oleh Model AI</p>
                </div>
                <div id="rekomendasi-loading" class="ml-auto hidden">
                    <div class="flex items-center gap-2 text-amber-500 text-sm">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Memproses AI...
                    </div>
                </div>
            </div>
            <div id="rekomendasi-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <div class="col-span-full text-center py-8 text-gray-500 text-sm" id="rekomendasi-placeholder">
                    <i class="bi bi-cpu text-3xl text-gray-700 mb-3 block"></i>
                    Klik <i>Suka</i> pada salah satu gambar untuk memunculkan rekomendasi AI.
                </div>
            </div>
        </div>
    </div>
    @endauth

</div>
@endsection

@push('scripts')
@auth
<script>
    @if(session('like_success'))
        document.getElementById('rekomendasi-section').classList.remove('hidden');
        showRekomendasi({{ session('like_success') }});
    @endif

    function handleDoubleClick(event, imageId) {
        event.preventDefault();
        
        const heartAnim = document.getElementById(`heart-anim-${imageId}`);
        if (heartAnim) {
            heartAnim.classList.remove('opacity-0', 'scale-50');
            heartAnim.classList.add('opacity-100', 'scale-110');
            setTimeout(() => {
                heartAnim.classList.remove('opacity-100', 'scale-110');
                heartAnim.classList.add('opacity-0', 'scale-50');
            }, 800);
        }

        const icon = document.getElementById(`like-icon-${imageId}`);
        if (icon && !icon.classList.contains('bi-heart-fill')) {
            toggleLike(imageId);
        }
    }

    function toggleLike(imageId) {
        const btn   = document.getElementById(`like-btn-${imageId}`);
        const icon  = document.getElementById(`like-icon-${imageId}`);
        const text  = document.getElementById(`like-text-${imageId}`);
        const count = document.getElementById(`like-count-${imageId}`);

        icon.classList.add('scale-150');
        setTimeout(() => icon.classList.remove('scale-150'), 200);

        fetch(`/api/batik-images/${imageId}/like`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                count.innerText = data.likes_count;
                if (data.is_liked) {
                    btn.className = 'flex items-center gap-1 px-2 py-1 rounded-lg transition-all text-[11px] border bg-red-500/20 text-red-400 border-red-500/50';
                    icon.className = 'bi bi-heart-fill';
                    text.innerText = 'Batal';
                    showRekomendasi(imageId);
                } else {
                    btn.className = 'flex items-center gap-1 px-2 py-1 rounded-lg transition-all text-[11px] border bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-400';
                    icon.className = 'bi bi-heart';
                    text.innerText = 'Suka';
                }
            }
        })
        .catch(err => console.error(err));
    }

    function showRekomendasi(likedImageId) {
        const section     = document.getElementById('rekomendasi-section');
        const loading     = document.getElementById('rekomendasi-loading');
        const grid        = document.getElementById('rekomendasi-grid');
        const placeholder = document.getElementById('rekomendasi-placeholder');

        section.classList.remove('hidden');
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        loading.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');

        grid.innerHTML = `${[1,2,3,4,5].map(() => `
            <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden animate-pulse">
                <div class="aspect-square bg-gray-700"></div>
                <div class="p-2"><div class="h-2.5 bg-gray-700 rounded w-3/4 mb-1"></div><div class="h-2 bg-gray-700 rounded w-1/2"></div></div>
            </div>`).join('')}`;

        fetch(`/api/batik-images/${likedImageId}/recommend`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => { if (!res.ok) throw new Error(); return res.json(); })
        .then(data => {
            loading.classList.add('hidden');
            renderRekomendasi(data.recommendations ?? []);
        })
        .catch(() => {
            loading.classList.add('hidden');
            grid.innerHTML = `
                <div class="col-span-full py-8 text-center border border-dashed border-amber-800/40 rounded-xl bg-amber-950/20">
                    <i class="bi bi-cpu text-3xl text-amber-600 mb-3 block"></i>
                    <p class="text-amber-400 font-medium text-sm">Model AI Rekomendasi</p>
                    <p class="text-gray-500 text-xs mt-1 max-w-xs mx-auto">Preferensi Anda tercatat. Rekomendasi muncul saat model AI terhubung.</p>
                </div>`;
        });
    }

    function renderRekomendasi(items) {
        const grid = document.getElementById('rekomendasi-grid');
        if (!items.length) { grid.innerHTML = `<div class="col-span-full text-center text-gray-500 text-sm py-8">Tidak ada rekomendasi saat ini.</div>`; return; }
        grid.innerHTML = items.map(item => `
            <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden shadow-lg group">
                <div class="aspect-square"><img src="${item.image_url}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"></div>
                <div class="p-2"><p class="text-white text-xs font-bold truncate">${item.name ?? 'Batik Serupa'}</p><p class="text-gray-500 text-[10px]">${item.type ?? ''}</p></div>
            </div>`).join('');
    }
</script>
@endauth
@endpush
