@props([
    'image'       => null,
    'title'       => 'Nama Batik',
    'description' => null,
    'badge'       => null,
    'href'        => '#',
])

<a href="{{ $href }}" class="group block bg-gray-900 border border-gray-700 rounded-xl overflow-hidden hover:border-primary transition-all duration-300 hover:shadow-lg hover:shadow-primary/20">

    {{-- Image --}}
    <div class="relative overflow-hidden aspect-video bg-gray-800">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        @else
            <div class="flex items-center justify-center w-full h-full text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        @endif

        @if ($badge)
            <span class="absolute top-3 left-3 px-2 py-0.5 text-xs font-semibold rounded-full bg-primary text-black">
                {{ $badge }}
            </span>
        @endif
    </div>

    {{-- Body --}}
    <div class="p-4 space-y-2">
        <h3 class="text-base font-semibold text-gold group-hover:text-amber-400 transition-colors duration-200 line-clamp-1">
            {{ $title }}
        </h3>

        @if ($description)
            <p class="text-sm text-white line-clamp-2">
                {{ $description }}
            </p>
        @endif

        {{-- Slot default (optional extra content) --}}
        @if ($slot->isNotEmpty())
            <div class="pt-2">
                {{ $slot }}
            </div>
        @endif
    </div>

    {{-- Footer divider hint --}}
    <div class="h-0.5 w-0 group-hover:w-full bg-primary transition-all duration-300"></div>
</a>
