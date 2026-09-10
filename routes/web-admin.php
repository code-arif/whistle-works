<?php

use App\Http\Controllers\Web\Backend\Access\PermissionController;
use App\Http\Controllers\Web\Backend\Access\RoleController;
use App\Http\Controllers\Web\Backend\Access\UserController;
use App\Http\Controllers\Web\Backend\Camp\CampController;
use App\Http\Controllers\Web\Backend\CMS\AboutPageController;
use App\Http\Controllers\Web\Backend\CMS\AboutPageOurTeamController;
use App\Http\Controllers\Web\Backend\CMS\FeaturesController;
use App\Http\Controllers\Web\Backend\CMS\GettingStartedController;
use App\Http\Controllers\Web\Backend\CMS\HomePageController;
use App\Http\Controllers\Web\Backend\CMS\SliderController;
use App\Http\Controllers\Web\Backend\CMS\TestimonialController;
use App\Http\Controllers\Web\Backend\CMS\Web\PrivacyTerms\PrivacAndTermsController;
use App\Http\Controllers\Web\Backend\ContactController;
use App\Http\Controllers\Web\Backend\Coupon\CouponController;
use App\Http\Controllers\Web\Backend\DashboardController;
use App\Http\Controllers\Web\Backend\Monitor\PaymentMonitorController;
use App\Http\Controllers\Web\Backend\FaqController;
use App\Http\Controllers\Web\Backend\LivewireController;
use App\Http\Controllers\Web\Backend\Settings\CaptchaController;
use App\Http\Controllers\Web\Backend\Settings\EnvController;
use App\Http\Controllers\Web\Backend\Settings\FirebaseController;
use App\Http\Controllers\Web\Backend\Settings\GoogleMapController;
use App\Http\Controllers\Web\Backend\Settings\LogoController;
use App\Http\Controllers\Web\Backend\Settings\MailSettingController;
use App\Http\Controllers\Web\Backend\Settings\OtherController;
use App\Http\Controllers\Web\Backend\Settings\ProfileController;
use App\Http\Controllers\Web\Backend\Settings\SettingController;
use App\Http\Controllers\Web\Backend\Settings\SignatureController;
use App\Http\Controllers\Web\Backend\Settings\SocialController;
use App\Http\Controllers\Web\Backend\Settings\StripeController;
use App\Http\Controllers\Web\Backend\SportsType\SportsTypeController;
use App\Http\Controllers\Web\Backend\SubscriberController;
use App\Http\Controllers\Web\Backend\User\UserManageController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Route
|--------------------------------------------------------------------------
*/

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Sports Type Management Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'sports-type', 'as' => 'sports-type.'], function () {
    Route::get('/', [SportsTypeController::class, 'index'])->name('index');
    Route::post('/store', [SportsTypeController::class, 'store'])->name('store');
    Route::post('/update/{id}', [SportsTypeController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [SportsTypeController::class, 'destroy'])->name('destroy');
    Route::get('/status/{id}', [SportsTypeController::class, 'status'])->name('status');
    // AJAX: get single sports type for editing
    Route::get('/get/{id}', [SportsTypeController::class, 'getSportsType'])->name('get');
});

