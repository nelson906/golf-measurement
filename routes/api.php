<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeasurementController;

Route::prefix('courses')->group(function () {
    Route::post('{course}/overlay', [MeasurementController::class, 'saveOverlayConfig']);
    Route::get('{course}/holes/{hole}', [MeasurementController::class, 'show']);
});

Route::prefix('drives')->group(function () {
    Route::post('/', [MeasurementController::class, 'saveDrive']);
    Route::get('/{drive}', [MeasurementController::class, 'getDrive']);
});

Route::prefix('measurements')->group(function () {
    Route::post('/', [MeasurementController::class, 'saveMeasurement']);
});

Route::prefix('holes')->group(function () {
    Route::post('{hole}/position', [MeasurementController::class, 'saveHolePosition']);
});
?>
