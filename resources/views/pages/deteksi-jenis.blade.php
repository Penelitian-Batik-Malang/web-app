@extends('layouts.layout')
@section('title', 'Deteksi Jenis Batik Malang')

@section('content')
<div class="max-w-4xl mx-auto space-y-12">

    {{-- Hero Header --}}
    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/30 text-primary text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-2">
            <i class="bi bi-cpu-fill"></i> Berbasis Model AI
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold text-white font-playfair leading-tight">
            Deteksi <span class="text-primary">Jenis</span> Batik
        </h1>
        <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
            Tidak yakin batik Anda hasil tulis tangan atau cap? Unggah foto dan AI akan menentukan jenisnya secara otomatis.
        </p>
    </div>

    {{-- Perbedaan Tulis vs Cap --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-900 border border-amber-800/40 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-amber-500/10 border border-amber-500/30 rounded-xl flex items-center justify-center">
                    <i class="bi bi-pencil-fill text-amber-400"></i>
                </div>
                <h3 class="text-white font-bold">Batik Tulis</h3>
            </div>
            <ul class="space-y-2 text-gray-400 text-sm">
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-amber-400 mt-0.5"></i> Dibuat dengan canting secara manual</li>
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-amber-400 mt-0.5"></i> Motif tidak sempurna sempurna & unik</li>
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-amber-400 mt-0.5"></i> Proses pembuatan lebih lama</li>
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-amber-400 mt-0.5"></i> Nilai seni lebih tinggi</li>
            </ul>
        </div>
        <div class="bg-gray-900 border border-blue-800/40 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-500/10 border border-blue-500/30 rounded-xl flex items-center justify-center">
                    <i class="bi bi-grid-3x3-gap-fill text-blue-400"></i>
                </div>
                <h3 class="text-white font-bold">Batik Cap</h3>
            </div>
            <ul class="space-y-2 text-gray-400 text-sm">
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-blue-400 mt-0.5"></i> Dibuat menggunakan stempel (cap) tembaga</li>
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-blue-400 mt-0.5"></i> Motif lebih seragam & teratur</li>
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-blue-400 mt-0.5"></i> Proses produksi lebih cepat</li>
                <li class="flex items-start gap-2"><i class="bi bi-check2 text-blue-400 mt-0.5"></i> Harga lebih terjangkau</li>
            </ul>
        </div>
    </div>

    {{-- CTA Deteksi --}}
    <div class="bg-gradient-to-br from-gray-900 via-blue-950/20 to-gray-900 border border-gray-800 rounded-3xl p-10 flex flex-col items-center text-center gap-6 shadow-2xl">
        <div class="w-20 h-20 bg-primary/10 border border-primary/20 rounded-2xl flex items-center justify-center">
            <i class="bi bi-layers text-primary text-4xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-white font-playfair mb-2">Identifikasi Jenis Batikmu</h2>
            <p class="text-gray-400 text-sm">Klik tombol di bawah untuk membuka panel deteksi jenis batik (Tulis / Cap)</p>
        </div>

        {{-- Komponen Reusable — endpoint berbeda, UI sama --}}
        <x-ml-detector
            id="modal-deteksi-jenis"
            title="Deteksi Jenis Batik"
            subtitle="Unggah foto kain batik dan AI akan menentukan apakah Batik Tulis atau Batik Cap."
            :endpoint="route('api.detect.jenis')"
            result-label="Hasil Deteksi Jenis"
            trigger-text="Mulai Deteksi Jenis"
            trigger-icon="bi-layers"
        />
    </div>

    {{-- Info cara kerja AI --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-camera text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">1. Foto Batik</h3>
            <p class="text-gray-500 text-xs">Ambil foto kain batik dari jarak dekat untuk hasil terbaik</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-cpu text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">2. Analisis Tekstur</h3>
            <p class="text-gray-500 text-xs">AI mengenali pola regularitas yang membedakan tulis dan cap</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
            <div class="w-12 h-12 bg-primary/10 border border-primary/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-patch-check text-primary text-xl"></i>
            </div>
            <h3 class="text-white font-semibold text-sm mb-1">3. Klasifikasi</h3>
            <p class="text-gray-500 text-xs">Output: jenis batik, persentase keyakinan, dan penjelasan</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/ml-detector.js') }}"></script>
@endpush
