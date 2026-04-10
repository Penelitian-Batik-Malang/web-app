@extends('layouts.layout')
@section('title', $batik->name . ' - Detail Batik')

@section('content')
<div class="max-w-7xl mx-auto space-y-8 pt-6">
    <div class="mb-2">
        <a href="{{ route('galeri') }}" class="text-amber-500 hover:text-amber-400 font-medium inline-flex items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Galeri
        </a>
    </div>

    {{-- Flash: Auto-Like Sukses --}}
    @if(session('like_success'))
        <div id="flash-like" class="flex items-center gap-3 bg-green-900/40 border border-green-700 text-green-300 px-5 py-3 rounded-xl text-sm">
            <i class="bi bi-heart-fill text-red-400 text-base"></i>
            <span>Gambar berhasil disukai! Preferensi Anda telah tercatat untuk rekomendasi AI.</span>
            <button onclick="document.getElementById('flash-like').remove()" class="ml-auto text-green-500 hover:text-white"><i class="bi bi-x"></i></button>
        </div>
    @endif

    {{-- Info Header Batik --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-6 opacity-5">
            <i class="bi bi-brush text-8xl"></i>
        </div>
        <div class="relative z-10">
            <h1 class="text-3xl lg:text-4xl font-bold text-white mb-3 font-playfair">{{ $batik->name }}</h1>
            <div class="flex gap-3 mb-4">
                <span class="bg-gray-800 border border-gray-700 text-gray-300 px-3 py-1 rounded-full text-xs font-medium tracking-wide">
                    Teknik: <span class="text-amber-500 uppercase">{{ $batik->type }}</span>
                </span>
            </div>
            <p class="text-gray-400 leading-relaxed">{{ $batik->description }}</p>
        </div>
    </div>

    {{-- Bagian Interaktif Foto Detail --}}
    <div>
        <h2 class="text-xl font-bold text-white mb-1 border-b border-gray-800 pb-3">Galeri Penjabaran Motif</h2>
        <p class="text-gray-500 text-sm mt-3 mb-6">
            Tinggalkan jejak <i>like</i> pada variasi motif yang Anda sukai untuk mengasah rekomendasi AI selanjutnya.
            @guest <span class="text-amber-500">— Login diperlukan untuk menyukai gambar.</span> @endguest
        </p>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($batik->images as $img)
                @php
                    $isLiked = auth()->check() && auth()->user()->likedBatikImages()->where('batik_image_id', $img->id)->exists();
                    // Cek apakah gambar ini baru saja di-auto-like (dari redirect)
                    $justLiked = session('like_success') == $img->id;
                    if ($justLiked) $isLiked = true;
                @endphp
                <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden group shadow-lg {{ $justLiked ? 'ring-2 ring-red-500/50' : '' }}"
                     data-image-id="{{ $img->id }}">
                    <div class="aspect-square relative">
                        <img src="{{ Storage::url($img->image_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @if($img->is_main)
                            <div class="absolute top-2 left-2 bg-primary text-black text-xs px-2 py-1 rounded shadow font-bold uppercase tracking-wider">Utama</div>
                        @endif
                        @if($justLiked)
                            <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full shadow font-bold animate-bounce">❤️ Baru Disukai!</div>
                        @endif
                    </div>
                    
                    {{-- Aksi Like --}}
                    <div class="px-3 py-2.5 flex justify-between items-center bg-gray-900 border-t border-gray-800">
                        <div class="text-gray-400 text-xs font-medium">
                            <span id="like-count-{{ $img->id }}" class="text-white font-bold">{{ $img->likes_count }}</span> suka
                        </div>
                        
                        @auth
                            {{-- User sudah login: tombol AJAX like/unlike --}}
                            <button 
                                onclick="toggleLike({{ $img->id }})"
                                id="like-btn-{{ $img->id }}"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all font-medium text-xs border {{ $isLiked ? 'bg-red-500/20 text-red-500 border-red-500/50' : 'bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-500' }}"
                            >
                                <i class="bi {{ $isLiked ? 'bi-heart-fill' : 'bi-heart' }} transition-transform" id="like-icon-{{ $img->id }}"></i>
                                <span id="like-text-{{ $img->id }}">{{ $isLiked ? 'Batal' : 'Suka' }}</span>
                            </button>
                        @else
                            {{-- Guest: link ke auto-like route, Laravel auth middleware simpan intended URL,
                                 setelah login otomatis terapkan like lalu redirect kembali --}}
                            <a href="{{ route('galeri.auto-like', $img->id) }}" 
                               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-400 transition-all"
                               title="Login untuk menyukai gambar ini">
                                <i class="bi bi-heart"></i> Suka
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

    {{-- Rekomendasi Section (muncul setelah like) --}}
    @auth
    <div id="rekomendasi-section" class="hidden">
        <div class="border-t border-gray-800 pt-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-1 h-8 bg-primary rounded-full"></div>
                <div>
                    <h2 class="text-xl font-bold text-white">Rekomendasi Motif Serupa</h2>
                    <p class="text-gray-500 text-xs mt-0.5">Berdasarkan gambar yang Anda sukai — diproses oleh Model AI</p>
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

            <div id="rekomendasi-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="col-span-full text-center py-8 text-gray-500 text-sm" id="rekomendasi-placeholder">
                    <i class="bi bi-cpu text-3xl text-gray-700 mb-3 block"></i>
                    Klik <i>Suka</i> pada salah satu gambar di atas untuk memunculkan rekomendasi batik serupa dari AI.
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
    // Tampilkan rekomendasi section jika user sudah ada yang dilike
    @if($batik->images->filter(fn($img) => auth()->user()->likedBatikImages->contains('id', $img->id))->count() > 0)
        document.getElementById('rekomendasi-section').classList.remove('hidden');
    @endif

    // Jika baru auto-like dari redirect, langsung trigger rekomendasi
    @if(session('like_success'))
        document.getElementById('rekomendasi-section').classList.remove('hidden');
        showRekomendasi({{ session('like_success') }});
    @endif

    function toggleLike(imageId) {
        const btn = document.getElementById(`like-btn-${imageId}`);
        const icon = document.getElementById(`like-icon-${imageId}`);
        const text = document.getElementById(`like-text-${imageId}`);
        const count = document.getElementById(`like-count-${imageId}`);
        
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
                count.innerText = data.likes_count;
                if(data.is_liked) {
                    btn.className = "flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all font-medium text-xs border bg-red-500/20 text-red-500 border-red-500/50";
                    icon.className = "bi bi-heart-fill transition-transform";
                    text.innerText = "Batal";
                    showRekomendasi(imageId);
                } else {
                    btn.className = "flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all font-medium text-xs border bg-gray-800 text-gray-400 border-gray-600 hover:border-red-500 hover:text-red-500";
                    icon.className = "bi bi-heart transition-transform";
                    text.innerText = "Suka";
                }
            }
        })
        .catch(err => console.error(err));
    }

    function showRekomendasi(likedImageId) {
        const section = document.getElementById('rekomendasi-section');
        const loading = document.getElementById('rekomendasi-loading');
        const grid = document.getElementById('rekomendasi-grid');
        const placeholder = document.getElementById('rekomendasi-placeholder');

        section.classList.remove('hidden');
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });

        loading.classList.remove('hidden');
        placeholder.classList.add('hidden');

        grid.innerHTML = `${[1,2,3,4].map(() => `
            <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden animate-pulse">
                <div class="aspect-square bg-gray-700"></div>
                <div class="p-3"><div class="h-3 bg-gray-700 rounded w-3/4 mb-2"></div><div class="h-2 bg-gray-700 rounded w-1/2"></div></div>
            </div>`).join('')}`;

        fetch(`/api/batik-images/${likedImageId}/recommend`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => { if(!res.ok) throw new Error(); return res.json(); })
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
                    <p class="text-gray-500 text-xs mt-1 max-w-xs mx-auto">Preferensi Anda telah tercatat. Rekomendasi visual akan muncul di sini ketika model AI telah terhubung.</p>
                </div>`;
        });
    }

    function renderRekomendasi(items) {
        const grid = document.getElementById('rekomendasi-grid');
        if(!items.length) { grid.innerHTML = `<div class="col-span-full text-center text-gray-500 text-sm py-8">Tidak ada rekomendasi saat ini.</div>`; return; }
        grid.innerHTML = items.map(item => `
            <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden shadow-lg group">
                <div class="aspect-square"><img src="${item.image_url}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"></div>
                <div class="p-3"><p class="text-white text-xs font-bold">${item.name ?? 'Batik Serupa'}</p><p class="text-gray-500 text-xs mt-0.5">${item.type ?? ''}</p></div>
            </div>`).join('');
    }
</script>
@endauth
@endpush
