@extends('layouts.layout')
@section('title', 'Deteksi Motif Batik Malang')

@section('content')
<div class="max-w-4xl mx-auto space-y-12">

    {{-- Hero Header --}}
    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/30 text-primary text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-cpu-fill"></i> Berbasis Model AI
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Deteksi <span class="text-primary">Motif</span> Batik
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Unggah foto kain batik Malang dan biarkan AI mengidentifikasi motifnya secara otomatis — akurat, cepat, dan instan.
        </p>
    </div>

    {{-- Cara Kerja --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-camera text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">1. Unggah Gambar</h3>
            <p class="text-gray-500 text-xs">Foto kain batik dari kamera atau galeri perangkat Anda</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-cpu text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">2. Analisis AI</h3>
            <p class="text-gray-500 text-xs">Model AI menganalisis pola, warna, dan tekstur kain batik</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-patch-check text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">3. Hasil & Deskripsi</h3>
            <p class="text-gray-500 text-xs">Dapatkan nama motif, persentase keyakinan, dan keterangan lengkap</p>
        </div>
    </div>

    {{-- CTA Deteksi --}}
    <div class="bg-gradient-to-br from-gray-900 via-amber-950/20 to-gray-900 border border-gray-800 rounded-3xl p-10 flex flex-col items-center text-center gap-6 shadow-2xl">
        <div class="w-20 h-20 bg-primary/10 border border-primary/20 rounded-2xl flex items-center justify-center">
            <i class="bi bi-search text-primary text-4xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-white font-playfair mb-2">Siap untuk Mendeteksi?</h2>
            <p class="text-gray-400 text-sm">Klik tombol di bawah untuk membuka panel deteksi motif batik</p>
        </div>

        {{-- Komponen Reusable --}}
        <x-ml-detector
            id="modal-deteksi-motif"
            title="Deteksi Motif Batik"
            subtitle="Unggah foto kain batik Malang dan biarkan AI mengidentifikasi motifnya secara otomatis."
            :endpoint="route('api.detect.motif')"
            result-label="Hasil Deteksi Motif"
            trigger-text="Mulai Deteksi Motif"
            trigger-icon="bi-search"
        />
    </div>

    {{-- Info Motif yang Didukung — DINAMIS dari API --}}
    <div>
        <div class="flex items-center justify-between mb-6 border-b border-gray-800 pb-3">
            <h2 class="text-xl font-bold text-white">Motif Batik yang Dapat Dideteksi</h2>
            <span id="motif-label-count" class="text-xs text-gray-500"></span>
        </div>

        {{-- Loading skeleton --}}
        <div id="motif-labels-skeleton" class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach(range(1, 8) as $_)
                <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-3 animate-pulse">
                    <div class="h-3 bg-gray-800 rounded w-3/4"></div>
                </div>
            @endforeach
        </div>

        {{-- Grid dinamis dari API --}}
        <div id="motif-labels-grid" class="hidden grid grid-cols-2 md:grid-cols-4 gap-3"></div>

        <p class="text-gray-600 text-xs mt-3 text-center">*Daftar motif diperbarui seiring perkembangan model AI</p>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/ml-detector.js') }}"></script>
<script>
// Fetch daftar motif secara dinamis dari Batik Service
(async () => {
    const skeleton = document.getElementById('motif-labels-skeleton');
    const grid     = document.getElementById('motif-labels-grid');
    const counter  = document.getElementById('motif-label-count');

    try {
        const res    = await fetch('{{ route('api.detect.motif.labels') }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const labels = await res.json();

        if (Array.isArray(labels) && labels.length) {
            counter.textContent = `${labels.length} motif`;
            grid.innerHTML = labels.map(motif => `
                <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-3 flex items-center gap-3 hover:border-primary/40 transition-colors">
                    <div class="w-2 h-2 rounded-full bg-primary flex-shrink-0"></div>
                    <span class="text-gray-300 text-sm font-medium">${motif}</span>
                </div>
            `).join('');
            skeleton.classList.add('hidden');
            grid.classList.remove('hidden');
            return;
        }
    } catch (_) {}

    // Fallback statis jika API tidak tersedia
    const fallback = ['Sido Mukti', 'Parang', 'Kawung', 'Banji', 'Ceplok', 'Truntum', 'Sekar Jagad', 'Balai Kota'];
    counter.textContent = `${fallback.length} motif (fallback)`;
    grid.innerHTML = fallback.map(motif => `
        <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-3 flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-gray-600 flex-shrink-0"></div>
            <span class="text-gray-400 text-sm font-medium">${motif}</span>
        </div>
    `).join('');
    skeleton.classList.add('hidden');
    grid.classList.remove('hidden');
})();
</script>
@endpush
