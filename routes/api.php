<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\FirebaseTokenController;
use App\Http\Controllers\Api\Auth\SocialLoginController;
use App\Http\Controllers\Api\Frontend\ContactController;
use App\Http\Controllers\Api\Frontend\SettingsController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Frontend\AnnouncementController;
use App\Http\Controllers\Api\Frontend\CMS\HomePageController;
use App\Http\Controllers\Api\Frontend\PrivecyPolicyController;
use App\Http\Controllers\Api\Frontend\Roster\RosterController;
use App\Http\Controllers\Api\Frontend\RefereeEvaluationController;
use App\Http\Controllers\Api\Frontend\Evaluator\EvaluatorController;
use App\Http\Controllers\Api\Frontend\Evaluator\GameOverviewController;
use App\Http\Controllers\Api\Frontend\Referee\RefereeAssignmentController;

// health check
Route::get('/health-check', function () {
    return "All Right... 👍";
});


/*
|--------------------------------------------------------------------------
| User Authentication Routes
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => 'guest:api'], function ($router) {
    //register
    Route::post('/register', [RegisterController::class, 'register']); // done
    Route::post('/verify-email', [RegisterController::class, 'VerifyEmail']); // done
    Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']); // done
    Route::post('/verify-otp', [RegisterController::class, 'VerifyEmail']); // working

    //login
    Route::post('/login', [LoginController::class, 'login'])->name('api.login'); // done

    //forgot password
    Route::post('/forgot-password', [ResetPasswordController::class, 'forgotPassword']); // done
    Route::post('/forgot-password/resend-otp', [ResetPasswordController::class, 'resendOtp']); // done
    Route::post('/otp-token', [ResetPasswordController::class, 'MakeOtpToken']); // done
    Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']); // done

    //social login
    Route::post('/social-login', [SocialLoginController::class, 'SocialLogin']);
});

/*
|--------------------------------------------------------------------------
| User Profile and After Auth Route
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:api', 'api-otp']], function ($router) {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']); // done
    Route::get('/user-details', [UserController::class, 'me']); // done
    Route::post('/update-profile', [UserController::class, 'updateProfile']); // done
    Route::post('/update-avatar', [UserController::class, 'updateAvatar']); // done
    Route::delete('/delete-profile', [UserController::class, 'destroy']); // done
    Route::post('/change-password', [UserController::class, 'changePassword']); // done
});

/*
|--------------------------------------------------------------------------
| Referee Evaluation API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:director|evaluator,api'])->group(function () {

    Route::prefix('referee/evaluation')->group(function () {
        Route::post('/store', [RefereeEvaluationController::class, 'store']); // working - store evaluation
        Route::post('/update/{id}', [RefereeEvaluationController::class, 'update']); // working - update evaluation
        Route::delete('/destroy/{id}', [RefereeEvaluationController::class, 'destroy']); // working - delete evaluation
        Route::get('/my-evaluations', [RefereeEvaluationController::class, 'getMyEvaluations']); // working - get my evaluations (edit required for permission director)
        Route::get('/camp/{campId}', [RefereeEvaluationController::class, 'getEvaluationsByCamp']); // working - get evaluations by camp
        Route::get('/details/{evaluationId}', [RefereeEvaluationController::class, 'show']); // working - get evaluation details

        // Get referee statistics
        Route::get('/{refereeId}/stats', [RefereeEvaluationController::class, 'getRefereeStats']);

        Route::get('/camp/{campId}/all-registered-in-referees', [RefereeEvaluationController::class, 'getAllRegisteredInReferees']); // change the route name;
    });

    // Roster - Get all information of a camp
    Route::get('/roster/camp/details/{campId}', [RosterController::class, 'campDetails']);

    // Game overview
    Route::get('/evaluator/{campId}/game-overview', [GameOverviewController::class,'gameOverview']); 
});

/*
|--------------------------------------------------------------------------
| Referee API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:referee'])->group(function () {
    // Get my evaluations (for logged-in referee)
    Route::get('/referee/my-evaluations', [RefereeEvaluationController::class, 'getRefereeEvaluations']); // working - get my evaluations

    Route::prefix('referee')->group(function () {
        // Get all game slots where referee is assigned
        Route::get('/my-assigned-slots', [RefereeAssignmentController::class, 'getMyAssignedSlots']);

        // Get specific game slot details with all assigned referees
        Route::get('/game-slot/{gameSlotId}', [RefereeAssignmentController::class, 'getGameSlotDetails']);

        // Get upcoming game slots only
        Route::get('/upcoming-slots', [RefereeAssignmentController::class, 'getUpcomingSlots']);
    });
});

/*
|--------------------------------------------------------------------------
| Evaluator API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:evaluator'])->group(function () {
    Route::prefix('/evaluator')->group(function () {
        Route::get('/active-camps', [EvaluatorController::class, 'getActiveCamps']);
    });
});

// contact from submit
Route::post('/contact-form', [ContactController::class, 'submitContact']);

// get home page cms data
Route::get('/cms/home', [HomePageController::class, 'home']);

// get privacy policy data
Route::get('/privacy-policy', [PrivecyPolicyController::class, 'index']);

// get setting data
Route::get('/settings', [SettingsController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Announcement making route
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->group(function () {
    // Making announcement only for director
    Route::post('/director/announcements', [AnnouncementController::class, 'store'])
        ->middleware('role:director');
    Route::delete('/announcements/delete/{id}', [AnnouncementController::class, 'destroy'])->middleware('role:director');

    // Announcement gat route for referee and evaluator
    Route::get('/announcement/my-announcements', [AnnouncementController::class, 'myAnnouncements'])->middleware('role:referee|evaluator|director,api');
    Route::patch('/announcements/{id}/read', [AnnouncementController::class, 'markAsRead'])->middleware('role:referee|evaluator|director,api');
    Route::patch('/announcements/mark-all-read', [AnnouncementController::class, 'markAllAsRead'])->middleware('role:referee|evaluator|director,api');
});

/*
|--------------------------------------------------------------------------
| Sinle chatting Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->controller(ChatController::class)->prefix('auth/chat')->group(function () {
    Route::get('/list', 'list'); // working
    Route::post('/send/{receiver_id}', 'send'); // working
    Route::get('/conversation/{receiver_id}', 'conversation'); // working
    Route::get('room/{receiver_id}', 'room');
    Route::get('/search', 'search'); // working
    Route::get('/seen/all/{receiver_id}', 'seenAll'); // working
    Route::get('/seen/single/{chat_id}', 'seenSingle'); // working
    Route::delete('/delete/{receiver_id}', 'deleteChat'); // working
    Route::delete('/delete/chat/messages', 'deleteMessages'); // working
});

/*
# Firebase Notification Route
*/
Route::middleware(['auth:api'])->controller(FirebaseTokenController::class)->prefix('firebase')->group(function () {
    Route::get("test", "test");
    Route::post("token/add", "store");
    Route::post("token/get", "getToken");
    Route::post("token/delete", "deleteToken");
});
