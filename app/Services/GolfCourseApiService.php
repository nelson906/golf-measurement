<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GolfCourseApiService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.golfcourseapi.base_url', 'https://api.golfcourseapi.com/v1');
        $this->apiKey = config('services.golfcourseapi.api_key');
    }

    /**
     * Verifica se l'API è configurata
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Cerca campi da golf per nome
     */
    public function searchCourses(string $query, ?string $country = null, int $limit = 10): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'API key non configurata', 'courses' => []];
        }

        $cacheKey = 'golf_search_' . md5($query . $country . $limit);

        return Cache::remember($cacheKey, 3600, function () use ($query, $country, $limit) {
            try {
                $params = [
                    'search' => $query,
                    'limit' => $limit,
                ];

                if ($country) {
                    $params['country'] = $country;
                }

                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/courses', $params);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'courses' => $data['courses'] ?? $data['data'] ?? $data,
                    ];
                }

                // Prova endpoint alternativo (alcuni API usano /search)
                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/courses/search', $params);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'courses' => $data['courses'] ?? $data['data'] ?? $data,
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Errore API: ' . $response->status(),
                    'courses' => [],
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'courses' => [],
                ];
            }
        });
    }

    /**
     * Ottieni dettagli di un campo specifico
     */
    public function getCourse(string $courseId): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'API key non configurata'];
        }

        $cacheKey = 'golf_course_' . $courseId;

        return Cache::remember($cacheKey, 3600, function () use ($courseId) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/courses/' . $courseId);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'course' => $response->json(),
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Corso non trovato',
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Cerca campi vicino a coordinate
     */
    public function searchByLocation(float $lat, float $lng, int $radiusMiles = 25): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'API key non configurata', 'courses' => []];
        }

        $cacheKey = 'golf_location_' . md5($lat . $lng . $radiusMiles);

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $radiusMiles) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/courses', [
                        'lat' => $lat,
                        'lng' => $lng,
                        'radius' => $radiusMiles,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'courses' => $data['courses'] ?? $data['data'] ?? $data,
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Errore API: ' . $response->status(),
                    'courses' => [],
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'courses' => [],
                ];
            }
        });
    }

    /**
     * Pulisci la cache per un campo
     */
    public function clearCache(?string $courseId = null): void
    {
        if ($courseId) {
            Cache::forget('golf_course_' . $courseId);
        }
    }
}
