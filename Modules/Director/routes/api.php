<?php

use Illuminate\Support\Facades\Route;
use Modules\Director\Http\Controllers\Api\Camp\Campcontroller;
use Modules\Director\Http\Controllers\DirectorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('directors', DirectorController::class)->names('director');
});
Route::middleware(['auth:api', 'role:director'])->prefix('v1')->group(function () {
    Route::controller(Campcontroller::class)->group(function () {
        Route::post('camp/create', 'createCamp'); // done
        Route::post('camp/update/{id}', 'updateCamp'); // done
        Route::post('camp/status/{id}', 'updateStatus'); // done
        Route::delete('camp/delete/{id}', 'deleteCamp'); // done
        Route::get('camp/details/{id}', 'campDetails'); // done
    });
});

// Camp List Route (no authentication required)
Route::get('v1/camp/sports-type', [Campcontroller::class, 'getSportsType']); // done
Route::get('v1/camp/list', [Campcontroller::class, 'campList']); // done
Route::get('v1/camp/locations', [Campcontroller::class, 'getLocations']); // done
