<?php

use \App\Http\Middleware\HandleInertiaRequests;
use \Inertia\Inertia;
use App\Http\Controllers\Api\Auth\V2\V2RegisterController;
use App\Http\Controllers\Api\Payment\StripeWebhookController as ApiStripeWebhookController;
use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\Facades\Route;


Route::get('/', fn() => redirect()->route('login'))->name('home');


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

/*
|--------------------------------------------------------------------------
| Modern Error Page Preview & Testing Route
|--------------------------------------------------------------------------
| Allows developers and testing previewing all HTTP error pages
| e.g. /error/404, /error/403, /error/401, /error/419, /error/429, /error/500, /error/503
*/
Route::get('/error/{code?}', function ($code = 404) {
    Inertia::setRootView('admin-v2');
    return Inertia::render('Errors/Index', [
        'status' => (int) $code,
        'message' => request('message', null),
    ]);
})->middleware(['web', HandleInertiaRequests::class])->name('error.preview');

