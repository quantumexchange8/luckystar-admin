<?php

use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\ProfileController;
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
        Route::get('/account_type', [AccountTypeController::class, 'index'])->name('account_type.index');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
