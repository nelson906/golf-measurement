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
        .map-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .map-preview {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 15px;
        }
        .upload-section {
            background: #f9f9f9;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
        }
        .upload-section h3 {
            margin-bottom: 15px;
            color: #666;
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
        input[type="file"] {
            display: none;
        }

        /* Ricerca Mappetta Online */
        .search-section {
            background: #f0f7ff;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .search-section h3 {
            color: #1976D2;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .search-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .search-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            transition: all 0.2s;
        }
        .search-link:hover {
            background: #e3f2fd;
            border-color: #2196F3;
        }
        .search-link img {
            width: 16px;
            height: 16px;
        }
        .url-import {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }
        .url-import input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .url-import input:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }
        .preview-container {
            margin-top: 15px;
            display: none;
        }
        .preview-container.active {
            display: block;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            border: 2px solid #4CAF50;
        }
        .preview-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .btn-success {
            background: #4CAF50;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-danger:hover {
            background: #d32f2f;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .help-note {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border-radius: 4px;
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
                <div class="info-card">
                    <h3>Stato Mappa</h3>
                    <p>
                        @if($course->map_image_path)
                            ✅ Caricata
                        @else
                            ❌ Non caricata
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Sezione Mappa -->
        <div class="map-section">
            <h2>🗺️ Mappa del Campo</h2>

            @if($course->map_image_path)
                <img src="{{ $course->map_url }}" alt="Mappa {{ $course->name }}" class="map-preview">

                <div style="margin-top: 20px;">
                    <label for="update-map" class="btn btn-secondary">📤 Aggiorna Mappa</label>
                    <input type="file" id="update-map" accept="image/*">
                </div>
            @else
                <div class="upload-section">
                    <h3>Carica la mappa del campo</h3>
                    <p style="color: #666; margin-bottom: 20px;">
                        Carica un'immagine JPG o PNG della mappa delle buche
                    </p>
                    <label for="upload-map" class="btn">📤 Carica Mappa</label>
                    <input type="file" id="upload-map" accept="image/*">
                </div>
            @endif

            <!-- Ricerca Mappetta Online -->
            <div class="search-section">
                <h3>🔍 Cerca Mappetta Online</h3>
                <p style="color: #666; margin-bottom: 15px; font-size: 14px;">
                    Non hai la mappetta? Cercala online e importala direttamente.
                </p>

                <div class="search-links">
                    <a href="https://www.google.com/search?tbm=isch&q={{ urlencode($course->name . ' golf course map layout holes') }}"
                       target="_blank" class="search-link">
                        🔍 Google Images
                    </a>
                    <a href="https://www.bing.com/images/search?q={{ urlencode($course->name . ' golf course map layout') }}"
                       target="_blank" class="search-link">
                        🔎 Bing Images
                    </a>
                    <a href="https://www.google.com/search?q={{ urlencode($course->name . ' golf scorecard') }}"
                       target="_blank" class="search-link">
                        📋 Scorecard
                    </a>
                    <a href="https://www.google.com/maps/search/{{ urlencode($course->name) }}/@{{ $course->latitude }},{{ $course->longitude }},16z"
                       target="_blank" class="search-link">
                        🗺️ Google Maps
                    </a>
                </div>

                <p style="color: #666; margin-bottom: 10px; font-size: 13px;">
                    <strong>Importa da URL:</strong> Copia l'indirizzo dell'immagine trovata
                </p>
                <div class="url-import">
                    <input type="text" id="image-url" placeholder="https://esempio.com/mappa-campo.jpg">
                    <button class="btn btn-secondary" onclick="previewImageUrl()" id="preview-btn">
                        👁️ Anteprima
                    </button>
                </div>

                <div class="preview-container" id="preview-container">
                    <p style="margin-bottom: 10px; color: #333; font-weight: bold;">Anteprima:</p>
                    <img id="preview-image" class="preview-image" src="" alt="Anteprima mappetta">
                    <div class="preview-actions">
                        <button class="btn btn-success" onclick="importImageUrl()" id="import-btn">
                            ✅ Importa questa mappa
                        </button>
                        <button class="btn btn-danger" onclick="cancelPreview()">
                            ❌ Annulla
                        </button>
                    </div>
                </div>

                <div class="help-note">
                    💡 <strong>Suggerimento:</strong> Cerca "{{ $course->name }} course map" o "{{ $course->name }} layout holes".
                    Una volta trovata l'immagine, fai click destro → "Copia indirizzo immagine" e incollalo qui sopra.
                </div>
            </div>
        </div>

        <!-- Sezione Buche -->
        <div class="holes-section">
            <h2>⛳ Buche del Campo</h2>

            <div class="holes-grid">
                @foreach($course->holes as $hole)
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
                                Non misurata
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
        // Upload/Update mappa
        const uploadInput = document.getElementById('upload-map');
        const updateInput = document.getElementById('update-map');

        if (uploadInput) {
            uploadInput.addEventListener('change', handleMapUpload);
        }

        if (updateInput) {
            updateInput.addEventListener('change', handleMapUpload);
        }

        async function handleMapUpload(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('map_image', file);

            try {
                const response = await fetch('{{ route("courses.upload-map", $course) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Mappa caricata con successo!');
                    location.reload();
                } else {
                    alert('❌ Errore: ' + (data.message || 'Errore sconosciuto'));
                }
            } catch (error) {
                console.error('Errore:', error);
                alert('❌ Errore durante l\'upload');
            }
        }

        // Vai a misurazione buca
        function measureHole(courseId, holeNumber) {
            window.location.href = `{{ url('courses/'.$course->id.'/holes') }}/${holeNumber}/measure`;
        }

        // === Ricerca Mappetta Online ===
        const imageUrlInput = document.getElementById('image-url');
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');
        const previewBtn = document.getElementById('preview-btn');
        const importBtn = document.getElementById('import-btn');

        function previewImageUrl() {
            const url = imageUrlInput.value.trim();
            if (!url) {
                alert('Inserisci un URL di immagine');
                return;
            }

            // Verifica che sia un URL valido
            try {
                new URL(url);
            } catch (e) {
                alert('URL non valido');
                return;
            }

            previewBtn.innerHTML = '<span class="loading"></span> Caricamento...';
            previewBtn.disabled = true;

            // Prova a caricare l'immagine
            const img = new Image();
            img.crossOrigin = 'anonymous';

            img.onload = function() {
                previewImage.src = url;
                previewContainer.classList.add('active');
                previewBtn.innerHTML = '👁️ Anteprima';
                previewBtn.disabled = false;
            };

            img.onerror = function() {
                // Prova comunque a mostrare - potrebbe essere CORS
                previewImage.src = url;
                previewContainer.classList.add('active');
                previewBtn.innerHTML = '👁️ Anteprima';
                previewBtn.disabled = false;
            };

            img.src = url;

            // Timeout fallback
            setTimeout(() => {
                if (previewBtn.disabled) {
                    previewImage.src = url;
                    previewContainer.classList.add('active');
                    previewBtn.innerHTML = '👁️ Anteprima';
                    previewBtn.disabled = false;
                }
            }, 3000);
        }

        function cancelPreview() {
            previewContainer.classList.remove('active');
            previewImage.src = '';
        }

        async function importImageUrl() {
            const url = imageUrlInput.value.trim();
            if (!url) return;

            importBtn.innerHTML = '<span class="loading"></span> Importazione...';
            importBtn.disabled = true;

            try {
                const response = await fetch('{{ route("courses.import-map-url", $course) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ url: url })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Mappa importata con successo!');
                    location.reload();
                } else {
                    alert('❌ Errore: ' + (data.message || 'Impossibile importare l\'immagine'));
                    importBtn.innerHTML = '✅ Importa questa mappa';
                    importBtn.disabled = false;
                }
            } catch (error) {
                console.error('Errore:', error);
                alert('❌ Errore durante l\'importazione. Prova a scaricare l\'immagine manualmente e caricarla.');
                importBtn.innerHTML = '✅ Importa questa mappa';
                importBtn.disabled = false;
            }
        }

        // Permetti invio con Enter
        imageUrlInput?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                previewImageUrl();
            }
        });
    </script>
</body>
</html>
