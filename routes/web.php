<?php

use App\Http\Controllers\GolfCourseController;
use App\Http\Controllers\GolfApiController;
use App\Http\Controllers\HoleDataController;
use App\Http\Controllers\MeasurementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('courses.index');
});

// Golf Courses Routes
Route::resource('courses', GolfCourseController::class);

// Upload mappa campo
Route::post('courses/{course}/upload-map', [GolfCourseController::class, 'uploadMap'])
    ->name('courses.upload-map');

// Salva configurazione overlay
Route::post('courses/{course}/save-overlay-config', [GolfCourseController::class, 'saveOverlayConfig'])
    ->name('courses.save-overlay-config');

// Importa mappa da URL
Route::post('courses/{course}/import-map-url', [GolfCourseController::class, 'importMapFromUrl'])
    ->name('courses.import-map-url');

// Measurement Routes
Route::get('courses/{course}/measure', [MeasurementController::class, 'index'])
    ->name('courses.measure');

Route::get('courses/{course}/holes/{hole}/measure', [MeasurementController::class, 'measureHole'])
    ->name('holes.measure');

Route::post('courses/{course}/holes/{hole}/geometry', [MeasurementController::class, 'saveHoleGeometry'])
    ->name('holes.geometry.save');

// Drive Routes
Route::post('drives', [MeasurementController::class, 'storeDrive'])
    ->name('drives.store');

Route::put('drives/{drive}', [MeasurementController::class, 'updateDrive'])
    ->name('drives.update');

Route::delete('drives/{drive}', [MeasurementController::class, 'destroyDrive'])
    ->name('drives.destroy');

// Measurement Routes (larghezze)
Route::post('measurements', [MeasurementController::class, 'storeMeasurement'])
    ->name('measurements.store');

// Golf Course API Routes
Route::prefix('api/golf')->group(function () {
    Route::get('status', [GolfApiController::class, 'status'])->name('golf-api.status');
    Route::get('search', [GolfApiController::class, 'search'])->name('golf-api.search');
    Route::get('nearby', [GolfApiController::class, 'searchByLocation'])->name('golf-api.nearby');
    Route::get('course/{id}', [GolfApiController::class, 'getCourse'])->name('golf-api.course');
});

// Hole Data Routes (OSM + Manual Mapping)
Route::get('courses/{course}/map-holes', [HoleDataController::class, 'mapHolesView'])->name('courses.map-holes');
Route::prefix('courses/{course}/holes-data')->group(function () {
    Route::get('load-osm', [HoleDataController::class, 'loadFromOSM'])->name('holes.load-osm');
    Route::get('mapping-status', [HoleDataController::class, 'getMappingStatus'])->name('holes.mapping-status');
    Route::post('batch', [HoleDataController::class, 'saveBatch'])->name('holes.save-batch');
    Route::post('{holeNumber}/green', [HoleDataController::class, 'saveGreenPoint'])->name('holes.save-green');
    Route::post('{holeNumber}/tee', [HoleDataController::class, 'saveTeePoint'])->name('holes.save-tee');
});
