<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nuovo Campo da Golf</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2196F3;
            text-decoration: none;
        }
        .content {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 20px;
        }
        .form-panel {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .map-panel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .search-box {
            position: relative;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-top: 5px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .search-results.active {
            display: block;
        }
        .search-result-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .search-result-item:hover {
            background: #f5f5f5;
        }
        .search-result-item:last-child {
            border-bottom: none;
        }
        .result-name {
            font-weight: bold;
            color: #333;
        }
        .result-address {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        #map {
            height: 500px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        #map-preview {
            max-width: 100%;
            border-radius: 6px;
            margin-top: 10px;
            display: none;
        }
        .coords-display {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
        .loading {
            display: none;
            color: #2196F3;
            font-size: 12px;
            margin-top: 5px;
        }
        .loading.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('courses.index') }}" class="back-link">← Torna ai campi</a>

        <h1>🏌️ Nuovo Campo da Golf</h1>

        <div class="content">
            <!-- Form Panel -->
            <div class="form-panel">
                <form id="course-form">
                    @csrf

                    <div class="form-group">
                        <label for="search">🔍 Cerca Campo</label>
                        <div class="search-box">
                            <input type="text" id="search"
                                   placeholder="es: Country Club Castel Gandolfo">
                            <div class="loading" id="search-loading">Ricerca in corso...</div>
                            <div class="search-results" id="search-results"></div>
                        </div>
                        <div class="help-text">
                            Digita il nome del campo per cercarlo automaticamente
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name">Nome Campo *</label>
                        <input type="text" id="name" name="name" required
                               placeholder="es: Country Club Castel Gandolfo">
                    </div>

                    <div class="form-group">
                        <label for="location">Località *</label>
                        <input type="text" id="location" name="location" required
                               placeholder="es: Castel Gandolfo, Roma, Italy">
                    </div>

                    <div class="coords-display" id="coords-display">
                        📍 Coordinate: Non selezionate
                    </div>

                    <input type="hidden" id="latitude" name="latitude" required>
                    <input type="hidden" id="longitude" name="longitude" required>

                    <div class="form-group">
                        <label for="map_image">Mappa del Campo (opzionale)</label>
                        <input type="file" id="map_image" name="map_image" accept="image/*">
                        <div class="help-text">Puoi caricarla anche dopo</div>
                        <img id="map-preview" alt="Preview mappa">
                    </div>

                    <button type="submit" class="btn" id="submit-btn" disabled>Crea Campo</button>
                </form>
            </div>

            <!-- Map Panel -->
            <div class="map-panel">
                <h2>📍 Posizione del Campo</h2>
                <div class="help-text" style="margin-bottom: 10px;">
                    Cerca il campo o clicca sulla mappa per selezionare la posizione
                </div>
                <div id="map"></div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Inizializza mappa
        const map = L.map('map').setView([41.9028, 12.4964], 6);

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 20
        }).addTo(map);

        let marker = null;
        let searchTimeout = null;

        // Click sulla mappa
        map.on('click', function(e) {
            setCoordinates(e.latlng.lat, e.latlng.lng);
        });

        // Ricerca automatica
        document.getElementById('search').addEventListener('input', function(e) {
            const query = e.target.value.trim();

            clearTimeout(searchTimeout);

            if (query.length < 3) {
                document.getElementById('search-results').classList.remove('active');
                return;
            }

            document.getElementById('search-loading').classList.add('active');

            searchTimeout = setTimeout(() => {
                searchPlace(query);
            }, 500);
        });

        // Cerca luogo con Nominatim (OpenStreetMap)
        async function searchPlace(query) {
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/search?` +
                    `q=${encodeURIComponent(query + ' golf')}&` +
                    `format=json&` +
                    `limit=5&` +
                    `countrycodes=it`
                );

                const results = await response.json();

                document.getElementById('search-loading').classList.remove('active');

                if (results.length > 0) {
                    displaySearchResults(results);
                } else {
                    document.getElementById('search-results').innerHTML =
                        '<div class="search-result-item">Nessun risultato trovato</div>';
                    document.getElementById('search-results').classList.add('active');
                }
            } catch (error) {
                console.error('Errore ricerca:', error);
                document.getElementById('search-loading').classList.remove('active');
            }
        }

        // Mostra risultati ricerca
        function displaySearchResults(results) {
            const container = document.getElementById('search-results');
            container.innerHTML = '';

            results.forEach(result => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.innerHTML = `
                    <div class="result-name">${result.name || result.display_name.split(',')[0]}</div>
                    <div class="result-address">${result.display_name}</div>
                `;

                item.onclick = () => selectSearchResult(result);

                container.appendChild(item);
            });

            container.classList.add('active');
        }

        // Seleziona risultato
        function selectSearchResult(result) {
            const nameParts = result.display_name.split(',');
            const name = result.name || nameParts[0];
            const location = nameParts.slice(0, 3).join(',').trim();

            document.getElementById('name').value = name;
            document.getElementById('location').value = location;

            setCoordinates(parseFloat(result.lat), parseFloat(result.lon));

            document.getElementById('search-results').classList.remove('active');

            map.setView([result.lat, result.lon], 16);
        }

        // Imposta coordinate
        function setCoordinates(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(7);
            document.getElementById('longitude').value = lng.toFixed(7);

            document.getElementById('coords-display').textContent =
                `📍 Coordinate: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;

            if (marker) {
                map.removeLayer(marker);
            }

            marker = L.marker([lat, lng]).addTo(map);

            // Abilita submit
            document.getElementById('submit-btn').disabled = false;
        }

        // Preview immagine
        document.getElementById('map_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('map-preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Chiudi risultati cliccando fuori
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box')) {
                document.getElementById('search-results').classList.remove('active');
            }
        });

        // Submit form
        document.getElementById('course-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creazione in corso...';

            const formData = new FormData(this);

            try {
                const response = await fetch('{{ route("courses.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    // Upload mappa se presente
                    const mapFile = document.getElementById('map_image').files[0];
                    if (mapFile) {
                        submitBtn.textContent = 'Upload mappa...';

                        const uploadData = new FormData();
                        uploadData.append('map_image', mapFile);

                        await fetch(`/courses/${data.course_id}/upload-map`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: uploadData
                        });
                    }

                    window.location.href = `/courses/${data.course_id}`;
                } else {
                    alert('Errore: ' + (data.message || JSON.stringify(data.errors)));
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Crea Campo';
                }
            } catch (error) {
                console.error('Errore:', error);
                alert('Errore durante la creazione');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crea Campo';
            }
        });
    </script>
</body>
</html>
