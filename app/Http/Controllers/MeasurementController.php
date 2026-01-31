<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use App\Models\Hole;
use App\Models\Drive;
use App\Models\Measurement;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    /**
     * Lista buche per misurazione
     */
    public function index(GolfCourse $course)
    {
        $course->load('holes');
        return view('measurements.index', compact('course'));
    }

    /**
     * Interfaccia misurazione buca
     */
    public function measureHole(GolfCourse $course, int $holeNumber)
    {
        $hole = Hole::query()
            ->where('golf_course_id', $course->id)
            ->where('hole_number', $holeNumber)
            ->firstOrFail();

        // Carica drive esistenti per questa buca
        $hole->load(['drives.measurements', 'drives.user']);

        return view('measurements.measure-simple', compact('course', 'hole'));
    }

    /**
     * Salva geometria buca (tee/green/centerline)
     */
    public function saveHoleGeometry(Request $request, GolfCourse $course, int $holeNumber)
    {
        $hole = Hole::query()
            ->where('golf_course_id', $course->id)
            ->where('hole_number', $holeNumber)
            ->firstOrFail();

        $validated = $request->validate([
            'tee_points' => 'nullable|array',
            'tee_points.yellow.lat' => 'nullable|numeric|between:-90,90',
            'tee_points.yellow.lng' => 'nullable|numeric|between:-180,180',
            'tee_points.red.lat' => 'nullable|numeric|between:-90,90',
            'tee_points.red.lng' => 'nullable|numeric|between:-180,180',
            'green_point' => 'nullable|array',
            'green_point.lat' => 'nullable|numeric|between:-90,90',
            'green_point.lng' => 'nullable|numeric|between:-180,180',
            'centerline' => 'nullable|array',
            'centerline.*.lat' => 'required_with:centerline|numeric|between:-90,90',
            'centerline.*.lng' => 'required_with:centerline|numeric|between:-180,180',
        ]);

        $hole->update([
            'tee_points' => $validated['tee_points'] ?? null,
            'green_point' => $validated['green_point'] ?? null,
            'centerline' => $validated['centerline'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'hole' => $hole->fresh(),
            'message' => 'Geometria buca salvata!',
        ]);
    }

    /**
     * Salva drive
     */
    public function storeDrive(Request $request)
    {
        $validated = $request->validate([
            'hole_id' => 'required|exists:holes,id',
            'tee_lat' => 'required|numeric',
            'tee_lng' => 'required|numeric',
            'shots' => 'required|array',
            'shots.*.lat' => 'required|numeric',
            'shots.*.lng' => 'required|numeric',
            'shots.*.distance_meters' => 'required|numeric',
            'shots.*.distance_yards' => 'required|numeric',
        ]);

        // Calcola totali
        $totalMeters = array_sum(array_column($validated['shots'], 'distance_meters'));
        $totalYards = array_sum(array_column($validated['shots'], 'distance_yards'));

        $userId = auth()->id() ?? 1; // TODO: implement auth

        [$drive, $created] = Drive::query()->updateOrCreate(
            [
                'hole_id' => $validated['hole_id'],
                'user_id' => $userId,
            ],
            [
                'tee_lat' => $validated['tee_lat'],
                'tee_lng' => $validated['tee_lng'],
                'total_distance_meters' => $totalMeters,
                'total_distance_yards' => $totalYards,
                'num_shots' => count($validated['shots']),
                'shots' => $validated['shots'],
            ]
        );

        // Aggiorna lunghezza buca se non impostata
        $hole = Hole::find($validated['hole_id']);
        if (!$hole->length_yards) {
            $hole->update([
                'length_yards' => round($totalYards),
                'par' => $this->calculatePar($totalMeters),
            ]);
        }

        return response()->json([
            'success' => true,
            'drive' => $drive,
            'created' => $created,
            'message' => $created ? 'Drive salvato con successo!' : 'Drive aggiornato con successo!',
        ]);
    }

    /**
     * Aggiorna drive esistente
     */
    public function updateDrive(Request $request, Drive $drive)
    {
        $validated = $request->validate([
            'tee_lat' => 'required|numeric',
            'tee_lng' => 'required|numeric',
            'shots' => 'required|array',
            'shots.*.lat' => 'required|numeric',
            'shots.*.lng' => 'required|numeric',
            'shots.*.distance_meters' => 'required|numeric',
            'shots.*.distance_yards' => 'required|numeric',
        ]);

        // Ricalcola totali
        $totalMeters = array_sum(array_column($validated['shots'], 'distance_meters'));
        $totalYards = array_sum(array_column($validated['shots'], 'distance_yards'));

        $drive->update([
            'tee_lat' => $validated['tee_lat'],
            'tee_lng' => $validated['tee_lng'],
            'total_distance_meters' => $totalMeters,
            'total_distance_yards' => $totalYards,
            'num_shots' => count($validated['shots']),
            'shots' => $validated['shots'],
        ]);

        // Aggiorna buca
        $hole = $drive->hole;
        $hole->update([
            'length_yards' => round($totalYards),
            'par' => $this->calculatePar($totalMeters),
        ]);

        return response()->json([
            'success' => true,
            'drive' => $drive,
            'message' => 'Drive aggiornato!',
        ]);
    }

    /**
     * Elimina drive
     */
    public function destroyDrive(Drive $drive)
    {
        $drive->delete();

        return response()->json([
            'success' => true,
            'message' => 'Drive eliminato!',
        ]);
    }

    /**
     * Salva misurazione larghezza
     */
    public function storeMeasurement(Request $request)
    {
        $validated = $request->validate([
            'drive_id' => 'required|exists:drives,id',
            'type' => 'required|in:width,hazard,green',
            'point_a_lat' => 'required|numeric',
            'point_a_lng' => 'required|numeric',
            'point_b_lat' => 'required|numeric',
            'point_b_lng' => 'required|numeric',
            'distance_yards' => 'required|numeric',
        ]);

        $measurement = Measurement::create($validated);

        return response()->json([
            'success' => true,
            'measurement' => $measurement,
            'message' => 'Misurazione salvata!',
        ]);
    }

    /**
     * Salva posizione tee o green di una buca
     */
    public function saveHolePosition(Request $request, Hole $hole)
    {
        $validated = $request->validate([
            'type' => 'required|in:tee,green',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $position = ['lat' => $validated['lat'], 'lng' => $validated['lng']];

        if ($validated['type'] === 'tee') {
            $teePoints = $hole->tee_points ?? [];
            $teePoints['yellow'] = $position;
            $hole->update(['tee_points' => $teePoints]);
        } else {
            $hole->update(['green_point' => $position]);
        }

        return response()->json([
            'success' => true,
            'hole' => $hole->fresh(),
            'message' => ucfirst($validated['type']) . ' salvato!',
        ]);
    }

    /**
     * Calcola par suggerito in base alla distanza
     */
    private function calculatePar(float $meters): int
    {
        if ($meters < 229) {
            return 3;
        } elseif ($meters < 430) {
            return 4;
        } else {
            return 5;
        }
    }
}
