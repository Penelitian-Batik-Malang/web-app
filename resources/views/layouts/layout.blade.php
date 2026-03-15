<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'BatikMalang.ai') }} - @yield('title', 'Digital Batik Gallery')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-dark" x-data="{ sidebarOpen: false, mobileMenuOpen: false }">
    
    {{-- Navbar --}}
    <nav class="fixed top-0 z-50 w-full bg-dark border-b border-secondary">
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start">
                    {{-- Toggle Sidebar Button (Mobile Only) --}}
                    <button 
                        @click="sidebarOpen = !sidebarOpen" 
                        type="button" 
                        class="lg:hidden inline-flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-600 transition-colors duration-200"
                    >
                        <span class="sr-only">Toggle sidebar</span>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>

                    {{-- Logo --}}
                    <a href="{{ url('/') }}" class="flex ml-2 md:mr-24">
                        <span class="self-center text-xl font-bold sm:text-2xl whitespace-nowrap text-white">
                            BatikMalang<span class="text-primary">.ai</span>
                        </span>
                    </a>
                </div>

                {{-- Desktop Navigation Menu --}}
                <div class="hidden md:flex md:items-center md:space-x-8">
                    @php
                        require_once resource_path('views/layouts/menu.php');
                    @endphp

                    @foreach($menu as $item)
                        @if(count($item->subItems) > 0)
                            {{-- Dropdown Menu --}}
                            <div class="relative" x-data="{ dropdownOpen: false }">
                                <button 
                                    @click="dropdownOpen = !dropdownOpen"
                                    @click.away="dropdownOpen = false"
                                    class="flex items-center text-gray-300 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                                >
                                    {{ $item->label }}
                                    <svg class="w-4 h-4 ml-1 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                
                                {{-- Dropdown Content --}}
                                <div 
                                    x-show="dropdownOpen"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                                    style="display: none;"
                                >
                                    <div class="py-1" role="menu">
                                        @foreach($item->subItems as $subItem)
                                            @php
                                                $currentPath = trim(request()->path(), '/');
                                                $itemPath = trim($subItem->url, '/');
                                                $isActive = $currentPath === $itemPath || str_starts_with($currentPath, $itemPath . '/');
                                            @endphp
                                            <a 
                                                href="{{ $subItem->url }}" 
                                                class="block px-4 py-2 text-sm transition-colors duration-200 {{ $isActive ? 'text-amber-600 bg-amber-50 font-medium' : 'text-gray-700 hover:bg-amber-50 hover:text-amber-600' }}"
                                                role="menuitem"
                                            >
                                                {{ $subItem->label }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Regular Menu Item --}}
                            <a 
                                href="{{ $item->url }}" 
                                class="text-gray-300 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->is(ltrim($item->url, '/') ?: '/') ? 'text-primary bg-secondary/40' : '' }}"
                            >
                                {{ $item->label }}
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Login Button --}}
                <div class="flex items-center">
                    <a 
                        href="{{ route('login') ?? '#' }}" 
                        class="text-gray-900 bg-primary hover:bg-amber-600 focus:ring-4 focus:outline-none focus:ring-amber-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-200 shadow-md hover:shadow-lg"
                    >
                        Masuk
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Main Content Area --}}
    <div class="transition-all duration-300 ease-in-out pt-16" :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-0'">
        <main class="min-h-screen">
            {{-- Content Section --}}
            <div class="p-4 md:p-6 lg:p-8">
                @yield('content')
            </div>
        </main>

        {{-- Footer --}}
        <footer class="bg-dark border-t border-secondary mt-auto">
            <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    {{-- BatikMalang.ai Section --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-white">
                            BatikMalang<span class="text-primary">.ai</span>
                        </h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Dikembangkan melalui kemitraan khusus dengan tiga sentra batik terkemuka di Malang.
                        </p>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Batik Blimbing</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Batik Soeandari</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Rumah Seni Budaya Singhasari</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Dibuat oleh Section --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-white">Dibuat oleh</h3>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li>Mamluatul Hani'ah</li>
                            <li>Vivi Nur Wijayaingrum</li>
                            <li>Wilda Imama Salsabilla</li>
                        </ul>
                    </div>

                    {{-- Kontak Section --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-white">Kontak</h3>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li class="flex items-center gap-2">
                                <i class="bi bi-instagram"></i>
                                <span>jtipolinema</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="bi bi-instagram"></i>
                                <span>polimema_campus</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Alamat Section --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-white">Alamat</h3>
                        <address class="not-italic text-gray-400 text-sm leading-relaxed">
                            <p>Politeknik Negeri Malang</p>
                            <p>Jalan Soekarno Hatta No. 9</p>
                            <p>Kota Malang, Jawa Timur</p>
                        </address>
                    </div>
                </div>

                {{-- Footer Bottom --}}
                <div class="mt-8 pt-8 border-t border-gray-800">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-gray-500 text-sm">
                        <p>Copyright {{ date('Y') }} BatikMalang.ai</p>
                        <p>No Surat Kementrian</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
