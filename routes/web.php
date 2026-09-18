<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\Frontend\HomeController;
use App\Http\Controllers\Api\Auth\SocialLoginController;
use App\Http\Controllers\Api\Auth\V2\V2RegisterController;
use App\Http\Controllers\Web\Frontend\AffiliateController;
use App\Http\Controllers\Web\Frontend\SubscriberController;
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

/*
|--------------------------------------------------------------------------
| Modern Error Page Preview & Testing Route
|--------------------------------------------------------------------------
| Allows developers and testing previewing all HTTP error pages
| e.g. /error/404, /error/403, /error/401, /error/419, /error/429, /error/500, /error/503
*/
Route::get('/error/{code?}', function ($code = 404) {
    \Inertia\Inertia::setRootView('admin-v2');
    return \Inertia\Inertia::render('Errors/Index', [
        'status' => (int) $code,
        'message' => request('message', null),
    ]);
})->middleware(['web', \App\Http\Middleware\HandleInertiaRequests::class])->name('error.preview');