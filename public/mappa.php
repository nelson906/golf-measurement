<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Golf Mapper Pro - Castelgandolfo</title>
    <meta name="map-secret" content="<?php echo htmlspecialchars(getenv('GOLF_MEASUREMENT_PUBLIC_SECRET') ?: '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; display: flex; flex-direction: column; height: 100vh; background: #1a1a1a; }
        .controls {
            background: #2c3e50; color: white; padding: 15px;
            display: flex; gap: 15px; align-items: center; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        #map { flex-grow: 1; width: 100%; cursor: crosshair; }
        select, input { padding: 8px; border-radius: 4px; border: 1px solid #34495e; background: #fff; }
        .btn-undo {
            background: #e74c3c; color: white; border: none; padding: 8px 15px;
            cursor: pointer; border-radius: 4px; font-weight: bold; transition: 0.3s;
        }
        .btn-undo:hover { background: #c0392b; transform: scale(1.05); }
        .info-panel {
            position: absolute; bottom: 30px; left: 20px;
            background: rgba(255,255,255,0.95); padding: 15px;
            border-radius: 10px; z-index: 1000; max-height: 250px; overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); width: 280px; border: 1px solid #ddd;
        }
        .log-entry { border-bottom: 1px solid #eee; padding: 5px 0; font-size: 0.85em; }
    </style>
</head>
<body>

<div class="controls">
    <div><strong>BUCA:</strong> <input type="number" id="hole_num" value="1" min="1" max="18" style="width: 50px;"></div>
    <div>
        <strong>TIPO:</strong>
        <select id="point_type">
            <option value="green">🎯 Centro Green</option>
            <option value="tee">⛳ Tee Box</option>
            <option value="fairway">⛳ Centro Fairway (Layup)</option>
        </select>
    </div>
    <button class="btn-undo" onclick="undoLast()">↩ Annulla Ultimo Click</button>
    <div style="margin-left: auto; font-size: 0.8em; color: #bdc3c7;">Usa il selettore in alto a destra per il Satellite</div>
</div>

<div id="map"></div>

<div class="info-panel" id="log">
    <strong>Punti Salvati:</strong>
    <div id="log_content" style="margin-top: 8px;"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var urlParams = new URLSearchParams(window.location.search);
    var courseId = parseInt(urlParams.get('course_id') || '', 10);
    if (!Number.isFinite(courseId) || courseId < 1) {
        courseId = null;
    }

    var centerLat = parseFloat(urlParams.get('lat') || '41.73943');
    var centerLng = parseFloat(urlParams.get('lng') || '12.62472');
    if (!Number.isFinite(centerLat) || centerLat < -90 || centerLat > 90) centerLat = 41.73943;
    if (!Number.isFinite(centerLng) || centerLng < -180 || centerLng > 180) centerLng = 12.62472;

    var mapSecret = document.querySelector('meta[name="map-secret"]')?.getAttribute('content') || '';

    var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Esri World Imagery'
    });

    var grafica = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OpenStreetMap'
    });

    var map = L.map('map', {
        center: [centerLat, centerLng],
        zoom: 18,
        layers: [grafica]
    });

    var baseMaps = { "Mappa Stradale": grafica, "Satellite (Precisione)": satellite };
    L.control.layers(baseMaps).addTo(map);

    var markersStack = [];

    map.on('click', function(e) {
        var lat = e.latlng.lat.toFixed(7);
        var lng = e.latlng.lng.toFixed(7);
        var hole = document.getElementById('hole_num').value;
        var type = document.getElementById('point_type').value;

        // Colori diversi per tipo di punto
        var colors = { 'green': '#2ecc71', 'tee': '#3498db', 'fairway': '#f1c40f' };

        var marker = L.circleMarker([lat, lng], {
            radius: 9,
            fillColor: colors[type],
            color: "#fff",
            weight: 2,
            opacity: 1,
            fillOpacity: 0.9
        }).addTo(map).bindPopup(`<b>Buca ${hole}</b><br>${type.toUpperCase()}<br>Lat: ${lat}<br>Lng: ${lng}`).openPopup();

        markersStack.push(marker);

        // Aggiorna Log visivo
        var logEntry = document.createElement('div');
        logEntry.className = 'log-entry';
        logEntry.innerHTML = `<strong>Buca ${hole}</strong> - ${type} <br><small>${lat}, ${lng}</small>`;
        document.getElementById('log_content').prepend(logEntry);

        saveToDb(hole, type, lat, lng);
    });

    function saveToDb(hole, type, lat, lng) {
        if (!courseId) {
            console.warn('course_id missing. Open mappa.php?course_id=123');
            return;
        }
        if (!mapSecret) {
            console.warn('Missing GOLF_MEASUREMENT_PUBLIC_SECRET env var. Requests will be rejected.');
            return;
        }
        var formData = new FormData();
        formData.append('course_id', courseId);
        formData.append('hole', hole);
        formData.append('type', type);
        formData.append('lat', lat);
        formData.append('lng', lng);

        fetch('save_point.php', {
            method: 'POST',
            headers: {
                'X-MAP-SECRET': mapSecret
            },
            body: formData
        })
        .then(r => r.text()).then(t => console.log('DB:', t));
    }

    function undoLast() {
        if (markersStack.length === 0) return;

        var lastMarker = markersStack.pop();
        map.removeLayer(lastMarker);

        // Rimuove la prima riga dal log visivo
        var log = document.getElementById('log_content');
        log.removeChild(log.firstChild);

        if (!courseId || !mapSecret) return;

        var formData = new FormData();
        formData.append('course_id', courseId);
        fetch('delete_last.php', {
            method: 'POST',
            headers: {
                'X-MAP-SECRET': mapSecret
            },
            body: formData
        });
    }
</script>
</body>
</html>
