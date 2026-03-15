@props(['title', 'description', 'icon', 'iconBgColor' => 'bg-primary/10', 'iconTextColor' => 'text-primary', 'badge' => null])

<div class="bg-gray-900 border border-gray-700 rounded-xl p-6 hover:border-primary transition-all duration-300 hover:shadow-lg hover:shadow-primary/20">
    <div class="flex items-start gap-4">
        <div class="{{ $iconBgColor }} {{ $iconTextColor }} p-3 rounded-lg shrink-0">
            <i class="{{ $icon }} text-2xl"></i>
        </div>
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold text-white text-lg">{{ $title }}</h3>
                @if($badge)
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-primary text-black">{{ $badge }}</span>
                @endif
            </div>
            <p class="text-gray-400 text-sm">{{ $description }}</p>
        </div>
    </div>
</div>
