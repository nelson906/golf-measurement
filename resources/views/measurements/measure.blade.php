<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misurazione Buca {{ $hole->hole_number }} - {{ $course->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: white;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            padding-top: 100px;
        }
        h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #4CAF50;
        }
        p {
            font-size: 18px;
            color: #888;
            margin-bottom: 30px;
        }
        .back-link {
            display: inline-block;
            padding: 12px 24px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        .back-link:hover {
            background: #0b7dda;
        }
        .info {
            background: #2a2a2a;
            padding: 30px;
            border-radius: 8px;
            margin-top: 40px;
        }
        .info h2 {
            color: #81C784;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏌️ Buca {{ $hole->hole_number }}</h1>
        <p>{{ $course->name }}</p>

        <div class="info">
            <h2>⚠️ Interfaccia di Misurazione in Costruzione</h2>
            <p style="margin-top: 15px;">
                Questa sarà l'interfaccia principale con:
            </p>
            <ul style="text-align: left; max-width: 500px; margin: 20px auto; line-height: 2;">
                <li>✅ Mappa satellitare con overlay della mappa campo</li>
                <li>✅ Sistema di allineamento overlay (rotazione, scala, spostamento)</li>
                <li>✅ Tracciamento drive con punti trascinabili</li>
                <li>✅ Misurazione larghezze fairway</li>
                <li>✅ Salvataggio configurazioni</li>
            </ul>

            @if($hole->length_yards)
                <div style="margin-top: 30px; padding: 20px; background: #1e1e1e; border-radius: 6px;">
                    <h3 style="color: #4CAF50; margin-bottom: 10px;">📊 Dati Attuali Buca</h3>
                    <p>Lunghezza: <strong>{{ $hole->length_yards }} yards</strong></p>
                    <p>Par: <strong>{{ $hole->par }}</strong></p>
                    <p>Drive salvati: <strong>{{ $hole->drives->count() }}</strong></p>
                </div>
            @else
                <div style="margin-top: 30px; padding: 20px; background: #1e1e1e; border-radius: 6px;">
                    <p style="color: #f44336;">⚠️ Buca non ancora misurata</p>
                </div>
            @endif
        </div>

        <a href="{{ route('courses.show', $course) }}" class="back-link">← Torna al Campo</a>
    </div>
</body>
</html>
