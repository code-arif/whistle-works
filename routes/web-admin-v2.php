<?php

use App\Http\Controllers\Web\Backend\V2\DashboardController;
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
