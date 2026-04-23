@extends('layouts.layout')
@section('title', $title)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" id="batik-app">

    <div class="text-center">
        <h1 class="text-4xl font-playfair font-bold text-white">{{ $title }}</h1>
        <p class="text-gray-400 mt-2 max-w-2xl mx-auto text-sm">
            {{ $description }}
        </p>
    </div>

    {{-- PHASE 1: Upload --}}
    @include('pages.features.shared.phase-upload')

    {{-- PHASE: Loading --}}
    @include('pages.features.shared.phase-loading')

    {{-- PHASE: CBIR Recommendation Result (only used in rekomendasi-batik mode) --}}
    @yield('phase_cbir')

    {{-- PHASE 3: Workspace --}}
    @include('pages.features.shared.phase-workspace')

    {{-- PHASE 4: Result --}}
    @include('pages.features.shared.phase-result')

    {{-- CUSTOM PANEL (Terapkan vs Rekomendasi) --}}
    @yield('custom_panel')

    {{-- WEBCAM MODAL --}}
    @include('pages.features.shared.webcam-modal')

</div>
@endsection


@push('scripts')
<script>
    const isRekomendasiMode = {{ $mode === 'rekomendasi' ? 'true' : 'false' }};
    const apiInferenceRoute = "{{ route('api.inference') }}";
    const apiResetRoute = "{{ route('api.reset') }}";
</script>
@include('pages.features.shared.scripts')
@yield('custom_scripts')
@endpush
