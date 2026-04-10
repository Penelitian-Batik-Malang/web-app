@extends('layouts.layout')
@section('title', 'Monitor Model AI')

@section('content')
<div class="max-w-6xl mx-auto py-10 space-y-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white">Monitor Model AI</h1>
            <p class="text-gray-400 mt-1">Status kesehatan layanan model AI secara realtime.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-gray-700 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
            Kembali
        </a>
    </div>

    @if($errorMessage)
        <div class="rounded-2xl border border-red-900/60 bg-red-950/20 p-5">
            <p class="text-red-300 font-semibold">Gagal memuat health model</p>
            <p class="text-red-200/90 text-sm mt-1">{{ $errorMessage }}</p>
        </div>
    @endif

    @if($health)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-gray-700 bg-gray-900 p-5">
                <p class="text-gray-400 text-sm">Service Status</p>
                <p class="text-xl font-bold mt-1 {{ $health['success'] ? 'text-green-400' : 'text-red-400' }}">
                    {{ $health['success'] ? 'Healthy' : 'Unhealthy' }}
                </p>
            </div>
            <div class="rounded-2xl border border-gray-700 bg-gray-900 p-5">
                <p class="text-gray-400 text-sm">Memory Usage</p>
                <p class="text-xl font-bold text-white mt-1">
                    {{ is_numeric($health['memory_usage_mb']) ? number_format((float) $health['memory_usage_mb'], 2) . ' MB' : '-' }}
                </p>
            </div>
            <div class="rounded-2xl border border-gray-700 bg-gray-900 p-5 md:col-span-2">
                <p class="text-gray-400 text-sm">Message</p>
                <p class="text-lg font-semibold text-white mt-1">{{ $health['message'] ?? '-' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-700 bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-700">
                <h2 class="text-white font-semibold">Raw Health Data</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-800/60 text-gray-300">
                    <tr>
                        <th class="text-left px-5 py-3 font-semibold">Field</th>
                        <th class="text-left px-5 py-3 font-semibold">Value</th>
                    </tr>
                </thead>
                <tbody class="text-gray-200">
                    <tr class="border-t border-gray-800">
                        <td class="px-5 py-3 font-mono">memory_usage_mb</td>
                        <td class="px-5 py-3">{{ $health['memory_usage_mb'] ?? '-' }}</td>
                    </tr>
                    <tr class="border-t border-gray-800">
                        <td class="px-5 py-3 font-mono">message</td>
                        <td class="px-5 py-3">{{ $health['message'] ?? '-' }}</td>
                    </tr>
                    <tr class="border-t border-gray-800">
                        <td class="px-5 py-3 font-mono">success</td>
                        <td class="px-5 py-3">{{ $health['success'] ? 'true' : 'false' }}</td>
                    </tr>
                    <tr class="border-t border-gray-800">
                        <td class="px-5 py-3 font-mono">timestamp</td>
                        <td class="px-5 py-3">{{ $health['timestamp'] ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const refreshMs = 10000; // 10 detik
        const url = new URL(window.location.href);
        // Penanda agar user tahu halaman ini berjalan dalam mode auto refresh.
        if (!url.searchParams.has('auto')) {
            url.searchParams.set('auto', '1');
            window.history.replaceState({}, '', url.toString());
        }

        setInterval(() => {
            window.location.reload();
        }, refreshMs);
    })();
</script>
@endpush
