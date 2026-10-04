<?php

use \Modules\Director\app\Http\Controllers\Api\Ai\AiChatController;
use Illuminate\Support\Facades\Route;
use Modules\Director\Http\Controllers\Api\Camp\CampManageController;
use Modules\Director\Http\Controllers\Api\Camp\NoAuthCampController;
use Modules\Director\Http\Controllers\Api\Court\CourtManageController;
use Modules\Director\Http\Controllers\Api\CourtAssign\AutoCourtAssignController;
use Modules\Director\Http\Controllers\Api\CourtAssign\CourtAssignController;
use Modules\Director\Http\Controllers\Api\Crew\CrewManageController;
use Modules\Director\Http\Controllers\Api\Payment\StripeConnectController;
use Modules\Director\Http\Controllers\Api\Referee\RefereeManageController;
use Modules\Director\Http\Controllers\Api\Schedule\RefereeCheckinController;
use Modules\Director\Http\Controllers\Api\Schedule\RefereeCheckinControllerV2;
use Modules\Director\Http\Controllers\Api\Schedule\ScheduleController;


/*
|--------------------------------------------------------------------------
| Public Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/camp')->group(function () {
    // Camp List & Details
    Route::get('/sports-type', [NoAuthCampController::class, 'getSportsType']);
    Route::get('/list', [NoAuthCampController::class, 'campList']);
    Route::get('/locations', [NoAuthCampController::class, 'getLocations']);
});

// No auth camp details
Route::get('no-auth/camp/details/{id}', [CampManageController::class, 'noAuthCampDetails']);

/*
|--------------------------------------------------------------------------
| Global Camp Details (Mixed Auth)
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => 'auth:api', 'role:director|referee|evaluator,api'], function () {
    Route::get('v1/camp/details/{id}', [CampManageController::class, 'campDetails']);
    Route::get('v1/camp/edit/{id}', [CampManageController::class, 'campEdit']);
});

/*
|--------------------------------------------------------------------------
| Crew Routes (Auth Required)
|--------------------------------------------------------------------------
*/
Route::get('v1/crew/{crewId}', [CrewManageController::class, 'getCrewDetails'])->middleware('auth:api');

