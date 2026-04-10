@extends('layouts.layout')
@section('title', isset($role) ? 'Edit Role' : 'Buat Role Baru')
@section('content')
<div class="max-w-4xl mx-auto py-10">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">{{ isset($role) ? 'Edit Role: ' . $role->name : 'Buat Role Baru' }}</h1>
        <p class="text-gray-400 mt-1">Konfigurasi batasan akses menu untuk entitas role ini.</p>
    </div>

    <!-- Gunakan route update dgn PUT jika Edit, atau store dengan POST jika Baru -->
    <form action="{{ isset($role) ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-3xl p-8 shadow-xl">
        @csrf
        @if(isset($role))
            @method('PUT')
        @endif

        <div class="mb-6">
            <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">Nama Identitas Role</label>
            <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" required class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500" placeholder="Contoh: Kurator Ahli">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-amber-500 mb-4 uppercase tracking-wider">Perizinan / Flagging Menu</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($menus as $menu)
                    @php
                        $isChecked = isset($roleMenus) ? in_array($menu->id, $roleMenus) : false;
                    @endphp
                    <label class="flex items-center space-x-3 p-4 border border-gray-700 rounded-xl hover:bg-gray-700/50 transition-colors cursor-pointer {{ $isChecked ? 'bg-gray-700/30' : '' }}">
                        <input type="checkbox" name="menus[]" value="{{ $menu->id }}" class="w-5 h-5 rounded border-gray-600 text-primary focus:ring-primary focus:ring-2 bg-gray-900" {{ $isChecked ? 'checked' : '' }}>
                        <span class="text-white font-medium">{{ $menu->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('menus')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="pt-6 border-t border-gray-700 flex justify-end gap-4 mt-8">
            <a href="{{ route('admin.roles.index') }}" class="px-8 py-3 rounded-xl border border-gray-600 text-gray-300 hover:bg-gray-700 transition-colors">Batal</a>
            <button type="submit" class="bg-primary hover:bg-amber-600 text-white font-medium py-3 px-10 rounded-xl transition-all shadow-lg shadow-primary/20">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
