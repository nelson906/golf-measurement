<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Misura Buca {{ $hole->hole_number }} - {{ $course->name }}</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; overflow: hidden; }
        .container { display: flex; height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 320px;
            background: #1a1a2e;
            color: white;
            padding: 20px;
            overflow-y: auto;
        }
        .back-link { color: #4CAF50; text-decoration: none; font-size: 13px; }
        h1 { font-size: 20px; margin: 10px 0; color: #4CAF50; }
        h2 { font-size: 14px; margin: 15px 0 10px; color: #81C784; border-bottom: 1px solid #333; padding-bottom: 5px; }

        .hole-info {
            background: #16213e;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .hole-info .stat { display: flex; justify-content: space-between; margin: 5px 0; }
        .hole-info .value { color: #4CAF50; font-weight: bold; }

        .status-bar {
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            font-size: 12px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .status-bar.ready { background: #1b5e20; color: #a5d6a7; }
        .status-bar.warning { background: #e65100; color: #ffcc80; }
        .status-bar.active { background: #b71c1c; color: #ffcdd2; }

        .control-group {
            background: #16213e;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        button {
            width: 100%;
            padding: 11px;
            margin-bottom: 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        button:hover { transform: translateY(-1px); }
        .btn-primary { background: #4CAF50; color: white; }
        .btn-primary:hover { background: #45a049; }
        .btn-secondary { background: #2196F3; color: white; }
        .btn-warning { background: #FF9800; color: white; }
        .btn-danger { background: #f44336; color: white; }
        button:disabled { background: #555; cursor: not-allowed; opacity: 0.5; }
        button.active { background: #FF5722; }

        .btn-group { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

        input[type="number"] {
            width: 70px;
            padding: 8px;
            background: #0f3460;
            border: 1px solid #1a1a2e;
            color: white;
            border-radius: 4px;
            font-size: 13px;
        }

        .inline { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
        .inline label { margin: 0; font-size: 12px; color: #888; }

        /* Results */
        .results {
            background: #0f3460;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            max-height: 250px;
            overflow-y: auto;
        }
        .result-item {
            padding: 8px;
            border-bottom: 1px solid #1a1a2e;
        }
        .result-item:last-child { border-bottom: none; }
        .result-total {
            font-size: 16px;
            font-weight: bold;
            color: #4CAF50;
            padding: 10px;
            background: #1a1a2e;
            border-radius: 4px;
            margin-top: 10px;
            text-align: center;
        }

        .help-text {
            font-size: 11px;
            color: #666;
            margin-top: 10px;
            padding: 10px;
            background: #0f3460;
            border-radius: 4px;
            line-height: 1.5;
        }

        /* Map */
        .map-container { flex: 1; position: relative; }
        #map { height: 100%; width: 100%; }

        /* Mode indicator on map */
        .mode-indicator {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            z-index: 1000;
            display: none;
            border: 2px solid #FF5722;
        }
        .mode-indicator.active { display: block; }

        /* Distance tooltip */
        .distance-tooltip {
            position: absolute;
            background: rgba(0,0,0,0.95);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            pointer-events: none;
            z-index: 2000;
            display: none;
            border: 2px solid #4CAF50;
        }
        .distance-tooltip.active { display: block; }

        /* Hole navigation */
        .hole-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        .hole-nav button { flex: 1; padding: 8px; font-size: 12px; }

    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <a href="{{ route('courses.show', $course) }}" class="back-link">← Torna al campo</a>

            <h1>⛳ Buca {{ $hole->hole_number }}</h1>
            <p style="font-size: 12px; color: #888; margin-bottom: 15px;">{{ $course->name }}</p>

            <!-- Navigazione Buche -->
            <div class="hole-nav">
                @if($hole->hole_number > 1)
                <a href="{{ route('holes.measure', [$course, $hole->hole_number - 1]) }}" style="flex:1;">
                    <button class="btn-secondary" style="width:100%;">← Buca {{ $hole->hole_number - 1 }}</button>
                </a>
                @endif
                @if($hole->hole_number < 18)
                <a href="{{ route('holes.measure', [$course, $hole->hole_number + 1]) }}" style="flex:1;">
                    <button class="btn-secondary" style="width:100%;">Buca {{ $hole->hole_number + 1 }} →</button>
                </a>
                @endif
            </div>

            <!-- Info Buca -->
            <div class="hole-info">
                <div class="stat">
                    <span>Par:</span>
                    <span class="value" id="hole-par">{{ $hole->par ?? '-' }}</span>
                </div>
                <div class="stat">
                    <span>Lunghezza:</span>
                    <span class="value" id="hole-length">{{ $hole->length_yards ? $hole->length_yards . ' yds' : '-' }}</span>
                </div>
                <div class="stat">
                    <span>Tee mappato:</span>
                    <span class="value">{{ $hole->tee_points && ($hole->tee_points['yellow'] ?? null) ? '✓' : '✗' }}</span>
                </div>
                <div class="stat">
                    <span>Green mappato:</span>
                    <span class="value">{{ $hole->green_point ? '✓' : '✗' }}</span>
                </div>
            </div>

            <!-- Status -->
            <div class="status-bar ready" id="status">
                @if($hole->green_point && ($hole->tee_points['yellow'] ?? null))
                    ✅ Pronto per misurare
                @else
                    ⚠️ Mappa prima tee e green
                @endif
            </div>

            <!-- Misurazione Drive -->
            <div class="control-group">
                <h2>🎯 Misurazione Drive</h2>

                <div class="inline">
                    <label>Drive iniziale:</label>
                    <input type="number" id="default-drive" value="250" min="100" max="350" step="10">
                    <span style="font-size:11px;color:#666;">yards</span>
                </div>

                <button class="btn-primary" onclick="startDrive()" id="start-drive-btn">
                    📍 Inizia Drive dal Tee
                </button>

                <button class="btn-warning" onclick="addShot()" id="add-shot-btn" disabled>
                    ➕ Aggiungi Colpo
                </button>

                <button class="btn-primary" onclick="completeDrive()" id="complete-btn" disabled>
                    ✓ Completa e Salva
                </button>

                <button class="btn-danger" onclick="cancelDrive()" id="cancel-btn" style="display:none;">
                    ✗ Annulla
                </button>
            </div>

            <!-- Misure Fairway -->
            <div class="control-group">
                <h2>📏 Misura Larghezza Fairway</h2>

                <button class="btn-secondary" onclick="startWidthMeasure()" id="width-btn">
                    📏 Misura Larghezza Fairway
                </button>

                <div class="help-text">
                    Click su due punti opposti del fairway per misurare la larghezza
                </div>

                <div id="fairway-widths" style="margin-top: 10px;">
                    <!-- Larghezze misurate verranno aggiunte qui -->
                </div>
            </div>

            <!-- Distanza Laterale -->
            <div class="control-group">
                <h2>↔️ Distanza Laterale</h2>

                <button class="btn-secondary" onclick="startLateralMeasure()" id="lateral-btn">
                    ↔️ Misura Distanza Laterale
                </button>

                <div class="help-text">
                    Click su due punti per misurare la distanza laterale (es. da linea di gioco a ostacolo)
                </div>

                <div id="lateral-distances" style="margin-top: 10px;">
                    <!-- Distanze laterali misurate verranno aggiunte qui -->
                </div>
            </div>

            <!-- Bunker -->
            <div class="control-group">
                <h2>🏖️ Bunker</h2>

                <div class="inline">
                    <label>N° Bunker in Fairway:</label>
                    <input type="number" id="bunker-count" value="0" min="0" max="20" style="width: 60px;">
                </div>

                <div class="inline" style="margin-top: 8px;">
                    <label>Bunker Fraction:</label>
                    <select id="bunker-fraction" style="padding: 8px; background: #0f3460; border: 1px solid #1a1a2e; color: white; border-radius: 4px; font-size: 13px;">
                        <option value="">(nessuno)</option>
                        <option value="> 0 to ¼">&gt; 0 to ¼</option>
                        <option value="> ¼ to ½">&gt; ¼ to ½</option>
                        <option value="> ½ to ¾">&gt; ½ to ¾</option>
                        <option value="> ¾">&gt; ¾</option>
                    </select>
                </div>

                <div class="help-text">
                    Bunker Fraction: frazione area landing zone coperta da bunker (USGA)
                </div>
            </div>

            <!-- Lunghezza Colpi (ShotLength) -->
            <div class="control-group">
                <h2>🎯 Lunghezza Colpi (ShotLength)</h2>

                <div class="inline">
                    <label>Genere:</label>
                    <select id="shot-gender" style="padding: 8px; background: #0f3460; border: 1px solid #1a1a2e; color: white; border-radius: 4px; font-size: 13px;" onchange="updateShotLengthDisplay()">
                        <option value="Mens" selected>Mens</option>
                        <option value="Womens">Womens</option>
                    </select>
                </div>

                <div class="inline" style="margin-top: 8px;">
                    <label>Livello:</label>
                    <select id="shot-level" style="padding: 8px; background: #0f3460; border: 1px solid #1a1a2e; color: white; border-radius: 4px; font-size: 13px;" onchange="updateShotLengthDisplay()">
                        <option value="Scratch" selected>Scratch</option>
                        <option value="Bogey">Bogey</option>
                    </select>
                </div>

                <div id="shot-length-values" style="margin-top: 10px; padding: 10px; background: #0f3460; border-radius: 4px; font-size: 12px;">
                    <!-- Valori verranno popolati da JS -->
                </div>

                <div class="help-text">
                    Valori USGA da golf-rating-system (altitudine 0 ft)
                </div>
            </div>

            <!-- Risultati -->
            <div class="control-group">
                <h2>📊 Risultati</h2>
                <div class="results" id="results">
                    <div style="color:#666; text-align:center; padding:20px;">
                        Nessuna misurazione ancora
                    </div>
                </div>

                <!-- Riepilogo Larghezze Fairway -->
                <div id="width-results" style="margin-top: 10px; display: none;">
                    <strong style="color: #2196F3;">Larghezze Fairway:</strong>
                    <div id="width-list" style="font-size: 12px; margin-top: 5px;"></div>
                </div>

                <!-- Riepilogo Distanze Laterali -->
                <div id="lateral-results" style="margin-top: 10px; display: none;">
                    <strong style="color: #9C27B0;">Distanze Laterali:</strong>
                    <div id="lateral-list" style="font-size: 12px; margin-top: 5px;"></div>
                </div>

                <!-- Riepilogo Bunker -->
                <div id="bunker-results" style="margin-top: 10px;">
                    <strong style="color: #FF9800;">Bunker:</strong>
                    <span id="bunker-summary">0 bunker, fraction: 0</span>
                </div>
            </div>
        </div>

        <div class="map-container">
            <div id="map"></div>

            <div class="mode-indicator" id="mode-indicator"></div>
            <div class="distance-tooltip" id="distance-tooltip"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Config
        const COURSE_ID = {{ $course->id }};
        const HOLE_NUMBER = {{ $hole->hole_number }};
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const YD = 0.9144; // metri per yard

        // Dati buca
        const HOLE_DATA = {
            tee: @json($hole->tee_points['yellow'] ?? null),
            green: @json($hole->green_point),
            par: {{ $hole->par ?? 'null' }},
            length: {{ $hole->length_yards ?? 'null' }}
        };

        // Stato
        let currentDrive = null;
        let widthMode = false;
        let widthPoints = [];
        let lateralMode = false;
        let lateralPoints = [];
        let fairwayWidths = [];  // Array per memorizzare più larghezze
        let lateralDistances = [];  // Array per memorizzare più distanze laterali
        let markers = { tee: null, green: null, shots: [], lines: [], width: [], lateral: [] };

        // Init mappa
        const map = L.map('map', { zoomControl: true });

        // Centro sulla buca o sul campo
        let center = [{{ $course->latitude }}, {{ $course->longitude }}];
        let zoom = 17;

        if (HOLE_DATA.green) {
            center = [HOLE_DATA.green.lat, HOLE_DATA.green.lng];
            zoom = 18;
        } else if (HOLE_DATA.tee) {
            center = [HOLE_DATA.tee.lat, HOLE_DATA.tee.lng];
            zoom = 18;
        }

        map.setView(center, zoom);

        // Layer satellitare
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 20
        }).addTo(map);

        // Helper: crea icona marker
        function makeIcon(color, label, size = 28) {
            return L.divIcon({
                className: 'custom-marker',
                html: `<div style="
                    background: ${color};
                    color: white;
                    width: ${size}px;
                    height: ${size}px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: ${size/2.5}px;
                    font-weight: bold;
                    border: 2px solid white;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
                ">${label}</div>`,
                iconSize: [size, size],
                iconAnchor: [size/2, size/2],
            });
        }

        // Mostra tee e green mappati
        function displayMappedPoints() {
            if (HOLE_DATA.tee) {
                markers.tee = L.marker([HOLE_DATA.tee.lat, HOLE_DATA.tee.lng], {
                    icon: makeIcon('#FFC107', 'T', 32)
                }).addTo(map).bindPopup('Tee Buca ' + HOLE_NUMBER);
            }

            if (HOLE_DATA.green) {
                markers.green = L.marker([HOLE_DATA.green.lat, HOLE_DATA.green.lng], {
                    icon: makeIcon('#4CAF50', 'G', 32)
                }).addTo(map).bindPopup('Green Buca ' + HOLE_NUMBER);
            }

            // Linea tee-green
            if (HOLE_DATA.tee && HOLE_DATA.green) {
                const line = L.polyline([
                    [HOLE_DATA.tee.lat, HOLE_DATA.tee.lng],
                    [HOLE_DATA.green.lat, HOLE_DATA.green.lng]
                ], {
                    color: '#4CAF50',
                    weight: 2,
                    dashArray: '10, 10',
                    opacity: 0.6
                }).addTo(map);

                // Calcola e mostra distanza
                const dist = map.distance(
                    L.latLng(HOLE_DATA.tee.lat, HOLE_DATA.tee.lng),
                    L.latLng(HOLE_DATA.green.lat, HOLE_DATA.green.lng)
                );
                const yards = Math.round(dist / YD);

                line.bindPopup(`Distanza Tee-Green: <strong>${yards} yards</strong>`);

                // Aggiorna info se non c'è già
                if (!HOLE_DATA.length) {
                    document.getElementById('hole-length').textContent = yards + ' yds (calc)';
                }

                // Fit bounds
                map.fitBounds([
                    [HOLE_DATA.tee.lat, HOLE_DATA.tee.lng],
                    [HOLE_DATA.green.lat, HOLE_DATA.green.lng]
                ], { padding: [50, 50] });
            }
        }

        displayMappedPoints();

        // === DRIVE MEASUREMENT ===

        function startDrive() {
            if (!HOLE_DATA.tee) {
                alert('Mappa prima il Tee di questa buca');
                return;
            }

            // Clear previous
            clearDrive();

            currentDrive = {
                tee: { lat: HOLE_DATA.tee.lat, lng: HOLE_DATA.tee.lng },
                shots: []
            };

            // Marker tee
            const teeMarker = L.marker([currentDrive.tee.lat, currentDrive.tee.lng], {
                icon: makeIcon('#FF5722', '⛳', 30)
            }).addTo(map);
            markers.shots.push(teeMarker);

            // Primo colpo automatico
            const defaultYards = parseInt(document.getElementById('default-drive').value) || 250;
            const bearing = HOLE_DATA.green ?
                getBearing(currentDrive.tee, HOLE_DATA.green) : 0;

            const firstShot = destinationPoint(currentDrive.tee, defaultYards * YD, bearing);

            addShotMarker(firstShot, 1);
            currentDrive.shots.push(firstShot);
            drawLines();

            // UI
            setMode('🎯 DRIVE: Trascina i marker o aggiungi colpi');
            document.getElementById('start-drive-btn').disabled = true;
            document.getElementById('add-shot-btn').disabled = false;
            document.getElementById('complete-btn').disabled = false;
            document.getElementById('cancel-btn').style.display = 'block';

            updateResults();
        }

        function addShot() {
            if (!currentDrive) return;

            // Calcola distanza già percorsa
            let totalMeters = 0;
            let prev = currentDrive.tee;
            currentDrive.shots.forEach(shot => {
                totalMeters += map.distance(L.latLng(prev.lat, prev.lng), L.latLng(shot.lat, shot.lng));
                prev = shot;
            });

            // Ottieni shot length dal selettore corrente
            const gender = document.getElementById('shot-gender').value;
            const level = document.getElementById('shot-level').value;
            const shotData = SHOT_LENGTH_TABLES[gender][level];
            const numShots = currentDrive.shots.length + 1; // prossimo colpo

            // Calcola distanza target cumulativa per questo colpo
            let targetTotalYards;
            if (numShots === 2) {
                targetTotalYards = shotData.shot2;
            } else if (numShots === 3 && shotData.shot3) {
                targetTotalYards = shotData.shot3;
            } else {
                // Fallback: aggiungi 150 yards
                targetTotalYards = Math.round(totalMeters / YD) + 150;
            }

            // Distanza da aggiungere = target totale - già percorso
            const alreadyYards = Math.round(totalMeters / YD);
            const addYards = Math.max(50, targetTotalYards - alreadyYards); // minimo 50 yards

            const lastShot = currentDrive.shots[currentDrive.shots.length - 1] || currentDrive.tee;
            const bearing = HOLE_DATA.green ? getBearing(lastShot, HOLE_DATA.green) : 0;
            const newShot = destinationPoint(lastShot, addYards * YD, bearing);

            currentDrive.shots.push(newShot);
            addShotMarker(newShot, currentDrive.shots.length);
            drawLines();
            updateResults();
        }

        function addShotMarker(pos, num) {
            const marker = L.marker([pos.lat, pos.lng], {
                icon: makeIcon('#FF9800', num, 26),
                draggable: true
            }).addTo(map);

            marker.shotIndex = num - 1;

            marker.on('drag', function(e) {
                currentDrive.shots[this.shotIndex] = {
                    lat: e.latlng.lat,
                    lng: e.latlng.lng
                };
                drawLines();
                updateResults();
                showDistanceTooltip(e.latlng, this.shotIndex);
            });

            marker.on('dragend', function() {
                hideDistanceTooltip();
            });

            markers.shots.push(marker);
        }

        function drawLines() {
            // Clear old lines
            markers.lines.forEach(l => map.removeLayer(l));
            markers.lines = [];

            if (!currentDrive || currentDrive.shots.length === 0) return;

            // Tee to first shot
            let points = [[currentDrive.tee.lat, currentDrive.tee.lng]];
            currentDrive.shots.forEach(s => points.push([s.lat, s.lng]));

            const line = L.polyline(points, {
                color: '#FF5722',
                weight: 3,
                opacity: 0.8
            }).addTo(map);

            markers.lines.push(line);
        }

        function clearDrive() {
            markers.shots.forEach(m => map.removeLayer(m));
            markers.lines.forEach(l => map.removeLayer(l));
            markers.shots = [];
            markers.lines = [];
            currentDrive = null;
        }

        function cancelDrive() {
            clearDrive();
            resetUI();
            document.getElementById('results').innerHTML = '<div style="color:#666; text-align:center; padding:20px;">Misurazione annullata</div>';
        }

        async function completeDrive() {
            if (!currentDrive || currentDrive.shots.length === 0) return;

            // Calcola distanze
            let totalMeters = 0;
            const shots = [];
            let prev = currentDrive.tee;

            currentDrive.shots.forEach((shot, i) => {
                const dist = map.distance(L.latLng(prev.lat, prev.lng), L.latLng(shot.lat, shot.lng));
                totalMeters += dist;
                shots.push({
                    lat: shot.lat,
                    lng: shot.lng,
                    distance_meters: Math.round(dist * 100) / 100,
                    distance_yards: Math.round(dist / YD)
                });
                prev = shot;
            });

            const totalYards = Math.round(totalMeters / YD);

            // Salva
            try {
                const response = await fetch('/drives', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({
                        hole_id: {{ $hole->id }},
                        tee_lat: currentDrive.tee.lat,
                        tee_lng: currentDrive.tee.lng,
                        shots: shots,
                        total_distance_meters: totalMeters,
                        total_distance_yards: totalYards,
                        num_shots: shots.length
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('status').className = 'status-bar ready';
                    document.getElementById('status').textContent = '✅ Drive salvato: ' + totalYards + ' yards';

                    // Aggiorna lunghezza buca se necessario
                    document.getElementById('hole-length').textContent = totalYards + ' yds';

                    resetUI();
                } else {
                    alert('Errore: ' + (data.message || 'Salvataggio fallito'));
                }
            } catch (e) {
                console.error(e);
                alert('Errore di connessione');
            }
        }

        function updateResults() {
            if (!currentDrive || currentDrive.shots.length === 0) {
                document.getElementById('results').innerHTML = '<div style="color:#666; text-align:center; padding:20px;">Nessuna misurazione</div>';
                return;
            }

            let html = '';
            let totalMeters = 0;
            let prev = currentDrive.tee;

            currentDrive.shots.forEach((shot, i) => {
                const dist = map.distance(L.latLng(prev.lat, prev.lng), L.latLng(shot.lat, shot.lng));
                totalMeters += dist;
                const yards = Math.round(dist / YD);

                html += `<div class="result-item">
                    <strong>Colpo ${i + 1}:</strong> ${yards} yards
                </div>`;

                prev = shot;
            });

            const totalYards = Math.round(totalMeters / YD);
            html += `<div class="result-total">Totale: ${totalYards} yards</div>`;

            document.getElementById('results').innerHTML = html;
        }

        // === WIDTH MEASUREMENT ===

        function startWidthMeasure() {
            if (widthMode) {
                cancelWidth();
                return;
            }

            widthMode = true;
            widthPoints = [];

            document.getElementById('width-btn').classList.add('active');
            document.getElementById('width-btn').textContent = '❌ Annulla Misurazione';
            setMode('📏 LARGHEZZA: Click su due punti del fairway');
        }

        function cancelWidth() {
            widthMode = false;
            widthPoints = [];
            markers.width.forEach(m => map.removeLayer(m));
            markers.width = [];

            document.getElementById('width-btn').classList.remove('active');
            document.getElementById('width-btn').textContent = '📏 Misura Larghezza Fairway';
            setMode('');
        }

        function handleWidthClick(latlng) {
            widthPoints.push(latlng);

            const marker = L.circleMarker(latlng, {
                radius: 6,
                color: '#2196F3',
                fillColor: '#2196F3',
                fillOpacity: 1
            }).addTo(map);
            markers.width.push(marker);

            if (widthPoints.length === 2) {
                // Calcola e mostra
                const dist = map.distance(widthPoints[0], widthPoints[1]);
                const yards = Math.round(dist / YD);
                const meters = Math.round(dist);

                const line = L.polyline([widthPoints[0], widthPoints[1]], {
                    color: '#2196F3',
                    weight: 3
                }).addTo(map);
                markers.width.push(line);

                line.bindPopup(`Larghezza: <strong>${yards} yards</strong> (${meters}m)`).openPopup();

                // Salva la larghezza nell'array
                fairwayWidths.push({ yards, meters });
                updateWidthResults();

                setMode(`📏 Larghezza: ${yards} yards (salvata)`);

                // Reset per nuova misurazione dopo 2 secondi
                setTimeout(() => {
                    widthMode = false;
                    widthPoints = [];
                    document.getElementById('width-btn').classList.remove('active');
                    document.getElementById('width-btn').textContent = '📏 Misura Larghezza Fairway';
                    setMode('');
                }, 2000);
            }
        }

        function updateWidthResults() {
            const container = document.getElementById('width-results');
            const list = document.getElementById('width-list');

            if (fairwayWidths.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            list.innerHTML = fairwayWidths.map((w, i) =>
                `<div style="padding: 3px 0;">Larghezza ${i + 1}: <strong>${w.yards} yds</strong> (${w.meters}m)</div>`
            ).join('');
        }

        // === LATERAL DISTANCE MEASUREMENT ===

        function startLateralMeasure() {
            if (lateralMode) {
                cancelLateral();
                return;
            }

            lateralMode = true;
            lateralPoints = [];

            document.getElementById('lateral-btn').classList.add('active');
            document.getElementById('lateral-btn').textContent = '❌ Annulla Misurazione';
            setMode('↔️ LATERALE: Click su due punti per misurare');
        }

        function cancelLateral() {
            lateralMode = false;
            lateralPoints = [];
            markers.lateral.forEach(m => map.removeLayer(m));
            markers.lateral = [];

            document.getElementById('lateral-btn').classList.remove('active');
            document.getElementById('lateral-btn').textContent = '↔️ Misura Distanza Laterale';
            setMode('');
        }

        function handleLateralClick(latlng) {
            lateralPoints.push(latlng);

            const marker = L.circleMarker(latlng, {
                radius: 6,
                color: '#9C27B0',
                fillColor: '#9C27B0',
                fillOpacity: 1
            }).addTo(map);
            markers.lateral.push(marker);

            if (lateralPoints.length === 2) {
                // Calcola e mostra
                const dist = map.distance(lateralPoints[0], lateralPoints[1]);
                const yards = Math.round(dist / YD);
                const meters = Math.round(dist);

                const line = L.polyline([lateralPoints[0], lateralPoints[1]], {
                    color: '#9C27B0',
                    weight: 3,
                    dashArray: '5, 10'
                }).addTo(map);
                markers.lateral.push(line);

                line.bindPopup(`Distanza Laterale: <strong>${yards} yards</strong> (${meters}m)`).openPopup();

                // Salva la distanza nell'array
                lateralDistances.push({ yards, meters });
                updateLateralResults();

                setMode(`↔️ Laterale: ${yards} yards (salvata)`);

                // Reset per nuova misurazione dopo 2 secondi
                setTimeout(() => {
                    lateralMode = false;
                    lateralPoints = [];
                    document.getElementById('lateral-btn').classList.remove('active');
                    document.getElementById('lateral-btn').textContent = '↔️ Misura Distanza Laterale';
                    setMode('');
                }, 2000);
            }
        }

        function updateLateralResults() {
            const container = document.getElementById('lateral-results');
            const list = document.getElementById('lateral-list');

            if (lateralDistances.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            list.innerHTML = lateralDistances.map((d, i) =>
                `<div style="padding: 3px 0;">Distanza ${i + 1}: <strong>${d.yards} yds</strong> (${d.meters}m)</div>`
            ).join('');
        }

        // === MAP CLICK HANDLER ===

        map.on('click', function(e) {
            if (widthMode) {
                handleWidthClick(e.latlng);
            } else if (lateralMode) {
                handleLateralClick(e.latlng);
            }
        });

        // === BUNKER SUMMARY ===

        function updateBunkerSummary() {
            const count = document.getElementById('bunker-count').value || 0;
            const fraction = document.getElementById('bunker-fraction').value || 0;
            document.getElementById('bunker-summary').textContent = `${count} bunker, fraction: ${fraction}`;
        }

        // Event listeners per bunker
        document.getElementById('bunker-count').addEventListener('change', updateBunkerSummary);
        document.getElementById('bunker-fraction').addEventListener('change', updateBunkerSummary);

        // === HELPERS ===

        function setMode(text) {
            const indicator = document.getElementById('mode-indicator');
            if (text) {
                indicator.textContent = text;
                indicator.classList.add('active');
            } else {
                indicator.classList.remove('active');
            }
        }

        function resetUI() {
            setMode('');
            document.getElementById('start-drive-btn').disabled = false;
            document.getElementById('add-shot-btn').disabled = true;
            document.getElementById('complete-btn').disabled = true;
            document.getElementById('cancel-btn').style.display = 'none';
        }

        function showDistanceTooltip(latlng, shotIndex) {
            const tooltip = document.getElementById('distance-tooltip');
            const prev = shotIndex === 0 ? currentDrive.tee : currentDrive.shots[shotIndex - 1];
            const dist = map.distance(L.latLng(prev.lat, prev.lng), latlng);
            const yards = Math.round(dist / YD);

            tooltip.textContent = `Colpo ${shotIndex + 1}: ${yards} yds`;
            tooltip.classList.add('active');

            const point = map.latLngToContainerPoint(latlng);
            tooltip.style.left = (point.x + 15) + 'px';
            tooltip.style.top = (point.y - 15) + 'px';
        }

        function hideDistanceTooltip() {
            document.getElementById('distance-tooltip').classList.remove('active');
        }

        // === SHOT LENGTH TABLES (from golf-rating-system) ===
        const SHOT_LENGTH_TABLES = {
            Mens: {
                Scratch: { shot1: 250, shot2: 470 },
                Bogey: { shot1: 200, shot2: 370, shot3: 540 }
            },
            Womens: {
                Scratch: { shot1: 210, shot2: 400 },
                Bogey: { shot1: 150, shot2: 280, shot3: 410 }
            }
        };

        function updateShotLengthDisplay() {
            const gender = document.getElementById('shot-gender').value;
            const level = document.getElementById('shot-level').value;
            const data = SHOT_LENGTH_TABLES[gender][level];

            let html = `<div style="color: #4CAF50; margin-bottom: 5px;"><strong>${gender} ${level}</strong></div>`;
            html += `<div>1° Colpo: <strong>${data.shot1} yds</strong></div>`;
            html += `<div>2 Colpi totali: <strong>${data.shot2} yds</strong></div>`;
            if (data.shot3) {
                html += `<div>3 Colpi totali: <strong>${data.shot3} yds</strong></div>`;
            }

            document.getElementById('shot-length-values').innerHTML = html;
        }

        // Inizializza display shot length e bunker summary
        updateShotLengthDisplay();
        updateBunkerSummary();

        // Calcola bearing tra due punti
        function getBearing(from, to) {
            const lat1 = from.lat * Math.PI / 180;
            const lat2 = to.lat * Math.PI / 180;
            const dLng = (to.lng - from.lng) * Math.PI / 180;

            const y = Math.sin(dLng) * Math.cos(lat2);
            const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);

            return Math.atan2(y, x) * 180 / Math.PI;
        }

        // Calcola punto di destinazione
        function destinationPoint(from, distanceMeters, bearingDeg) {
            const R = 6371000; // Earth radius in meters
            const d = distanceMeters;
            const brng = bearingDeg * Math.PI / 180;
            const lat1 = from.lat * Math.PI / 180;
            const lng1 = from.lng * Math.PI / 180;

            const lat2 = Math.asin(
                Math.sin(lat1) * Math.cos(d / R) +
                Math.cos(lat1) * Math.sin(d / R) * Math.cos(brng)
            );

            const lng2 = lng1 + Math.atan2(
                Math.sin(brng) * Math.sin(d / R) * Math.cos(lat1),
                Math.cos(d / R) - Math.sin(lat1) * Math.sin(lat2)
            );

            return {
                lat: lat2 * 180 / Math.PI,
                lng: lng2 * 180 / Math.PI
            };
        }
    </script>
</body>
</html>
