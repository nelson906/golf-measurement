<?php

namespace App\Http\Controllers;

use App\Services\GolfCourseApiService;
use Illuminate\Http\Request;

class GolfApiController extends Controller
{
    protected GolfCourseApiService $golfApi;

    public function __construct(GolfCourseApiService $golfApi)
    {
        $this->golfApi = $golfApi;
    }

    /**
     * Verifica se l'API è configurata
     */
    public function status()
    {
        return response()->json([
            'configured' => $this->golfApi->isConfigured(),
        ]);
    }

    /**
     * Cerca campi da golf per nome
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'country' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $result = $this->golfApi->searchCourses(
            $request->input('q'),
            $request->input('country'),
            $request->input('limit', 10)
        );

        return response()->json($result);
    }

    /**
     * Cerca campi vicino a coordinate
     */
    public function searchByLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:50',
        ]);

        $result = $this->golfApi->searchByLocation(
            $request->input('lat'),
            $request->input('lng'),
            $request->input('radius', 25)
        );

        return response()->json($result);
    }

    /**
     * Ottieni dettagli di un campo
     */
    public function getCourse(string $courseId)
    {
        $result = $this->golfApi->getCourse($courseId);
        return response()->json($result);
    }
}