/*
|--------------------------------------------------------------------------
| Camp Management Routes
|--------------------------------------------------------------------------
*/
Route::prefix('camps')->name('camps.')->group(function () {
    Route::get('/', [CampController::class, 'index'])->name('index');
    Route::post('/', [CampController::class, 'store'])->name('store');
    Route::get('/{id}', [CampController::class, 'show'])->name('show');
    Route::post('/{id}', [CampController::class, 'update'])->name('update');
    Route::delete('/{id}', [CampController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/status', [CampController::class, 'status'])->name('status');
});

/*
|--------------------------------------------------------------------------
| User Management Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'users', 'as' => 'users.manage.'], function () {
    Route::get('/list', [UserManageController::class, 'index'])->name('index');
    Route::post('/status/{id}', [UserManageController::class, 'status'])->name('status');
    Route::get('/show/{id}', [UserManageController::class, 'show'])->name('show');
    Route::delete('/delete/{id}', [UserManageController::class, 'destroy'])->name('destroy');
    Route::post('/export', [UserManageController::class, 'export'])->name('export');
    Route::get('/trash', [UserManageController::class, 'trash'])->name('trash');
    Route::post('/restore/{id}', [UserManageController::class, 'restore'])->name('restore');
    Route::delete('/force-delete/{id}', [UserManageController::class, 'forceDelete'])->name('forceDelete');
});

/*
|--------------------------------------------------------------------------
| Coupon Management Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'coupon', 'as' => 'coupon.'], function () {
    Route::get('/', [CouponController::class, 'index'])->name('index');
    Route::post('/store', [CouponController::class, 'store'])->name('store');
    Route::get('/get/{id}', [CouponController::class, 'getCoupon'])->name('getCoupon');
    Route::post('/update/{id}', [CouponController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [CouponController::class, 'destroy'])->name('destroy');
    Route::get('/status/{id}', [CouponController::class, 'status'])->name('status');
});

/*
|--------------------------------------------------------------------------
| FAQ Management Routes
|--------------------------------------------------------------------------
*/
Route::controller(FaqController::class)->prefix('faq')->name('faq.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

/*
|--------------------------------------------------------------------------
| Subscriber Routes
|--------------------------------------------------------------------------
*/
Route::get('subscriber', [SubscriberController::class, 'index'])->name('subscriber.index');

/*
|--------------------------------------------------------------------------
| Contact Management Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'contact', 'as' => 'contact.'], function () {
    Route::get('/', [ContactController::class, 'index'])->name('index');
    Route::get('/status/{id}', [ContactController::class, 'status'])->name('status');
});

/*
|--------------------------------------------------------------------------
| CMS Routes (Home, About, Privacy & Terms)
|--------------------------------------------------------------------------
*/
Route::prefix('cms')->name('cms.')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Privacy & Terms Section
    |--------------------------------------------------------------------------
    */
    Route::controller(PrivacAndTermsController::class)->prefix('privecyandterms')->name('privecyandterms.')->group(function () {
        // View routes
        Route::get('/terms', 'termsAndCondition')->name('terms');
        Route::get('/privacy', 'privacyPolicy')->name('privacy');

        // Update routes
        Route::post('/terms-condition/update', 'termsAndConditionUpdate')->name('terms.update');
        Route::post('/privacy-policy/update', 'privacyPolicyUpdate')->name('privacy.update');
        Route::post('/why-desi-carousel/update', 'whyDesiCarouselUpdate')->name('why.desi.carousel.update');
        Route::post('/trust-and-sefty/update', 'trustAndService')->name('trust-and-sefty.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Home Page — Hero Section
    |--------------------------------------------------------------------------
    */
    Route::get('/home/hero', [HomePageController::class, 'heroIndex'])->name('home.hero.section');
    Route::post('/home/hero/update', [HomePageController::class, 'heroUpdate'])->name('home.hero.section.update');

    /*
    |--------------------------------------------------------------------------
    | Home Page — Training Camp Section
    |--------------------------------------------------------------------------
    */
    Route::get('/home/training-camp', [HomePageController::class, 'trainingCampIndex'])->name('home.training-camp.section');
    Route::post('/home/training-camp/update', [HomePageController::class, 'trainingCampUpdate'])->name('home.training-camp.section.update');

    /*
    |--------------------------------------------------------------------------
    | Home Page — Partners / Slider Section
    |--------------------------------------------------------------------------
    */
    Route::get('/home/partner', [SliderController::class, 'index'])->name('home.slider.index');
    Route::post('/partner/update', [SliderController::class, 'headerUpdate'])->name('home.slider.header.update');
    Route::post('/partner/store', [SliderController::class, 'store'])->name('home.slider.store');
    Route::post('/partner/{id}/status', [SliderController::class, 'updateStatus'])->name('home.slider.status');
    Route::delete('/partner/{id}', [SliderController::class, 'destroy'])->name('home.slider.destroy');
    Route::post('/partner/update-order', [SliderController::class, 'updateOrder'])->name('home.slider.updateOrder');

    /*
    |--------------------------------------------------------------------------
    | Home Page — Features Section
    |--------------------------------------------------------------------------
    */
    Route::get('/home/features', [FeaturesController::class, 'index'])->name('home.features.index');
    Route::post('/home/features/store', [FeaturesController::class, 'store'])->name('home.features.store');
    Route::post('/home/features/item/store', [FeaturesController::class, 'storeItem'])->name('home.features.item.store');
    Route::get('/home/features/item/edit/{id}', [FeaturesController::class, 'editItem'])->name('home.features.item.edit');
    Route::post('/home/features/item/update/{id}', [FeaturesController::class, 'updateItem'])->name('home.features.item.update');
    Route::delete('/home/features/item/delete/{id}', [FeaturesController::class, 'destroy'])->name('home.features.item.destroy');

    /*
    |--------------------------------------------------------------------------
    | Home Page — Operations Section
    |--------------------------------------------------------------------------
    */
    Route::get('/home/operations', [HomePageController::class, 'operationIndex'])->name('home.operation.section');
    Route::post('/home/operations/update', [HomePageController::class, 'operationUpdate'])->name('home.operation.section.update');

    /*
    |--------------------------------------------------------------------------
    | Home Page — Testimonial Section
    |--------------------------------------------------------------------------
    */
    Route::get('/home/testimonial', [TestimonialController::class, 'index'])->name('home.testimonial.index');
    Route::post('/home/testimonial/update', [TestimonialController::class, 'update'])->name('home.testimonial.update');
    Route::post('/reviews/store', [TestimonialController::class, 'storeReview'])->name('home.testimonial.item.store');
    Route::get('/reviews/edit/{id}', [TestimonialController::class, 'editReview'])->name('home.testimonial.item.edit');
    Route::get('/reviews/show/{id}', [TestimonialController::class, 'showReview'])->name('home.testimonial.item.show');
    Route::post('/reviews/update/{id}', [TestimonialController::class, 'updateReview'])->name('home.testimonial.item.update');
    Route::delete('/reviews/delete/{id}', [TestimonialController::class, 'destroyReview'])->name('home.testimonial.item.delete');

    /*
    |--------------------------------------------------------------------------
    | About Page CMS
    |--------------------------------------------------------------------------
    */
    Route::prefix('/about')->name('about.')->group(function () {
        // Main Index
        Route::get('/', [AboutPageController::class, 'index'])->name('index');

        // Page Title Section
        Route::post('/page-title/store', [AboutPageController::class, 'storePageTitle'])->name('page-title.store');

        // Mission Section
        Route::post('/mission/store', [AboutPageController::class, 'storeMission'])->name('mission.store');

        // Key to Excellence Section
        Route::post('/key-to-excellence/store', [AboutPageController::class, 'storeKeyToExcellence'])->name('key-to-excellence.store');

        // Bottom Description Section
        Route::post('/bottom-description/store', [AboutPageController::class, 'storeBottomDescription'])->name('bottom-description.store');

        // Owner Info Section
        Route::post('/owner-info/store', [AboutPageController::class, 'storeOwnerInfo'])->name('owner-info.store');

        // Feature Items
        Route::get('/items', [AboutPageController::class, 'items'])->name('items.index');
        Route::post('/items/store', [AboutPageController::class, 'storeItem'])->name('item.store');
        Route::get('/items/{id}/edit', [AboutPageController::class, 'editItem'])->name('item.edit');
        Route::post('/items/{id}/update', [AboutPageController::class, 'updateItem'])->name('item.update');
        Route::delete('/items/{id}/destroy', [AboutPageController::class, 'destroyItem'])->name('item.destroy');

        // ================================
        // OUR TEAM SECTION
        // ================================

        // Team Section Header
        Route::get('/team', [AboutPageOurTeamController::class, 'index'])->name('team.index');
        Route::post('/team-header/store', [AboutPageOurTeamController::class, 'storeTeamHeader'])->name('team-header.store');

        // Team Members CRUD
        Route::get('/team-members', [AboutPageOurTeamController::class, 'teamMembers'])->name('team-members.index');
        Route::post('/team-members/store', [AboutPageOurTeamController::class, 'storeTeamMember'])->name('team-member.store');
        Route::get('/team-members/{id}/edit', [AboutPageOurTeamController::class, 'editTeamMember'])->name('team-member.edit');
        Route::post('/team-members/{id}/update', [AboutPageOurTeamController::class, 'updateTeamMember'])->name('team-member.update');
        Route::delete('/team-members/{id}/destroy', [AboutPageOurTeamController::class, 'destroyTeamMember'])->name('team-member.destroy');

        // ================================
        // GETTING STARTED SECTION
        // ================================

        // Getting Started Section Header
        Route::get('/getting-started', [GettingStartedController::class, 'index'])->name('getting-started.index');
        Route::post('/getting-started-header/store', [GettingStartedController::class, 'storePageTitle'])->name('getting-started-header.store');
    });
});

/*
|--------------------------------------------------------------------------
| User Access & Role Management Routes
|--------------------------------------------------------------------------
*/
Route::resource('users', UserController::class);
Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
    Route::get('/status/{id}', 'status')->name('status');
    Route::get('/new', 'new')->name('new.index');
    Route::get('/ajax/new/count', 'newCount')->name('ajax.new.count');
    Route::get('/card/{slug}', 'card')->name('card');
});
Route::resource('permissions', PermissionController::class);
Route::resource('roles', RoleController::class);

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/

// Profile Settings
Route::controller(ProfileController::class)->group(function () {
    Route::get('setting/profile', 'index')->name('setting.profile.index');
    Route::put('setting/profile/update', 'UpdateProfile')->name('setting.profile.update');
    Route::put('setting/profile/update/Password', 'UpdatePassword')->name('setting.profile.update.Password');
    Route::post('setting/profile/update/avatar', 'UpdateProfilePicture')->name('update.profile.picture');
});

// Mail Settings
Route::controller(MailSettingController::class)->group(function () {
    Route::get('setting/mail', 'index')->name('setting.mail.index');
    Route::patch('setting/mail', 'update')->name('setting.mail.update');
    Route::post('setting/send', 'send')->name('setting.mail.send');
});

// Stripe Settings
Route::controller(StripeController::class)->prefix('setting/stripe')->name('setting.stripe.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
    Route::patch('/update-onboarding', 'updateOnboarding')->name('update-onboarding');
    Route::patch('/update-percentage', 'updateAdminPercentage')->name('update-percentage');
});

// Firebase Settings
Route::controller(FirebaseController::class)->prefix('setting/firebase')->name('setting.firebase.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
});

