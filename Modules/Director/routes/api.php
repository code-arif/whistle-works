<?php

use Illuminate\Support\Facades\Route;
use Modules\Director\Http\Controllers\DirectorController;
use Modules\Director\Http\Controllers\Api\Camp\Campcontroller;
use Modules\Director\Http\Controllers\Api\Schedule\RefereeAssignController;
use Modules\Director\Http\Controllers\Api\Schedule\ScheduleController;
use Modules\Director\Http\Controllers\Api\Schedule\RefereeCheckinController;

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


// ==========================================
// DIRECTOR ROUTES (Auth Required)
// ==========================================
Route::middleware(['auth:api', 'role:director'])->prefix('v1')->group(function () {
    // Schedule Management
    Route::controller(ScheduleController::class)->group(function () {
        // Create & View Schedule
        Route::post('camp/{campId}/schedule/create', 'createSchedule'); // working
        Route::get('camp/{campId}/schedule', 'getSchedule'); // working
        Route::delete('camp/{campId}/schedule', 'deleteSchedule'); // working

        // Game Slots
        Route::get('camp/{campId}/schedule/game-slots', 'getGameSlots'); // working

        // Schedule Actions
        Route::post('camp/{campId}/schedule/publish', 'publishSchedule');
        Route::post('camp/{campId}/schedule/clear', 'clearSchedule');
    });

    // Referee Assignment Management
    Route::controller(RefereeAssignController::class)->group(function () {
        Route::post('camp/{campId}/assign-referees', 'assignReferees');
        Route::delete('camp/{campId}/remove-referee/{refereeId}', 'removeReferee');
        Route::get('camp/{campId}/assigned-referees', 'getAssignedReferees');
    });

    // Referee Check-in Management (Director View)
    Route::controller(RefereeCheckinController::class)->group(function () {
        Route::get('camp/{campId}/checked-in-referees', 'getCheckedInReferees');
        Route::post('camp/{campId}/bulk-checkin', 'bulkCheckin');
    });
});

// ==========================================
// REFEREE ROUTES (Auth Required)
// ==========================================
Route::middleware(['auth:api', 'role:referee'])->prefix('v1')->group(function () {
    Route::controller(RefereeCheckinController::class)->group(function () {
        Route::post('camp/{campId}/checkin', 'checkin'); // done
        Route::get('my-checkins', 'getMyCheckins');
    });
});

// ==========================================
// PUBLIC ROUTES (No Auth Required)
// ==========================================
Route::prefix('v1')->group(function () {
    // Camp List & Details (Public)
    Route::get('camp/sports-type', [Campcontroller::class, 'getSportsType']);
    Route::get('camp/list', [Campcontroller::class, 'campList']);
    Route::get('camp/locations', [Campcontroller::class, 'getLocations']);
});
