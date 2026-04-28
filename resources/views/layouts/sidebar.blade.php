{{-- Sidebar Component --}}
@php
    require_once resource_path('views/layouts/menu.php');
@endphp

<!-- Sidebar untuk Desktop dan Tablet -->
<aside 
    x-cloak
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    class="fixed top-0 left-0 z-40 h-screen bg-dark border-r border-amber-500 pt-16"
    :class="sidebarOpen ? 'w-64' : 'w-0'"
>
    <div class="h-full px-3 pb-4 overflow-y-auto">
        <!-- Menu Items -->
        <ul class="space-y-2 font-medium mt-4">
            @foreach($menu as $item)
                <li x-data="{ open: false }">
                    @if(count($item->subItems) > 0)
                        {{-- Menu dengan Sub-Items (Dropdown) --}}
                        @php
                            $hasActiveChild = false;
                            foreach($item->subItems as $subItem) {
                                $subPath = ltrim($subItem->url, '/') ?: '/';
                                if(request()->is($subPath) || ($subPath !== '/' && request()->is($subPath . '/*'))) {
                                    $hasActiveChild = true;
                                    break;
                                }
                            }
                        @endphp
                        <button 
                            @click="open = !open"
                            type="button"
                            class="flex items-center w-full rounded-lg transition-colors duration-200 group {{ $hasActiveChild ? 'bg-amber-50 text-amber-600 font-medium px-8 py-2' : 'text-white hover:bg-amber-50 hover:text-amber-600 px-8 py-2' }}"
                        >
                            @if($item->icon)
                                <i class="{{ $item->icon }} text-lg {{ $hasActiveChild ? 'text-amber-600' : 'text-white group-hover:text-amber-600' }}"></i>
                            @endif
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">{{ $item->label }}</span>
                            <svg 
                                class="w-4 h-4 transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"
                                fill="currentColor" 
                                viewBox="0 0 20 20" 
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        
                        {{-- Sub-menu Items --}}
                        <ul x-show="open" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="py-2 space-y-2 pl-6"
                        >
                            @foreach($item->subItems as $subItem)
                                <li>
                                    @php
                                        $subPath = ltrim($subItem->url, '/') ?: '/';
                                        $isActive = request()->is($subPath) || ($subPath !== '/' && request()->is($subPath . '/*'));
                                    @endphp
                                    <a 
                                        href="{{ $subItem->url }}"
                                        class="flex items-center rounded-lg transition-colors duration-200 group {{ $isActive ? 'text-amber-600 bg-amber-50 font-medium px-8 py-2' : 'text-white hover:bg-amber-50 hover:text-amber-600 px-8 py-2' }}"
                                    >
                                        @if($subItem->icon)
                                            <i class="{{ $subItem->icon }} text-base {{ $isActive ? 'text-amber-600' : 'text-white group-hover:text-amber-600' }}"></i>
                                        @endif
                                        <span class="ml-2">{{ $subItem->label }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        {{-- Menu tanpa Sub-Items --}}
                        @php
                            $urlPath = ltrim($item->url, '/') ?: '/';
                            $isActive = request()->is($urlPath) || ($urlPath !== '/' && request()->is($urlPath . '/*'));
                        @endphp
                        <a 
                            href="{{ $item->url }}"
                            class="flex items-center rounded-lg transition-colors duration-200 group {{ $isActive ? 'bg-amber-50 text-amber-600 font-medium px-8 py-2' : 'text-white hover:bg-amber-50 hover:text-amber-600 px-8 py-2' }}"
                        >
                            @if($item->icon)
                                <i class="{{ $item->icon }} text-lg {{ $isActive ? 'text-amber-600' : 'text-white group-hover:text-amber-600' }}"></i>
                            @endif
                            <span class="ml-3">{{ $item->label }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</aside>

<!-- Overlay untuk Mobile ketika sidebar terbuka -->
<div 
    x-cloak
    x-show="sidebarOpen" 
    @click="sidebarOpen = false"
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-30 bg-gray-900 bg-opacity-50 lg:hidden"
></div>