// Environment Settings
Route::controller(EnvController::class)->group(function () {
    Route::get('setting/env', 'index')->name('setting.env.index');
    Route::patch('setting/env', 'update')->name('setting.env.update');
});

// Social Settings
Route::controller(SocialController::class)->prefix('setting/social')->name('setting.social.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
});

// General Settings
Route::controller(SettingController::class)->group(function () {
    Route::get('setting/general', 'index')->name('setting.general.index');
    Route::patch('setting/general', 'update')->name('setting.general.update');
});

// Logo Settings
Route::controller(LogoController::class)->group(function () {
    Route::get('setting/logo', 'index')->name('setting.logo.index');
    Route::patch('setting/logo', 'update')->name('setting.logo.update');
});

// Google Map Settings
Route::controller(GoogleMapController::class)->group(function () {
    Route::get('setting/google/map', 'index')->name('setting.google.map.index');
    Route::patch('setting/google/map', 'update')->name('setting.google.map.update');
});

// Signature Settings
Route::controller(SignatureController::class)->group(function () {
    Route::get('setting/signature', 'index')->name('setting.signature.index');
    Route::patch('setting/signature', 'update')->name('setting.signature.update');
});

// Captcha Settings
Route::controller(CaptchaController::class)->group(function () {
    Route::get('setting/captcha', 'index')->name('setting.captcha.index');
    Route::patch('setting/captcha', 'update')->name('setting.captcha.update');
});

