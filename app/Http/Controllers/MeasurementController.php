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
    public function measureHole(GolfCourse $course, $holeNumber)
    {
        $hole = $course->holes()->where('hole_number', $holeNumber)->firstOrFail();

        // Carica drive esistenti per questa buca
        $hole->load(['drives.measurements', 'drives.user']);

        return view('measurements.measure', compact('course', 'hole'));
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

        $drive = Drive::create([
            'hole_id' => $validated['hole_id'],
            'user_id' => auth()->id() ?? 1, // TODO: implement auth
            'tee_lat' => $validated['tee_lat'],
            'tee_lng' => $validated['tee_lng'],
            'total_distance_meters' => $totalMeters,
            'total_distance_yards' => $totalYards,
            'num_shots' => count($validated['shots']),
            'shots' => $validated['shots'],
        ]);

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
            'message' => 'Drive salvato con successo!',
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
