@extends('layouts.layout')
@section('title', 'Kelola Role & Hak Akses')
@section('content')
<div class="max-w-6xl mx-auto py-10">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Kelola Role Spesialisasi</h1>
            <p class="text-gray-400 mt-1">Mengatur spesialisasi kelompok kerja dan penentuan kapabilitas menu</p>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white border border-gray-600 rounded-lg px-4 py-2 hover:bg-gray-800 transition-colors">Kembali</a>
            <a href="{{ route('admin.roles.create') }}" class="bg-primary hover:bg-amber-600 text-white rounded-lg px-4 py-2 font-medium transition-colors shadow-lg">+ Buat Role Baru</a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-gray-800 border border-gray-700 rounded-3xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-sm text-gray-400">
            <thead class="text-xs uppercase bg-gray-900 text-gray-300">
                <tr>
                    <th scope="col" class="px-6 py-4">Nama Role</th>
                    <th scope="col" class="px-6 py-4">Daftar Menu Flagging (Bisa Mengakses)</th>
                    <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-white">
                        {{ $role->name }}
                        @if($role->name === 'Admin')
                            <span class="ml-2 bg-primary/20 text-primary text-xs px-2 py-0.5 rounded-full border border-primary/30">Super</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-2">
                            @forelse($role->menus as $menu)
                                <span class="bg-gray-700 text-gray-300 text-xs px-2.5 py-1 rounded-md border border-gray-600">{{ $menu->name }}</span>
                            @empty
                                <span class="text-gray-500 italic">Tidak ada akses spesifik (User Biasa)</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-3">
                            @if($role->name !== 'Admin')
                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="text-amber-500 hover:text-amber-400 font-medium tracking-wide">Edit</a>
                            <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus role ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-400 font-medium tracking-wide">Hapus</button>
                            </form>
                            @else
                            <span class="text-gray-500 italic text-xs mt-1" title="Sistem Keamanan Pusat">Terkunci (View Only)</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
