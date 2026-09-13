<?php

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

