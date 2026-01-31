<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $course->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #2196F3;
            text-decoration: none;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .course-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
        }
        .info-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .info-card p {
            font-size: 18px;
            color: #333;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #2196F3;
        }
        .btn-secondary:hover {
            background: #0b7dda;
        }
        .map-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .holes-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .holes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .hole-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            cursor: pointer;
        }
        .hole-card:hover {
            border-color: #4CAF50;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .hole-number {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .hole-info {
            font-size: 14px;
            color: #666;
        }
        .hole-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        .status-measured {
            background: #d4edda;
            color: #155724;
        }
        .status-not-measured {
            background: #fff3cd;
            color: #856404;
        }
        .status-mapped {
            background: #c8e6c9;
            color: #2e7d32;
        }
        .status-partial {
            background: #ffe0b2;
            color: #e65100;
        }
        .status-not-mapped {
            background: #ffcdd2;
            color: #c62828;
        }
        .mapping-indicator {
            font-size: 11px;
            margin-top: 5px;
            padding: 3px 8px;
            border-radius: 3px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ route('courses.index') }}" class="back-link">← Torna ai campi</a>

            <h1>🏌️ {{ $course->name }}</h1>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="course-info">
                <div class="info-card">
                    <h3>Località</h3>
                    <p>{{ $course->location }}</p>
                </div>
                <div class="info-card">
                    <h3>Coordinate</h3>
                    <p>{{ number_format($course->latitude, 6) }}, {{ number_format($course->longitude, 6) }}</p>
                </div>
                <div class="info-card">
                    <h3>Buche</h3>
                    <p>{{ $course->holes->count() }}</p>
                </div>
            </div>
        </div>


        <!-- Sezione Mappatura Buche -->
        <div class="map-section" style="background: #e8f5e9; border: 2px solid #4CAF50;">
            <h2>📍 Mappatura Coordinate Buche</h2>
            <p style="color: #666; margin-bottom: 15px;">
                Prima di misurare, mappa le coordinate dei green e tee di ogni buca.
                Puoi caricare automaticamente da OpenStreetMap o mappare manualmente.
            </p>
            <a href="{{ route('courses.map-holes', $course) }}" class="btn" style="display: inline-block; width: auto; padding: 12px 24px;">
                📍 Mappa le Buche
            </a>
        </div>

        <!-- Sezione Buche -->
        <div class="holes-section">
            <h2>⛳ Buche del Campo</h2>

            <div class="holes-grid">
                @foreach($course->holes->sortBy('hole_number') as $hole)
                    @php
                        $hasTee = !empty($hole->tee_points['yellow']) || !empty($hole->tee_points['red']);
                        $hasGreen = !empty($hole->green_point);
                        $isComplete = $hasTee && $hasGreen;
                        $isPartial = ($hasTee || $hasGreen) && !$isComplete;
                    @endphp
                    <div class="hole-card" onclick="measureHole({{ $course->id }}, {{ $hole->hole_number }})">
                        <div class="hole-number">Buca {{ $hole->hole_number }}</div>
                        <div class="hole-info">
                            @if($hole->par)
                                Par {{ $hole->par }}
                            @else
                                Par: -
                            @endif
                        </div>
                        <div class="hole-info">
                            @if($hole->length_yards)
                                {{ $hole->length_yards }} yards
                            @else
                                -
                            @endif
                        </div>
                        <div class="mapping-indicator {{ $isComplete ? 'status-mapped' : ($isPartial ? 'status-partial' : 'status-not-mapped') }}">
                            @if($isComplete)
                                📍 Mappata
                            @elseif($isPartial)
                                ◐ Parziale
                            @else
                                ✗ Non mappata
                            @endif
                        </div>
                        <div class="hole-status {{ $hole->drives->count() > 0 ? 'status-measured' : 'status-not-measured' }}">
                            {{ $hole->drives->count() > 0 ? '✓ Misurata' : '○ Da misurare' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        // Vai a misurazione buca
        function measureHole(courseId, holeNumber) {
            window.location.href = `{{ url('courses/'.$course->id.'/holes') }}/${holeNumber}/measure`;
        }
    </script>
</body>
</html>
