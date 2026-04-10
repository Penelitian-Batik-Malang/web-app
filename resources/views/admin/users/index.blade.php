@extends('layouts.layout')
@section('title', 'Kelola Pengguna')
@section('content')
<div class="max-w-6xl mx-auto py-10">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Kelola Pengguna</h1>
            <p class="text-gray-400 mt-1">Mengelola daftar semua pengguna di aplikasi dan spesialisasi role-nya</p>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white border border-gray-600 rounded-lg px-4 py-2 hover:bg-gray-800 transition-colors">Kembali</a>
            <a href="{{ route('admin.users.create') }}" class="bg-primary hover:bg-amber-600 text-white rounded-lg px-4 py-2 font-medium transition-colors shadow-lg">+ Buat User Baru</a>
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
                    <th scope="col" class="px-6 py-4">Nama Pengguna</th>
                    <th scope="col" class="px-6 py-4">Email Address</th>
                    <th scope="col" class="px-6 py-4">Jabatan (Role)</th>
                    <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-white">
                        {{ $user->name }}
                        @if($user->email === 'admin@email.com')
                            <span class="ml-2 bg-primary/20 text-primary text-xs px-2 py-0.5 rounded-full border border-primary/30" title="Akun kebal penghapusan">Super Admin</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        @if($user->role)
                            <span class="bg-gray-700 text-gray-300 text-xs px-2.5 py-1 rounded-md border border-gray-600">{{ $user->role->name }}</span>
                        @else
                            <span class="text-gray-500 italic">User Biasa</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-3">
                            @if($user->email !== 'admin@email.com' || auth()->user()->email === 'admin@email.com')
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="text-amber-500 hover:text-amber-400 font-medium tracking-wide">Edit</a>
                            @endif
                            @if($user->email !== 'admin@email.com')
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-400 font-medium tracking-wide">Hapus</button>
                            </form>
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
