<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy Admin Route Redirection (Migrated to V2)
|--------------------------------------------------------------------------
|
| All legacy Bootstrap Blade admin routes have been fully migrated to
| Whistle-Works Admin V2 (Inertia.js + Vue 3). Any hits to legacy /admin/*
| endpoints are gracefully redirected to their respective /admin/v2/* pages.
|
*/

Route::get('/', fn() => redirect()->route('admin.v2.dashboard'))->name('index');
Route::get('dashboard', fn() => redirect()->route('admin.v2.dashboard'))->name('dashboard');

Route::any('sports-type/{any?}', fn() => redirect('/admin/v2/sports-types'))->where('any', '.*');
Route::any('camps/{any?}', fn() => redirect('/admin/v2/camps'))->where('any', '.*');
Route::any('users/{any?}', fn() => redirect('/admin/v2/users'))->where('any', '.*');
Route::any('user/{any?}', fn() => redirect('/admin/v2/users'))->where('any', '.*');
Route::any('monitor/{any?}', fn() => redirect('/admin/v2/monitor/payments'))->where('any', '.*');
Route::any('coupon/{any?}', fn() => redirect('/admin/v2/coupons'))->where('any', '.*');
Route::any('coupons/{any?}', fn() => redirect('/admin/v2/coupons'))->where('any', '.*');
Route::any('settings/{any?}', fn() => redirect('/admin/v2/settings'))->where('any', '.*');
Route::any('profile/{any?}', fn() => redirect('/admin/v2/profile'))->where('any', '.*');
Route::any('roles/{any?}', fn() => redirect('/admin/v2/roles'))->where('any', '.*');
Route::any('permissions/{any?}', fn() => redirect('/admin/v2/roles'))->where('any', '.*');
Route::any('cms/{any?}', fn() => redirect('/admin/v2/cms/home'))->where('any', '.*');
Route::any('privacyandterms/{any?}', fn() => redirect('/admin/v2/terms-privacy'))->where('any', '.*');
Route::any('logs/{any?}', fn() => redirect('/admin/v2/logs'))->where('any', '.*');

// Fallback for any other legacy /admin/* endpoints
Route::fallback(fn() => redirect()->route('admin.v2.dashboard'));
