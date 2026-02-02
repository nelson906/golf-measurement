<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OverpassService
{
    protected string $baseUrl = 'https://overpass-api.de/api/interpreter';

    /**
     * Cerca buche golf in un'area definita da coordinate
     */
    public function getGolfHoles(float $lat, float $lng, float $radiusMeters = 2000): array
    {
        $cacheKey = 'osm_golf_holes_' . md5($lat . $lng . $radiusMeters);

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $radiusMeters) {
            return $this->queryGolfElements($lat, $lng, $radiusMeters);
        });
    }

    /**
     * Cerca buche golf usando il nome del campo
     */
    public function getGolfHolesByName(string $courseName): array
    {
        $cacheKey = 'osm_golf_name_' . md5($courseName);

        return Cache::remember($cacheKey, 3600, function () use ($courseName) {
            return $this->queryGolfElementsByName($courseName);
        });
    }

    /**
     * Query Overpass per elementi golf in un raggio
     */
    protected function queryGolfElements(float $lat, float $lng, float $radiusMeters): array
    {
        // Query per cercare tutti gli elementi golf nel raggio
        // Query semplificata per evitare timeout
        $query = sprintf('[out:json][timeout:60];
(
  node["golf"](around:%d,%f,%f);
  way["golf"](around:%d,%f,%f);
);
out body;
>;
out skel qt;',
            $radiusMeters, $lat, $lng,
            $radiusMeters, $lat, $lng
        );

        return $this->executeQuery($query);
    }

    /**
     * Query Overpass per elementi golf per nome area
     */
    protected function queryGolfElementsByName(string $courseName): array
    {
        // Cerca prima l'area del golf course, poi gli elementi al suo interno
        // Query semplificata per nome
        $query = sprintf('[out:json][timeout:60];
area["leisure"="golf_course"]["name"~"%s",i]->.golfcourse;
(
  node["golf"](area.golfcourse);
  way["golf"](area.golfcourse);
);
out body;
>;
out skel qt;',
            addslashes($courseName)
        );

        return $this->executeQuery($query);
    }

    /**
     * Esegue la query Overpass
     */
    protected function executeQuery(string $query): array
    {
        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'User-Agent' => 'GolfMeasurement/1.0 (golf rating app)',
                ])
                ->asForm()
                ->post($this->baseUrl, [
                    'data' => $query,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Overpass API error: ' . $response->status(),
                    'holes' => [],
                ];
            }

            $data = $response->json();
            return $this->parseGolfElements($data);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'holes' => [],
            ];
        }
    }

    /**
     * Parsa gli elementi OSM e raggruppa per buca
     * Usa ref + name per distinguere buche in campi con 27/36 buche
     */
    protected function parseGolfElements(array $data): array
    {
        $elements = $data['elements'] ?? [];
        $holes = [];
        $nodes = []; // Per risolvere i way

        // Prima passa: raccogli tutti i nodi
        foreach ($elements as $el) {
            if ($el['type'] === 'node') {
                $nodes[$el['id']] = ['lat' => $el['lat'], 'lng' => $el['lon']];
            }
        }

        // Seconda passa: processa gli elementi golf
        foreach ($elements as $el) {
            $tags = $el['tags'] ?? [];
            $golfType = $tags['golf'] ?? null;

            if (!$golfType) continue;

            // Usa ref per il numero buca, name per identificare il percorso
            $holeRef = $tags['ref'] ?? null;
            $holeName = $tags['name'] ?? null;

            // Estrai coordinate
            $coords = null;
            if ($el['type'] === 'node') {
                $coords = ['lat' => $el['lat'], 'lng' => $el['lon']];
            } elseif ($el['type'] === 'way' && isset($el['nodes'])) {
                // Calcola centroide del way
                $coords = $this->calculateCentroid($el['nodes'], $nodes);
            }

            if (!$coords) continue;

            // Raggruppa per numero buca + nome percorso
            $holeNum = $this->extractHoleNumber($holeRef ?? $holeName);
            $courseName = $this->extractCourseName($holeName, $holeRef);

            if ($holeNum === null) {
                // Elemento senza numero buca specifico
                $holes['unassigned'][] = [
                    'type' => $golfType,
                    'ref' => $holeRef,
                    'name' => $holeName,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'tags' => $tags,
                ];
            } else {
                // Chiave univoca: courseName + holeNum (per campi 27/36 buche)
                $holeKey = $courseName ? "{$courseName}_{$holeNum}" : $holeNum;

                if (!isset($holes[$holeKey])) {
                    $holes[$holeKey] = [
                        'hole_number' => $holeNum,
                        'course_name' => $courseName,
                        'tee' => null,
                        'green' => null,
                        'pin' => null,
                        'fairway' => null,
                        'centerline' => null,
                        'par' => $tags['par'] ?? null,
                    ];
                }

                // Assegna al tipo corretto
                switch ($golfType) {
                    case 'tee':
                        $holes[$holeKey]['tee'] = $coords;
                        break;
                    case 'green':
                    case 'pin':
                        $holes[$holeKey]['green'] = $coords;
                        break;
                    case 'hole':
                        // Un 'hole' way contiene la centerline
                        if ($el['type'] === 'way' && isset($el['nodes'])) {
                            // Salva la centerline come array di coordinate
                            $centerline = [];
                            foreach ($el['nodes'] as $nodeId) {
                                if (isset($nodes[$nodeId])) {
                                    $centerline[] = $nodes[$nodeId];
                                }
                            }
                            if (count($centerline) >= 2) {
                                $holes[$holeKey]['centerline'] = $centerline;
                                // Primo punto = tee, ultimo = green (se non già impostati)
                                if (!$holes[$holeKey]['tee']) {
                                    $holes[$holeKey]['tee'] = $centerline[0];
                                }
                                if (!$holes[$holeKey]['green']) {
                                    $holes[$holeKey]['green'] = end($centerline);
                                }
                            }
                        }
                        break;
                    case 'fairway':
                        $holes[$holeKey]['fairway'] = $coords;
                        break;
                }

                // Estrai par se presente
                if (isset($tags['par'])) {
                    $holes[$holeKey]['par'] = (int)$tags['par'];
                }
            }
        }

        // Raggruppa le buche per percorso (course_name)
        $courseGroups = [];
        $simpleHoles = []; // Buche senza course_name (campi 18 buche standard)

        foreach ($holes as $key => $hole) {
            if ($key === 'unassigned') continue;

            $courseName = $hole['course_name'] ?? null;
            if ($courseName) {
                if (!isset($courseGroups[$courseName])) {
                    $courseGroups[$courseName] = [];
                }
                $courseGroups[$courseName][$hole['hole_number']] = $hole;
            } else {
                $simpleHoles[$hole['hole_number']] = $hole;
            }
        }

        // Ordina le buche per numero in ogni gruppo
        ksort($simpleHoles);
        foreach ($courseGroups as $name => $group) {
            ksort($courseGroups[$name]);
        }

        return [
            'success' => true,
            'holes' => $simpleHoles,
            'courses' => $courseGroups, // Per campi 27/36 buche
            'unassigned' => $holes['unassigned'] ?? [],
            'total_found' => count($simpleHoles) + array_sum(array_map('count', $courseGroups)),
            'raw_elements' => count($elements),
        ];
    }

    /**
     * Estrae il numero della buca da una stringa ref
     */
    protected function extractHoleNumber(?string $ref): ?int
    {
        if ($ref === null) return null;

        // Cerca un numero nella stringa
        if (preg_match('/(\d+)/', $ref, $matches)) {
            $num = (int)$matches[1];
            // Supporta buche 1-18 per ogni percorso (anche campi 27/36 buche hanno max 18 per percorso)
            if ($num >= 1 && $num <= 18) {
                return $num;
            }
        }

        return null;
    }

    /**
     * Estrae il nome del percorso da name/ref per campi con 27/36 buche
     * Es: "Blue Course Hole 5", "Red 3", "Percorso Giallo - Buca 7"
     */
    protected function extractCourseName(?string $name, ?string $ref): ?string
    {
        // Prima prova con name
        if ($name) {
            // Pattern comuni: "Blue Course Hole 5", "Red 3", "Percorso Giallo - Buca 7"
            // Rimuovi numeri e parole comuni per estrarre il nome del percorso
            $courseName = preg_replace('/\b(hole|buca|buche|\d+)\b/i', '', $name);
            $courseName = preg_replace('/[-–—:,\.]+/', ' ', $courseName);
            $courseName = trim(preg_replace('/\s+/', ' ', $courseName));

            if (!empty($courseName) && strlen($courseName) > 1) {
                return $courseName;
            }
        }

        // Prova con ref se contiene lettere (es: "A5", "B3", "Blue-7")
        if ($ref && preg_match('/([A-Za-z]+)/', $ref, $matches)) {
            $prefix = $matches[1];
            // Solo se è più di una lettera o è una lettera significativa
            if (strlen($prefix) > 1 || in_array(strtoupper($prefix), ['A', 'B', 'C', 'R', 'G', 'Y'])) {
                return ucfirst(strtolower($prefix));
            }
        }

        return null;
    }

    /**
     * Calcola il centroide di un way
     */
    protected function calculateCentroid(array $nodeIds, array $nodes): ?array
    {
        $sumLat = 0;
        $sumLng = 0;
        $count = 0;

        foreach ($nodeIds as $nodeId) {
            if (isset($nodes[$nodeId])) {
                $sumLat += $nodes[$nodeId]['lat'];
                $sumLng += $nodes[$nodeId]['lng'];
                $count++;
            }
        }

        if ($count === 0) return null;

        return [
            'lat' => $sumLat / $count,
            'lng' => $sumLng / $count,
        ];
    }

    /**
     * Pulisci la cache per un campo
     */
    public function clearCache(float $lat, float $lng): void
    {
        Cache::forget('osm_golf_holes_' . md5($lat . $lng . '2000'));
    }
}
