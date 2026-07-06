@if(isset($query))
<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-white">
                Hasil untuk <span class="text-amber-500">"{{ $query }}"</span>
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                @if ($error)
                    {{ $error }}
                @else
                    {{ $hasResults ? count($results) . ' gambar ditemukan, diurutkan berdasarkan kemiripan' : 'Tidak ada gambar yang cocok' }}
                @endif
            </p>
        </div>
        <a href="{{ route('search') }}" id="resetSearchLink" class="text-sm text-gray-400 hover:text-white underline">
            Reset pencarian
        </a>
    </div>

    @if ($hasResults)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach ($results as $item)
                @php
                    $score = $item['score'] ?? 0;
                    $pct = round(min(max($score * 100, 0), 100), 1);
                    $scoreColor = match (true) {
                        $pct >= 80 => ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-400'],
                        $pct >= 50 => ['bar' => 'bg-amber-500', 'text' => 'text-amber-400'],
                        default => ['bar' => 'bg-gray-600', 'text' => 'text-gray-400'],
                    };
                @endphp
                <div class="result-card group bg-gray-900 rounded-2xl overflow-hidden border border-gray-800 hover:border-gray-600 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                    data-item="{!! htmlspecialchars(json_encode([
                        'url' => $item['image_url'], 'category' => $item['category'], 'filename' => $item['filename'],
                        'rank' => $item['rank'], 'score' => $score, 'pct' => $pct,
                        'barColor' => $scoreColor['bar'], 'textColor' => $scoreColor['text'],
                    ]), ENT_QUOTES, 'UTF-8') !!}">
                    <div class="relative overflow-hidden bg-gray-800">
                        <img src="{{ $item['image_url'] }}" alt="{{ $item['category'] }}"
                            class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        <span class="absolute top-2 left-2 bg-black/70 backdrop-blur-sm rounded-full px-2 py-1 text-xs font-bold text-gray-200">#{{ $item['rank'] }}</span>
                        <span class="absolute bottom-2 right-2 bg-black/70 text-white text-xs font-semibold px-2 py-1 rounded">{{ number_format($score, 4) }}</span>
                    </div>
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-gray-100 truncate">{{ $item['category'] }}</h3>
                        <p class="text-xs text-gray-500 truncate mb-3">{{ $item['filename'] }}</p>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-500">Kemiripan</span>
                            <span class="text-xs font-semibold {{ $scoreColor['text'] }}">{{ $pct }}%</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-1.5">
                            <div class="{{ $scoreColor['bar'] }} h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-20 border border-dashed border-gray-800 rounded-2xl">
            <div class="w-14 h-14 rounded-2xl bg-gray-900 flex items-center justify-center mb-4">
                <i class="bi bi-search text-2xl text-gray-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-300">Tidak ada hasil</h3>
            <p class="text-sm text-gray-500 mt-2 text-center max-w-sm">
                Coba deskripsikan warna, motif, atau jenis batik dengan cara lain.
            </p>
        </div>
    @endif
</div>
@endif
