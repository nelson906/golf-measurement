<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use App\Models\Hole;
use App\Services\OverpassService;
use Illuminate\Http\Request;

class HoleDataController extends Controller
{
    protected OverpassService $overpass;

    public function __construct(OverpassService $overpass)
    {
        $this->overpass = $overpass;
    }

    /**
     * Mostra la pagina di mappatura buche
     */
    public function mapHolesView(GolfCourse $course)
    {
        return view('courses.map-holes', compact('course'));
    }

    /**
     * Carica dati buche da OpenStreetMap
     */
    public function loadFromOSM(GolfCourse $course)
    {
        $result = $this->overpass->getGolfHoles(
            $course->latitude,
            $course->longitude,
            2500 // raggio in metri
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Errore caricamento da OSM: ' . ($result['error'] ?? 'sconosciuto'),
            ]);
        }

        $updated = 0;
        $holes = $result['holes'];

        foreach ($holes as $holeNum => $data) {
            $hole = $course->holes()->where('hole_number', $holeNum)->first();

            if ($hole) {
                $updates = [];

                // Aggiorna tee points se disponibili
                if ($data['tee']) {
                    $teePoints = $hole->tee_points ?? ['yellow' => null, 'red' => null];
                    $teePoints['yellow'] = $data['tee'];
                    $updates['tee_points'] = $teePoints;
                }

                // Aggiorna green point se disponibile
                if ($data['green']) {
                    $updates['green_point'] = $data['green'];
                }

                // Aggiorna par se disponibile
                if ($data['par'] && !$hole->par) {
                    $updates['par'] = $data['par'];
                }

                if (!empty($updates)) {
                    $hole->update($updates);
                    $updated++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Caricati dati per {$updated} buche da OpenStreetMap",
            'total_found' => $result['total_found'],
            'updated' => $updated,
            'holes' => $holes,
            'unassigned' => $result['unassigned'] ?? [],
        ]);
    }

    /**
     * Salva coordinate green per una buca (mappatura manuale)
     */
    public function saveGreenPoint(Request $request, GolfCourse $course, int $holeNumber)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $hole = $course->holes()->where('hole_number', $holeNumber)->first();

        if (!$hole) {
            return response()->json([
                'success' => false,
                'message' => 'Buca non trovata',
            ], 404);
        }

        $hole->update([
            'green_point' => [
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Green buca {$holeNumber} salvato",
            'green_point' => $hole->green_point,
        ]);
    }

    /**
     * Salva coordinate tee per una buca (mappatura manuale)
     */
    public function saveTeePoint(Request $request, GolfCourse $course, int $holeNumber)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'color' => 'required|in:yellow,red',
        ]);

        $hole = $course->holes()->where('hole_number', $holeNumber)->first();

        if (!$hole) {
            return response()->json([
                'success' => false,
                'message' => 'Buca non trovata',
            ], 404);
        }

        $teePoints = $hole->tee_points ?? ['yellow' => null, 'red' => null];
        $teePoints[$validated['color']] = [
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
        ];

        $hole->update(['tee_points' => $teePoints]);

        return response()->json([
            'success' => true,
            'message' => "Tee {$validated['color']} buca {$holeNumber} salvato",
            'tee_points' => $teePoints,
        ]);
    }

    /**
     * Salva tutte le coordinate buche in batch (mappatura rapida)
     */
    public function saveBatch(Request $request, GolfCourse $course)
    {
        $validated = $request->validate([
            'holes' => 'required|array',
            'holes.*.hole_number' => 'required|integer|between:1,18',
            'holes.*.green' => 'nullable|array',
            'holes.*.green.lat' => 'required_with:holes.*.green|numeric',
            'holes.*.green.lng' => 'required_with:holes.*.green|numeric',
            'holes.*.tee_yellow' => 'nullable|array',
            'holes.*.tee_yellow.lat' => 'required_with:holes.*.tee_yellow|numeric',
            'holes.*.tee_yellow.lng' => 'required_with:holes.*.tee_yellow|numeric',
            'holes.*.tee_red' => 'nullable|array',
            'holes.*.tee_red.lat' => 'required_with:holes.*.tee_red|numeric',
            'holes.*.tee_red.lng' => 'required_with:holes.*.tee_red|numeric',
            'holes.*.par' => 'nullable|integer|between:3,6',
        ]);

        $updated = 0;

        foreach ($validated['holes'] as $holeData) {
            $hole = $course->holes()
                ->where('hole_number', $holeData['hole_number'])
                ->first();

            if (!$hole) continue;

            $updates = [];

            if (isset($holeData['green'])) {
                $updates['green_point'] = $holeData['green'];
            }

            if (isset($holeData['tee_yellow']) || isset($holeData['tee_red'])) {
                $teePoints = $hole->tee_points ?? ['yellow' => null, 'red' => null];
                if (isset($holeData['tee_yellow'])) {
                    $teePoints['yellow'] = $holeData['tee_yellow'];
                }
                if (isset($holeData['tee_red'])) {
                    $teePoints['red'] = $holeData['tee_red'];
                }
                $updates['tee_points'] = $teePoints;
            }

            if (isset($holeData['par'])) {
                $updates['par'] = $holeData['par'];
            }

            if (!empty($updates)) {
                $hole->update($updates);
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Aggiornate {$updated} buche",
            'updated' => $updated,
        ]);
    }

    /**
     * Ottieni stato mappatura buche del campo
     */
    public function getMappingStatus(GolfCourse $course)
    {
        $holes = $course->holes()->orderBy('hole_number')->get();

        $status = [];
        $complete = 0;
        $partial = 0;
        $empty = 0;

        foreach ($holes as $hole) {
            $hasGreen = !empty($hole->green_point);
            $hasTee = !empty($hole->tee_points['yellow']) || !empty($hole->tee_points['red']);

            $holeStatus = [
                'hole_number' => $hole->hole_number,
                'has_green' => $hasGreen,
                'has_tee' => $hasTee,
                'green_point' => $hole->green_point,
                'tee_points' => $hole->tee_points,
                'par' => $hole->par,
                'status' => 'empty',
            ];

            if ($hasGreen && $hasTee) {
                $holeStatus['status'] = 'complete';
                $complete++;
            } elseif ($hasGreen || $hasTee) {
                $holeStatus['status'] = 'partial';
                $partial++;
            } else {
                $empty++;
            }

            $status[] = $holeStatus;
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'complete' => $complete,
                'partial' => $partial,
                'empty' => $empty,
                'total' => count($holes),
            ],
            'holes' => $status,
        ]);
    }
}
