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

            $holeRef = $tags['ref'] ?? $tags['name'] ?? null;

            // Estrai coordinate
            $coords = null;
            if ($el['type'] === 'node') {
                $coords = ['lat' => $el['lat'], 'lng' => $el['lon']];
            } elseif ($el['type'] === 'way' && isset($el['nodes'])) {
                // Calcola centroide del way
                $coords = $this->calculateCentroid($el['nodes'], $nodes);
            }

            if (!$coords) continue;

            // Raggruppa per numero buca
            $holeNum = $this->extractHoleNumber($holeRef);

            if ($holeNum === null) {
                // Elemento senza numero buca specifico
                $holes['unassigned'][] = [
                    'type' => $golfType,
                    'ref' => $holeRef,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'tags' => $tags,
                ];
            } else {
                if (!isset($holes[$holeNum])) {
                    $holes[$holeNum] = [
                        'hole_number' => $holeNum,
                        'tee' => null,
                        'green' => null,
                        'pin' => null,
                        'fairway' => null,
                        'par' => $tags['par'] ?? null,
                    ];
                }

                // Assegna al tipo corretto
                switch ($golfType) {
                    case 'tee':
                        $holes[$holeNum]['tee'] = $coords;
                        break;
                    case 'green':
                    case 'pin':
                        $holes[$holeNum]['green'] = $coords;
                        break;
                    case 'hole':
                        // Un 'hole' way potrebbe contenere info sulla lunghezza
                        if (!$holes[$holeNum]['tee'] && isset($el['nodes'][0])) {
                            $firstNode = $el['nodes'][0];
                            if (isset($nodes[$firstNode])) {
                                $holes[$holeNum]['tee'] = $nodes[$firstNode];
                            }
                        }
                        if (!$holes[$holeNum]['green'] && isset($el['nodes'])) {
                            $lastNode = end($el['nodes']);
                            if (isset($nodes[$lastNode])) {
                                $holes[$holeNum]['green'] = $nodes[$lastNode];
                            }
                        }
                        break;
                    case 'fairway':
                        $holes[$holeNum]['fairway'] = $coords;
                        break;
                }

                // Estrai par se presente
                if (isset($tags['par'])) {
                    $holes[$holeNum]['par'] = (int)$tags['par'];
                }
            }
        }

        // Ordina le buche per numero
        $sortedHoles = [];
        for ($i = 1; $i <= 18; $i++) {
            if (isset($holes[$i])) {
                $sortedHoles[$i] = $holes[$i];
            }
        }

        return [
            'success' => true,
            'holes' => $sortedHoles,
            'unassigned' => $holes['unassigned'] ?? [],
            'total_found' => count($sortedHoles),
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
            if ($num >= 1 && $num <= 18) {
                return $num;
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
