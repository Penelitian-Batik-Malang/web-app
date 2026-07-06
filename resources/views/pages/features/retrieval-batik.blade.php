@extends('layouts.layout')

@section('title', 'Pencarian Batik Malang')

@section('content')

    @php
        $hasResults = isset($results) && count($results) > 0;
    @endphp

    <div class="max-w-7xl mx-auto px-4 py-10 space-y-10">

        {{-- Hero / Search --}}
        <div class="flex flex-col items-center gap-6 text-center border-b border-gray-800 pb-10">

            <div class="space-y-3 max-w-2xl">
                <span class="inline-block text-xs font-semibold tracking-widest text-amber-500 uppercase">
                    Text-to-Image Retrieval
                </span>
                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight">
                    Ceritakan batik yang kamu bayangkan
                </h1>
                <p class="text-sm md:text-base text-gray-400">
                    Tidak perlu tahu nama motifnya. Tulis saja deskripsinya, misalnya
                    <span class="text-gray-300 italic">"batik warna coklat dengan motif bunga"</span>,
                    dan sistem akan mencarikan gambar yang paling sesuai.
                </p>
            </div>

            <form id="searchForm" action="{{ route('search') }}" method="GET" class="w-full max-w-2xl">
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    <input type="text" name="query" id="searchInput" value="{{ $query ?? '' }}"
                        placeholder="Deskripsikan batik yang kamu cari..."
                        class="w-full pl-11 pr-28 py-4 bg-gray-900 border border-gray-700 text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all"
                        required autofocus>
                    <button type="submit" id="searchBtn"
                        class="absolute right-2 top-1/2 -translate-y-1/2 px-5 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-black text-sm font-semibold transition-colors">
                        <span id="searchBtnText">Cari</span>
                    </button>
                </div>
            </form>

            {{-- Contoh prompt --}}
            <div id="examplePrompts" class="flex flex-wrap justify-center gap-2 max-w-2xl"
                @unless (!isset($query)) style="display:none;" @endunless>
                @foreach (['batik warna coklat dengan motif bunga', 'batik cap motif geometris biru', 'batik tulis dominan warna merah', 'motif daun dan sulur warna hijau'] as $contoh)
                    <a href="{{ route('search', ['query' => $contoh]) }}" data-query="{{ $contoh }}"
                        class="example-link text-xs px-3 py-1.5 rounded-full border border-gray-700 text-gray-400 hover:text-white hover:border-gray-500 transition-colors">
                        {{ $contoh }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Container hasil pencarian: HANYA bagian ini yang di-update via AJAX --}}
        <div id="resultsContainer">
            @include('pages.features.partials.retrieval-results', [
                'query' => $query ?? null,
                'results' => $results ?? [],
                'error' => $error ?? null,
                'hasResults' => $hasResults,
            ])
        </div>
    </div>

    {{-- ===================== MODAL DETAIL GAMBAR ===================== --}}
    <div id="imageModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        onclick="if (event.target === this) closeImageModal()">

        <div class="bg-gray-900 border border-gray-700 rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto relative">
            <button onclick="closeImageModal()"
                class="absolute top-3 right-3 z-10 w-9 h-9 flex items-center justify-center rounded-full bg-black/60 hover:bg-black/80 text-white transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>

            <div class="bg-gray-800">
                <img id="modalImage" src="" alt="" class="w-full max-h-[60vh] object-contain">
            </div>

            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 id="modalCategory" class="text-xl font-bold text-white"></h3>
                        <p id="modalFilename" class="text-sm text-gray-500 mt-1"></p>
                    </div>
                    <span id="modalRank"
                        class="shrink-0 bg-gray-800 border border-gray-700 rounded-full px-3 py-1 text-xs font-bold text-gray-300"></span>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-400">Tingkat Kemiripan</span>
                        <span id="modalPct" class="text-sm font-semibold"></span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-2">
                        <div id="modalBar" class="h-2 rounded-full transition-all duration-300"></div>
                    </div>
                    <p id="modalScore" class="text-xs text-gray-500 mt-2"></p>
                </div>

                <a id="modalDownload" href="" download target="_blank"
                    class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-black font-semibold transition-colors">
                    <i class="bi bi-download"></i>
                    Buka gambar penuh
                </a>
            </div>
        </div>
    </div>

    <script>
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const searchBtnText = document.getElementById('searchBtnText');
        const resultsContainer = document.getElementById('resultsContainer');
        const examplePrompts = document.getElementById('examplePrompts');
        const searchUrl = "{{ route('search') }}";

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function scoreColorClasses(pct) {
            if (pct >= 80) return {
                bar: 'bg-emerald-500',
                text: 'text-emerald-400'
            };
            if (pct >= 50) return {
                bar: 'bg-amber-500',
                text: 'text-amber-400'
            };
            return {
                bar: 'bg-gray-600',
                text: 'text-gray-400'
            };
        }

        function renderResults(query, results, error) {
            const hasResults = Array.isArray(results) && results.length > 0;
            examplePrompts.style.display = 'none';

            let html = '<div>';
            html += '<div class="mb-6 flex items-center justify-between">';
            html += '<div>';
            html +=
                `<h2 class="text-lg font-semibold text-white">Hasil untuk <span class="text-amber-500">"${escapeHtml(query)}"</span></h2>`;
            html += '<p class="mt-1 text-sm text-gray-500">';
            html += error ? escapeHtml(error) : (hasResults ?
                `${results.length} gambar ditemukan, diurutkan berdasarkan kemiripan` : 'Tidak ada gambar yang cocok');
            html += '</p></div>';
            html +=
                `<a href="${searchUrl}" id="resetSearchLink" class="text-sm text-gray-400 hover:text-white underline">Reset pencarian</a>`;
            html += '</div>';

            if (hasResults) {
                html += '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">';
                results.forEach((item) => {
                    const score = item.score ?? 0;
                    const pct = Math.round(Math.min(Math.max(score * 100, 0), 100) * 10) / 10;
                    const color = scoreColorClasses(pct);
                    const itemData = {
                        url: item.image_url,
                        category: item.category,
                        filename: item.filename,
                        rank: item.rank,
                        score: score,
                        pct: pct,
                        barColor: color.bar,
                        textColor: color.text,
                    };
                    html +=
                        `<div class="result-card group bg-gray-900 rounded-2xl overflow-hidden border border-gray-800 hover:border-gray-600 hover:-translate-y-1 transition-all duration-300 cursor-pointer" data-item='${escapeHtml(JSON.stringify(itemData))}'>`;
                    html += `<div class="relative overflow-hidden bg-gray-800">`;
                    html +=
                        `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.category)}" class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">`;
                    html +=
                        `<span class="absolute top-2 left-2 bg-black/70 backdrop-blur-sm rounded-full px-2 py-1 text-xs font-bold text-gray-200">#${item.rank}</span>`;
                    html +=
                        `<span class="absolute bottom-2 right-2 bg-black/70 text-white text-xs font-semibold px-2 py-1 rounded">${Number(score).toFixed(4)}</span>`;
                    html += `</div><div class="p-3">`;
                    html +=
                        `<h3 class="font-semibold text-sm text-gray-100 truncate">${escapeHtml(item.category)}</h3>`;
                    html += `<p class="text-xs text-gray-500 truncate mb-3">${escapeHtml(item.filename)}</p>`;
                    html +=
                        `<div class="flex items-center justify-between mb-1"><span class="text-xs text-gray-500">Kemiripan</span><span class="text-xs font-semibold ${color.text}">${pct}%</span></div>`;
                    html +=
                        `<div class="w-full bg-gray-800 rounded-full h-1.5"><div class="${color.bar} h-1.5 rounded-full" style="width: ${pct}%"></div></div>`;
                    html += `</div></div>`;
                });
                html += '</div>';
            } else {
                html += `<div class="flex flex-col items-center justify-center py-20 border border-dashed border-gray-800 rounded-2xl">
                    <div class="w-14 h-14 rounded-2xl bg-gray-900 flex items-center justify-center mb-4">
                        <i class="bi bi-search text-2xl text-gray-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-300">Tidak ada hasil</h3>
                    <p class="text-sm text-gray-500 mt-2 text-center max-w-sm">Coba deskripsikan warna, motif, atau jenis batik dengan cara lain.</p>
                </div>`;
            }
            html += '</div>';

            resultsContainer.innerHTML = html;
            attachResultCardListeners();

            document.getElementById('resetSearchLink')?.addEventListener('click', function(e) {
                e.preventDefault();
                resetSearch();
            });
        }

        function resetSearch() {
            searchInput.value = '';
            examplePrompts.style.display = 'flex';
            resultsContainer.innerHTML = '';
            history.pushState({}, '', searchUrl);
        }

        async function doSearch(query, pushHistory = true) {
            if (!query) return;
            searchBtn.disabled = true;
            searchBtnText.textContent = 'Mencari...';
            resultsContainer.innerHTML = `<div class="flex justify-center py-16 text-gray-500 text-sm">
                <i class="bi bi-arrow-repeat animate-spin mr-2"></i> Mencari batik yang sesuai...
            </div>`;

            try {
                const url = `${searchUrl}?query=${encodeURIComponent(query)}`;
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                renderResults(data.query ?? query, data.results ?? [], data.error);

                if (pushHistory) {
                    history.pushState({
                        query
                    }, '', searchUrl);
                }
            } catch (err) {
                resultsContainer.innerHTML = `<div class="text-center py-16 text-red-400 text-sm">
                    Terjadi kesalahan saat menghubungi server. Coba lagi.
                </div>`;
                console.error(err);
            } finally {
                searchBtn.disabled = false;
                searchBtnText.textContent = 'Cari';
            }
        }

        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            doSearch(searchInput.value.trim());
        });

        document.querySelectorAll('.example-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const q = this.dataset.query;
                searchInput.value = q;
                doSearch(q);
            });
        });

        // Dukung tombol back/forward browser
        window.addEventListener('popstate', function() {
            const params = new URLSearchParams(window.location.search);
            const q = params.get('query');
            if (q) {
                searchInput.value = q;
                doSearch(q, false);
            } else {
                resetSearch();
            }
        });

        function attachResultCardListeners() {
            document.querySelectorAll('.result-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    try {
                        const data = JSON.parse(card.dataset.item);
                        openImageModal(data);
                    } catch (e) {
                        console.error('Gagal membaca data gambar:', e);
                    }
                });
            });
        }
        attachResultCardListeners();

        function openImageModal(data) {
            document.getElementById('modalImage').src = data.url;
            document.getElementById('modalImage').alt = data.category;
            document.getElementById('modalCategory').textContent = data.category;
            document.getElementById('modalFilename').textContent = data.filename;
            document.getElementById('modalRank').textContent = '#' + data.rank;
            document.getElementById('modalPct').textContent = data.pct + '%';
            document.getElementById('modalPct').className = 'text-sm font-semibold ' + data.textColor;
            document.getElementById('modalBar').className = 'h-2 rounded-full transition-all duration-300 ' + data.barColor;
            document.getElementById('modalBar').style.width = data.pct + '%';
            document.getElementById('modalScore').textContent = 'Skor kemiripan: ' + data.score.toFixed(4);
            document.getElementById('modalDownload').href = data.url;

            const modal = document.getElementById('imageModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeImageModal();
        });
    </script>
@endsection
