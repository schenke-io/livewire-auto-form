<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\BrandController;
use Workbench\App\Http\Controllers\CityController;
use Workbench\App\Http\Controllers\CountryController;
use Workbench\App\Http\Controllers\HomeController;
use Workbench\App\Http\Controllers\LanguageController;
use Workbench\App\Http\Controllers\RiverController;

Route::get('/', HomeController::class);

Route::post('/terminate', function () {
    // Check if the POSIX extension is enabled
    if (function_exists('posix_kill')) {
        // Send SIGKILL (9) to the current process ID
        posix_kill(getmypid(), SIGKILL);
    }

    // Fallback if POSIX is missing (e.g., some configurations)
    // This executes a terminal command to kill the process
    exec('kill -9 '.getmypid());
    exit;
})->name('terminate');

Route::resource('cities', CityController::class)->only(['index', 'show']);
Route::resource('countries', CountryController::class)->only(['index', 'show']);
Route::resource('brands', BrandController::class)->only(['index', 'show']);
Route::resource('rivers', RiverController::class)->only(['index', 'show']);
Route::resource('languages', LanguageController::class)->only(['index', 'show']);
