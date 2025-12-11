<?php

use Modules\Director\Models\Crew;
use Illuminate\Support\Facades\Route;
use Modules\Director\Http\Controllers\DirectorController;
use Modules\Director\Http\Controllers\Api\Camp\Campcontroller;
use Modules\Director\Http\Controllers\Api\Camp\NoAuthCampController;
use Modules\Director\Http\Controllers\Api\Crew\CrewManageController;
use Modules\Director\Http\Controllers\Api\Court\CourtManageController;
use Modules\Director\Http\Controllers\Api\Schedule\ScheduleController;
use Modules\Director\Http\Controllers\Api\Schedule\RefereeAssignController;
use Modules\Director\Http\Controllers\Api\CourtAssign\CourtAssignController;
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
        Route::get('camp/details/{id}', 'campDetails'); // for deployeing

        // Director camp list
        Route::get('/director/camp/list', 'directorCampList'); // done
    });
});


// ==========================================
// PUBLIC ROUTES (No Auth Required)
// ==========================================
Route::prefix('v1/camp')->group(function () {
    // Camp List & Details (Public)
    Route::get('/sports-type', [NoAuthCampController::class, 'getSportsType']);
    Route::get('/list', [NoAuthCampController::class, 'campList']);
    Route::get('/locations', [NoAuthCampController::class, 'getLocations']);
});


// ==========================================
// DIRECTOR ROUTES (Auth Required)
// ==========================================
Route::middleware(['auth:api', 'role:director'])->prefix('v1')->group(function () {

    // Crew Management
    Route::group([], function () {
        // CRUD Operations
        Route::post('/camp/{campId}/crew/create', [CrewManageController::class, 'createCrew']); // working
        Route::get('/camp/{campId}/crews', [CrewManageController::class, 'getCrews']); // working
        Route::get('/crew/{crewId}', [CrewManageController::class, 'getCrewDetails']); // working
        Route::put('/crew/{crewId}', [CrewManageController::class, 'updateCrew']); // working
        Route::delete('/crew/{crewId}', [CrewManageController::class, 'deleteCrew']); // working

        // Member Management
        Route::post('/crew/{crewId}/add-members', [CrewManageController::class, 'addMembers']);
        Route::delete('/crew/{crewId}/remove-members', [CrewManageController::class, 'removeMembers']);

        // Available Referees
        Route::get('/camp/{campId}/available-referees-for-crew', [CrewManageController::class, 'getAvailableReferees']);
        Route::get('/camp/{campId}/all-checked-in-referees', [CrewManageController::class, 'getAllCheckedInReferees']);

        // Crew Assignment to Game Slots
        // Route::post('game-slot/{gameSlotId}/assign-crew', [CrewManageController::class, 'assignCrewToSlot']);
        // Route::delete('game-slot/{gameSlotId}/remove-crew', [CrewManageController::class, 'removeCrewFromSlot']);
    });

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

    // Referee Check-in Management (Director View)
    Route::controller(RefereeCheckinController::class)->group(function () {
        Route::get('camp/{campId}/checked-in-referees', 'getCheckedInReferees');
        Route::post('camp/{campId}/bulk-checkin', 'bulkCheckin');
    });

    // Referee Assignment Management
    Route::controller(RefereeAssignController::class)->group(function () {
        // Slot-specific assignment
        Route::post('game-slot/{slotId}/assign-referee', 'assignReferees'); // working
        Route::delete('game-slot/{assignmentId}/remove-referee', 'removeReferee');

        // Camp-wide referees
        Route::get('camp/{campId}/available-referees', 'getAvailableReferees'); // working
        Route::get('game-slot/{slotId}/assigned-referees', 'getAssignedReferees'); // working
    });

    Route::post('/game-slot/{slotId}/block-unblock', [CourtManageController::class, 'blockUnblockGameSlot']); // working


    // Court Assignment Management
    // routes/api.php or module routes file

    Route::prefix('director/court-assign')->group(function () {
        // Individual referee assignment
        Route::post('slot/{slotId}/assign-individual', [CourtAssignController::class, 'assignIndividualReferees']);

        // Crew assignment
        Route::post('slot/{slotId}/assign-crew', [CourtAssignController::class, 'assignCrew']);

        // Auto-assign all slots
        Route::post('camp/{campId}/auto-assign', [CourtAssignController::class, 'autoAssignReferees']);

        // Remove assignment (crew or individual)
        Route::delete('assignment/{assignmentId}/remove', [CourtAssignController::class, 'removeAssignment']);

        // Get slot assignments
        Route::get('slot/{slotId}/assignments', [CourtAssignController::class, 'getSlotAssignments']);

        // Get available referees
        Route::get('camp/{campId}/available-referees', [CourtAssignController::class, 'getAvailableReferees']);
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
