<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use App\Models\Drive;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    public function show(GolfCourse $course, $holeNumber)
    {
        $hole = $course->holes()->where('hole_number', $holeNumber)->firstOrFail();

        return view('courses.measure', [
            'course' => $course,
            'hole' => $hole,
            'overlayConfig' => $course->overlay_config ?? $this->getDefaultConfig()
        ]);
    }

    public function saveDrive(Request $request)
    {
        $validated = $request->validate([
            'hole_id' => 'required|exists:holes,id',
            'tee_point' => 'required|array',
            'shots' => 'required|array'
        ]);

        $drive = Drive::create([
            'hole_id' => $validated['hole_id'],
            'tee_point' => DB::raw("POINT({$validated['tee_point']['lat']}, {$validated['tee_point']['lng']})"),
            'shots' => json_encode($validated['shots']),
            // calcola distanze...
        ]);

        return response()->json($drive);
    }

    public function saveOverlayConfig(Request $request, GolfCourse $course)
    {
        $course->update([
            'overlay_config' => $request->all()
        ]);

        return response()->json(['success' => true]);
    }
}
