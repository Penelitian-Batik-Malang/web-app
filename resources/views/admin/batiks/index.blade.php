@extends('layouts.layout')
@section('title', 'Kelola Galeri Batik')
@section('content')
<div class="max-w-7xl mx-auto py-10">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Kelola Data Galeri Batik</h1>
            <p class="text-gray-400 mt-1">Mengelola ensiklopedia corak batik Malang beserta aset visualnya.</p>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white border border-gray-600 rounded-lg px-4 py-2 hover:bg-gray-800 transition-colors">Kembali</a>
            <a href="{{ route('admin.batiks.create') }}" class="bg-primary hover:bg-amber-600 text-white rounded-lg px-4 py-2 font-medium transition-colors shadow-lg">+ Karya Baru</a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-gray-800 border border-gray-700 rounded-3xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-sm text-gray-400">
            <thead class="text-xs uppercase bg-gray-900 text-gray-300">
                <tr>
                    <th scope="col" class="px-6 py-4">Thumbnail</th>
                    <th scope="col" class="px-6 py-4">Nama Batik</th>
                    <th scope="col" class="px-6 py-4">Jenis Teknik</th>
                    <th scope="col" class="px-6 py-4 text-center">Status Tayang</th>
                    <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batiks as $batik)
                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4">
                        @if($batik->mainImage)
                            <img src="{{ Storage::url($batik->mainImage->image_path) }}" alt="{{ $batik->name }}" class="h-16 w-16 object-cover rounded-lg border border-gray-600">
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
                            <span class="bg-red-900/50 text-red-400 border border-red-800 px-3 py-1 rounded-full text-xs">Draft / Sembunyi</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.batiks.edit', $batik->id) }}" class="text-amber-500 hover:text-amber-400 font-medium tracking-wide">Kelola</a>
                            <form action="{{ route('admin.batiks.destroy', $batik->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus seluruh aset batik ini secara permanen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-400 font-medium tracking-wide">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">Belum ada karya batik yang ditambahkan ke ensiklopedia galeri Anda.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
