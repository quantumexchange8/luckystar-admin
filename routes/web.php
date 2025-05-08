<?php

use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SelectOptionController;
use App\Http\Controllers\TopUpProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Redirect::route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // select option
    Route::get('/get_countries', [SelectOptionController::class, 'getCountries'])->name('getCountries');

    /**
     * ==============================
     *           Members
     * ==============================
     */
    Route::prefix('member')->group(function () {
        Route::get('/listing', function () {
            // Matches The "/admin/users" URL
        })->name('member.listing');
    });

    /**
     * ==============================
     *         Configurations
     * ==============================
     */
    Route::prefix('configuration')->group(function () {
        // Account Type
        Route::get('/account_type', [AccountTypeController::class, 'index'])->name('account_type.index');

        Route::post('/addAccountType', [AccountTypeController::class, 'addAccountType'])->name('account_type.addAccountType');

        // Top Up Profile
        Route::get('/top_up_profile', [TopUpProfileController::class, 'index'])->name('configuration.top_up_profile');

        Route::post('/addTopUpProfile', [TopUpProfileController::class, 'addTopUpProfile'])->name('account_type.addTopUpProfile');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
