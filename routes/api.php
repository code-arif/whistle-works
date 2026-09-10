<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TwilioTestController;
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
use App\Http\Controllers\Api\Auth\V2\V2RegisterController;
use App\Http\Controllers\Api\Frontend\AnnouncementController;
use App\Http\Controllers\Api\Frontend\CMS\HomePageController;
use App\Http\Controllers\Api\Frontend\NotificationController;
use App\Http\Controllers\Api\Frontend\CMS\AboutPageController;
use App\Http\Controllers\Api\Frontend\PrivecyPolicyController;
use App\Http\Controllers\Api\Frontend\Roster\RosterController;
use App\Http\Controllers\Api\Auth\V2\V2ResetPasswordController;
use App\Http\Controllers\Api\Frontend\RefereeEvaluationController;
use App\Http\Controllers\Api\Frontend\Evaluator\EvaluatorController;
use App\Http\Controllers\Api\Frontend\Evaluator\GameOverviewController;
use App\Http\Controllers\Api\Frontend\Referee\EvaluatedRefereeController;
use App\Http\Controllers\Api\Frontend\Referee\RefereeAssignmentController;
use App\Http\Controllers\Api\Frontend\Referee\RefereeAssignmentCrewController;
use App\Http\Controllers\Api\Frontend\CampRanking\CampRankingSettingsController;
use App\Http\Controllers\Api\Frontend\Referee\RefereeDetailsController;
use Modules\Director\Http\Controllers\Api\Crew\CrewManageController;
use App\Http\Controllers\Api\Frontend\Evaluator\CampEvaluatorRegistrationController;
use App\Http\Controllers\Api\Frontend\Evaluator\CampEvaluatorRegisterManageForDirectorController;
use App\Http\Controllers\Api\Frontend\DirectorCampManage\AssistantDirectorPermissionController;
use App\Http\Controllers\Api\StripeWebhookController;

/*
|--------------------------------------------------------------------------
| Stripe Webhook Route
|--------------------------------------------------------------------------
*/
Route::post('/webhook/stripe', [StripeWebhookController::class, 'HandlePaymentWebhook']);

/*
|--------------------------------------------------------------------------
| System & Health Routes
|--------------------------------------------------------------------------
*/

Route::get('/health-check', function () {
    return "All Right... 👍";
});

/*
|--------------------------------------------------------------------------
| Public Frontend Data Routes (CMS, Settings, Policies, Contact)
|--------------------------------------------------------------------------
*/
// get home page cms data
Route::get('/cms/home', [HomePageController::class, 'home']);
Route::get('/cms/about', [AboutPageController::class, 'about']);

// get privacy policy data
Route::get('/privacy-policy', [PrivecyPolicyController::class, 'privecyPolicy']);
Route::get('/terms-and-conditions', [PrivecyPolicyController::class, 'termsAndConditions']);

// get setting data
Route::get('/settings', [SettingsController::class, 'index']);

// contact from submit
Route::post('/contact-form', [ContactController::class, 'submitContact']);

