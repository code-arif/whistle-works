<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\Frontend\HomeController;
use App\Http\Controllers\Api\Auth\SocialLoginController;
use App\Http\Controllers\Api\Auth\V2\V2RegisterController;
use App\Http\Controllers\Web\Frontend\AffiliateController;
use App\Http\Controllers\Web\Frontend\SubscriberController;
use App\Https\App\Http\Controllers\Api\Gateway\Stripe\StripeWebhookController;
use App\Http\Controllers\Api\StripeWebhookController as ApiStripeWebhookController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/affiliate/{slug}', [AffiliateController::class, 'store'])->name('store');

Route::get('/post', [HomeController::class, 'index'])->name('post.index');
Route::get('/post/show/{slug}', [HomeController::class, 'post'])->name('post.show');

//Social login test routes
Route::get('social-login/{provider}', [SocialLoginController::class, 'RedirectToProvider'])->name('social.login');
Route::get('social-login/{provider}/callback', [SocialLoginController::class, 'HandleProviderCallback']);

Route::post('subscriber/store', [SubscriberController::class, 'store'])->name('subscriber.data.store');



Route::controller(NotificationController::class)->prefix('notification')->name('notification.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('read/single/{id}', 'readSingle')->name('read.single');
    Route::POST('read/all', 'readAll')->name('read.all');
})->middleware('auth');

require __DIR__ . '/auth.php';

// stripe webhook route
Route::post('/webhook/stripe', [ApiStripeWebhookController::class, 'HandlePaymentWebhook']);

// user email verify
// Route::get('/verify-email', [V2RegisterController::class, 'verifyEmail']);
Route::get('/verify-email', [V2RegisterController::class, 'verifyEmail'])
    ->withoutMiddleware([
        'auth',
        'auth:web',
        'auth:admin',
    ]);


// Route::post('/rental/webhook', [RentedPaymentController::class, 'handleWebhook']);
