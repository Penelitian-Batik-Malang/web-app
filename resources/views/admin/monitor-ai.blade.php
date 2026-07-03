@extends('layouts.layout')
@section('title', 'Monitor Model AI')

@section('content')
<div class="max-w-6xl mx-auto py-10 space-y-10">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white">Monitor Model AI</h1>
            <p class="text-gray-400 mt-1 text-sm">Status layanan, model yang dimuat, dan dokumentasi endpoint API.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-gray-500 text-xs" id="last-refresh">Diperbarui: sekarang</span>
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-gray-700 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors text-sm">
                Kembali
            </a>
        </div>
    </div>

    {{-- Error Messages --}}
    @if(count($errorMessages) > 0)
        <div class="space-y-3">
            @foreach($errorMessages as $msg)
                <div class="rounded-2xl border border-red-900/60 bg-red-950/20 p-4 flex items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-red-400"></i>
                    <p class="text-red-300 text-sm font-medium">{{ $msg }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Service Health Status ──────────────────────────────────────────── --}}
    <section>
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="bi bi-activity text-green-400"></i> Status Layanan
        </h2>
        <div class="grid grid-cols-1 gap-6">
            @foreach($services as $key => $health)
                @php
                    $isOk         = $health && $health['success'];
                    $message      = $health['message'] ?? '-';
                    $modelsStatus = $health['models'] ?? [];   // { motif: bool, tulis: bool, cbir: bool }
                    $loadedModels = array_keys(array_filter($modelsStatus));
                    $allModels    = ['motif', 'tulis', 'cbir'];
                    $uptime       = $health['uptime'] ?? null;
                @endphp

                <div class="rounded-2xl border {{ $isOk ? 'border-green-900/50 bg-green-950/10' : 'border-red-900/50 bg-red-950/10' }} overflow-hidden">
                    {{-- Service header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b {{ $isOk ? 'border-green-900/30 bg-green-950/20' : 'border-red-900/30 bg-red-950/20' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full {{ $isOk ? 'bg-green-400 animate-pulse' : 'bg-red-500' }}"></div>
                            <span class="font-bold text-white text-base">{{ $health['name'] ?? ucfirst($key) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-mono border {{ $isOk ? 'text-green-400 border-green-700 bg-green-950/40' : 'text-red-400 border-red-700 bg-red-950/40' }}">
                                {{ $isOk ? 'ONLINE' : 'OFFLINE' }}
                            </span>
                        </div>
                        <div class="text-right">
                            @if($uptime !== null)
                                <p class="text-gray-400 text-xs">Uptime: <span class="text-white font-mono">{{ gmdate('H:i:s', (int)$uptime) }}</span></p>
                            @endif
                            @if($health && isset($health['timestamp']))
                                <p class="text-gray-600 text-[10px] font-mono">{{ $health['timestamp'] }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Model Status Grid --}}
                    <div class="px-6 py-5 grid grid-cols-3 gap-4">
                        @foreach($allModels as $model)
                            @php $loaded = (bool)($modelsStatus[$model] ?? false); @endphp
                            <div class="rounded-xl border {{ $loaded ? 'border-green-800/60 bg-green-950/30' : 'border-gray-700 bg-gray-900' }} p-4 text-center">
                                <div class="text-2xl mb-1">
                                    @if($model === 'motif') 🎨
                                    @elseif($model === 'tulis') ✍️
                                    @else 🔍
                                    @endif
                                </div>
                                <p class="text-white font-bold text-sm capitalize">{{ $model }}</p>
                                <p class="text-xs mt-1 font-medium {{ $loaded ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $loaded ? '✓ Loaded' : '✗ Not Loaded' }}
                                </p>
                                <p class="text-gray-500 text-[10px] mt-1">
                                    @if($model === 'motif') CNN Parallel ELU
                                    @elseif($model === 'tulis') ConvNeXt Tiny
                                    @else ConvNeXt Small + KMeans
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @if(!$isOk)
                    <div class="px-6 pb-4">
                        <div class="rounded-xl bg-gray-900 border border-gray-700 p-4 text-xs text-gray-400">
                            <p class="font-semibold text-white mb-1">Cara menjalankan ML Server:</p>
                            <code class="block font-mono text-amber-400 bg-black/40 rounded p-2 mt-1">
                                cd model-ml && .venv\Scripts\python.exe main.py
                            </code>
                        </div>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── API Endpoint Documentation ──────────────────────────────────────── --}}
    <section>
        <h2 class="text-lg font-bold text-white mb-1 flex items-center gap-2">
            <i class="bi bi-code-square text-amber-400"></i> Dokumentasi Endpoint API
        </h2>
        <p class="text-gray-500 text-xs mb-5">
            Base URL: <code class="font-mono text-amber-400 bg-gray-900 px-2 py-0.5 rounded">{{ rtrim(config('services.ml.url', 'http://127.0.0.1:8001'), '/') }}</code>
            &nbsp;|&nbsp; Auth header: <code class="font-mono text-amber-400 bg-gray-900 px-2 py-0.5 rounded">X-API-Key: &lt;API_KEY&gt;</code>
        </p>

        <div class="space-y-4" id="endpoint-accordion">

            {{-- ── 1. Health Check ──────────────────────────────────────────────── --}}
            @php $endpoints = [
                [
                    'tag'    => 'Health',
                    'color'  => 'blue',
                    'icon'   => 'bi-heart-pulse',
                    'items'  => [
                        [
                            'method'    => 'GET',
                            'path'      => '/api/health',
                            'summary'   => 'Cek status layanan & model',
                            'auth'      => false,
                            'request'   => null,
                            'response'  => '{
  "status": 200,
  "message": "Service is healthy",
  "data": {
    "status": "healthy",
    "is_model_loaded": true,
    "models": { "motif": true, "tulis": true, "cbir": true },
    "model_exists": true,
    "uptime": 44.64
  }
}',
                        ],
                    ]
                ],
                [
                    'tag'    => 'Deteksi Motif',
                    'color'  => 'amber',
                    'icon'   => 'bi-palette',
                    'items'  => [
                        [
                            'method'    => 'GET',
                            'path'      => '/api/detection/motif/labels',
                            'summary'   => 'Daftar label motif yang didukung model',
                            'auth'      => true,
                            'request'   => null,
                            'response'  => '{
  "status": 200,
  "data": ["Trembesi", "Parang", "Kawung", "Balai Kota", ...]
}',
                        ],
                        [
                            'method'    => 'POST',
                            'path'      => '/api/detection/motif',
                            'summary'   => 'Deteksi motif batik dari gambar',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

file  →  [image file]   (JPEG / PNG / WebP, maks 20 MB)',
                            'response'  => '{
  "status": 200,
  "message": "Motif detection successful",
  "data": {
    "motif": "Trembesi",
    "confidence": 0.9967,
    "probability_distribution": {
      "Trembesi": 0.9967,
      "Balai Kota": 0.0031,
      "Batik Topeng Malang": 0.0001
    }
  }
}',
                        ],
                    ]
                ],
                [
                    'tag'    => 'Deteksi Jenis',
                    'color'  => 'purple',
                    'icon'   => 'bi-brush',
                    'items'  => [
                        [
                            'method'    => 'GET',
                            'path'      => '/api/detection/type/labels',
                            'summary'   => 'Daftar label jenis (tulis/cap)',
                            'auth'      => true,
                            'request'   => null,
                            'response'  => '{
  "status": 200,
  "data": ["batik_tulis", "batik_cap"]
}',
                        ],
                        [
                            'method'    => 'POST',
                            'path'      => '/api/detection/type',
                            'summary'   => 'Deteksi jenis batik (tulis / cap)',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

file  →  [image file]   (JPEG / PNG / WebP, maks 20 MB)',
                            'response'  => '{
  "status": 200,
  "message": "Type detection successful",
  "data": {
    "label": "batik_tulis",
    "confidence": 0.8812,
    "probability_distribution": {
      "batik_tulis": 0.8812,
      "batik_cap": 0.1188
    }
  }
}',
                        ],
                    ]
                ],
                [
                    'tag'    => 'Pencarian Batik (CBIR)',
                    'color'  => 'green',
                    'icon'   => 'bi-search',
                    'items'  => [
                        [
                            'method'    => 'POST',
                            'path'      => '/api/search/general',
                            'summary'   => 'Cari batik serupa berdasarkan gambar (ConvNeXt + KMeans)',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

file  →  [image file]   (JPEG / PNG / WebP, maks 20 MB)

── ATAU ──

Content-Type: application/json

{ "image": "data:image/jpeg;base64,..." }',
                            'response'  => '{
  "status": 200,
  "message": "Search successful",
  "data": {
    "success": true,
    "cluster_id": 0,
    "results": [
      {
        "path_s3": "random_crop/Arca Ganesa/crop4_IMG_8742.JPG",
        "label": "Arca Ganesa",
        "cluster": 0,
        "similarity": 0.7555
      },
      ...
    ],
    "message": "Found 10 similar images in cluster 0"
  }
}',
                        ],
                    ]
                ],
                [
                    'tag'    => 'Fashion Segmentation',
                    'color'  => 'rose',
                    'icon'   => 'bi-person-bounding-box',
                    'items'  => [
                        [
                            'method'    => 'POST',
                            'path'      => '/api/fashion/segment',
                            'summary'   => 'Segmentasi pakaian dari foto model fashion',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

image  →  [image file]   (JPEG / PNG / WebP, maks 20 MB)',
                            'response'  => '{
  "status": 200,
  "data": {
    "session_id": "uuid-...",
    "result_image": "data:image/jpeg;base64,...",
    "parts": [
      {
        "name": "Upper-clothes",
        "label": 4,
        "cbir": { "top_k": [ { "filename": "...", "similarity": 0.93 } ] }
      }
    ]
  }
}',
                        ],
                        [
                            'method'    => 'POST',
                            'path'      => '/api/fashion/blend-manual',
                            'summary'   => 'Terapkan motif batik ke segmen pakaian (dari URL / upload)',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

session_id      →  string   (UUID dari /fashion/segment)
part            →  string   (nama segmen, e.g. "Upper-clothes")
instance_index  →  integer  (default: 0)
batik_image     →  [image file] atau URL string',
                            'response'  => '{
  "status": 200,
  "data": {
    "image_b64": "data:image/jpeg;base64,..."
  }
}',
                        ],
                        [
                            'method'    => 'POST',
                            'path'      => '/api/fashion/blend-cbir',
                            'summary'   => 'Terapkan batik rekomendasi CBIR ke segmen pakaian',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

session_id      →  string   (UUID dari /fashion/segment)
part            →  string   (nama segmen)
instance_index  →  integer
batik_filename  →  string   (URL S3 dari cbir.top_k[n].filename)',
                            'response'  => '{
  "status": 200,
  "data": { "image_b64": "data:image/jpeg;base64,..." }
}',
                        ],
                        [
                            'method'    => 'POST',
                            'path'      => '/api/fashion/reset-session',
                            'summary'   => 'Reset sesi segmentasi (hapus cache)',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

session_id  →  string',
                            'response'  => '{
  "status": 200,
  "data": { "message": "Session reset successful" }
}',
                        ],
                        [
                            'method'    => 'GET',
                            'path'      => '/api/fashion/session/{session_id}',
                            'summary'   => 'Ambil data sesi segmentasi yang ada',
                            'auth'      => true,
                            'request'   => 'URL param: session_id (UUID)',
                            'response'  => '{
  "status": 200,
  "data": { "session_id": "...", "parts": [...], "result_image": "..." }
}',
                        ],
                    ]
                ],
                [
                    'tag'    => 'Pewarnaan Palet (FAISS)',
                    'color'  => 'cyan',
                    'icon'   => 'bi-droplet-half',
                    'items'  => [
                        [
                            'method'    => 'POST',
                            'path'      => '/api/color-palette-faiss',
                            'summary'   => 'Recolor batik berdasarkan palet warna dari gambar referensi',
                            'auth'      => true,
                            'request'   => 'Content-Type: multipart/form-data

image           →  [batik image file]
color_image     →  [referensi warna image file]
method          →  "kmeans" | "median_cut"    (opsional, default: kmeans)
n_colors        →  integer                     (opsional, default: 6)',
                            'response'  => '{
  "status": 200,
  "data": {
    "result_image_url": "/uploads/result_xxx.jpg",
    "processing_time_ms": 1240,
    "palettes": { "kmeans": [[255,180,0], ...] }
  }
}',
                        ],
                    ]
                ],
            ]; @endphp

            @foreach($endpoints as $group)
                @php
                    $colors = [
                        'blue'   => ['border' => 'border-blue-800/50',   'bg' => 'bg-blue-950/20',   'badge' => 'bg-blue-900/60 text-blue-300 border-blue-700',   'dot' => 'bg-blue-400'],
                        'amber'  => ['border' => 'border-amber-800/50',  'bg' => 'bg-amber-950/20',  'badge' => 'bg-amber-900/60 text-amber-300 border-amber-700',  'dot' => 'bg-amber-400'],
                        'purple' => ['border' => 'border-purple-800/50', 'bg' => 'bg-purple-950/20', 'badge' => 'bg-purple-900/60 text-purple-300 border-purple-700','dot' => 'bg-purple-400'],
                        'green'  => ['border' => 'border-green-800/50',  'bg' => 'bg-green-950/20',  'badge' => 'bg-green-900/60 text-green-300 border-green-700',  'dot' => 'bg-green-400'],
                        'rose'   => ['border' => 'border-rose-800/50',   'bg' => 'bg-rose-950/20',   'badge' => 'bg-rose-900/60 text-rose-300 border-rose-700',     'dot' => 'bg-rose-400'],
                        'cyan'   => ['border' => 'border-cyan-800/50',   'bg' => 'bg-cyan-950/20',   'badge' => 'bg-cyan-900/60 text-cyan-300 border-cyan-700',     'dot' => 'bg-cyan-400'],
                    ];
                    $c = $colors[$group['color']] ?? $colors['blue'];
                    $groupId = 'group-' . Str::slug($group['tag']);
                @endphp

                <div class="rounded-2xl border {{ $c['border'] }} overflow-hidden">
                    {{-- Group Header (collapsible) --}}
                    <button onclick="toggleGroup('{{ $groupId }}')"
                            class="w-full flex items-center justify-between px-6 py-4 {{ $c['bg'] }} hover:brightness-110 transition-all text-left">
                        <div class="flex items-center gap-3">
                            <i class="bi {{ $group['icon'] }} text-lg" style="color: inherit"></i>
                            <span class="font-bold text-white">{{ $group['tag'] }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full border font-mono {{ $c['badge'] }}">
                                {{ count($group['items']) }} endpoint{{ count($group['items']) > 1 ? 's' : '' }}
                            </span>
                        </div>
                        <i class="bi bi-chevron-down text-gray-400 transition-transform duration-200" id="{{ $groupId }}-chevron"></i>
                    </button>

                    {{-- Endpoints --}}
                    <div id="{{ $groupId }}" class="divide-y divide-gray-800">
                        @foreach($group['items'] as $ep)
                            @php
                                $methodColors = [
                                    'GET'  => 'bg-blue-900/60 text-blue-300 border-blue-700',
                                    'POST' => 'bg-green-900/60 text-green-300 border-green-700',
                                    'PUT'  => 'bg-amber-900/60 text-amber-300 border-amber-700',
                                    'DELETE' => 'bg-red-900/60 text-red-300 border-red-700',
                                ];
                                $mc = $methodColors[$ep['method']] ?? 'bg-gray-700 text-gray-300';
                            @endphp
                            <div class="px-6 py-5 space-y-4 bg-gray-900/30">
                                {{-- Method + Path + Summary --}}
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg border {{ $mc }}">
                                        {{ $ep['method'] }}
                                    </span>
                                    <code class="font-mono text-amber-300 text-sm">{{ $ep['path'] }}</code>
                                    @if($ep['auth'])
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="bi bi-lock-fill text-amber-600"></i> Butuh X-API-Key
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="bi bi-unlock-fill text-green-600"></i> Publik
                                        </span>
                                    @endif
                                </div>
                                <p class="text-gray-300 text-sm">{{ $ep['summary'] }}</p>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    {{-- Request --}}
                                    @if($ep['request'])
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Request Body / Params</p>
                                        <pre class="bg-gray-950 border border-gray-700 rounded-xl p-4 text-xs font-mono text-gray-300 overflow-x-auto whitespace-pre-wrap leading-relaxed">{{ $ep['request'] }}</pre>
                                    </div>
                                    @else
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Request Body / Params</p>
                                        <div class="bg-gray-950 border border-gray-700 rounded-xl p-4 text-xs font-mono text-gray-600 italic">Tidak ada body — query params saja.</div>
                                    </div>
                                    @endif

                                    {{-- Response --}}
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Contoh Response (200 OK)</p>
                                        <pre class="bg-gray-950 border border-gray-700 rounded-xl p-4 text-xs font-mono text-green-300 overflow-x-auto whitespace-pre-wrap leading-relaxed">{{ $ep['response'] }}</pre>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

        </div>{{-- /accordion --}}
    </section>

    {{-- Error Code Reference --}}
    <section>
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="bi bi-exclamation-octagon text-red-400"></i> Kode Error Standar
        </h2>
        <div class="rounded-2xl border border-gray-700 bg-gray-900 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-800 text-gray-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold w-20">Kode</th>
                        <th class="px-5 py-3 text-left font-semibold w-40">Status</th>
                        <th class="px-5 py-3 text-left font-semibold">Penyebab Umum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800 text-gray-300">
                    @foreach([
                        ['code'=>'200','label'=>'OK','color'=>'text-green-400','desc'=>'Request berhasil'],
                        ['code'=>'400','label'=>'Bad Request','color'=>'text-yellow-400','desc'=>'File tidak valid, format salah, atau field wajib tidak ada'],
                        ['code'=>'401','label'=>'Unauthorized','color'=>'text-orange-400','desc'=>'X-API-Key tidak disertakan atau salah'],
                        ['code'=>'422','label'=>'Validation Error','color'=>'text-yellow-400','desc'=>'Field gagal validasi (detail di errors[])'],
                        ['code'=>'429','label'=>'Too Many Requests','color'=>'text-orange-400','desc'=>'Rate limit terlampaui — tunggu 1 menit'],
                        ['code'=>'500','label'=>'Internal Server Error','color'=>'text-red-400','desc'=>'Kesalahan tak terduga di sisi server'],
                        ['code'=>'503','label'=>'Service Unavailable','color'=>'text-red-400','desc'=>'Model belum dimuat atau server baru restart'],
                        ['code'=>'504','label'=>'Gateway Timeout','color'=>'text-red-400','desc'=>'Inference melebihi 30 detik timeout'],
                    ] as $err)
                    <tr>
                        <td class="px-5 py-3 font-mono font-bold {{ $err['color'] }}">{{ $err['code'] }}</td>
                        <td class="px-5 py-3 font-semibold">{{ $err['label'] }}</td>
                        <td class="px-5 py-3 text-gray-400 text-xs">{{ $err['desc'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
(function () {
    // Auto refresh status
    const refreshMs = 30000;
    let countdown = refreshMs / 1000;
    const label = document.getElementById('last-refresh');

    setInterval(() => {
        countdown--;
        if (countdown <= 0) {
            window.location.reload();
        } else {
            if (label) label.textContent = 'Refresh dalam ' + countdown + 's';
        }
    }, 1000);

    // Accordion toggle
    window.toggleGroup = function(id) {
        const el      = document.getElementById(id);
        const chevron = document.getElementById(id + '-chevron');
        if (!el) return;
        const isHidden = el.classList.contains('hidden');
        el.classList.toggle('hidden', !isHidden);
        if (chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
    };

    // Collapse all groups by default except the first
    document.querySelectorAll('[id^="group-"]').forEach((el, idx) => {
        if (idx > 0) {
            el.classList.add('hidden');
        }
        const chevron = document.getElementById(el.id + '-chevron');
        if (chevron && idx > 0) chevron.style.transform = 'rotate(0deg)';
        if (chevron && idx === 0) chevron.style.transform = 'rotate(180deg)';
    });
})();
</script>
@endpush
