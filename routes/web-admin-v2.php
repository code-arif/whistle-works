<?php

use App\Http\Controllers\Admin\CmsAboutController;
use App\Http\Controllers\Admin\CmsHomeController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\PaymentMonitorController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TermsPrivacyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Web\Backend\V2\CampController;
use App\Http\Controllers\Web\Backend\V2\DashboardController;
use App\Http\Controllers\Web\Backend\V2\SportsTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Whistle-Works Admin V2 Routes (Inertia.js + Vue 3)
|--------------------------------------------------------------------------
|
| These routes handle the modernized V2 Admin Panel. All new features,
| dashboards, and components are incrementally built here without touching
| the existing live V1 Blade admin routes.
|
*/

// V2 Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('index');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/refresh', [DashboardController::class, 'refreshMetrics'])->name('dashboard.refresh');

// V2 Sports Types
Route::prefix('sports-types')->name('sports-types.')->group(function () {
    Route::get('/', [SportsTypeController::class, 'index'])->name('index');
    Route::post('/', [SportsTypeController::class, 'store'])->name('store');
    Route::post('/{id}', [SportsTypeController::class, 'update'])->name('update');
    Route::post('/{id}/status', [SportsTypeController::class, 'toggleStatus'])->name('status');
    Route::delete('/{id}', [SportsTypeController::class, 'destroy'])->name('destroy');
});

// V2 Camps Management
Route::prefix('camps')->name('camps.')->group(function () {
    Route::get('/', [CampController::class, 'index'])->name('index');
    Route::post('/', [CampController::class, 'store'])->name('store');
    Route::post('/{id}', [CampController::class, 'update'])->name('update');
    Route::post('/{id}/status', [CampController::class, 'toggleStatus'])->name('status');
    Route::delete('/{id}', [CampController::class, 'destroy'])->name('destroy');
});

// V2 Users Management
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/export', [UserController::class, 'export'])->name('export');
    Route::get('/{id}', [UserController::class, 'show'])->name('show');
    Route::post('/{id}/status', [UserController::class, 'toggleStatus'])->name('status');
    Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force-delete', [UserController::class, 'forceDelete'])->name('force-delete');
});

// V2 Payment Monitor
Route::prefix('monitor/payments')->name('monitor.payments.')->group(function () {
    Route::get('/', [PaymentMonitorController::class, 'index'])->name('index');
    Route::post('/refresh', [PaymentMonitorController::class, 'refresh'])->name('refresh');
});

// V2 Discount Coupons
Route::prefix('coupons')->name('coupons.')->group(function () {
    Route::get('/', [CouponController::class, 'index'])->name('index');
    Route::post('/', [CouponController::class, 'store'])->name('store');
    Route::post('/{id}', [CouponController::class, 'update'])->name('update');
    Route::post('/{id}/status', [CouponController::class, 'toggleStatus'])->name('status');
    Route::delete('/{id}', [CouponController::class, 'destroy'])->name('destroy');
});

// V2 Executive Settings Hub
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::post('/general', [SettingController::class, 'updateGeneral'])->name('general');
    Route::post('/stripe', [SettingController::class, 'updateStripe'])->name('stripe');
    Route::post('/mail', [SettingController::class, 'updateMail'])->name('mail');
    Route::post('/mail/test', [SettingController::class, 'sendTestMail'])->name('mail.test');
    Route::post('/integrations', [SettingController::class, 'updateIntegrations'])->name('integrations');
    Route::post('/system', [SettingController::class, 'updateSystem'])->name('system');
});

// V2 Admin Profile Settings
Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::post('/update', [ProfileController::class, 'updateProfile'])->name('update');
    Route::post('/password', [ProfileController::class, 'updatePassword'])->name('password');
    Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar');
});

// V2 Admin Terms & Privacy Management
Route::prefix('terms-privacy')->name('terms-privacy.')->group(function () {
    Route::get('/', [TermsPrivacyController::class, 'index'])->name('index');
    Route::post('/', [TermsPrivacyController::class, 'update'])->name('update');
});

