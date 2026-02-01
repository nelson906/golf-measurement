<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campi da Golf</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .btn:hover {
            background: #45a049;
        }
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .course-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.3s;
        }
        .course-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .course-card h2 {
            color: #4CAF50;
            margin-bottom: 10px;
            font-size: 20px;
        }
        .course-card p {
            color: #666;
            margin-bottom: 15px;
        }
        .course-card .actions {
            display: flex;
            gap: 10px;
        }
        .course-card .actions a {
            flex: 1;
            padding: 8px;
            text-align: center;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .course-card .actions a:hover {
            background: #0b7dda;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .no-courses {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏌️ Campi da Golf</h1>

        @if(session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        <a href="{{ route('courses.create') }}" class="btn">+ Nuovo Campo</a>

        @if($courses->count() > 0)
            <div class="courses-grid">
                @foreach($courses as $course)
                    <div class="course-card">
                        <h2>{{ $course->name }}</h2>
                        <p>📍 {{ $course->location }}</p>
                        <p>🗺️ {{ $course->latitude }}, {{ $course->longitude }}</p>

                        @php
                            $mappedHoles = $course->holes->filter(fn($h) => !empty($h->green_point) || !empty($h->tee_points['yellow']))->count();
                            $totalHoles = $course->holes->count();
                        @endphp
                        @if($mappedHoles > 0)
                            <p style="color: #4CAF50;">📍 {{ $mappedHoles }}/{{ $totalHoles }} buche mappate</p>
                        @else
                            <p style="color: #f44336;">✗ Buche non mappate</p>
                        @endif

                        <div class="actions">
                            <a href="{{ route('courses.show', $course) }}">Dettagli</a>
                            <a href="{{ route('courses.measure', $course) }}">Misura</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-courses">
                <h2>Nessun campo ancora</h2>
                <p>Inizia creando il tuo primo campo da golf</p>
            </div>
        @endif
    </div>
</body>
</html>