/*
|--------------------------------------------------------------------------
| Director Routes (Auth Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:director'])->prefix('v1')->group(function () {

    // Camp Management
    Route::group([], function () {
        Route::post('/camp/create', [CampManageController::class, 'createCamp']);
        Route::post('/camp/update/{id}', [CampManageController::class, 'updateCamp']);
        Route::get('/timezones/available', [CampManageController::class, 'getAvailableTimezones']);
        Route::post('/camp/status/{id}', [CampManageController::class, 'updateStatus']);
        Route::delete('/camp/delete/{id}', [CampManageController::class, 'deleteCamp']);
        Route::get('/director/camp/list', [CampManageController::class, 'directorCampList']);
        Route::get('/camp/admin/fee', [CampManageController::class, 'getAdminFee']);
    });

    // Crew Management
    Route::group([], function () {
        // CRUD Operations
        Route::post('/camp/{campId}/crew/create', [CrewManageController::class, 'createCrew']);
        Route::get('/camp/{campId}/crews', [CrewManageController::class, 'getCrews']);
        Route::get('/crew/{crewId}', [CrewManageController::class, 'getCrewDetails']);
        Route::post('/crew/update/{crewId}', [CrewManageController::class, 'updateCrew']);
        Route::delete('/crew/delete/{crewId}', [CrewManageController::class, 'deleteCrew']);

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
        Route::post('camp/{campId}/schedule/create', [ScheduleController::class, 'createSchedule']);
        Route::get('camp/{campId}/schedule', [ScheduleController::class, 'getSchedule']);
        Route::delete('camp/{campId}/schedule', [ScheduleController::class, 'deleteSchedule']);

        // Game Slots
        Route::get('camp/{campId}/schedule/game-slots', [ScheduleController::class, 'getGameSlots']);

        // Schedule Actions
        Route::post('camp/{campId}/schedule/publish', [ScheduleController::class, 'publishSchedule']);
        Route::post('camp/{campId}/schedule/clear', [ScheduleController::class, 'clearSchedule']);
    });

    // Court Assignment Management
    Route::prefix('director/court-assign')->group(function () {
        // Individual referee assignment
        Route::post('slot/{slotId}/assign-individual', [CourtAssignController::class, 'assignIndividualReferees']);

        // Crew assignment
        Route::post('slot/{slotId}/assign-crew', [CourtAssignController::class, 'assignCrew']);

        // Auto-assign all slots
        Route::post('camp/{campId}/auto-assign', [AutoCourtAssignController::class, 'autoAssignReferees']);

        // Remove assignment (crew or individual)
        Route::delete('assignment/{assignmentId}/remove', [CourtAssignController::class, 'removeAssignment']);

        // Clear game slot assignments for a camp
        Route::delete('/schedules/{scheduleId}/clear-assignments', [CourtAssignController::class, 'clearScheduleAssignments']);

        // Get slot assignments
        Route::get('slot/{slotId}/assignments', [CourtAssignController::class, 'getSlotAssignments']);

        // Get available referees
        Route::get('camp/{campId}/available-referees', [CourtAssignController::class, 'getAvailableReferees']);

        // Get assigned referees for a slot
        Route::get('slot/{slotId}/assigned-referees-crew', [CourtAssignController::class, 'getAssignedRefereesOrCrew']);

        Route::get('slot/{slotId}/available-referees-with-time-check', [CourtAssignController::class, 'getAvailableRefereesForSlot']);
        Route::get('slot/{slotId}/available-crew-with-time-check', [CourtAssignController::class, 'getAvailableCrewsForSlot']);

        // Switch game slot mode (crew or individual)
        Route::post('slot/{slotId}/switch-mode', [CourtAssignController::class, 'switchMode']);

        // Bulk switch game slots mode (for schedule or camp)
        Route::post('camp/{campId}/schedule/{scheduleId}/bulk-switch-mode', [CourtAssignController::class, 'bulkSwitchMode']);
    });

    // Court Management
    Route::patch('slot/{slotId}/toggle-block', [CourtManageController::class, 'toggleBlockSlot']);
    Route::put('location/{locationId}/court/{courtNumber}/update-name', [CourtManageController::class, 'updateCourtName']);
    Route::post('location/{locationId}/courts/bulk-update-names', [CourtManageController::class, 'bulkUpdateCourtNames']);
    Route::get('location/{locationId}/court-names', [CourtManageController::class, 'getCourtNames']);
    Route::post('schedule/{scheduleId}/bulk-toggle-block', [CourtManageController::class, 'bulkToggleBlockByTime']);

    // Referee Management
    Route::prefix('director')->group(function () {
        // Manual check-in (single referee)
        Route::put('camp/{campId}/referee/{refereeId}/manual-checkin', [RefereeManageController::class, 'directorManualCheckInReferee']);

        // Bulk manual check-in (optional - multiple referees at once)
        Route::post('camp/{campId}/bulk-manual-checkin', [RefereeManageController::class, 'bulkManualCheckIn']);

        // Input jurcy number for a referee
        Route::patch('camp/{campId}/referee/{refereeId}/update-jourcy-number', [RefereeManageController::class, 'updateRefereeJourcyNumber']);

        // Remote referee from camp
        Route::delete('camps/{campId}/referees/{refereeId}', [RefereeManageController::class, 'removeRefereeFromCamp']);

        // Stripe Connect onboarding
        Route::post('stripe/connect', [StripeConnectController::class, 'connect']);
        Route::get('stripe/status', [StripeConnectController::class, 'status']);
        Route::get('stripe/dashboard', [StripeConnectController::class, 'dashboard']);

        // Get previous camps
        Route::get('/camp/previous-camps', [RefereeCheckinController::class, 'getDirectorPreviousCamps']);

        // AI Coach & Analytics Engine
        Route::group(['prefix' => 'ai', 'middleware' => ['ai.quota']], function () {
            Route::post('/chat', [AiChatController::class, 'chat']);
            Route::post('/session/reset', [AiChatController::class, 'resetSession']);
            Route::get('/quota', [AiChatController::class, 'getQuota']);
            Route::get('/session/{session_uuid}/history', [AiChatController::class, 'getSessionHistory']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Referee Routes (Auth Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:referee'])->prefix('v1')->group(function () {
    Route::group(['prefix' => 'referee'], function () {
        // Register for camp (Payment = Registration)
        Route::post('/camp/{campId}/register', [RefereeCheckinController::class, 'registerForCamp']);

        // Handle payment success callback (auto-registration)
        Route::post('/camp/payment-success', [RefereeCheckinController::class, 'handlePaymentSuccess']);

        // Check-in to camp (physical attendance)
        Route::post('/camp/{campId}/checkin', [RefereeCheckinController::class, 'checkIn']);

        // Get my registrations
        Route::get('/camp/my-registrations', [RefereeCheckinController::class, 'getMyRegistrations']);

        // Get my checkins camp list
        Route::get('/camp/my-checkins', [RefereeCheckinController::class, 'getMyCheckins']);

        // Get active camps
        Route::get('/camp/active-camps', [RefereeCheckinController::class, 'getActiveCamps']);

        // Get previous camps
        Route::get('/camp/previous-camps', [RefereeCheckinController::class, 'getPreviousCamps']);
    });
});

Route::middleware(['auth:api', 'role:referee'])->prefix('v2')->group(function () {
    Route::group(['prefix' => 'referee'], function () {
        // Get camp pricing with sports fee breakdown
        Route::get('/camp/{campId}/pricing', [RefereeCheckinControllerV2::class, 'getCampPricing']);

        // Validate coupon code before registration
        Route::post('/camp/{campId}/validate-coupon', [RefereeCheckinControllerV2::class, 'validateCoupon']);

        // Register for camp V2 (Payment = Registration) with Coupon + Sports Fee support
        Route::post('/camp/{campId}/register', [RefereeCheckinControllerV2::class, 'registerForCamp']);
    });
});
