@extends('layouts.layout')
@section('title', 'Kelola Galeri Batik')
@section('content')
<div class="max-w-7xl mx-auto py-10">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Kelola Data Galeri Batik</h1>
            <p class="text-gray-400 mt-1">Mengelola ensiklopedia corak batik Malang beserta aset visualnya.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form action="{{ route('admin.batiks.sync-s3') }}" method="POST" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Syncing...';">
                @csrf
                <button type="submit" class="text-gray-300 hover:text-white border border-cyan-700 bg-cyan-900/30 hover:bg-cyan-800/50 rounded-lg px-4 py-2 font-medium transition-colors flex items-center gap-2">
                    <i class="bi bi-cloud-download"></i> Sync dari S3
                </button>
            </form>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white border border-gray-600 rounded-lg px-4 py-2 hover:bg-gray-800 transition-colors">Kembali</a>
            <a href="{{ route('admin.batiks.create') }}" class="bg-primary hover:bg-amber-600 text-white rounded-lg px-4 py-2 font-medium transition-colors shadow-lg">+ Karya Baru</a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-900/40 border border-green-700 text-green-300 px-4 py-3 rounded-xl mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-900/40 border border-red-700 text-red-300 px-4 py-3 rounded-xl mb-4">{{ session('error') }}</div>
    @endif

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('admin.batiks.index') }}" class="flex flex-wrap gap-3 mb-6">
        <div class="relative flex-1 min-w-[200px]">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
            <input type="text" name="cari" value="{{ request('cari') }}"
                placeholder="Cari nama batik..."
                class="w-full bg-gray-800 border border-gray-700 text-white pl-9 pr-4 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500 transition-colors">
        </div>
        <select name="tipe" class="bg-gray-800 border border-gray-700 text-gray-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
            <option value="">Semua Jenis</option>
            <option value="tulis" {{ request('tipe') === 'tulis' ? 'selected' : '' }}>Tulis</option>
            <option value="cap" {{ request('tipe') === 'cap' ? 'selected' : '' }}>Cap</option>
        </select>
        <select name="status" class="bg-gray-800 border border-gray-700 text-gray-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
            <option value="">Semua Status</option>
            <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
            <option value="sembunyi" {{ request('status') === 'sembunyi' ? 'selected' : '' }}>Disembunyikan</option>
        </select>
        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Filter</button>
        @if(request()->hasAny(['cari','tipe','status']))
            <a href="{{ route('admin.batiks.index') }}" class="text-gray-400 hover:text-white border border-gray-600 px-4 py-2 rounded-lg text-sm transition-colors">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-gray-800 border border-gray-700 rounded-3xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-sm text-gray-400">
            <thead class="text-xs uppercase bg-gray-900 text-gray-300">
                <tr>
                    <th scope="col" class="px-6 py-4">Thumbnail</th>
                    <th scope="col" class="px-6 py-4">Nama Batik</th>
                    <th scope="col" class="px-6 py-4">Jenis</th>
                    <th scope="col" class="px-6 py-4 text-center">Status</th>
                    <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batiks as $batik)
                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors {{ !$batik->is_active ? 'opacity-60' : '' }}">
                    <td class="px-6 py-4">
                        @if($batik->mainImage)
                            <img src="{{ $batik->mainImage->full_url }}" alt="{{ $batik->name }}" loading="lazy" class="h-16 w-16 object-cover rounded-lg border border-gray-600">
                        @else
                            <div class="h-16 w-16 bg-gray-900 border border-gray-700 rounded-lg flex items-center justify-center text-gray-500 text-xs">No Image</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-white font-bold">{{ $batik->name }}</td>
                    <td class="px-6 py-4 uppercase text-xs tracking-wider">
                        @if($batik->type === 'tulis')
                            <span class="text-amber-500">Tulis</span>
                        @else
                            <span class="text-blue-500">Cap</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($batik->is_active)
                            <span class="bg-green-900/50 text-green-400 border border-green-800 px-3 py-1 rounded-full text-xs">Aktif</span>
                        @else
                            <span class="bg-yellow-900/50 text-yellow-400 border border-yellow-800 px-3 py-1 rounded-full text-xs">Disembunyikan</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.batiks.edit', $batik->id) }}" class="text-amber-500 hover:text-amber-400 font-medium tracking-wide">Kelola</a>

                            @if($batik->is_active)
                                {{-- Aktif → bisa disembunyikan --}}
                                <form action="{{ route('admin.batiks.destroy', $batik->id) }}" method="POST"
                                      onsubmit="return confirm('Sembunyikan \'{{ $batik->name }}\' dari galeri publik? Data & likes tetap aman.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-yellow-500 hover:text-yellow-400 font-medium">Sembunyikan</button>
                                </form>
                            @else
                                {{-- Sembunyi → bisa diaktifkan --}}
                                <form action="{{ route('admin.batiks.activate', $batik->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-green-500 hover:text-green-400 font-medium">Aktifkan</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                        @if(request()->hasAny(['cari','tipe','status']))
                            Tidak ada batik yang cocok dengan filter.
                            <a href="{{ route('admin.batiks.index') }}" class="text-amber-500 underline ml-1">Reset filter</a>
                        @else
                            Belum ada karya batik yang ditambahkan ke ensiklopedia galeri Anda.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($batiks->hasPages())
        <div class="flex justify-center pt-6">
            {{ $batiks->links() }}
        </div>
    @endif
</div>
@endsection