// V2 Content Management System (CMS)
Route::prefix('cms')->name('cms.')->group(function () {
    // Home Page CMS
    Route::prefix('home')->name('home.')->group(function () {
        Route::get('/', [CmsHomeController::class, 'index'])->name('index');
        Route::post('/hero', [CmsHomeController::class, 'updateHero'])->name('hero');
        Route::post('/training-camp', [CmsHomeController::class, 'updateTrainingCamp'])->name('training-camp');
        Route::post('/operations', [CmsHomeController::class, 'updateOperations'])->name('operations');

        // Partners / Sliders
        Route::post('/partners/header', [CmsHomeController::class, 'updatePartnerHeader'])->name('partners.header');
        Route::post('/partners/slider', [CmsHomeController::class, 'storeSlider'])->name('partners.slider.store');
        Route::post('/partners/slider/{id}', [CmsHomeController::class, 'updateSlider'])->name('partners.slider.update');
        Route::post('/partners/slider/{id}/status', [CmsHomeController::class, 'toggleSliderStatus'])->name('partners.slider.status');
        Route::delete('/partners/slider/{id}', [CmsHomeController::class, 'destroySlider'])->name('partners.slider.destroy');

        // Features
        Route::post('/features/header', [CmsHomeController::class, 'updateFeatureHeader'])->name('features.header');
        Route::post('/features/card', [CmsHomeController::class, 'storeFeatureCard'])->name('features.card.store');
        Route::post('/features/card/{id}', [CmsHomeController::class, 'updateFeatureCard'])->name('features.card.update');
        Route::delete('/features/card/{id}', [CmsHomeController::class, 'destroyFeatureCard'])->name('features.card.destroy');

        // Testimonials
        Route::post('/testimonials/header', [CmsHomeController::class, 'updateTestimonialHeader'])->name('testimonials.header');
        Route::post('/testimonials/card', [CmsHomeController::class, 'storeTestimonialCard'])->name('testimonials.card.store');
        Route::post('/testimonials/card/{id}', [CmsHomeController::class, 'updateTestimonialCard'])->name('testimonials.card.update');
        Route::delete('/testimonials/card/{id}', [CmsHomeController::class, 'destroyTestimonialCard'])->name('testimonials.card.destroy');
    });

    // About Page CMS
    Route::prefix('about')->name('about.')->group(function () {
        Route::get('/', [CmsAboutController::class, 'index'])->name('index');
        Route::post('/page-title', [CmsAboutController::class, 'updatePageTitle'])->name('page-title');
        Route::post('/mission', [CmsAboutController::class, 'updateMission'])->name('mission');
        Route::post('/key-to-excellence', [CmsAboutController::class, 'updateKeyToExcellence'])->name('key-to-excellence');
        Route::post('/bottom-description', [CmsAboutController::class, 'updateBottomDescription'])->name('bottom-description');
        Route::post('/getting-started', [CmsAboutController::class, 'updateGettingStarted'])->name('getting-started');

        // Owner Info
        Route::post('/owner-info', [CmsAboutController::class, 'updateOwnerInfo'])->name('owner-info');

        // Feature Highlights
        Route::post('/feature-cards', [CmsAboutController::class, 'storeFeatureCard'])->name('feature-cards.store');
        Route::post('/feature-cards/{id}', [CmsAboutController::class, 'updateFeatureCard'])->name('feature-cards.update');
        Route::delete('/feature-cards/{id}', [CmsAboutController::class, 'destroyFeatureCard'])->name('feature-cards.destroy');

        // Team
        Route::post('/team/header', [CmsAboutController::class, 'updateTeamHeader'])->name('team.header');
        Route::post('/team/members', [CmsAboutController::class, 'storeTeamMember'])->name('team.members.store');
        Route::post('/team/members/{id}', [CmsAboutController::class, 'updateTeamMember'])->name('team.members.update');
        Route::delete('/team/members/{id}', [CmsAboutController::class, 'destroyTeamMember'])->name('team.members.destroy');
    });
});







