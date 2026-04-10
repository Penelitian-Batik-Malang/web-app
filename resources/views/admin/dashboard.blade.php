@extends('layouts.layout')
@section('title', 'Admin Dashboard')
@section('content')
<div class="max-w-7xl mx-auto py-10">
    <h1 class="text-3xl font-bold text-white mb-8">Admin Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($menus as $menu)
            @php
                $routeMap = [
                    'kelola-konten' => route('admin.landing-contents.index'),
                    'kelola-user' => route('admin.users.index'),
                    'kelola-role' => route('admin.roles.index'),
                    'kelola-galeri' => route('admin.batiks.index'),
                    'monitor-ai' => route('admin.monitor-ai.index'),
                ];
                
                $url = array_key_exists($menu->code, $routeMap) ? $routeMap[$menu->code] : '#';

                $icon = 'bi-grid-fill';
                if ($menu->code === 'kelola-konten') $icon = 'bi-file-earmark-richtext';
                if ($menu->code === 'kelola-user') $icon = 'bi-people';
                if ($menu->code === 'kelola-role') $icon = 'bi-shield-lock';
                if ($menu->code === 'kelola-galeri') $icon = 'bi-images';
                if ($menu->code === 'monitor-ai') $icon = 'bi-cpu';
            @endphp
            
            @if(str_contains($menu->code, 'kelola') || $menu->code === 'monitor-ai')
            <a href="{{ $url }}" class="bg-gray-800 border border-gray-700 hover:border-primary p-6 rounded-2xl transition-all duration-300 group shadow-lg">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gray-900 rounded-lg flex items-center justify-center text-primary border border-gray-600 group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all">
                        <i class="bi {{ $icon }} text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ $menu->name }}</h2>
                        <p class="text-sm text-gray-400 mt-1 cursor-pointer group-hover:text-primary transition-colors">Akses modul {{ strtolower(str_replace('Menu ', '', $menu->name)) }} →</p>
                    </div>
                </div>
            </a>
            @endif
        @endforeach
    </div>
</div>
@endsection