/*
|--------------------------------------------------------------------------
| User Authentication Routes (V1)
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => 'guest:api'], function ($router) {
    // Registration
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/verify-email', [RegisterController::class, 'VerifyEmail']);
    Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']);

    // Login
    Route::post('/login', [LoginController::class, 'login'])->name('api.login');
    Route::post('/social-login', [SocialLoginController::class, 'SocialLogin']);

    // Forgot & Reset Password
    Route::post('/forgot-password', [ResetPasswordController::class, 'forgotPassword']);
    Route::post('/forgot-password/resend-otp', [ResetPasswordController::class, 'resendOtp']);
    Route::post('/otp-token', [ResetPasswordController::class, 'MakeOtpToken']);
    Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);
});

/*
|--------------------------------------------------------------------------
| User Authentication Routes (V2)
|--------------------------------------------------------------------------
*/
Route::prefix('v2')->group(function () {
    Route::group(['middleware' => 'guest:api'], function () {
        // Registration with Email Verification Token
        Route::post('/register', [V2RegisterController::class, 'register']);
        Route::post('/verify-email', [V2RegisterController::class, 'verifyEmail']);
        Route::post('/resend-verification', [V2RegisterController::class, 'resendVerification']);

        // Login (Re-using V1 controller)
        Route::post('/login', [LoginController::class, 'login']);

        // Password Reset with Token
        Route::post('/forgot-password', [V2ResetPasswordController::class, 'forgotPassword']);
        Route::post('/resend-reset-link', [V2ResetPasswordController::class, 'resendResetLink']);
        Route::post('/verify-reset-token', [V2ResetPasswordController::class, 'verifyResetToken']);
        Route::post('/reset-password', [V2ResetPasswordController::class, 'resetPassword']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated User Profile Routes
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:api', 'api-otp']], function ($router) {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/user-details', [UserController::class, 'me']);
    Route::post('/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/update-avatar', [UserController::class, 'updateAvatar']);
    Route::delete('/delete-profile', [UserController::class, 'destroy']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Roster & General Camp Detail Routes (Director/Referee/Evaluator)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'role:director|evaluator|referee,api'])->group(function () {
    Route::prefix('roster/evaluator-registrations')->group(function () {
        // global (with optional status filter)
        Route::get('/camp/{campId}', [CampEvaluatorRegisterManageForDirectorController::class, 'index']);

        // specific status APIs
        Route::get('/camp/{campId}/approved', [CampEvaluatorRegisterManageForDirectorController::class, 'approved']);
        Route::get('/camp/{campId}/pending', [CampEvaluatorRegisterManageForDirectorController::class, 'pending']);
        Route::get('/camp/{campId}/rejected', [CampEvaluatorRegisterManageForDirectorController::class, 'rejected']);
    });

    // Get sports type
    Route::get('/referee/evaluation/recommended-highest-level', [RefereeEvaluationController::class, 'getSportTypes']);

    Route::get('/roster/camp/details/{campId}', [RosterController::class, 'campDetails']); // Roster camp details
    Route::get('/camp/{campId}/referee/{refereeId}/history', [RefereeEvaluationController::class, 'getRefereeEvaluationHistory']); // Referee history
    Route::get('/referee/evaluation/camp/{campId}', [RefereeEvaluationController::class, 'getEvaluationsByCamp']); // Referee evaluation history
    Route::get('/referee-details/{campId}/{refereeId}', [RefereeDetailsController::class, 'getRefereeDetails']); // Referee details (profile, evaluations, assigned slots)
});

/*
|--------------------------------------------------------------------------
| Referee Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'role:referee'])->group(function () {
    // Get my evaluations (for logged-in referee)
    Route::get('/referee/my-evaluations/{campId}', [EvaluatedRefereeController::class, 'getRefereeEvaluations']);

    Route::prefix('referee')->group(function () {
        // Assignments & Slots
        Route::get('/my-assigned-slots', [RefereeAssignmentController::class, 'getMyAssignedSlots']);
        Route::get('/camp/{campId}/assigned-slots', [RefereeAssignmentController::class, 'getCampAssignedSlots']);
        Route::get('/game-slot/{gameSlotId}', [RefereeAssignmentController::class, 'getGameSlotDetails']);
        Route::get('/upcoming-slots', [RefereeAssignmentController::class, 'getUpcomingSlots']);

        // Crews
        Route::get('/my-crews', [RefereeAssignmentCrewController::class, 'getMyCrews']);
        Route::get('/camp/{campId}/crews', [RefereeAssignmentCrewController::class, 'getCampCrews']);
        Route::get('/crew/{crewId}/details', [RefereeAssignmentCrewController::class, 'getCrewDetails']);
        Route::get('/my-crews/upcoming-games', [RefereeAssignmentCrewController::class, 'getMyCrewUpcomingGames']);
    });
});

/*
|--------------------------------------------------------------------------
| Evaluator Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:evaluator'])->group(function () {
    Route::prefix('/evaluator')->group(function () {
        Route::get('/active-camps', [EvaluatorController::class, 'getActiveCamps']);
    });
});

Route::middleware(['auth:api', 'role:evaluator,api'])->group(function () {
    // Evaluator registers for camps
    Route::prefix('evaluator/camp-registration')->group(function () {
        Route::post('/register', [CampEvaluatorRegistrationController::class, 'register']);
        Route::get('/my-registrations', [CampEvaluatorRegistrationController::class, 'myRegistrations']);
        Route::delete('/cancel/{registrationId}', [CampEvaluatorRegistrationController::class, 'cancel']);
        Route::get('/previous-camps', [CampEvaluatorRegistrationController::class, 'previousCamp']);
    });
});

/*
|--------------------------------------------------------------------------
| Director & Evaluator (Referee Evaluation) Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:director|evaluator,api'])->group(function () {
    Route::prefix('referee/evaluation')->group(function () {
        Route::post('/upsert', [RefereeEvaluationController::class, 'storeOrUpdate']);
        Route::delete('/destroy/{id}', [RefereeEvaluationController::class, 'destroy']);
        Route::get('/my-evaluations/{campId}', [RefereeEvaluationController::class, 'getMyEvaluations']);
        // Route::get('/camp/{campId}', [RefereeEvaluationController::class, 'getEvaluationsByCamp']);
        Route::get('/details/{evaluationId}', [RefereeEvaluationController::class, 'show']);

        // Get referee statistics
        Route::get('/{refereeId}/stats', [RefereeEvaluationController::class, 'getRefereeStats']);

        // Get all registered referee
        Route::get('/camp/{campId}/all-registered-in-referees', [RefereeEvaluationController::class, 'getAllRegisteredInReferees']);
        Route::get('/camp/{campId}/all-referees', [RefereeEvaluationController::class, 'getAllReferees']);
    });

    // Game overview
    Route::get('/evaluator/{campId}/game-overview', [GameOverviewController::class, 'gameOverview']);

});

/*
|--------------------------------------------------------------------------
| Director Routes (Management, Approvals, Announcements)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:director,api'])->group(function () {
    // Export Roster Camp Details CSV
    Route::get('/roster/export/camp/{campId}', [RosterController::class, 'exportCampDetailsCsv']);
    Route::get('/roster/export/referees/camp/{campId}', [RosterController::class, 'exportCampReferees']);

    // Export Referee Evaluations CSV/Excel
    Route::get('/referee/evaluation/export/camp/{campId}', [RefereeEvaluationController::class, 'exportEvaluationsByCamp']);

    // Export Evaluator Registrations CSV/Excel
    Route::get('/evaluator-registrations/export/camp/{campId}', [CampEvaluatorRegisterManageForDirectorController::class, 'exportEvaluatorRegistrations']);

    // Export Crews CSV/Excel
    Route::get('/crews/export/camp/{campId}', [CrewManageController::class, 'exportCampCrews']);

    // Assistant Director Assign with permission
    Route::get('/assistant-director/list', [AssistantDirectorPermissionController::class, 'assistantDirectorList']);
    Route::post('/assistant-director-permissions/assign', [AssistantDirectorPermissionController::class, 'storeOrUpdate']);
    Route::get('/assistant-director/camp/list', [AssistantDirectorPermissionController::class, 'assistantDirectorCampList']);
    Route::delete('/assistant-director/camp/permission/remove', [AssistantDirectorPermissionController::class, 'assistantDirectorCampPermissionRemove']);

    // Manage Evaluator Registrations
    Route::prefix('director/evaluator-registrations')->group(function () {
        Route::get('/camp/{campId}', [CampEvaluatorRegisterManageForDirectorController::class, 'getCampRegistrations']);
        Route::post('/approve/{registrationId}', [CampEvaluatorRegisterManageForDirectorController::class, 'approve']);
        Route::post('/reject/{registrationId}', [CampEvaluatorRegisterManageForDirectorController::class, 'reject']);
        Route::delete('/remove/{registrationId}', [CampEvaluatorRegisterManageForDirectorController::class, 'removeEvaluator']);
    });

    // Camp Ranking Settings
    Route::prefix('camp/{campId}/ranking-settings')->group(function () {
        Route::get('/', [CampRankingSettingsController::class, 'getRankingSettings']);
        Route::put('/', [CampRankingSettingsController::class, 'updateRankingSettings']);
        Route::put('/evaluator/{evaluatorId}/toggle-permission', [CampRankingSettingsController::class, 'toggleEvaluatorPermission']);
    });
});

Route::middleware(['auth:api', 'role:director,api'])->group(function () {
    // Making announcement only for director
    Route::post('/director/announcement/store', [AnnouncementController::class, 'store']);
    Route::delete('/director/announcements/delete/{id}', [AnnouncementController::class, 'destroy']);
    Route::get('/director/announcements/my', [AnnouncementController::class, 'myAnnouncements']);
});

/*
|--------------------------------------------------------------------------
| Unified Notifications Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:referee|evaluator|director,api'])->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread', [NotificationController::class, 'unread']);
    Route::get('/counts', [NotificationController::class, 'counts']);
    Route::get('/{notificationId}', [NotificationController::class, 'show']);
    Route::post('/{notificationId}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/delete/{notificationId}', [NotificationController::class, 'destroy']);
    Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
});

/*
|--------------------------------------------------------------------------
| Chatting Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->prefix('auth/chat')->group(function () {
    Route::get('/list', [ChatController::class, 'list']);
    Route::post('/send/{receiver_id}', [ChatController::class, 'send']);
    Route::get('/conversation/{receiver_id}', [ChatController::class, 'conversation']);
    Route::get('room/{receiver_id}', [ChatController::class, 'room']);
    Route::get('/search', [ChatController::class, 'search']);
    Route::get('/seen/all/{receiver_id}', [ChatController::class, 'seenAll']);
    Route::get('/seen/single/{chat_id}', [ChatController::class, 'seenSingle']);
    Route::delete('/delete/{receiver_id}', [ChatController::class, 'deleteChat']);
    Route::delete('/delete/chat/messages', [ChatController::class, 'deleteMessages']);
});

/*
|--------------------------------------------------------------------------
| Firebase Notification Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->prefix('firebase')->group(function () {
    Route::get("test", [FirebaseTokenController::class, 'test']);
    Route::post("token/add", [FirebaseTokenController::class, 'store']);
    Route::post("token/get", [FirebaseTokenController::class, 'getToken']);
    Route::post("token/delete", [FirebaseTokenController::class, 'deleteToken']);
});

/*
|--------------------------------------------------------------------------
| Twilio Test Route
|--------------------------------------------------------------------------
*/
Route::post('/twilio-test', [TwilioTestController::class, 'sendTestSms']);