// Other Ajax Settings
Route::prefix('setting/other')->name('setting.other')->group(function () {
    Route::get('/', [OtherController::class, 'index'])->name('.index');
    Route::get('/mail', [OtherController::class, 'mail'])->name('.mail');
    Route::get('/sms', [OtherController::class, 'sms'])->name('.sms');
    Route::get('/recaptcha', [OtherController::class, 'recaptcha'])->name('.recaptcha');
    Route::get('/pagination', [OtherController::class, 'pagination'])->name('.pagination');
    Route::get('/reverb', [OtherController::class, 'reverb'])->name('.reverb');
    Route::get('/debug', [OtherController::class, 'debug'])->name('.debug');
    Route::get('/access', [OtherController::class, 'access'])->name('.access');
});

// Livewire CRUD
Route::prefix('livewire/crud')->name('livewire.crud')->group(function () {
    Route::get('/', [LivewireController::class, 'index'])->name('.index');
});

/*
|--------------------------------------------------------------------------
| Payment Monitor Routes
|--------------------------------------------------------------------------
*/
Route::prefix('monitor')->name('monitor.')->group(function () {
    Route::get('/', [PaymentMonitorController::class, 'index'])->name('index');
});

/*
|--------------------------------------------------------------------------
| Cache & Optimization Route
|--------------------------------------------------------------------------
*/
Route::get('/optimize', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:cache');
    Redis::flushAll();

    return redirect()->back()->with('t-success', 'Message sent successfully');
})->name('optimize');
