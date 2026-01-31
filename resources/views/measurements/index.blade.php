<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misurazioni - {{ $course->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
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
        .subtitle {
            color: #666;
            margin-bottom: 20px;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
        }
        .stat-card .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Actions */
        .actions {
            margin-bottom: 20px;
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
            margin-right: 10px;
            margin-bottom: 10px;
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
        .btn-warning {
            background: #FF9800;
        }
        .btn-warning:hover {
            background: #F57C00;
        }

        /* Holes Grid */
        .holes-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .holes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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
            position: relative;
        }
        .hole-card:hover {
            border-color: #4CAF50;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .hole-card.complete {
            border-color: #4CAF50;
            background: #e8f5e9;
        }
        .hole-card.partial {
            border-color: #FF9800;
            background: #fff3e0;
        }
        .hole-card.not-mapped {
            border-color: #f44336;
            background: #ffebee;
        }
        .hole-number {
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .hole-card.not-mapped .hole-number {
            color: #999;
        }
        .hole-info {
            font-size: 13px;
            color: #666;
            margin: 5px 0;
        }
        .mapping-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .mapping-status.complete {
            background: #c8e6c9;
            color: #2e7d32;
        }
        .mapping-status.partial {
            background: #ffe0b2;
            color: #e65100;
        }
        .mapping-status.empty {
            background: #ffcdd2;
            color: #c62828;
        }
        .measure-status {
            margin-top: 8px;
            font-size: 12px;
        }
        .measure-status.done {
            color: #2e7d32;
        }
        .measure-status.pending {
            color: #999;
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 2px solid;
        }
        .legend-color.complete {
            background: #e8f5e9;
            border-color: #4CAF50;
        }
        .legend-color.partial {
            background: #fff3e0;
            border-color: #FF9800;
        }
        .legend-color.not-mapped {
            background: #ffebee;
            border-color: #f44336;
        }

        /* Warning box */
        .warning-box {
            background: #fff3e0;
            border: 2px solid #FF9800;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .warning-box h3 {
            color: #e65100;
            margin-bottom: 10px;
        }
        .warning-box p {
            color: #666;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ route('courses.show', $course) }}" class="back-link">← Torna al campo</a>

            <h1>📐 Misurazioni: {{ $course->name }}</h1>
            <p class="subtitle">Seleziona una buca per iniziare a misurare</p>

            @php
                $mappedCount = 0;
                $partialCount = 0;
                $measuredCount = 0;
                foreach($course->holes as $hole) {
                    $hasTee = !empty($hole->tee_points['yellow']) || !empty($hole->tee_points['red']);
                    $hasGreen = !empty($hole->green_point);
                    if ($hasTee && $hasGreen) $mappedCount++;
                    elseif ($hasTee || $hasGreen) $partialCount++;
                    if ($hole->drives->count() > 0) $measuredCount++;
                }
            @endphp

            <div class="stats">
                <div class="stat-card">
                    <div class="value">{{ $course->holes->count() }}</div>
                    <div class="label">Buche Totali</div>
                </div>
                <div class="stat-card">
                    <div class="value" style="color: #4CAF50;">{{ $mappedCount }}</div>
                    <div class="label">Mappate Completamente</div>
                </div>
                <div class="stat-card">
                    <div class="value" style="color: #FF9800;">{{ $partialCount }}</div>
                    <div class="label">Parzialmente Mappate</div>
                </div>
                <div class="stat-card">
                    <div class="value" style="color: #2196F3;">{{ $measuredCount }}</div>
                    <div class="label">Misurate</div>
                </div>
            </div>

            <div class="actions">
                <a href="{{ route('courses.map-holes', $course) }}" class="btn btn-warning">
                    📍 Mappa Buche
                </a>
                <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">
                    🏌️ Dettagli Campo
                </a>
            </div>
        </div>

        @if($mappedCount == 0)
        <div class="warning-box">
            <h3>⚠️ Nessuna buca mappata</h3>
            <p>
                Prima di misurare, devi mappare le coordinate dei tee e green di ogni buca.
                Usa il pulsante "Mappa Buche" per caricare i dati da OpenStreetMap o mappare manualmente.
            </p>
            <a href="{{ route('courses.map-holes', $course) }}" class="btn">
                📍 Inizia Mappatura
            </a>
        </div>
        @endif

        <div class="holes-section">
            <h2>⛳ Seleziona una Buca</h2>

            <div class="holes-grid">
                @foreach($course->holes->sortBy('hole_number') as $hole)
                    @php
                        $hasTee = !empty($hole->tee_points['yellow']) || !empty($hole->tee_points['red']);
                        $hasGreen = !empty($hole->green_point);
                        $isComplete = $hasTee && $hasGreen;
                        $isPartial = ($hasTee || $hasGreen) && !$isComplete;
                        $cardClass = $isComplete ? 'complete' : ($isPartial ? 'partial' : 'not-mapped');
                        $statusClass = $isComplete ? 'complete' : ($isPartial ? 'partial' : 'empty');
                        $statusText = $isComplete ? 'Mappata' : ($isPartial ? 'Parziale' : 'Non mappata');
                    @endphp
                    <a href="{{ route('holes.measure', [$course, $hole->hole_number]) }}" style="text-decoration: none;">
                        <div class="hole-card {{ $cardClass }}">
                            <div class="hole-number">{{ $hole->hole_number }}</div>
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
                            <div class="mapping-status {{ $statusClass }}">
                                @if($hasTee && $hasGreen)
                                    ✓ Tee + Green
                                @elseif($hasTee)
                                    ◐ Solo Tee
                                @elseif($hasGreen)
                                    ◐ Solo Green
                                @else
                                    ✗ Non mappata
                                @endif
                            </div>
                            <div class="measure-status {{ $hole->drives->count() > 0 ? 'done' : 'pending' }}">
                                @if($hole->drives->count() > 0)
                                    ✓ Misurata
                                @else
                                    ○ Da misurare
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color complete"></div>
                    <span>Tee + Green mappati</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color partial"></div>
                    <span>Parzialmente mappata</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color not-mapped"></div>
                    <span>Non mappata</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
