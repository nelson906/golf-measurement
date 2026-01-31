<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mappa Buche - {{ $course->name }}</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
        .container { display: flex; height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 350px;
            background: #1a1a2e;
            color: white;
            padding: 20px;
            overflow-y: auto;
        }
        .back-link {
            color: #4CAF50;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
            margin-bottom: 15px;
        }
        h1 { font-size: 20px; margin-bottom: 5px; color: #4CAF50; }
        h2 { font-size: 14px; margin: 20px 0 10px; color: #81C784; border-bottom: 1px solid #333; padding-bottom: 5px; }
        .subtitle { font-size: 12px; color: #888; margin-bottom: 20px; }

        /* Status Summary */
        .status-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .status-box {
            background: #16213e;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }
        .status-box .number { font-size: 24px; font-weight: bold; }
        .status-box .label { font-size: 10px; color: #888; margin-top: 4px; }
        .status-box.complete .number { color: #4CAF50; }
        .status-box.partial .number { color: #FF9800; }
        .status-box.empty .number { color: #f44336; }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: #4CAF50; color: white; }
        .btn-primary:hover { background: #45a049; }
        .btn-secondary { background: #2196F3; color: white; }
        .btn-secondary:hover { background: #1976D2; }
        .btn-warning { background: #FF9800; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn:disabled { background: #555; cursor: not-allowed; opacity: 0.6; }
        .btn.active { background: #FF5722; }

        /* Holes Grid */
        .holes-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        .hole-btn {
            padding: 10px;
            border: 2px solid #333;
            border-radius: 6px;
            background: #16213e;
            color: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        .hole-btn:hover { border-color: #4CAF50; }
        .hole-btn.selected { border-color: #FF5722; background: #FF5722; }
        .hole-btn.complete { border-color: #4CAF50; }
        .hole-btn.complete::after { content: ' ✓'; color: #4CAF50; }
        .hole-btn.partial { border-color: #FF9800; }
        .hole-btn.partial::after { content: ' ◐'; color: #FF9800; }

        /* Instructions */
        .instructions {
            background: #16213e;
            padding: 15px;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.6;
        }
        .instructions strong { color: #4CAF50; }
        .mode-indicator {
            background: #FF5722;
            color: white;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            display: none;
        }
        .mode-indicator.active { display: block; }

        /* Map */
        .map-container { flex: 1; position: relative; }
        #map { height: 100%; width: 100%; }

        /* Reference Map Panel */
        .ref-panel {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background: rgba(0,0,0,0.9);
            border: 2px solid #9C27B0;
            border-radius: 10px;
            overflow: hidden;
            z-index: 1000;
        }
        .ref-panel-header {
            padding: 8px 12px;
            background: #1a1a2e;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #E1BEE7;
        }
        .ref-panel-header button {
            background: #f44336;
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
        }
        .ref-panel img {
            width: 100%;
            display: block;
        }
        .ref-panel.hidden { display: none; }

        /* Loading */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <a href="{{ route('courses.show', $course) }}" class="back-link">← Torna al campo</a>

            <h1>📍 Mappa Buche</h1>
            <div class="subtitle">{{ $course->name }}</div>

            <div class="status-summary">
                <div class="status-box complete">
                    <div class="number" id="complete-count">-</div>
                    <div class="label">Complete</div>
                </div>
                <div class="status-box partial">
                    <div class="number" id="partial-count">-</div>
                    <div class="label">Parziali</div>
                </div>
                <div class="status-box empty">
                    <div class="number" id="empty-count">-</div>
                    <div class="label">Da mappare</div>
                </div>
            </div>

            <h2>🌍 Carica da OpenStreetMap</h2>
            <button class="btn btn-secondary" onclick="loadFromOSM()" id="osm-btn">
                📡 Carica dati OSM
            </button>

            <h2>🎯 Mappatura Manuale</h2>
            <div class="mode-indicator" id="mode-indicator">
                Modalità: Clicca sulla mappa per posizionare
            </div>

            <p style="font-size: 12px; color: #888; margin-bottom: 10px;">
                Seleziona una buca, poi scegli cosa mappare:
            </p>

            <div class="holes-grid" id="holes-grid">
                @for($i = 1; $i <= 18; $i++)
                    <div class="hole-btn" data-hole="{{ $i }}" onclick="selectHole({{ $i }})">
                        {{ $i }}
                    </div>
                @endfor
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px;">
                <button class="btn btn-primary" onclick="setMode('green')" id="green-btn" disabled>
                    🟢 Green
                </button>
                <button class="btn btn-warning" onclick="setMode('tee')" id="tee-btn" disabled>
                    🟡 Tee
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px;">
                <button class="btn btn-danger" onclick="clearSelectedGreen()" id="clear-green-btn" disabled>
                    🧹 Cancella Green
                </button>
                <button class="btn btn-danger" onclick="clearSelectedTee()" id="clear-tee-btn" disabled>
                    🧹 Cancella Tee
                </button>
            </div>

            <button class="btn btn-danger" onclick="clearMode()" id="clear-btn" style="display:none;">
                ❌ Annulla
            </button>

            <div class="instructions">
                <strong>Come funziona:</strong><br>
                1. Clicca "Carica dati OSM" per importare dati esistenti<br>
                2. Per le buche mancanti: seleziona il numero<br>
                3. Clicca "Green" o "Tee"<br>
                4. Clicca sulla mappa satellitare per posizionare<br>
                5. I dati vengono salvati automaticamente
            </div>

            <h2 style="margin-top: 20px;">🗺️ Mappetta Riferimento</h2>
            <button class="btn btn-secondary" onclick="toggleRefPanel()">
                👁️ Mostra/Nascondi Mappetta
            </button>
        </div>

        <div class="map-container">
            <div id="map"></div>

            @if($course->map_image_path)
            <div class="ref-panel" id="ref-panel">
                <div class="ref-panel-header">
                    <span>Mappetta di riferimento</span>
                    <button onclick="toggleRefPanel()">✕</button>
                </div>
                <img src="{{ $course->map_url }}" alt="Mappa riferimento">
            </div>
            @endif
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const COURSE_ID = {{ $course->id }};
        const CENTER = [{{ $course->latitude }}, {{ $course->longitude }}];
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        // State
        let selectedHole = null;
        let currentMode = null; // 'green' | 'tee'
        let holesStatus = {};
        let markers = {};

        // Init map
        const map = L.map('map').setView(CENTER, 17);
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 20
        }).addTo(map);

        // Marker icons
        function makeIcon(color, label) {
            return L.divIcon({
                className: 'custom-marker',
                html: `<div style="
                    background: ${color};
                    color: white;
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                    border: 2px solid white;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                ">${label}</div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });
        }

        // Load initial status
        loadMappingStatus();

        async function loadMappingStatus() {
            try {
                const response = await fetch(`/courses/${COURSE_ID}/holes-data/mapping-status`);
                const data = await response.json();

                if (data.success) {
                    updateStatusDisplay(data);
                    displayExistingMarkers(data.holes);
                }
            } catch (e) {
                console.error('Error loading status:', e);
            }
        }

        function updateStatusDisplay(data) {
            document.getElementById('complete-count').textContent = data.summary.complete;
            document.getElementById('partial-count').textContent = data.summary.partial;
            document.getElementById('empty-count').textContent = data.summary.empty;

            // Update hole buttons
            data.holes.forEach(hole => {
                const btn = document.querySelector(`.hole-btn[data-hole="${hole.hole_number}"]`);
                if (btn) {
                    btn.classList.remove('complete', 'partial');
                    if (hole.status === 'complete') btn.classList.add('complete');
                    else if (hole.status === 'partial') btn.classList.add('partial');
                }
                holesStatus[hole.hole_number] = hole;
            });
        }

        function displayExistingMarkers(holes) {
            // Clear existing markers
            Object.values(markers).forEach(m => {
                if (m.green) map.removeLayer(m.green);
                if (m.tee) map.removeLayer(m.tee);
            });
            markers = {};

            holes.forEach(hole => {
                markers[hole.hole_number] = {};

                if (hole.green_point) {
                    markers[hole.hole_number].green = L.marker(
                        [hole.green_point.lat, hole.green_point.lng],
                        { icon: makeIcon('#4CAF50', hole.hole_number) }
                    ).addTo(map).bindPopup(`Green buca ${hole.hole_number}`);
                }

                if (hole.tee_points?.yellow) {
                    markers[hole.hole_number].tee = L.marker(
                        [hole.tee_points.yellow.lat, hole.tee_points.yellow.lng],
                        { icon: makeIcon('#FFC107', 'T' + hole.hole_number) }
                    ).addTo(map).bindPopup(`Tee buca ${hole.hole_number}`);
                }
            });
        }

        async function loadFromOSM() {
            const btn = document.getElementById('osm-btn');
            btn.innerHTML = '<span class="loading"></span> Caricamento...';
            btn.disabled = true;

            try {
                const response = await fetch(`/courses/${COURSE_ID}/holes-data/load-osm`);
                const data = await response.json();

                if (data.success) {
                    alert(`✅ ${data.message}\n\nBuche trovate: ${data.total_found}`);
                    loadMappingStatus();
                } else {
                    alert('❌ ' + (data.message || 'Errore caricamento OSM'));
                }
            } catch (e) {
                console.error('OSM error:', e);
                alert('❌ Errore connessione OSM');
            }

            btn.innerHTML = '📡 Carica dati OSM';
            btn.disabled = false;
        }

        function selectHole(num) {
            // Deselect previous
            document.querySelectorAll('.hole-btn').forEach(b => b.classList.remove('selected'));

            // Select new
            selectedHole = num;
            document.querySelector(`.hole-btn[data-hole="${num}"]`).classList.add('selected');

            // Enable mode buttons
            document.getElementById('green-btn').disabled = false;
            document.getElementById('tee-btn').disabled = false;
            document.getElementById('clear-green-btn').disabled = false;
            document.getElementById('clear-tee-btn').disabled = false;

            // Zoom to hole if marker exists
            if (markers[num]?.green) {
                map.setView(markers[num].green.getLatLng(), 18);
            } else if (markers[num]?.tee) {
                map.setView(markers[num].tee.getLatLng(), 18);
            }
        }

        function setMode(mode) {
            if (!selectedHole) {
                alert('Seleziona prima una buca');
                return;
            }

            currentMode = mode;

            // Update UI
            document.getElementById('green-btn').classList.toggle('active', mode === 'green');
            document.getElementById('tee-btn').classList.toggle('active', mode === 'tee');
            document.getElementById('clear-btn').style.display = 'block';

            const modeText = mode === 'green' ? '🟢 GREEN' : '🟡 TEE';
            document.getElementById('mode-indicator').textContent =
                `Buca ${selectedHole}: Clicca sulla mappa per posizionare ${modeText}`;
            document.getElementById('mode-indicator').classList.add('active');
        }

        function clearMode() {
            currentMode = null;
            document.getElementById('green-btn').classList.remove('active');
            document.getElementById('tee-btn').classList.remove('active');
            document.getElementById('clear-btn').style.display = 'none';
            document.getElementById('mode-indicator').classList.remove('active');
        }

        // Map click handler
        map.on('click', async function(e) {
            if (!currentMode || !selectedHole) return;

            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            try {
                let url, body;

                if (currentMode === 'green') {
                    url = `/courses/${COURSE_ID}/holes-data/${selectedHole}/green`;
                    body = { lat, lng };
                } else {
                    url = `/courses/${COURSE_ID}/holes-data/${selectedHole}/tee`;
                    body = { lat, lng, color: 'yellow' };
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify(body),
                });

                const data = await response.json();

                if (data.success) {
                    // Update marker
                    if (!markers[selectedHole]) markers[selectedHole] = {};

                    if (currentMode === 'green') {
                        if (markers[selectedHole].green) {
                            map.removeLayer(markers[selectedHole].green);
                        }
                        markers[selectedHole].green = L.marker(
                            [lat, lng],
                            { icon: makeIcon('#4CAF50', selectedHole) }
                        ).addTo(map).bindPopup(`Green buca ${selectedHole}`);
                    } else {
                        if (markers[selectedHole].tee) {
                            map.removeLayer(markers[selectedHole].tee);
                        }
                        markers[selectedHole].tee = L.marker(
                            [lat, lng],
                            { icon: makeIcon('#FFC107', 'T' + selectedHole) }
                        ).addTo(map).bindPopup(`Tee buca ${selectedHole}`);
                    }

                    // Refresh status
                    loadMappingStatus();

                    // Auto-advance to next hole or clear mode
                    if (currentMode === 'green') {
                        // Switch to tee mode for same hole
                        setMode('tee');
                    } else {
                        // Move to next hole
                        const nextHole = selectedHole < 18 ? selectedHole + 1 : 1;
                        selectHole(nextHole);
                        setMode('green');
                    }
                } else {
                    alert('❌ ' + (data.message || 'Errore salvataggio'));
                }
            } catch (e) {
                console.error('Save error:', e);
                alert('❌ Errore di connessione');
            }
        });

        function toggleRefPanel() {
            const panel = document.getElementById('ref-panel');
            if (panel) {
                panel.classList.toggle('hidden');
            }
        }

        async function clearSelectedGreen() {
            if (!selectedHole) {
                alert('Seleziona prima una buca');
                return;
            }
            if (!confirm(`Cancellare il Green della buca ${selectedHole}?`)) return;

            try {
                const response = await fetch(`/courses/${COURSE_ID}/holes-data/${selectedHole}/green`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                });
                const data = await response.json();
                if (data.success) {
                    if (markers[selectedHole]?.green) {
                        map.removeLayer(markers[selectedHole].green);
                        markers[selectedHole].green = null;
                    }
                    loadMappingStatus();
                } else {
                    alert('❌ ' + (data.message || 'Errore cancellazione'));
                }
            } catch (e) {
                console.error('Clear green error:', e);
                alert('❌ Errore di connessione');
            }
        }

        async function clearSelectedTee() {
            if (!selectedHole) {
                alert('Seleziona prima una buca');
                return;
            }
            if (!confirm(`Cancellare il Tee (giallo) della buca ${selectedHole}?`)) return;

            try {
                const response = await fetch(`/courses/${COURSE_ID}/holes-data/${selectedHole}/tee?color=yellow`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                });
                const data = await response.json();
                if (data.success) {
                    if (markers[selectedHole]?.tee) {
                        map.removeLayer(markers[selectedHole].tee);
                        markers[selectedHole].tee = null;
                    }
                    loadMappingStatus();
                } else {
                    alert('❌ ' + (data.message || 'Errore cancellazione'));
                }
            } catch (e) {
                console.error('Clear tee error:', e);
                alert('❌ Errore di connessione');
            }
        }
    </script>
</body>
</html>
