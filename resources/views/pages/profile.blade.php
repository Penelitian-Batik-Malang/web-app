@extends('layouts.layout')

@section('title', 'Profil Akun - BatikMalang.ai')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-8">
        <h2 class="text-2xl font-bold text-white mb-6">Profil Akun</h2>

        @if(session('status'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Name Input -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                    Nama Lengkap
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                >
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Input -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                    Email
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                >
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <hr class="border-gray-700">
            <h3 class="text-lg font-medium text-white mb-4">Ganti Password (Opsional)</h3>

            <!-- Password Input -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                    Password Baru
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                    placeholder="Kosongkan jika tidak ingin ganti password"
                >
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Confirm Input -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">
                    Konfirmasi Password Baru
                </label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation"
                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                    placeholder="••••••••"
                >
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4">
                <button 
                    type="submit"
                    class="bg-primary hover:bg-amber-600 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
