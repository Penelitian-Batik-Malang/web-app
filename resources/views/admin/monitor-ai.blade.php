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

    @if(count($errorMessages) > 0)
        <div class="space-y-3">
            @foreach($errorMessages as $msg)
                <div class="rounded-2xl border border-red-900/60 bg-red-950/20 p-4">
                    <p class="text-red-300 text-sm font-medium">{{ $msg }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @foreach($services as $key => $health)
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-8 rounded-full {{ $health && $health['success'] ? 'bg-green-500' : 'bg-red-500' }}"></div>
                    <h2 class="text-xl font-bold text-white">{{ $health['name'] ?? ucfirst($key) . ' Service' }}</h2>
                </div>

                @if($health)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-gray-700 bg-gray-900 p-5">
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-semibold">Status</p>
                            <p class="text-xl font-bold mt-1 {{ $health['success'] ? 'text-green-400' : 'text-red-400' }}">
                                {{ $health['success'] ? 'Healthy' : 'Unhealthy' }}
                            </p>
                        </div>
                        @if(isset($health['memory_usage_mb']) && $health['memory_usage_mb'] !== null)
                        <div class="rounded-2xl border border-gray-700 bg-gray-900 p-5">
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-semibold">Memory</p>
                            <p class="text-xl font-bold text-white mt-1">
                                {{ number_format((float) $health['memory_usage_mb'], 2) . ' MB' }}
                            </p>
                        </div>
                        @endif
                        <div class="rounded-2xl border border-gray-700 bg-gray-900 p-5 {{ isset($health['memory_usage_mb']) && $health['memory_usage_mb'] !== null ? '' : 'sm:col-span-2' }}">
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-semibold">Message / Response</p>
                            <p class="text-lg font-semibold text-white mt-1">{{ $health['message'] ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-700 bg-gray-900 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-700 bg-gray-800/50">
                            <h3 class="text-gray-300 text-xs uppercase tracking-wider font-bold">Raw Health Data</h3>
                        </div>
                        <table class="w-full text-xs">
                            <tbody class="text-gray-300">
                                <tr class="border-t border-gray-800">
                                    <td class="px-5 py-2 font-mono text-gray-500 w-1/3">status</td>
                                    <td class="px-5 py-2 font-semibold {{ ($health['message'] ?? '') === 'ok' ? 'text-green-400' : '' }}">
                                        {{ $health['message'] ?? '-' }}
                                    </td>
                                </tr>
                                @if(isset($health['memory_usage_mb']) && $health['memory_usage_mb'] !== null)
                                <tr class="border-t border-gray-800">
                                    <td class="px-5 py-2 font-mono text-gray-500">memory_mb</td>
                                    <td class="px-5 py-2">{{ $health['memory_usage_mb'] }}</td>
                                </tr>
                                @endif
                                <tr class="border-t border-gray-800">
                                    <td class="px-5 py-2 font-mono text-gray-500">success</td>
                                    <td class="px-5 py-2">
                                        <span class="{{ $health['success'] ? 'text-green-500' : 'text-red-500' }}">
                                            {{ $health['success'] ? 'true' : 'false' }}
                                        </span>
                                    </td>
                                </tr>
                                @if(isset($health['timestamp']) && $health['timestamp'] !== null)
                                <tr class="border-t border-gray-800">
                                    <td class="px-5 py-2 font-mono text-gray-500">timestamp</td>
                                    <td class="px-5 py-2">{{ $health['timestamp'] }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-2xl border border-gray-800 bg-gray-900/50 p-10 text-center">
                        <p class="text-gray-500 italic">Data tidak tersedia</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
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
