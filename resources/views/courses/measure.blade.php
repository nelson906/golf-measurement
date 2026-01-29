@extends('layouts.app')

@section('content')
<div id="golf-measurement-app">
    <div id="sidebar">
        <h1>{{ $course->name }}</h1>
        <h2>Buca {{ $hole->hole_number }} - Par {{ $hole->par }}</h2>

        <div id="overlay-controls">
            <!-- Controlli overlay -->
        </div>

        <div id="drive-controls">
            <!-- Controlli drive -->
        </div>

        <div id="measurements">
            <!-- Risultati -->
        </div>
    </div>

    <div id="map"></div>
</div>
@endsection

@push('scripts')
<script>
    const courseData = @json($course);
    const holeData = @json($hole);
    const overlayConfig = @json($overlayConfig);

    // Inizializza app
    const mapHandler = new MapHandler('map',
        [{{ $course->coordinates->latitude }}, {{ $course->coordinates->longitude }}],
        16
    );

    const overlayManager = new OverlayManager(
        mapHandler.map,
        '{{ asset($course->map_image_path) }}',
        overlayConfig.bounds
    );

    if (overlayConfig) {
        overlayManager.loadConfig(overlayConfig);
    }

    const driveTracker = new DriveTracker(mapHandler.map);
</script>
@endpush
