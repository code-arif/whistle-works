<?php

use Illuminate\Support\Facades\Route;
use Modules\Director\Http\Controllers\Api\Camp\CampManageController;
use Modules\Director\Http\Controllers\Api\Camp\NoAuthCampController;
use Modules\Director\Http\Controllers\Api\Crew\CrewManageController;
use Modules\Director\Http\Controllers\Api\Schedule\ScheduleController;
use Modules\Director\Http\Controllers\Api\CourtAssign\CourtAssignController;
use Modules\Director\Http\Controllers\Api\Schedule\RefereeCheckinController;

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
    // Camp Management
    Route::group([], function () {
        Route::post('/camp/create', [CampManageController::class, 'createCamp']); // done
        Route::post('/camp/update/{id}', [CampManageController::class, 'updateCamp']); // done
        Route::post('/camp/status/{id}', [CampManageController::class, 'updateStatus']); // done
        Route::delete('/camp/delete/{id}', [CampManageController::class, 'deleteCamp']); // done
        Route::get('/director/camp/list', [CampManageController::class, 'directorCampList']); // done
    });

    // Crew Management
    Route::group([], function () {
        // CRUD Operations
        Route::post('/camp/{campId}/crew/create', [CrewManageController::class, 'createCrew']); // working
        Route::get('/camp/{campId}/crews', [CrewManageController::class, 'getCrews']); // working
        Route::get('/crew/{crewId}', [CrewManageController::class, 'getCrewDetails']); // working- crew details
        Route::put('/crew/{crewId}', [CrewManageController::class, 'updateCrew']); // working
        Route::delete('/crew/{crewId}', [CrewManageController::class, 'deleteCrew']); // working

        // Member Management
        Route::post('/crew/{crewId}/add-members', [CrewManageController::class, 'addMembers']);
        Route::delete('/crew/{crewId}/remove-members', [CrewManageController::class, 'removeMembers']);

        // Available Referees
        Route::get('/camp/{campId}/available-referees-for-crew', [CrewManageController::class, 'getAvailableReferees']);
        Route::get('/camp/{campId}/all-checked-in-referees', [CrewManageController::class, 'getAllCheckedInReferees']);
    });

    // Schedule Management
    Route::group([], function () {
        // Get camp date range before creating schedule
        Route::get('camp/{campId}/date-range', [ScheduleController::class, 'getCampDateRange']);

        // Create & View Schedule
        Route::post('camp/{campId}/schedule/create', [ScheduleController::class, 'createSchedule']); // working
        Route::get('camp/{campId}/schedule', [ScheduleController::class, 'getSchedule']); // working
        Route::delete('camp/{campId}/schedule', [ScheduleController::class, 'deleteSchedule']); // pending

        // Game Slots
        Route::get('camp/{campId}/schedule/game-slots', [ScheduleController::class, 'getGameSlots']); // working

        // Game slots management
        Route::get('camp/{campId}/schedule/game-slots', [ScheduleController::class, 'getGameSlots']); // working

        // Schedule Actions
        Route::post('camp/{campId}/schedule/publish', [ScheduleController::class, 'publishSchedule']); // working
        Route::post('camp/{campId}/schedule/clear', [ScheduleController::class, 'clearSchedule']);
    });

    // Court Assignment Management
    Route::prefix('director/court-assign')->group(function () {
        // Individual referee assignment
        Route::post('slot/{slotId}/assign-individual', [CourtAssignController::class, 'assignIndividualReferees']); // working - assign individual referees to a slot

        // Crew assignment
        Route::post('slot/{slotId}/assign-crew', [CourtAssignController::class, 'assignCrew']); // working - assign crew to a slot

        // Auto-assign all slots
        Route::post('camp/{campId}/auto-assign', [CourtAssignController::class, 'autoAssignReferees']); // problem

        // Remove assignment (crew or individual)
        Route::delete('assignment/{assignmentId}/remove', [CourtAssignController::class, 'removeAssignment']); // working - remove assignment by ID (crew or individual)

        // Clear game slot assignments for a camp
        Route::delete('/schedules/{scheduleId}/clear-assignments', [CourtAssignController::class, 'clearScheduleAssignments']); // working - clear all assignments for a schedule

        // Get slot assignments
        Route::get('slot/{slotId}/assignments', [CourtAssignController::class, 'getSlotAssignments']); // working - get all assignments for a slot

        // Get available referees
        Route::get('camp/{campId}/available-referees', [CourtAssignController::class, 'getAvailableReferees']); // working - get all available referees for the camp and slot assignments

        // Get assigned referees for a slot
        Route::get('slot/{slotId}/assigned-referees-crew', [CourtAssignController::class, 'getAssignedRefereesOrCrew']); // working - get all assigned referees for the slot

        // Court Block/Unblock
        Route::patch('slot/{slotId}/toggle-block', [CourtAssignController::class, 'toggleBlockSlot']); // working
    });
});

/**
 * globar camp details 
 */
Route::get('/camp/details/{id}', [CampManageController::class, 'campDetails'])->middleware('auth:api', 'role:director|referee|evaluator'); // done

// ==========================================
// REFEREE ROUTES (Auth Required)
// ==========================================
// Referee Routes - Protected by auth:api and role:referee
Route::middleware(['auth:api', 'role:referee'])->prefix('v1')->group(function () {
    Route::group(['prefix' => 'referee'], function () {

        // ONE-CLICK CHECK-IN (handles payment automatically)
        Route::post('/camp/{campId}/checkin', [RefereeCheckinController::class, 'checkin']);

        // Complete check-in after payment (called from frontend after Stripe redirect) // testing
        Route::post('/camp/complete-checkin', [RefereeCheckinController::class, 'completeCheckin']);

        // Get my check-ins
        Route::get('/camp/my-checkins', [RefereeCheckinController::class, 'getMyCheckins']);

        // Get previous/past camps only (ended camps)
        Route::get('/camp/previous-camps', [RefereeCheckinController::class, 'getPreviousCamps']);

        // Get active camps (ongoing + upcoming)
        Route::get('/camp/active-camps', [RefereeCheckinController::class, 'getActiveCamps']);
    });
});
