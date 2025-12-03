<?php

use Illuminate\Support\Facades\Route;
use Modules\Director\Http\Controllers\DirectorController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('directors', DirectorController::class)->names('director');
});
