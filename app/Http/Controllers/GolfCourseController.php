<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GolfCourseController extends Controller
{
    /**
     * Mostra lista campi
     */
    public function index()
    {
        $courses = GolfCourse::latest()->get();
        return view('courses.index', compact('courses'));
    }

    /**
     * Form creazione nuovo campo
     */
    public function create()
    {
        return view('courses.create');
    }

    /**
     * Salva nuovo campo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $course = GolfCourse::create($validated);

        // Crea automaticamente 18 buche
        for ($i = 1; $i <= 18; $i++) {
            $course->holes()->create([
                'hole_number' => $i,
                'par' => null,
                'length_yards' => null,
            ]);
        }

        // Se richiesta AJAX, ritorna JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'course_id' => $course->id,
                'message' => 'Campo creato con successo!'
            ]);
        }

        return redirect()->route('courses.show', $course)
            ->with('success', 'Campo creato con successo!');
    }

    /**
     * Mostra dettaglio campo
     */
    public function show(GolfCourse $course)
    {
        $course->load('holes');
        return view('courses.show', compact('course'));
    }

    /**
     * Form modifica campo
     */
    public function edit(GolfCourse $course)
    {
        return view('courses.edit', compact('course'));
    }

    /**
     * Aggiorna campo
     */
    public function update(Request $request, GolfCourse $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $course->update($validated);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Campo aggiornato!');
    }

    /**
     * Elimina campo
     */
    public function destroy(GolfCourse $course)
    {
        // Elimina mappa se esiste
        if ($course->map_image_path) {
            Storage::disk('public')->delete($course->map_image_path);
        }

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Campo eliminato!');
    }

    /**
     * Upload mappa del campo
     */
    public function uploadMap(Request $request, GolfCourse $course)
    {
        $request->validate([
            'map_image' => 'required|image|mimes:jpeg,png,jpg|max:10240', // Max 10MB
        ]);

        // Elimina vecchia mappa se esiste
        if ($course->map_image_path) {
            Storage::disk('public')->delete($course->map_image_path);
        }

        // Salva nuova mappa
        $path = $request->file('map_image')->store('course-maps', 'public');

        $course->update([
            'map_image_path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mappa caricata con successo!',
            'path' => Storage::url($path),
        ]);
    }

    /**
     * Salva configurazione overlay
     */
    public function saveOverlayConfig(Request $request, GolfCourse $course)
    {
        $validated = $request->validate([
            'rotation' => 'required|numeric',
            'scaleX' => 'required|numeric',
            'scaleY' => 'required|numeric',
            'offsetX' => 'required|numeric',
            'offsetY' => 'required|numeric',
            'bounds' => 'required|array',
            'opacity' => 'required|numeric|between:0,1',
        ]);

        $course->update([
            'overlay_config' => $validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configurazione overlay salvata!',
        ]);
    }
}
