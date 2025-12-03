<?php

use Illuminate\Support\Facades\Route;
use Modules\Director\Http\Controllers\Api\Camp\Campcontroller;
use Modules\Director\Http\Controllers\DirectorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('directors', DirectorController::class)->names('director');
});
Route::middleware(['auth:api', 'role:director'])->prefix('v1')->group(function () {
    Route::controller(Campcontroller::class)->group(function () {
        Route::get('camp/sports-type', 'getSportsType');
    });
});
 