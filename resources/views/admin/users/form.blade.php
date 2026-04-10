@extends('layouts.layout')
@section('title', isset($user) ? 'Edit Pengguna' : 'Buat Pengguna Baru')
@section('content')
<div class="max-w-4xl mx-auto py-10">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">{{ isset($user) ? 'Edit Profil: ' . $user->name : 'Registrasi User Baru' }}</h1>
        <p class="text-gray-400 mt-1">Isi formulir berikut untuk mengatur identitas kredensial dan hak aksesnya.</p>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-3xl p-8 shadow-xl">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500" placeholder="John Doe">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required {{ (isset($user) && $user->email === 'admin@email.com') ? 'readonly' : '' }} class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500 {{ (isset($user) && $user->email === 'admin@email.com') ? 'opacity-50 cursor-not-allowed' : '' }}" placeholder="email@contoh.com">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @if(isset($user) && $user->email === 'admin@email.com')
                    <p class="text-xs text-gray-500 mt-1">Super Admin tidak dapat merubah alamat pelacakan mutlaknya.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">Spesialisasi (Role)</label>
                <select name="role_id" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500" {{ (isset($user) && $user->email === 'admin@email.com') ? 'disabled' : '' }}>
                    <option value="">-- Hanya User Biasa (Tanpa Hak Khusus) --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ (old('role_id', $user->role_id ?? '') == $role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
                @if(isset($user) && $user->email === 'admin@email.com')
                    <!-- Kirim nilai asli karena input ter-disable -->
                    <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                @endif
                @error('role_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">
                    Password Akses {{ isset($user) ? '(Kosongkan jika tidak ubah)' : '' }}
                </label>
                <input type="password" name="password" {{ !isset($user) ? 'required' : '' }} class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500" placeholder="••••••••">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pt-6 border-t border-gray-700 flex justify-end gap-4 mt-8">
            <a href="{{ route('admin.users.index') }}" class="px-8 py-3 rounded-xl border border-gray-600 text-gray-300 hover:bg-gray-700 transition-colors">Batal</a>
            <button type="submit" class="bg-primary hover:bg-amber-600 text-white font-medium py-3 px-10 rounded-xl transition-all shadow-lg shadow-primary/20">
                Simpan Profil Pengguna
            </button>
        </div>
    </form>
</div>
@endsection
