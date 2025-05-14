<?php

use App\Http\Controllers\GroupController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\SelectOptionController;
use App\Http\Controllers\TopUpProfileController;

Route::get('/', function () {
    return Redirect::route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // select option
    Route::get('/get_countries', [SelectOptionController::class, 'getCountries'])->name('getCountries');
    Route::get('/get_group_uplines', [SelectOptionController::class, 'getUplinesByGroup'])->name('getUplinesByGroup');
    Route::get('/getAvailableLeader', [SelectOptionController::class, 'getAvailableLeader'])->name('getAvailableLeader');
    Route::get('/getSettingRanks', [SelectOptionController::class, 'getSettingRanks'])->name('getSettingRanks');

    /**
     * ==============================
     *           Members
     * ==============================
     */
    Route::prefix('member')->group(function () {
        // Listing Routes
        Route::get('/listing', [MemberController::class, 'listing'])->name('member.listing');
        Route::get('/getMemberListingData', [MemberController::class, 'getMemberListingData'])->name('member.getMemberListingData');
        Route::get('/getFilterData', [MemberController::class, 'getFilterData'])->name('member.getFilterData');
        Route::get('/getAvailableUplines', [MemberController::class, 'getAvailableUplines'])->name('member.getAvailableUplines');

        Route::get('/access_portal/{user}', [MemberController::class, 'access_portal'])->name('member.access_portal');

        Route::post('/addNewMember', [MemberController::class, 'addNewMember'])->name('member.addNewMember');
        Route::post('/updateMemberStatus', [MemberController::class, 'updateMemberStatus'])->name('member.updateMemberStatus');
        Route::post('/resetPassword', [MemberController::class, 'resetPassword'])->name('member.resetPassword');

        Route::delete('/deleteMember', [MemberController::class, 'deleteMember'])->name('member.deleteMember');

        // details
        Route::get('/detail/{id_number}', [MemberController::class, 'detail'])->name('member.detail');
        Route::get('/getUserData', [MemberController::class, 'getUserData'])->name('member.getUserData');
    });

    /**
     * ==============================
     *            Group
     * ==============================
     */
    Route::prefix('group')->group(function () {
        Route::get('/listing', [GroupController::class, 'index'])->name('group.listing');
        Route::get('/getGroupsData', [GroupController::class, 'getGroupsData'])->name('group.getGroupsData');

        Route::post('/addGroup', [GroupController::class, 'addGroup'])->name('group.addGroup');
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
