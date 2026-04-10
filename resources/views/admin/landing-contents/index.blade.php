@extends('layouts.layout')
@section('title', 'Kelola Konten Global')
@section('content')
<div class="max-w-5xl mx-auto py-10">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white">Kelola Konten Global</h1>
            <p class="text-gray-400 mt-1">Ubah teks dan logo untuk bagian Landing Page (Beranda)</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white border border-gray-600 rounded-lg px-4 py-2 hover:bg-gray-800 transition-colors">← Kembali ke Dashboard</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.landing-contents.update') }}" method="POST" enctype="multipart/form-data" class="bg-gray-800 border border-gray-700 rounded-3xl p-8 shadow-xl">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($contents as $item)
                <div class="{{ (strlen($item->value) > 80 || str_contains($item->value, '\n') || $item->type === 'image') ? 'md:col-span-2' : '' }}">
                    <label class="block text-sm font-medium text-amber-500 mb-2 uppercase tracking-wider">
                        {{ ucwords(str_replace('_', ' ', $item->key)) }} 
                        <span class="text-gray-500 text-xs normal-case">({{ $item->type }})</span>
                    </label>
                    
                    @if($item->type === 'image')
                        <div class="flex gap-4 items-start">
                            @if($item->value)
                                <img src="{{ $item->value }}" class="w-24 h-24 object-cover rounded-lg border border-gray-600 bg-gray-900">
                            @endif
                            <div class="flex-1 space-y-2">
                                <input type="text" name="{{ $item->key }}" value="{{ $item->value }}" placeholder="URL Gambar / Path..." class="px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500 w-full mb-2">
                                <span class="text-gray-400 text-sm">ATAU Unggah Gambar Baru:</span>
                                <input type="file" name="{{ $item->key }}" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                            </div>
                        </div>
                    @elseif(strlen($item->value) > 80 || str_contains($item->value, '\n'))
                        <textarea name="{{ $item->key }}" rows="4" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500 leading-relaxed max-h-48">{{ str_replace('\n', "\n", $item->value) }}</textarea>
                    @else
                        <input type="text" name="{{ $item->key }}" value="{{ $item->value }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500">
                    @endif
                </div>
            @endforeach
        </div>

        <div class="pt-8 border-t border-gray-700 mt-10 md:-mx-8 md:px-8 bg-gray-800/80 rounded-b-3xl -mb-8 pb-8 flex items-center justify-end">
            <button type="submit" class="bg-primary hover:bg-amber-600 text-white font-medium py-3.5 px-12 rounded-xl transition-all shadow-lg shadow-primary/20 transform hover:-translate-y-0.5 mt-2 text-lg">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
